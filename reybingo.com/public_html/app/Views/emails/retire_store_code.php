<!DOCTYPE html>
<html>
    <body style="background-color: #1a1c2e; padding: 20px; font-size: 14px; line-height: 1.5; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;">
        <div style="max-width: 600px; margin: 0px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0px 20px 50px rgba(0,0,0,0.15);">
            <table style="width: 100%; background: linear-gradient(135deg, #111827 0%, #1f2937 100%); padding: 20px;">
                <tr>
                    <td>
                        <img alt="logo" src="<?= site_url('assets/img/logo.png'); ?>" style="max-width: 140px; height: auto;">
                    </td>
                    <td style="text-align: right; color: #f59e0b; font-weight: 600; font-size: 14px;">
                        🏪 Retiro en Punto de Venta
                    </td>
                </tr>
            </table>

            <div style="padding: 35px 30px;">
                <h2 style="margin-top: 0; color: #111827; font-size: 1.4rem;">
                    ¡Hola, <strong><?= esc($user['firstname'] ?? 'Jugador'); ?></strong>!
                </h2>
                <p style="color: #4b5563; font-size: 15px; margin-bottom: 20px;">
                    Has generado exitosamente una solicitud de retiro para cobrar en efectivo en un <strong>Punto de Venta</strong>.
                </p>

                <!-- Tarjeta destacada con el código -->
                <div style="background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border: 2px dashed #4B72FA; border-radius: 12px; padding: 25px; text-align: center; margin: 25px 0;">
                    <div style="color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold; margin-bottom: 8px;">
                        Tu Código de Retiro
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #1e40af; letter-spacing: 4px; font-family: monospace; padding: 10px 0;">
                        <?= esc($code); ?>
                    </div>
                    <div style="color: #059669; font-size: 18px; font-weight: 700; margin-top: 6px;">
                        Monto: <?= esc($currency); ?> <?= number_format((float) $amount, 2); ?>
                    </div>
                </div>

                <!-- Datos de la solicitud -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px;">
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 10px 0; color: #6b7280;">Número de Cédula / Documento:</td>
                        <td style="padding: 10px 0; font-weight: 600; text-align: right; color: #111827;"><?= esc($user['document'] ?? '-'); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 10px 0; color: #6b7280;">Fecha de Solicitud:</td>
                        <td style="padding: 10px 0; font-weight: 600; text-align: right; color: #111827;"><?= date('d/m/Y h:i A'); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 10px 0; color: #6b7280;">Método de Cobro:</td>
                        <td style="padding: 10px 0; font-weight: 600; text-align: right; color: #2563eb;">Efectivo en Punto de Venta</td>
                    </tr>
                </table>

                <!-- Instrucciones -->
                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px 18px; border-radius: 6px; margin-bottom: 25px;">
                    <strong style="color: #92400e; font-size: 14px; display: block; margin-bottom: 5px;">
                        📌 ¿Cómo cobrar tu dinero?
                    </strong>
                    <ol style="color: #78350f; font-size: 13px; margin: 0; padding-left: 20px; line-height: 1.6;">
                        <li>Acércate a cualquier <strong>Punto de Venta</strong> autorizado.</li>
                        <li>Indica tu número de cédula: <strong><?= esc($user['document'] ?? '-'); ?></strong>.</li>
                        <li>Proporciona tu código de retiro: <strong style="font-family: monospace; font-size: 14px;"><?= esc($code); ?></strong>.</li>
                        <li>El operador validará la información y te entregará tu dinero en efectivo.</li>
                    </ol>
                </div>

                <p style="color: #9ca3af; font-size: 12px; margin-top: 25px;">
                    Si no realizaste esta solicitud, por favor comunícate de inmediato con el equipo de soporte.
                </p>
            </div>

            <div style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                <div style="color: #9ca3af; font-size: 11px;">
                    &copy; <?= date('Y'); ?> <?= APP_NAME; ?> · <?= translate('all rights reserved'); ?>.
                </div>
            </div>
        </div>
    </body>
</html>
