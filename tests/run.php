<?php

declare(strict_types=1);

/**
 * CircleWork Africa — lightweight test runner.
 *
 * Usage:
 *   php tests/run.php
 *   php tests/run.php --filter=Auth
 *   php tests/run.php --no-http     (skip end-to-end HTTP tests)
 *
 * Requires no Composer and no external test framework.
 */

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/Support.php';
require __DIR__ . '/Fixtures.php';

$options = [];
foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^--([a-z\-]+)(?:=(.*))?$/i', $argument, $matches) === 1) {
        $options[strtolower($matches[1])] = $matches[2] ?? '1';
    }
}

$filter = (string) ($options['filter'] ?? '');
$runHttp = !isset($options['no-http']);

$httpBaseUrl = 'http://127.0.0.1:8123';
$serverProcess = null;
$serverLog = sys_get_temp_dir() . '/circlework-test-server.log';

if ($runHttp) {
    $command = sprintf(
        'DB_DATABASE=%s APP_ENV=testing APP_DEBUG=0 %s -S 127.0.0.1:8123 -t %s %s > %s 2>&1 &',
        escapeshellarg(TEST_DB),
        escapeshellarg(PHP_BINARY),
        escapeshellarg(CIRCLEWORK_ROOT . '/public'),
        escapeshellarg(CIRCLEWORK_ROOT . '/public/index.php'),
        escapeshellarg($serverLog)
    );

    exec($command, $output, $exitCode);

    // Wait for the server to accept connections.
    $ready = false;
    for ($attempt = 0; $attempt < 40; $attempt++) {
        $socket = @fsockopen('127.0.0.1', 8123, $errno, $errstr, 0.5);
        if (is_resource($socket)) {
            fclose($socket);
            $ready = true;
            break;
        }
        usleep(150000);
    }

    if (!$ready) {
        fwrite(STDERR, "Could not start the test HTTP server. See {$serverLog}\n");
        $runHttp = false;
    }
}

/** @var array<int,array{group:string,name:string,callable:callable}> $tests */
$tests = [];

foreach (glob(__DIR__ . '/cases/*.php') ?: [] as $file) {
    $group = basename($file, '.php');

    if ($filter !== '' && stripos($group, $filter) === false) {
        continue;
    }

    $definitions = require $file;

    if (!is_array($definitions)) {
        continue;
    }

    foreach ($definitions as $name => $callable) {
        $tests[] = ['group' => $group, 'name' => (string) $name, 'callable' => $callable];
    }
}

$passed = 0;
$failed = 0;
$failures = [];
$currentGroup = null;

foreach ($tests as $test) {
    if ($test['group'] !== $currentGroup) {
        $currentGroup = $test['group'];
        fwrite(STDOUT, PHP_EOL . '── ' . strtoupper($currentGroup) . ' ' . str_repeat('─', max(0, 60 - strlen($currentGroup))) . PHP_EOL);
    }

    try {
        ($test['callable'])();
        $passed++;
        fwrite(STDOUT, '  PASS  ' . $test['name'] . PHP_EOL);
    } catch (AssertionFailure $e) {
        $failed++;
        $failures[] = $test['group'] . ' → ' . $test['name'] . ': ' . $e->getMessage();
        fwrite(STDOUT, '  FAIL  ' . $test['name'] . PHP_EOL . '        ' . $e->getMessage() . PHP_EOL);
    } catch (Throwable $e) {
        $failed++;
        $failures[] = $test['group'] . ' → ' . $test['name'] . ': ' . $e::class . ': ' . $e->getMessage();
        fwrite(STDOUT, '  ERROR ' . $test['name'] . PHP_EOL . '        ' . $e::class . ': ' . $e->getMessage() . PHP_EOL);
    }
}

if ($runHttp) {
    exec("pkill -f 'php -S 127.0.0.1:8123' 2>/dev/null");
}

fwrite(STDOUT, PHP_EOL . str_repeat('=', 62) . PHP_EOL);
fwrite(STDOUT, sprintf('Tests: %d passed, %d failed, %d total', $passed, $failed, $passed + $failed) . PHP_EOL);

if ($failures !== []) {
    fwrite(STDOUT, PHP_EOL . 'Failures:' . PHP_EOL);
    foreach ($failures as $failure) {
        fwrite(STDOUT, '  • ' . $failure . PHP_EOL);
    }
}

fwrite(STDOUT, str_repeat('=', 62) . PHP_EOL);

exit($failed === 0 ? 0 : 1);
