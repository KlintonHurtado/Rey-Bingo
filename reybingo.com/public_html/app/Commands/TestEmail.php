<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestEmail extends BaseCommand
{
    protected $group       = 'Email';
    protected $name        = 'email:test';
    protected $description = 'Prueba el envío de correos mediante Resend o SMTP configurado en .env';
    protected $usage       = 'email:test [destinatario@correo.com]';
    protected $arguments   = [
        'email' => 'Correo electrónico destinatario para la prueba (opcional)',
    ];

    public function run(array $params)
    {
        CLI::write('=====================================================', 'yellow');
        CLI::write('  REY BINGO - Test de Envío de Correo (Resend / SMTP)', 'yellow');
        CLI::write('=====================================================', 'yellow');

        $config = config(\Config\Email::class);

        $protocol = $config->protocol;
        $fromEmail = $config->fromEmail;
        $fromName  = $config->fromName;
        $apiKey    = $config->resendApiKey;

        CLI::write("Protocolo activo: {$protocol}", 'cyan');
        CLI::write("Remitente: {$fromName} <{$fromEmail}>", 'cyan');

        if (! empty($apiKey)) {
            $maskedKey = substr($apiKey, 0, 7) . '...' . substr($apiKey, -4);
            CLI::write("RESEND_API_KEY detectada: {$maskedKey}", 'green');
        } else {
            CLI::write("RESEND_API_KEY: [NO CONFIGURADA EN .ENV]", 'red');
        }

        $recipient = $params[0] ?? CLI::prompt('Introduce el correo electrónico destinatario para la prueba:');
        $recipient = trim((string) $recipient);

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            CLI::error("El correo proporcionado '{$recipient}' no es válido.");
            return;
        }

        CLI::write("\nEnviando correo de prueba a: {$recipient} ...", 'yellow');

        try {
            $email = \Config\Services::email();
            $email->clear(true);
            $email->setFrom($fromEmail, $fromName);
            $email->setTo($recipient);
            $email->setSubject('Prueba de Envío Rey Bingo · Resend');
            $email->setMessage(
                '<div style="font-family: Arial, sans-serif; background-color: #1b1238; padding: 24px; color: #ffffff; border-radius: 12px; max-width: 600px; margin: 0 auto;">'
                . '<h2 style="color: #8767fa; margin-top: 0;">¡Prueba de Resend Exitosa!</h2>'
                . '<p style="font-size: 15px; line-height: 1.6; color: #e2e8f0;">Este es un mensaje de prueba generado desde el sistema <strong>Rey Bingo</strong>.</p>'
                . '<p style="font-size: 14px; color: #a0aec0;">Protocolo: <code>' . esc($protocol) . '</code><br>Fecha y hora: ' . date('Y-m-d H:i:s') . '</p>'
                . '<div style="margin-top: 20px; padding: 12px; background: rgba(255,255,255,0.08); border-radius: 8px; font-size: 13px;">'
                . 'Si recibes este correo, la configuración de Resend en tu archivo <code>.env</code> está funcionando a la perfección.'
                . '</div>'
                . '</div>'
            );

            $sent = $email->send(false);

            if ($sent) {
                CLI::write("\n[EXITO] El correo fue enviado satisfactoriamente.", 'green');
                $debugger = (string) $email->printDebugger(['headers']);
                if (! empty($debugger)) {
                    CLI::write(strip_tags($debugger), 'white');
                }
            } else {
                CLI::error("\n[ERROR] No se pudo enviar el correo.");
                CLI::write("\nDetalles del error / depurador:", 'light_red');
                $debugger = (string) $email->printDebugger(['headers', 'subject']);
                CLI::write(strip_tags($debugger), 'red');
            }
        } catch (\Throwable $e) {
            CLI::error("\n[EXCEPCION] " . $e->getMessage());
        }
    }
}
