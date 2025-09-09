<?php
declare(strict_types=1);

namespace App\Mail;

use App\Config;

final class Mailer
{
    public static function send(string $to, string $subject, string $html, string $from = ''): bool
    {
        $fromEmail = $from !== '' ? $from : Config::smtpFrom();
        $fromName = Config::smtpFromName();

        // Se houver credenciais SMTP, tenta via SMTP
        if (Config::smtpUser() !== '' && Config::smtpPass() !== '') {
            return self::sendSmtp($to, $subject, $html, $fromEmail, $fromName);
        }

        // Fallback mail()
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . ($fromName ? (self::mimeEncode($fromName) . ' <' . $fromEmail . '>') : $fromEmail);
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headersStr = implode("\r\n", $headers);
        return @mail($to, $subject, $html, $headersStr);
    }

    private static function sendSmtp(string $to, string $subject, string $html, string $fromEmail, string $fromName): bool
    {
        $host = Config::smtpHost();
        $port = Config::smtpPort();
        $secure = Config::smtpSecure(); // 'ssl' ou 'tls'
        $username = Config::smtpUser();
        $password = Config::smtpPass();

        $transport = ($secure === 'ssl') ? 'ssl://' : '';
        $remote = $transport . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
            ],
        ]);

        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$fp) {
            return false;
        }
        stream_set_timeout($fp, 15);

        $read = static function () use ($fp): string {
            $data = '';
            while ($str = fgets($fp, 515)) {
                $data .= $str;
                if (isset($str[3]) && $str[3] === ' ') break;
            }
            return $data;
        };
        $write = static function (string $cmd) use ($fp): void {
            fwrite($fp, $cmd . "\r\n");
        };
        $expect = static function (string $response, string $data): bool {
            return strncmp($data, $response, 3) === 0;
        };

        $greeting = $read();
        if (!preg_match('/^220\s/', $greeting)) { fclose($fp); return false; }

        $write('EHLO erosvitta.com.br');
        $ehlo = $read();
        if (!preg_match('/^250-/', $ehlo) && !preg_match('/^250\s/', $ehlo)) { fclose($fp); return false; }

        if ($secure === 'tls') {
            $write('STARTTLS');
            $tls = $read();
            if (!preg_match('/^220\s/', $tls)) { fclose($fp); return false; }
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT)) { fclose($fp); return false; }
            $write('EHLO erosvitta.com.br');
            $ehlo2 = $read();
            if (!preg_match('/^250-/', $ehlo2) && !preg_match('/^250\s/', $ehlo2)) { fclose($fp); return false; }
        }

        $write('AUTH LOGIN');
        if (!$expect('334', $read())) { fclose($fp); return false; }
        $write(base64_encode($username));
        if (!$expect('334', $read())) { fclose($fp); return false; }
        $write(base64_encode($password));
        if (!$expect('235', $read())) { fclose($fp); return false; }

        $write('MAIL FROM: <' . $fromEmail . '>');
        if (!$expect('250', $read())) { fclose($fp); return false; }
        $write('RCPT TO: <' . $to . '>');
        if (!$expect('250', $read())) { fclose($fp); return false; }
        $write('DATA');
        if (!$expect('354', $read())) { fclose($fp); return false; }

        $headers = [];
        $headers[] = 'From: ' . ($fromName ? (self::mimeEncode($fromName) . ' <' . $fromEmail . '>') : $fromEmail);
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . self::mimeEncode($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'X-Mailer: ErosVitta SMTP';

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
        $write($message);
        if (!$expect('250', $read())) { fclose($fp); return false; }

        $write('QUIT');
        fclose($fp);
        return true;
    }

    private static function mimeEncode(string $text): string
    {
        // Q-encoding simples para UTF-8 em headers
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }
}
