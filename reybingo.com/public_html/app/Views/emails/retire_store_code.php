<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(systemGet('name') ?: APP_NAME); ?> · Código de Retiro en Punto de Venta</title>
</head>
<body style="margin:0;padding:0;background-color:#1b1238;font-family:Arial,Helvetica,sans-serif;color:#2d3748;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#1b1238;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#6236ff,#8767fa);padding:20px 24px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left">
                                        <img src="<?= site_url('assets/img/logo.png'); ?>" alt="<?= esc(systemGet('name') ?: APP_NAME); ?>" width="110" style="display:block;border:0;max-width:110px;height:auto;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <a href="<?= site_url('signin'); ?>" style="color:#ffffff;text-decoration:underline;font-size:13px;font-weight:700;"><?= translate('signin'); ?></a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px 12px 28px;">
                            <h1 style="margin:0 0 12px 0;font-size:22px;line-height:1.3;color:#3b1f9c;">
                                Nota de Retiro Aprobada - Punto de Venta
                            </h1>
                            <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#4a5568;">
                                <?= translate('hello'); ?> <strong><?= esc($user['firstname'] ?? ''); ?></strong>,
                            </p>
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#4a5568;">
                                ¡Buenas noticias! Tu solicitud de retiro ha sido <strong>APROBADA</strong> por la administración. Ya puedes cobrar tu dinero en efectivo en cualquiera de nuestros <strong>Puntos de Venta</strong> autorizados presentando el siguiente código:
                            </p>

                            <!-- Recuadro del Código de Retiro -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:20px 0;background-color:#f7f5ff;border:2px dashed #6236ff;border-radius:12px;text-align:center;">
                                <tr>
                                    <td style="padding:22px 16px;">
                                        <div style="font-size:13px;font-weight:700;color:#6236ff;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px;">
                                            Tu Código de Retiro
                                        </div>
                                        <div style="display:inline-block;padding:12px 24px;background-color:#6236ff;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:3px;font-family:monospace;border-radius:10px;">
                                            <?= esc($code); ?>
                                        </div>
                                        <div style="margin-top:10px;font-size:17px;font-weight:700;color:#198754;">
                                            Monto: <?= esc($currency); ?> <?= number_format((float) $amount, 2); ?>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Detalle de la Solicitud -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;font-size:14px;border-top:1px solid #edf2f7;">
                                <tr>
                                    <td style="padding:10px 0;color:#718096;border-bottom:1px solid #edf2f7;">Cédula / Documento:</td>
                                    <td style="padding:10px 0;font-weight:700;text-align:right;color:#2d3748;border-bottom:1px solid #edf2f7;"><?= esc($user['document'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#718096;border-bottom:1px solid #edf2f7;">Fecha de Solicitud:</td>
                                    <td style="padding:10px 0;font-weight:700;text-align:right;color:#2d3748;border-bottom:1px solid #edf2f7;"><?= date('d/m/Y h:i A'); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#718096;border-bottom:1px solid #edf2f7;">Método:</td>
                                    <td style="padding:10px 0;font-weight:700;text-align:right;color:#6236ff;border-bottom:1px solid #edf2f7;">Efectivo en Punto de Venta</td>
                                </tr>
                            </table>

                            <!-- Instrucciones para cobrar -->
                            <div style="background-color:#fffbeb;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:6px;margin:20px 0;">
                                <strong style="color:#92400e;font-size:14px;display:block;margin-bottom:6px;">
                                    ¿Cómo cobrar tu dinero?
                                </strong>
                                <ol style="color:#78350f;font-size:13px;line-height:1.6;margin:0;padding-left:18px;">
                                    <li>Acércate a cualquier Punto de Venta autorizado.</li>
                                    <li>Indica tu número de cédula: <strong><?= esc($user['document'] ?? '-'); ?></strong>.</li>
                                    <li>Muestra o proporciona tu código de retiro: <strong style="font-family:monospace;color:#6236ff;"><?= esc($code); ?></strong>.</li>
                                    <li>El cajero validará la información y te entregará tu dinero en efectivo.</li>
                                </ol>
                            </div>

                            <p style="margin:20px 0 6px 0;font-size:15px;line-height:1.6;color:#4a5568;">
                                <?= translate('thanks'); ?>,<br>
                                <?= translate('team'); ?> <?= esc(systemGet('name') ?: APP_NAME); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 24px 28px;">
                            <h3 style="margin:0 0 8px 0;font-size:15px;color:#3b1f9c;"><?= translate('need help?'); ?></h3>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#718096;">
                                <?= translate('if you have any questions you can simply reply to this email or find our contact information below.'); ?>
                                <?= translate('also contact us at'); ?>
                                <a href="mailto:<?= esc(systemGet('email')); ?>" style="color:#6236ff;text-decoration:underline;"><?= esc(systemGet('email')); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f7f5ff;padding:18px 28px;text-align:center;">
                            <p style="margin:0 0 10px 0;font-size:12px;line-height:1.5;color:#8a849c;">
                                <?= translate('you are receiving this email because you signed up for'); ?>
                                <?= esc(systemGet('name') ?: APP_NAME); ?>.
                            </p>
                            <p style="margin:0;font-size:11px;line-height:1.5;color:#a0aec0;">
                                <?= esc(systemGet('address')); ?>
                                <?php if (systemGet('city')): ?>, <?= esc(systemGet('city')); ?><?php endif; ?>
                                <?php if (systemGet('state')): ?>, <?= esc(systemGet('state')); ?><?php endif; ?>
                                <?php if (systemGet('country')): ?>, <?= esc(systemGet('country')); ?><?php endif; ?>
                            </p>
                            <p style="margin:8px 0 0 0;font-size:11px;color:#a0aec0;">
                                &copy; <?= date('Y'); ?> <?= esc(APP_NAME); ?> · <?= translate('all rights reserved'); ?>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
