<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    /**
     * Remitente por defecto
     */
    public string $fromEmail = 'contacto@reybingo.com';
    public string $fromName  = 'Rey Bingo';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * Protocolo de envío: 'resend', 'smtp', 'mail', 'sendmail'
     * Si RESEND_API_KEY está configurada en .env, por defecto se utiliza 'resend' (vía HTTPS API).
     */
    public string $protocol = 'resend';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname (para fallback SMTP tradicional o smtp.resend.com)
     */
    public string $SMTPHost = 'smtp.resend.com';

    /**
     * SMTP Username ('resend' si se usa SMTP de Resend)
     */
    public string $SMTPUser = 'resend';

    /**
     * SMTP Password (nunca quemada en código duro, se lee de .env)
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port (465 para SSL o 587 para TLS)
     */
    public int $SMTPPort = 465;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 15;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption: 'ssl' (puerto 465) o 'tls' (puerto 587)
     */
    public string $SMTPCrypto = 'ssl';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character.
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character.
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    /**
     * Resend API Key (leída desde .env)
     */
    public string $resendApiKey = '';

    public function __construct()
    {
        parent::__construct();

        // 1. Resend API Key
        $apiKey = (string) (env('RESEND_API_KEY') ?: getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? ''));
        $this->resendApiKey = trim($apiKey);

        // 2. Protocolo de envío
        $envProtocol = env('EMAIL_PROTOCOL') ?: getenv('EMAIL_PROTOCOL') ?: ($_ENV['EMAIL_PROTOCOL'] ?? null);
        if ($envProtocol) {
            $this->protocol = strtolower(trim((string) $envProtocol));
        } elseif (! empty($this->resendApiKey)) {
            $this->protocol = 'resend';
        } else {
            $this->protocol = 'smtp';
        }

        // 3. Remitente (From)
        $fromEmail = env('RESEND_FROM_EMAIL') ?: env('EMAIL_FROM_EMAIL') ?: getenv('RESEND_FROM_EMAIL') ?: ($_ENV['RESEND_FROM_EMAIL'] ?? null);
        if ($fromEmail) {
            $this->fromEmail = trim((string) $fromEmail);
        }

        $fromName = env('RESEND_FROM_NAME') ?: env('EMAIL_FROM_NAME') ?: getenv('RESEND_FROM_NAME') ?: ($_ENV['RESEND_FROM_NAME'] ?? null);
        if ($fromName) {
            $this->fromName = trim((string) $fromName);
        }

        // 4. Parámetros SMTP (fallback o modo SMTP)
        $smtpHost = env('SMTP_HOST') ?: env('email.SMTPHost') ?: getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? null);
        if ($smtpHost) {
            $this->SMTPHost = trim((string) $smtpHost);
        }

        $smtpUser = env('SMTP_USER') ?: env('email.SMTPUser') ?: getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? null);
        if ($smtpUser) {
            $this->SMTPUser = trim((string) $smtpUser);
        }

        $smtpPass = env('SMTP_PASS') ?: env('email.SMTPPass') ?: getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? null);
        if ($smtpPass !== null) {
            $this->SMTPPass = (string) $smtpPass;
        } elseif (! empty($this->resendApiKey) && $this->SMTPUser === 'resend') {
            // Si usa SMTP de Resend, el password es el API key
            $this->SMTPPass = $this->resendApiKey;
        }

        $smtpPort = env('SMTP_PORT') ?: env('email.SMTPPort') ?: getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? null);
        if ($smtpPort) {
            $this->SMTPPort = (int) $smtpPort;
        }

        $smtpCrypto = env('SMTP_CRYPTO') ?: env('email.SMTPCrypto') ?: getenv('SMTP_CRYPTO') ?: ($_ENV['SMTP_CRYPTO'] ?? null);
        if ($smtpCrypto) {
            $this->SMTPCrypto = trim((string) $smtpCrypto);
        }
    }
}
