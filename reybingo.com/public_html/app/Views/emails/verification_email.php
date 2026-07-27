<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(systemGet('name') ?: APP_NAME); ?> · <?= translate('please verify your email address'); ?></title>
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
                                <?= translate('welcome to'); ?> <strong><?= esc(systemGet('name') ?: APP_NAME); ?></strong>
                            </h1>
                            <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#4a5568;">
                                <?= translate('hello'); ?> <strong><?= esc($user['firstname'] ?? ''); ?></strong>,
                            </p>
                            <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#4a5568;">
                                <?= translate('thank you for creating an account in our system.'); ?>
                                <?= translate('we are excited to welcome you to our great family.'); ?>
                            </p>
                            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;color:#4a5568;">
                                <?= translate('to complete your registration, please click the button below to verify your email address'); ?>
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 18px auto;">
                                <tr>
                                    <td align="center" bgcolor="#6236ff" style="border-radius:10px;">
                                        <a href="<?= site_url('verify/' . $token); ?>"
                                           style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;background-color:#6236ff;">
                                            <?= translate('verify'); ?>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 8px 0;font-size:13px;line-height:1.5;color:#718096;">
                                <?= translate('if the button does not work copy this link'); ?>:
                            </p>
                            <p style="margin:0 0 18px 0;font-size:12px;line-height:1.5;word-break:break-all;">
                                <a href="<?= site_url('verify/' . $token); ?>" style="color:#6236ff;"><?= site_url('verify/' . $token); ?></a>
                            </p>
                            <p style="margin:0 0 6px 0;font-size:15px;line-height:1.6;color:#4a5568;">
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
