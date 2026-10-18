<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Email\Email;

/**
 * ResendEmail extends CodeIgniter's native Email class to support
 * sending emails via the Resend REST API (HTTPS port 443) or via standard SMTP.
 *
 * Sending via HTTPS API avoids common hosting port restrictions (ports 25, 465, 587)
 * and provides clear, immediate feedback from Resend.
 */
class ResendEmail extends Email
{
    /**
     * Resend API endpoint
     */
    protected string $resendApiUrl = 'https://api.resend.com/emails';

    /**
     * Valid protocol values including 'resend'
     *
     * @var list<string>
     */
    protected $protocols = [
        'mail',
        'sendmail',
        'smtp',
        'resend',
    ];

    /**
     * Constructor
     *
     * @param array|\Config\Email|null $config
     */
    public function __construct($config = null)
    {
        if (! in_array('resend', $this->protocols, true)) {
            $this->protocols[] = 'resend';
        }

        parent::__construct($config);
    }

    /**
     * Override setProtocol to ensure 'resend' is recognized
     *
     * @param string $protocol
     * @return $this
     */
    public function setProtocol($protocol = 'mail')
    {
        $protocol = strtolower(trim((string) $protocol));
        if (! in_array('resend', $this->protocols, true)) {
            $this->protocols[] = 'resend';
        }

        if ($protocol === 'resend') {
            $this->protocol = 'resend';
            return $this;
        }

        return parent::setProtocol($protocol);
    }

    /**
     * Sends email via Resend REST API (POST https://api.resend.com/emails)
     *
     * Called automatically by CodeIgniter's spoolEmail() when protocol is 'resend'.
     */
    protected function sendWithResend(): bool
    {
        $apiKey = (string) (env('RESEND_API_KEY') ?: getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? ''));
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            $msg = 'Resend API Key no configurada. Por favor define RESEND_API_KEY en tu archivo .env.';
            $this->setErrorMessage($msg);
            log_message('error', '[ResendEmail] ' . $msg);
            return false;
        }

        // 1. Sender
        $fromEmail = ! empty($this->fromEmail) ? $this->fromEmail : (string) (env('RESEND_FROM_EMAIL') ?: env('EMAIL_FROM_EMAIL') ?: 'contacto@reybingo.com');
        $fromName  = ! empty($this->fromName) ? $this->fromName : (string) (env('RESEND_FROM_NAME') ?: env('EMAIL_FROM_NAME') ?: 'Rey Bingo');

        // Check if $fromEmail already has "Name <email@domain>" format
        if (preg_match('/^(.*?)\s*<([^>]+)>\s*$/', $fromEmail, $matches)) {
            $fromName  = ! empty($fromName) ? $fromName : trim($matches[1], " \"'");
            $fromEmail = trim($matches[2]);
        }

        $fromFormatted = ! empty($fromName) ? "{$fromName} <{$fromEmail}>" : $fromEmail;

        // 2. Recipients
        $to = [];
        if (is_array($this->recipients)) {
            $to = $this->recipients;
        } elseif (is_string($this->recipients) && trim($this->recipients) !== '') {
            $to = explode(',', $this->recipients);
        }

        $to = array_values(array_filter(array_map('trim', $to)));

        if (empty($to)) {
            $msg = 'Resend: No se especificaron destinatarios (To).';
            $this->setErrorMessage($msg);
            log_message('error', '[ResendEmail] ' . $msg);
            return false;
        }

        // 3. Subject
        $subject = (string) ($this->tmpArchive['subject'] ?? ($this->headers['Subject'] ?? ''));
        if (str_starts_with($subject, '=?')) {
            $decoded = iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded !== false) {
                $subject = $decoded;
            }
        }

        // 4. Payload construction
        $payload = [
            'from'    => $fromFormatted,
            'to'      => $to,
            'subject' => $subject,
        ];

        // Body: HTML or plain text
        $rawBody = (string) $this->body;
        if ($this->mailType === 'html') {
            $payload['html'] = $rawBody;
            $payload['text'] = trim(strip_tags($rawBody));
        } else {
            $payload['text'] = $rawBody;
        }

        // Reply-To
        $replyTo = $this->tmpArchive['replyTo'] ?? null;
        if (! empty($replyTo)) {
            $payload['reply_to'] = $replyTo;
        }

        // CC
        if (! empty($this->CCArray)) {
            $payload['cc'] = array_values(array_filter(array_map('trim', $this->CCArray)));
        }

        // BCC
        if (! empty($this->BCCArray)) {
            $payload['bcc'] = array_values(array_filter(array_map('trim', $this->BCCArray)));
        }

        // Attachments
        if (! empty($this->attachments)) {
            $payload['attachments'] = [];
            foreach ($this->attachments as $att) {
                $filename = ! empty($att['name'][1]) ? $att['name'][1] : basename((string) ($att['name'][0] ?? 'attachment'));
                $cleanB64 = str_replace(["\r", "\n", ' '], '', (string) ($att['content'] ?? ''));
                $payload['attachments'][] = [
                    'filename' => $filename,
                    'content'  => $cleanB64,
                ];
            }
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 5. Send HTTP request via cURL
        $ch = curl_init($this->resendApiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'User-Agent: ReyBingo-Resend/1.0',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response   = curl_exec($ch);
        $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($response === false || ! empty($curlError)) {
            $msg = 'Resend cURL Error: ' . ($curlError ?: 'No response from server');
            $this->setErrorMessage($msg);
            log_message('error', '[ResendEmail] ' . $msg);
            return false;
        }

        $resData = json_decode((string) $response, true);

        // HTTP 200..299 with an 'id' is a success
        if ($httpCode >= 200 && $httpCode < 300 && isset($resData['id'])) {
            $msg = "Resend email sent successfully (ID: {$resData['id']})";
            $this->setErrorMessage($msg);
            log_message('info', "[ResendEmail] {$msg} to " . implode(', ', $to));
            return true;
        }

        // Error handling
        $errorDetail = $resData['message'] ?? $resData['error'] ?? $response;
        if (is_array($errorDetail)) {
            $errorDetail = json_encode($errorDetail, JSON_UNESCAPED_UNICODE);
        }
        $msg = "Resend API Error (HTTP {$httpCode}): {$errorDetail}";
        $this->setErrorMessage($msg);
        log_message('error', "[ResendEmail] {$msg} | From: {$fromFormatted} | To: " . implode(', ', $to));

        return false;
    }
}
