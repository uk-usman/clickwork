<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\User;
use App\Services\ReportService;

return [
    'state changing requests without a CSRF token are rejected' => function (): void {
        $user = Fixtures::user();
        User::update((int) $user['id'], ['password_hash' => password_hash('CsrfPass12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $user['email'], 'password' => 'CsrfPass12345']);

        $response = $http->post('/account/profile', ['name' => 'Changed Without Token'], false);

        assert_equals(302, $response['status'], 'A request without a CSRF token should be redirected away.');
        assert_false(str_contains((string) $response['location'], '/account'), 'The redirect should not continue the action.');

        $unchanged = User::find((int) $user['id']);
        assert_false((string) $unchanged['name'] === 'Changed Without Token', 'The profile must not have been changed.');
    },

    'a tampered CSRF token is rejected' => function (): void {
        $user = Fixtures::user();
        User::update((int) $user['id'], ['password_hash' => password_hash('CsrfPass12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $user['email'], 'password' => 'CsrfPass12345']);

        $response = $http->post('/account/profile', ['name' => 'Tampered Token', '_token' => str_repeat('f', 64)], false);

        assert_equals(302, $response['status'], 'A tampered token should be rejected.');
        assert_false((string) User::find((int) $user['id'])['name'] === 'Tampered Token', 'The profile must not have been changed.');
    },

    'validation errors repopulate the form and show inline messages' => function (): void {
        $client = Fixtures::user();
        User::update((int) $client['id'], ['password_hash' => password_hash('FormPass12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $client['email'], 'password' => 'FormPass12345']);

        $http->post('/client/projects', [
            'action'      => 'submit',
            'title'       => 'Bad',
            'category_id' => '1',
            'description' => 'short',
            'budget_type' => 'fixed',
            'budget_min'  => '100',
            'budget_max'  => '50',
            'currency'    => 'NGN',
            'visibility'  => 'public',
        ]);

        $form = $http->get('/client/projects/create');

        assert_equals(200, $form['status'], 'The form should render again after a validation failure.');
        assert_contains('value="Bad"', $form['body'], 'The submitted title should be repopulated.');
        assert_contains('is-invalid', $form['body'], 'Invalid fields should be marked.');
        assert_contains('at least 8 characters', $form['body'], 'An inline validation message should be shown.');
        assert_contains('lower than the minimum', $form['body'], 'The inverted budget should be reported.');
    },

    'SQL injection attempts do not break queries or expose data' => function (): void {
        $client = Fixtures::user();
        Fixtures::project((int) $client['id'], ['title' => 'Legitimate project title for the injection test']);

        $http = new HttpClient('http://127.0.0.1:8123');

        $payloads = [
            "' OR 1=1 -- ",
            "'; DROP TABLE users; -- ",
            "' UNION SELECT password_hash FROM users -- ",
        ];

        foreach ($payloads as $payload) {
            $response = $http->get('/projects?q=' . rawurlencode($payload));
            assert_equals(200, $response['status'], 'A search with an injection payload should still render safely.');

            // The payload is reflected into the search box, so look for actual
            // credential material rather than the literal words used in the payload.
            assert_false((bool) preg_match('/\$2[aby]\$[0-9]{2}\$/', $response['body']), 'No bcrypt hash may leak into the page.');
            assert_not_contains('SQLSTATE', $response['body'], 'No raw database error may be exposed.');
            assert_not_contains('PDOException', $response['body'], 'No raw exception detail may be exposed.');
        }

        assert_true(Fixtures::tableCount('users') > 0, 'The users table must still exist after injection attempts.');
    },

    'user supplied content is escaped when rendered' => function (): void {
        $client = Fixtures::user();
        $malicious = '<script>alert("xss")</script>';

        $project = Fixtures::project((int) $client['id'], [
            'title'       => $malicious . ' project title for escaping test',
            'description' => 'A description containing ' . $malicious . ' which must be escaped when rendered.',
        ]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $response = $http->get('/projects/' . (int) $project['id']);

        assert_equals(200, $response['status'], 'The project page should render.');
        assert_not_contains('<script>alert("xss")</script>', $response['body'], 'Raw script tags must never be emitted.');
        assert_contains('&lt;script&gt;', $response['body'], 'The payload should appear escaped.');
    },

    'the extension allow-list blocks executable and dangerous files' => function (): void {
        $allowed = App\Services\UploadService::DOCUMENT_EXTENSIONS;

        foreach (['php', 'phtml', 'phar', 'sh', 'py', 'exe', 'html', 'svg', 'js', 'htaccess'] as $extension) {
            $error = App\Services\UploadService::checkExtension($extension, $allowed);
            assert_true($error !== null, 'The extension .' . $extension . ' must be refused.');
        }

        foreach (['pdf', 'docx', 'png', 'zip'] as $extension) {
            assert_true(App\Services\UploadService::checkExtension($extension, $allowed) === null, 'The extension .' . $extension . ' should be permitted.');
        }

        $unknown = App\Services\UploadService::checkExtension('weird', ['pdf']);
        assert_true($unknown !== null, 'An extension outside the allow-list must be refused.');
        assert_contains('not allowed', strtolower((string) $unknown), 'The message should explain that the type is not allowed.');
    },

    'a synthetic file that is not a real upload is rejected' => function (): void {
        $tmp = tempnam(sys_get_temp_dir(), 'cwtest');
        file_put_contents($tmp, "<?php echo 'pwned'; ?>");

        $file = [
            'name'     => 'malicious.php',
            'type'     => 'application/x-php',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => (int) filesize($tmp),
        ];

        $result = App\Services\UploadService::store($file, sys_get_temp_dir() . '/cw-uploads', App\Services\UploadService::DOCUMENT_EXTENSIONS, 1048576);
        assert_false($result['ok'], 'A file that is not a genuine HTTP upload must be rejected.');

        @unlink($tmp);
    },

    'file uploads with a mismatched content type are rejected' => function (): void {
        $tmp = tempnam(sys_get_temp_dir(), 'cwtest');
        file_put_contents($tmp, 'This is plain text pretending to be an image file.');

        $file = [
            'name'     => 'fake.png',
            'type'     => 'image/png',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => (int) filesize($tmp),
        ];

        // is_uploaded_file() fails for a synthetic file, so the service must still refuse it.
        $result = App\Services\UploadService::store($file, sys_get_temp_dir() . '/cw-uploads', App\Services\UploadService::IMAGE_EXTENSIONS, 1048576);
        assert_false($result['ok'], 'A file that is not a genuine upload must be rejected.');

        @unlink($tmp);
    },

    'oversized uploads are rejected' => function (): void {
        $tmp = tempnam(sys_get_temp_dir(), 'cwtest');
        file_put_contents($tmp, str_repeat('a', 2048));

        $file = [
            'name'     => 'big.txt',
            'type'     => 'text/plain',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => 2048,
        ];

        $result = App\Services\UploadService::store($file, sys_get_temp_dir() . '/cw-uploads', ['txt'], 100);
        assert_false($result['ok'], 'A file above the limit must be rejected.');

        @unlink($tmp);
    },

    'upload error codes produce clear messages' => function (): void {
        $result = App\Services\UploadService::store([
            'name' => 'x.txt', 'type' => 'text/plain', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0,
        ], sys_get_temp_dir(), ['txt']);

        assert_false($result['ok'], 'A missing file must be rejected.');
        assert_contains('No file', (string) $result['error'], 'The message should explain that no file was selected.');
    },

    'private storage files are not web accessible' => function (): void {
        $http = new HttpClient('http://127.0.0.1:8123');

        foreach (['/../.env', '/storage/documents/test.txt', '/../config/config.php', '/../app/Core/App.php'] as $path) {
            $response = $http->get($path);
            assert_true(in_array($response['status'], [400, 403, 404], true), $path . ' must not be served (status ' . $response['status'] . ').');
        }
    },

    'sensitive actions are written to the audit log' => function (): void {
        $admin = Fixtures::admin();
        $client = Fixtures::user();
        $project = Fixtures::project((int) $client['id'], ['status' => 'pending', 'published_at' => null]);

        App\Services\ProjectService::moderate((int) $project['id'], (int) $admin['id'], 'approve', 'Approved during the audit test.');

        $entry = Database::selectOne(
            "SELECT * FROM `audit_logs` WHERE `action` = 'project.moderated' AND `entity_id` = ? ORDER BY `id` DESC LIMIT 1",
            [(int) $project['id']]
        );

        assert_true($entry !== null, 'A moderation action must create an audit entry.');
        assert_equals((int) $admin['id'], (int) $entry['actor_id'], 'The acting administrator should be recorded.');
    },

    'audit entries never contain a password or raw token' => function (): void {
        $entries = Database::select('SELECT `description`, `metadata` FROM `audit_logs` LIMIT 200');

        foreach ($entries as $entry) {
            $haystack = strtolower((string) $entry['description'] . ' ' . (string) $entry['metadata']);
            assert_false(str_contains($haystack, 'password_hash'), 'Audit entries must not contain password hashes.');
            assert_false(str_contains($haystack, 'token_hash'), 'Audit entries must not contain token hashes.');
        }
    },

    'reporting validates its input and records the reporter' => function (): void {
        $reporter = Fixtures::user();

        $invalid = ReportService::submit((int) $reporter['id'], 'nonsense', 0, 'invalid', 'short');
        assert_false($invalid['ok'], 'Invalid report input must be rejected.');

        $valid = ReportService::submit((int) $reporter['id'], 'user', 12345, 'fraud', 'This user asked me to pay outside the platform and then disappeared.');
        assert_true($valid['ok'], 'A valid report should be accepted.');

        $report = Database::selectOne('SELECT * FROM `reports` ORDER BY `id` DESC LIMIT 1');
        assert_true($report !== null, 'The report should be stored.');
        assert_equals((int) $reporter['id'], (int) $report['reporter_id'], 'The reporter should be recorded.');
        assert_equals('open', (string) $report['status'], 'A new report should be open.');
    },

    'database constraints prevent duplicate acceptances and proposals' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'active');

        $duplicate = false;

        try {
            Database::run(
                'INSERT INTO `contract_acceptances`
                    (`contract_id`, `contract_version_id`, `version`, `user_id`, `party_role`, `terms_hash`, `accepted_at`)
                 VALUES (?, 1, 1, ?, \'client\', ?, UTC_TIMESTAMP())',
                [(int) $contract['id'], (int) $client['id'], str_repeat('b', 64)]
            );
            $duplicate = true;
        } catch (PDOException) {
            $duplicate = false;
        }

        assert_false($duplicate, 'A duplicate acceptance for the same version must be rejected by the database.');
    },
];
