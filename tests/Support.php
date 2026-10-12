<?php

declare(strict_types=1);

/**
 * Assertion helpers and HTTP client used by the test runner.
 *
 * No PHPUnit and no Composer are required.
 */

final class AssertionFailure extends RuntimeException
{
}

function fail(string $message): never
{
    throw new AssertionFailure($message);
}

function assert_true(bool $condition, string $message = 'Expected condition to be true.'): void
{
    if (!$condition) {
        fail($message);
    }
}

function assert_false(bool $condition, string $message = 'Expected condition to be false.'): void
{
    if ($condition) {
        fail($message);
    }
}

function assert_equals(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected != $actual) {
        fail(($message !== '' ? $message . ' ' : '') . sprintf(
            'Expected %s but received %s.',
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        fail(($message !== '' ? $message . ' ' : '') . sprintf(
            'Expected identical %s but received %s.',
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assert_contains(string $needle, string $haystack, string $message = ''): void
{
    if (!str_contains($haystack, $needle)) {
        fail(($message !== '' ? $message . ' ' : '') . 'Expected to find ' . var_export($needle, true) . ' in the output.');
    }
}

function assert_not_contains(string $needle, string $haystack, string $message = ''): void
{
    if (str_contains($haystack, $needle)) {
        fail(($message !== '' ? $message . ' ' : '') . 'Did not expect to find ' . var_export($needle, true) . ' in the output.');
    }
}

function assert_greater(float|int $expected, float|int $actual, string $message = ''): void
{
    if ($actual <= $expected) {
        fail(($message !== '' ? $message . ' ' : '') . sprintf('Expected a value greater than %s but received %s.', (string) $expected, (string) $actual));
    }
}

/**
 * Tiny cookie-aware HTTP client for end-to-end tests against the built-in server.
 */
final class HttpClient
{
    /** @var array<string,string> */
    private array $cookies = [];

    public function __construct(private string $baseUrl)
    {
    }

    /**
     * @param  array<string,string> $data
     * @return array{status:int,body:string,location:?string}
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param  array<string,string> $data
     * @return array{status:int,body:string,location:?string}
     */
    public function post(string $path, array $data = [], bool $withCsrf = true): array
    {
        if ($withCsrf && !isset($data['_token'])) {
            $data['_token'] = $this->csrfToken($path);
        }

        return $this->request('POST', $path, $data);
    }

    /**
     * Fetch a CSRF token from a page (or reuse the session token).
     */
    public function csrfToken(string $path = '/'): string
    {
        $response = $this->request('GET', $path);

        if (preg_match('/name="_token" value="([^"]+)"/', $response['body'], $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    /**
     * @param  array<string,string> $data
     * @return array{status:int,body:string,location:?string}
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $ch = curl_init();

        $url = rtrim($this->baseUrl, '/') . $path;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_PATH_AS_IS, true);

        if ($this->cookies !== []) {
            $pairs = [];
            foreach ($this->cookies as $name => $value) {
                $pairs[] = $name . '=' . $value;
            }
            curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $pairs));
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            fail('HTTP request failed: ' . $error);
        }

        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $rawHeaders = substr((string) $response, 0, $headerSize);
        $body = substr((string) $response, $headerSize);

        $this->captureCookies($rawHeaders);

        $location = null;
        if (preg_match('/^Location:\s*(.+)$/mi', $rawHeaders, $matches) === 1) {
            $location = trim($matches[1]);
        }

        return ['status' => $status, 'body' => $body, 'location' => $location];
    }

    private function captureCookies(string $rawHeaders): void
    {
        if (preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]*)/mi', $rawHeaders, $matches, PREG_SET_ORDER) === false) {
            return;
        }

        foreach ($matches as $match) {
            $name = trim($match[1]);
            $value = trim($match[2]);

            if ($value === '' || strcasecmp($value, 'deleted') === 0) {
                unset($this->cookies[$name]);
                continue;
            }

            $this->cookies[$name] = $value;
        }
    }
}
