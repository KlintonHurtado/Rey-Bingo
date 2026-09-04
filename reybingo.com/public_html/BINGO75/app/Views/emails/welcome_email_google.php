<!DOCTYPE html>
<html>
    <link rel="stylesheet" href="<?= site_url('assets/css/styles.css'); ?>?<?= md5(date("Hms")); ?>">
    <body style="background-color: #222533; padding: 20px; font-size: 14px; line-height: 1.43; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;">
        <div style="max-width: 600px; margin: 0px auto; background-color: #fff; box-shadow: 0px 20px 50px rgba(0,0,0,0.05);">
            <table style="width: 100%;">
                <tr>
                    <td style="background-color: #fff;">
                        <img alt="logo" src="<?= site_url('assets/img/logo.png'); ?>" style="width: 120px; padding: 5px">
                    </td>
                    <td style="padding-left: 50px; text-align: right; padding-right: 20px;">
                        <a href="<?= site_url('signin'); ?>" style="color: #261D1D; text-decoration: underline; font-size: 14px; letter-spacing: 1px;"><?= translate('signin'); ?></a>
                    </td>
                </tr>
            </table>
            <div style="padding: 40px; border-top: 1px solid rgba(0,0,0,0.05);">
                <h1 style="margin-top: 0px; font-size: 1.5rem;">
                    <?= translate('welcome to'); ?> <strong>Reybingo.com</strong>.
                </h1>
                <div style="color: #636363; font-size: 14px;">
                    <?= translate('hello'); ?> <strong><?= $user['firstname']; ?></strong>,<br /> <?= translate('thank you for creating an account in our system.'); ?> <?= translate('we are excited to welcome you to our great family.'); ?> <?= translate('get ready to enjoy a world full of fun and excitement with our exclusive features designed to give you the bingo experience like never before.'); ?>
                </div>
                <div style="color: #636363; font-size: 14px; margin-top: 5px;">
                    <?= translate('your temporary password is'); ?>: <strong>123456</strong> <?= translate('we recommend updating your password for a more secure one.'); ?>
                </div>
                <div style="color: #636363; font-size: 14px; margin-top: 10px;">
                    <?= translate('start playing, discover all the surprises we have for you and experience the excitement of ' . strtolower(APP_NAME) . '!'); ?>
                </div>
                <div style="color: #636363; font-size: 14px; margin-top: 10px;">
                    <?= translate('thanks'); ?>,<br /> <?= translate('team'); ?> <?= systemGet('name'); ?>
                </div>
                <h4 style="margin-bottom: 10px;"><?= translate('need help?'); ?></h4>
                <div style="color: #A5A5A5; font-size: 12px;">
                    <p>
                        <?= translate('if you have any questions you can simply reply to this email or find our contact information below.'); ?> <?= translate('also contact us at'); ?> <a href="javascript:void(0);" style="text-decoration: underline; color: #4B72FA;"> <?= systemGet('email'); ?></a>
                    </p>
                </div>
            </div>
            <div style="background-color: #F5F5F5; padding: 20px 40px; text-align: center;">
                <div style="color: #A5A5A5; font-size: 12px; margin-bottom: 20px; padding: 0px 20px;">
                    <?= translate('you are receiving this email because you signed up for'); ?> <?= systemGet('name'); ?>.
                </div>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05);">
                    <div style="color: #A5A5A5; font-size: 10px; margin-bottom: 5px;">
                        <?= systemGet('address'); ?>, <?= systemGet('city'); ?> <?= systemGet('zipcode'); ?>, <?= systemGet('state'); ?>, <?= systemGet('country'); ?>
                    </div>
                    <div style="color: #A5A5A5; font-size: 10px;">
                        &copy; <?= date("Y");?> <?= APP_NAME; ?> · <?= translate('all rights reserved'); ?>.
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>