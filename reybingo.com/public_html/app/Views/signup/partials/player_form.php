<?php
$formId = $formId ?? 'player-signup-form';
$fieldPrefix = $fieldPrefix ?? 'player-signup';
$referrerName = $referrerName ?? '';
$showHeader = ! isset($showHeader) || $showHeader;
$showSigninLink = ! isset($showSigninLink) || $showSigninLink;
$submitLabel = $submitLabel ?? translate('create');
$storeRegistering = ! empty($storeRegistering);
$backUrl = $backUrl ?? site_url('store/affiliate');
?>
<div class="player-signup-form-wrap <?= ! empty($embedded) ? 'player-signup-form-embedded' : ''; ?>">
    <?php if ($showHeader) : ?>
        <div class="text-center player-signup-form-head">
            <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid logo" alt="img">
            <h5 class="mb-2 p-2"><?= translate('create player account'); ?></h5>
            <span class="badge bg-primary mb-2"><?= translate('player'); ?></span>
            <?php if ($storeRegistering) : ?>
                <p class="small text-info mb-2"><?= translate('store registering player note'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php echo form_open(site_url('signup/signupSubmit'), ['id' => $formId]); ?>
        <?= csrf_field() ?>
        <input type="hidden" name="signup_context" value="store_affiliate">

        <div class="row player-signup-form-grid g-2 g-md-3">
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-firstname" class="form-label"><?= translate('first name'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="<?= esc($fieldPrefix); ?>-firstname" <?= empty($embedded) ? 'autofocus' : ''; ?> autocomplete="given-name" required>
                <small id="<?= esc($fieldPrefix); ?>-firstname-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-lastname" class="form-label"><?= translate('last name'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="<?= esc($fieldPrefix); ?>-lastname" autocomplete="family-name" required>
                <small id="<?= esc($fieldPrefix); ?>-lastname-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-document" class="form-label"><?= translate('document'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="document" id="<?= esc($fieldPrefix); ?>-document" autocomplete="off" inputmode="numeric" required>
                <small id="<?= esc($fieldPrefix); ?>-document-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-phone" class="form-label"><?= translate('phone'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="<?= esc($fieldPrefix); ?>-phone" autocomplete="tel" required>
                <small id="<?= esc($fieldPrefix); ?>-phone-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-address_line" class="form-label"><?= translate('address'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="<?= esc($fieldPrefix); ?>-address_line" autocomplete="street-address" required>
                <small id="<?= esc($fieldPrefix); ?>-address_line-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-email" class="form-label"><?= translate('email'); ?></label>
                <input type="email" class="form-control form-control-lg form-bingo" name="email" id="<?= esc($fieldPrefix); ?>-email" autocomplete="email" required>
                <small id="<?= esc($fieldPrefix); ?>-email-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-username" class="form-label"><?= translate('username'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="username" id="<?= esc($fieldPrefix); ?>-username" autocomplete="username" required>
                <small id="<?= esc($fieldPrefix); ?>-username-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-password" class="form-label"><?= translate('password'); ?></label>
                <input type="password" class="form-control form-control-lg form-bingo" name="password" id="<?= esc($fieldPrefix); ?>-password" autocomplete="new-password" required>
                <small id="<?= esc($fieldPrefix); ?>-password-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 player-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-password_confirm" class="form-label"><?= translate('password confirm'); ?></label>
                <input type="password" class="form-control form-control-lg form-bingo" name="password_confirm" id="<?= esc($fieldPrefix); ?>-password_confirm" autocomplete="new-password" required>
                <small id="<?= esc($fieldPrefix); ?>-password_confirm-error" class="text-danger d-none"></small>
            </div>
            <?php if (bingo_terms_require_accept()) : ?>
            <div class="col-12 player-signup-form-field">
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" value="1" name="accept_terms" id="<?= esc($fieldPrefix); ?>-accept_terms">
                    <label class="form-check-label text-white" for="<?= esc($fieldPrefix); ?>-accept_terms">
                        <?= translate('i accept the'); ?>
                        <a href="<?= site_url('terminos'); ?>" target="_blank" rel="noopener"><?= translate('terms and conditions'); ?></a>
                        <?= translate('and'); ?>
                        <a href="<?= site_url('promociones'); ?>" target="_blank" rel="noopener"><?= translate('promotions'); ?></a>
                    </label>
                </div>
                <small id="<?= esc($fieldPrefix); ?>-accept_terms-error" class="text-danger d-none"></small>
            </div>
            <?php endif; ?>
            <div class="col-12 player-signup-form-actions">
                <button type="submit" class="btn btn-primary btn-bingo mt-2" id="<?= esc($fieldPrefix); ?>-submit">
                    <?= esc($submitLabel); ?>
                </button>
            </div>
        </div>
    <?php echo form_close(); ?>

    <?php if ($showSigninLink && empty($embedded)) : ?>
        <div class="text-center mt-3">
            <a href="<?= site_url('signin'); ?>" class="text-white"><?= translate('already have an account'); ?> <?= translate('sign in'); ?></a>
        </div>
    <?php elseif ($storeRegistering && empty($embedded)) : ?>
        <div class="text-center mt-3">
            <a href="<?= esc($backUrl); ?>" class="text-white"><i class="fa-duotone fa-solid fa-arrow-left"></i> <?= translate('back to store panel'); ?></a>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
$(function() {
    const formId = '<?= esc($formId, 'js'); ?>';
    const fieldPrefix = '<?= esc($fieldPrefix, 'js'); ?>';
    const $form = $('#' + formId);

    $form.on('submit', function(e) {
        e.preventDefault();
        const $button = $('#' + fieldPrefix + '-submit');
        $button.prop('disabled', true);
        $form.find('.text-danger').addClass('d-none').text('');
        $form.find('.form-control').removeClass('is-invalid');

        $.ajax({
            url: '<?= site_url('signup/signupSubmit') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success && response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                if (response.errors) {
                    const genericErrors = [];
                    $.each(response.errors, function(field, message) {
                        const $field = $('#' + fieldPrefix + '-' + field);
                        const $error = $('#' + fieldPrefix + '-' + field + '-error');
                        if ($error.length) {
                            $error.text(message).removeClass('d-none');
                        } else {
                            genericErrors.push(message);
                        }
                        if ($field.length) {
                            $field.addClass('is-invalid');
                        }
                    });
                    if (genericErrors.length) {
                        alert(genericErrors.join('\n'));
                    }
                } else if (response.error) {
                    alert(response.error);
                } else {
                    alert('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>');
                }
            },
            error: function() {
                alert('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
});
</script>
