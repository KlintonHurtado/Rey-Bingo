<?php
$formId = $formId ?? 'store-signup-form';
$fieldPrefix = $fieldPrefix ?? 'store-signup';
$referrerName = $referrerName ?? '';
$referrerType = $referrerType ?? 'operator';
$showHeader = ! isset($showHeader) || $showHeader;
$showSigninLink = ! isset($showSigninLink) || $showSigninLink;
$submitLabel = $submitLabel ?? translate('operator enter');
$storeRegistering = ! empty($storeRegistering);
$operatorRegistering = ! empty($operatorRegistering);
$backUrl = $backUrl ?? site_url('store/affiliate');
$backLabel = $backLabel ?? translate('back to store panel');
$roleLabel = translate('point of sale');
?>
<div class="store-signup-form-wrap <?= ! empty($embedded) ? 'store-signup-form-embedded' : ''; ?>">
    <?php if ($showHeader) : ?>
        <div class="text-center store-signup-form-head">
            <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid logo" alt="img">
            <h5 class="mb-2 p-2"><?= translate('create point of sale account'); ?></h5>
            <span class="badge bg-success mb-2"><?= esc($roleLabel); ?></span>
        </div>
    <?php endif; ?>

    <?php echo form_open(site_url('signup/storeSignupSubmit'), ['id' => $formId]); ?>
        <?= csrf_field() ?>
        <?php if ($storeRegistering && ! $operatorRegistering) : ?>
            <input type="hidden" name="from_store_panel" value="1">
        <?php endif; ?>
        <?php if ($operatorRegistering) : ?>
            <input type="hidden" name="from_operator_panel" value="1">
        <?php endif; ?>

        <div class="row store-signup-form-grid g-2 g-md-3">
            <?php if ($referrerName !== '' && $referrerType === 'operator') : ?>
            <div class="col-12 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-referrer" class="form-label"><?= translate('operator'); ?></label>
                <input
                    type="text"
                    class="form-control form-control-lg form-bingo"
                    id="<?= esc($fieldPrefix); ?>-referrer"
                    value="<?= esc($referrerName); ?>"
                    readonly
                    tabindex="-1"
                >
            </div>
            <?php endif; ?>

            <div class="col-12 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-business_name" class="form-label"><?= translate('business name'); ?></label>
                <input
                    type="text"
                    class="form-control form-control-lg form-bingo"
                    name="business_name"
                    id="<?= esc($fieldPrefix); ?>-business_name"
                    placeholder="<?= translate('business name'); ?>"
                    <?= empty($embedded) ? 'autofocus' : ''; ?>
                    autocomplete="organization"
                    required
                >
                <small id="<?= esc($fieldPrefix); ?>-business_name-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-firstname" class="form-label"><?= translate('first name'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="<?= esc($fieldPrefix); ?>-firstname" autocomplete="given-name" required>
                <small id="<?= esc($fieldPrefix); ?>-firstname-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-lastname" class="form-label"><?= translate('last name'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="<?= esc($fieldPrefix); ?>-lastname" autocomplete="family-name" required>
                <small id="<?= esc($fieldPrefix); ?>-lastname-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-document" class="form-label"><?= translate('document'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="document" id="<?= esc($fieldPrefix); ?>-document" autocomplete="off" inputmode="numeric" required>
                <small id="<?= esc($fieldPrefix); ?>-document-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-phone" class="form-label"><?= translate('phone'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="<?= esc($fieldPrefix); ?>-phone" autocomplete="tel" required>
                <small id="<?= esc($fieldPrefix); ?>-phone-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-address_line" class="form-label"><?= translate('address'); ?></label>
                <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="<?= esc($fieldPrefix); ?>-address_line" autocomplete="street-address" required>
                <small id="<?= esc($fieldPrefix); ?>-address_line-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-email" class="form-label"><?= translate('email'); ?></label>
                <input type="email" class="form-control form-control-lg form-bingo" name="email" id="<?= esc($fieldPrefix); ?>-email" autocomplete="email" required>
                <small id="<?= esc($fieldPrefix); ?>-email-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-password" class="form-label"><?= translate('password'); ?></label>
                <input type="password" class="form-control form-control-lg form-bingo" name="password" id="<?= esc($fieldPrefix); ?>-password" autocomplete="new-password" required>
                <small id="<?= esc($fieldPrefix); ?>-password-error" class="text-danger d-none"></small>
            </div>
            <div class="col-12 col-md-6 store-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-password_confirm" class="form-label"><?= translate('password confirm'); ?></label>
                <input type="password" class="form-control form-control-lg form-bingo" name="password_confirm" id="<?= esc($fieldPrefix); ?>-password_confirm" autocomplete="new-password" required>
                <small id="<?= esc($fieldPrefix); ?>-password_confirm-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 store-signup-form-actions">
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
    <?php elseif ($storeRegistering && ! $operatorRegistering && empty($embedded)) : ?>
        <div class="text-center mt-3 mb-4">
            <a href="<?= esc($backUrl); ?>" class="text-white"><i class="fa-duotone fa-solid fa-arrow-left"></i> <?= esc($backLabel); ?></a>
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

        $.ajax({
            url: '<?= site_url('signup/storeSignupSubmit') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success && response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                if (response.errors) {
                    $.each(response.errors, function(field, message) {
                        $('#' + fieldPrefix + '-' + field + '-error').text(message).removeClass('d-none');
                    });
                } else if (response.error) {
                    alert(response.error);
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
