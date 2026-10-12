<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;

/**
 * Mail transport without Composer.
 *
 * Drivers:
 *   log  — writes the message to storage/logs/mail.log (safe local default)
 *   mail — uses PHP mail() when the host supports it
 *   smtp — direct SMTP socket implementation (no external library)
 */
final class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $driver = (string) App::configValue('mail.driver', 'log');

        return match ($driver) {
            'mail' => self::sendWithMailFunction($toEmail, $toName, $subject, $body),
            'smtp' => self::sendWithSmtp($toEmail, $toName, $subject, $body),
            default => self::log($toEmail, $subject, $body),
        };
    }

    private static function log(string $toEmail, string $subject, string $body): bool
    {
        $path = (string) App::configValue('storage.logs', sys_get_temp_dir()) . '/mail.log';
        $entry = sprintf(
            "[%s] TO: %s\nSUBJECT: %s\n%s\n%s\n",
            gmdate('Y-m-d H:i:s') . ' UTC',
            $toEmail,
            $subject,
            $body,
            str_repeat('-', 60)
        );

        @file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);

        return true;
    }

    private static function sendWithMailFunction(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $from = (string) App::configValue('mail.from_address', 'no-reply@example.com');
        $fromName = (string) App::configValue('mail.from_name', 'CircleWork Africa');

        $headers = [
            'From: ' . self::encodeName($fromName) . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ];

        $ok = @mail(
            self::encodeName($toName) . ' <' . $toEmail . '>',
            $subject,
            $body,
            implode("\r\n", $headers)
        );

        if (!$ok) {
            Logger::warning('mail() delivery failed', ['to' => $toEmail]);
        }

        return $ok;
    }

    private static function sendWithSmtp(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $host = (string) App::configValue('mail.smtp.host', '');
        $port = (int) App::configValue('mail.smtp.port', 587);
        $username = (string) App::configValue('mail.smtp.username', '');
        $password = (string) App::configValue('mail.smtp.password', '');
        $encryption = (string) App::configValue('mail.smtp.encryption', 'tls');
        $useAuth = (bool) App::configValue('mail.smtp.auth', true);

        if ($host === '') {
            Logger::warning('SMTP host not configured; falling back to log driver');
            return self::log($toEmail, $subject, $body);
        }

        $from = (string) App::configValue('mail.from_address', 'no-reply@example.com');
        $fromName = (string) App::configValue('mail.from_name', 'CircleWork Africa');

        $transport = $encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 15);

        if (!is_resource($socket)) {
            Logger::error('SMTP connection failed', ['host' => $host, 'port' => $port, 'error' => $errstr]);
            return false;
        }

        stream_set_timeout($socket, 15);

        try {
            self::expect($socket, '220');
            self::command($socket, 'EHLO ' . (gethostname() ?: 'localhost'), '250');

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', '220');
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Unable to start TLS');
                }
                self::command($socket, 'EHLO ' . (gethostname() ?: 'localhost'), '250');
            }

            if ($useAuth && $username !== '') {
                self::command($socket, 'AUTH LOGIN', '334');
                self::command($socket, base64_encode($username), '334');
                self::command($socket, base64_encode($password), '235');
            }

            self::command($socket, 'MAIL FROM:<' . $from . '>', '250');
            self::command($socket, 'RCPT TO:<' . $toEmail . '>', '250');
            self::command($socket, 'DATA', '354');

            $headers = 'From: ' . self::encodeName($fromName) . ' <' . $from . ">\r\n"
                . 'To: ' . self::encodeName($toName) . ' <' . $toEmail . ">\r\n"
                . 'Subject: ' . self::encodeHeader($subject) . "\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . 'Date: ' . gmdate('D, d M Y H:i:s') . " +0000\r\n";

            $data = $headers . "\r\n" . str_replace("\n.", "\n..", $body) . "\r\n.";
            self::command($socket, $data, '250');
            self::command($socket, 'QUIT', '221');

            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            Logger::error('SMTP send failed: ' . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }

    /**
     * @param resource $socket
     */
    private static function expect($socket, string $code): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        if (!str_starts_with(trim($response), $code)) {
            throw new \RuntimeException('SMTP unexpected response: ' . trim($response));
        }
    }

    /**
     * @param resource $socket
     */
    private static function command($socket, string $command, string $expectCode): void
    {
        fwrite($socket, $command . "\r\n");
        self::expect($socket, $expectCode);
    }

    private static function encodeName(string $name): string
    {
        return preg_match('/[^\x20-\x7E]/', $name) === 1
            ? '=?UTF-8?B?' . base64_encode($name) . '?='
            : $name;
    }

    private static function encodeHeader(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) === 1
            ? '=?UTF-8?B?' . base64_encode($value) . '?='
            : $value;
    }
}
