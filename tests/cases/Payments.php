<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\PaymentRecord;
use App\Models\User;
use App\Services\PaymentService;

return [
    'a recorded payment starts as reported and is never labelled verified' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $result = PaymentService::report((int) $contract['id'], (int) $client['id'], [
            'amount'          => '15000',
            'method'          => 'bank_transfer',
            'payer_reference' => 'TRX-123456',
            'paid_on'         => gmdate('Y-m-d'),
        ]);

        assert_true($result['ok'], 'Recording a payment should succeed.');
        assert_contains('Reported', $result['message'], 'The message should make clear the payment is only reported.');

        $payment = PaymentRecord::find((int) $result['payment_id']);
        assert_equals('reported', (string) $payment['status'], 'A new record must have reported status.');
        assert_true($payment['confirmed_at'] === null, 'A reported payment must not have a confirmation timestamp.');
    },

    'payer and payee are taken from the contract, not the request' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $attacker = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $result = PaymentService::report((int) $contract['id'], (int) $client['id'], [
            'amount'  => '5000',
            'method'  => 'cash',
            'payer_id' => (string) $attacker['id'],
            'payee_id' => (string) $attacker['id'],
        ]);

        assert_true($result['ok'], 'The payment should be recorded.');

        $payment = PaymentRecord::find((int) $result['payment_id']);
        assert_equals((int) $client['id'], (int) $payment['payer_id'], 'The payer must come from the contract.');
        assert_equals((int) $freelancer['id'], (int) $payment['payee_id'], 'The payee must come from the contract.');
        assert_false((int) $payment['payer_id'] === (int) $attacker['id'], 'Injected payer ids must be ignored.');
    },

    'a non-party cannot record a payment on a contract' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $result = PaymentService::report((int) $contract['id'], (int) $outsider['id'], [
            'amount' => '1000',
            'method' => 'cash',
        ]);

        assert_false($result['ok'], 'A non-party must not record a payment.');
    },

    'invalid payment amounts are rejected' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        foreach (['0', '-50', 'abc', ''] as $amount) {
            $result = PaymentService::report((int) $contract['id'], (int) $client['id'], ['amount' => $amount, 'method' => 'cash']);
            assert_false($result['ok'], 'An amount of ' . var_export($amount, true) . ' must be rejected.');
        }
    },

    'the reporter cannot confirm their own payment' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $paymentId = (int) PaymentService::report((int) $contract['id'], (int) $client['id'], ['amount' => '20000', 'method' => 'bank_transfer'])['payment_id'];

        $selfConfirm = PaymentService::confirm($paymentId, (int) $client['id']);
        assert_false($selfConfirm['ok'], 'The reporter must not confirm their own record.');
        assert_equals('reported', (string) PaymentRecord::find($paymentId)['status'], 'The record must remain reported.');
    },

    'the counterparty can confirm a reported payment' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $paymentId = (int) PaymentService::report((int) $contract['id'], (int) $client['id'], ['amount' => '20000', 'method' => 'bank_transfer'])['payment_id'];

        $confirmed = PaymentService::confirm($paymentId, (int) $freelancer['id']);
        assert_true($confirmed['ok'], 'The counterparty should be able to confirm.');

        $payment = PaymentRecord::find($paymentId);
        assert_equals('confirmed', (string) $payment['status'], 'The record should be confirmed.');
        assert_equals((int) $freelancer['id'], (int) $payment['confirmed_by'], 'The confirming account should be recorded.');
        assert_true($payment['confirmed_at'] !== null, 'A confirmation timestamp should be recorded.');
    },

    'a non-party cannot confirm a payment' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $paymentId = (int) PaymentService::report((int) $contract['id'], (int) $client['id'], ['amount' => '9000', 'method' => 'cash'])['payment_id'];

        $result = PaymentService::confirm($paymentId, (int) $outsider['id']);
        assert_false($result['ok'], 'A non-party must not confirm the payment.');
    },

    'a payment can be flagged as disputed with a reason' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $paymentId = (int) PaymentService::report((int) $contract['id'], (int) $client['id'], ['amount' => '7000', 'method' => 'mobile_money'])['payment_id'];

        $tooShort = PaymentService::dispute($paymentId, (int) $freelancer['id'], 'Nope');
        assert_false($tooShort['ok'], 'A very short dispute reason must be rejected.');

        $disputed = PaymentService::dispute($paymentId, (int) $freelancer['id'], 'The amount received does not match the recorded amount.');
        assert_true($disputed['ok'], 'A valid dispute should be recorded.');
        assert_contains('cannot recover', $disputed['message'], 'The message should be honest about recovery limitations.');

        $payment = PaymentRecord::find($paymentId);
        assert_equals('disputed', (string) $payment['status'], 'The record should be disputed.');
        assert_equals((int) $freelancer['id'], (int) $payment['disputed_by'], 'The disputing account should be recorded.');
    },

    'a payment record cannot be opened by a non-party over HTTP' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        PaymentService::report((int) $contract['id'], (int) $client['id'], ['amount' => '4000', 'method' => 'cash']);

        User::update((int) $outsider['id'], ['password_hash' => password_hash('OutsiderPass123', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $outsider['email'], 'password' => 'OutsiderPass123']);

        $response = $http->get('/contracts/' . (int) $contract['id'] . '/payments');
        assert_equals(403, $response['status'], 'A non-party must be denied the payment page.');
    },

    'an administrator review records an outcome without changing who paid whom' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $paymentId = (int) PaymentService::report((int) $contract['id'], (int) $client['id'], ['amount' => '12000', 'method' => 'bank_transfer'])['payment_id'];

        PaymentRecord::update($paymentId, ['status' => 'reversed', 'admin_note' => 'Reviewed by an administrator during the test.']);

        $payment = PaymentRecord::find($paymentId);
        assert_equals('reversed', (string) $payment['status'], 'The review outcome should be stored.');
        assert_equals((int) $client['id'], (int) $payment['payer_id'], 'The payer must remain unchanged.');
        assert_equals((int) $freelancer['id'], (int) $payment['payee_id'], 'The payee must remain unchanged.');
    },

    'the platform never stores a wallet or escrow balance' => function (): void {
        $tables = array_map(
            static fn (array $row): string => (string) $row['table_name'],
            Database::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [TEST_DB])
        );

        foreach ($tables as $table) {
            assert_false(str_contains($table, 'wallet'), 'No wallet table may exist (found ' . $table . ').');
            assert_false(str_contains($table, 'escrow'), 'No escrow table may exist (found ' . $table . ').');
            assert_false(str_contains($table, 'payout'), 'No payout table may exist (found ' . $table . ').');
        }
    },
];
