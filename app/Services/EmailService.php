<?php
/**
 * EmailService — Servicio de envío de emails.
 *
 * Envía emails usando PHP mail() nativo o SMTP vía streams.
 * Configurado por variables de entorno en .env.
 *
 * Soporta:
 * - Correo nativo PHP (mail())
 * - SMTP sin dependencias externas (via sockets)
 * - Adjuntos (QR codes en base64)
 *
 * @package App\Services
 * @version 1.0.0
 *
 * @example
 * ```php
 * $email = new EmailService();
 *
 * // Email simple
 * $email->send('destinatario@email.com', 'Nombre', 'Asunto', '<p>Cuerpo HTML</p>');
 *
 * // Con QR adjunto
 * $email->send('dest@mail.com', 'Juan', 'Confirmación', $html, '/uploads/qrcodes/ABC123.png');
 *
 * // Email de prueba
 * $email->test('mi@email.com');
 * ```
 */

declare(strict_types=1);

namespace App\Services;

class EmailService
{
    private string $driver;
    private string $fromEmail;
    private string $fromName;
    private string $smtpHost;
    private int    $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $encryption;

    public function __construct()
    {
        $this->driver     = env('MAIL_DRIVER', 'mail');
        $this->fromEmail  = env('MAIL_FROM_ADDRESS', 'noreply@eventosaas.com');
        $this->fromName   = env('MAIL_FROM_NAME', env('APP_NAME', 'EventoSaaS'));
        $this->smtpHost   = env('MAIL_HOST', 'smtp.mailtrap.io');
        $this->smtpPort   = (int)env('MAIL_PORT', 2525);
        $this->smtpUser   = env('MAIL_USERNAME', '');
        $this->smtpPass   = env('MAIL_PASSWORD', '');
        $this->encryption = env('MAIL_ENCRYPTION', 'tls');
    }

    /**
     * Envía un email HTML a un destinatario.
     *
     * @param  string      $to        Email destino
     * @param  string      $name      Nombre del destinatario
     * @param  string      $subject   Asunto
     * @param  string      $body      Cuerpo HTML
     * @param  string|null $attachQr  Ruta del archivo QR a adjuntar
     * @return bool
     */
    public function send(
        string $to,
        string $name,
        string $subject,
        string $body,
        ?string $attachQr = null
    ): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            appLog('warning', "EmailService: dirección inválida: {$to}");
            return false;
        }

        try {
            return match ($this->driver) {
                'smtp'  => $this->sendSmtp($to, $name, $subject, $body, $attachQr),
                'log'   => $this->sendToLog($to, $name, $subject, $body),
                default => $this->sendNative($to, $name, $subject, $body, $attachQr),
            };
        } catch (\Throwable $e) {
            appLog('error', "EmailService error [{$this->driver}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía email de prueba de conexión.
     *
     * @param  string $to
     * @return bool
     */
    public function test(string $to): bool
    {
        return $this->send(
            $to,
            'Test',
            '✅ Test de correo — ' . env('APP_NAME', 'EventoSaaS'),
            '<h2>¡Funciona!</h2><p>La configuración de email es correcta.</p>'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Implementaciones de envío
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía usando PHP mail() nativo con soporte MIME para HTML y adjuntos.
     */
    private function sendNative(
        string $to,
        string $name,
        string $subject,
        string $body,
        ?string $attachQr
    ): bool {
        $boundary = md5(uniqid('', true));
        $from     = "{$this->fromName} <{$this->fromEmail}>";

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$from}\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: EventoSaaS/1.0\r\n";

        if ($attachQr && file_exists($attachQr)) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

            $message  = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body)) . "\r\n";

            $fileContent = file_get_contents($attachQr);
            $filename    = basename($attachQr);
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: image/png; name=\"{$filename}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
            $message .= chunk_split(base64_encode($fileContent)) . "\r\n";
            $message .= "--{$boundary}--";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";
            $message  = chunk_split(base64_encode($body));
        }

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedTo      = "{$name} <{$to}>";

        $result = mail($encodedTo, $encodedSubject, $message, $headers);

        appLog('info', "Email [native] " . ($result ? 'enviado' : 'falló') . " a {$to}");
        return $result;
    }

    /**
     * Envía vía SMTP usando sockets PHP (sin dependencias externas).
     */
    private function sendSmtp(
        string $to,
        string $name,
        string $subject,
        string $body,
        ?string $attachQr
    ): bool {
        $context = stream_context_create();

        $scheme = match ($this->encryption) {
            'ssl'   => 'ssl',
            'tls'   => 'tcp',
            default => 'tcp',
        };

        $socket = @stream_socket_client(
            "{$scheme}://{$this->smtpHost}:{$this->smtpPort}",
            $errno, $errstr, 30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            appLog('error', "SMTP conexión fallida: {$errstr} ({$errno})");
            return false;
        }

        stream_set_timeout($socket, 30);

        // Handshake SMTP
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "EHLO {$_SERVER['SERVER_NAME'] ?? 'localhost'}\r\n");
        $this->smtpRead($socket);

        // STARTTLS si es necesario
        if ($this->encryption === 'tls') {
            $this->smtpWrite($socket, "STARTTLS\r\n");
            $this->smtpRead($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->smtpWrite($socket, "EHLO {$_SERVER['SERVER_NAME'] ?? 'localhost'}\r\n");
            $this->smtpRead($socket);
        }

        // Auth
        if ($this->smtpUser) {
            $this->smtpWrite($socket, "AUTH LOGIN\r\n");
            $this->smtpRead($socket);
            $this->smtpWrite($socket, base64_encode($this->smtpUser) . "\r\n");
            $this->smtpRead($socket);
            $this->smtpWrite($socket, base64_encode($this->smtpPass) . "\r\n");
            $response = $this->smtpRead($socket);
            if (!str_starts_with($response, '235')) {
                fclose($socket);
                appLog('error', "SMTP auth fallida: {$response}");
                return false;
            }
        }

        // Envío
        $this->smtpWrite($socket, "MAIL FROM: <{$this->fromEmail}>\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "RCPT TO: <{$to}>\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "DATA\r\n");
        $this->smtpRead($socket);

        $boundary = md5(uniqid('', true));
        $from     = "{$this->fromName} <{$this->fromEmail}>";
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $message  = "From: {$from}\r\n";
        $message .= "To: {$name} <{$to}>\r\n";
        $message .= "Subject: {$encodedSubject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";

        if ($attachQr && file_exists($attachQr)) {
            $message .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";
            $message .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body)) . "\r\n";
            $fname    = basename($attachQr);
            $fcontent = base64_encode(file_get_contents($attachQr));
            $message .= "--{$boundary}\r\nContent-Type: image/png; name=\"{$fname}\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$fname}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split($fcontent) . "\r\n--{$boundary}--";
        } else {
            $message .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body));
        }

        $this->smtpWrite($socket, $message . "\r\n.\r\n");
        $response = $this->smtpRead($socket);

        $this->smtpWrite($socket, "QUIT\r\n");
        fclose($socket);

        $success = str_starts_with($response, '250');
        appLog('info', "Email [smtp] " . ($success ? 'enviado' : 'falló') . " a {$to}");
        return $success;
    }

    /**
     * Solo registra el email en el log (modo 'log' para desarrollo).
     */
    private function sendToLog(string $to, string $name, string $subject, string $body): bool
    {
        appLog('info', "[MAIL LOG] To:{$to} | Subject:{$subject}");
        return true;
    }

    private function smtpWrite($socket, string $data): void
    {
        fwrite($socket, $data);
    }

    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if ($line[3] === ' ') break;
        }
        return $response;
    }
}
