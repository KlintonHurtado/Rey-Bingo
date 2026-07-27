<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-6 col-xl-5">
            <div class="text-center mb-3">
                <img src="<?= site_url('assets/img/logo_principal.png'); ?>?v=2" class="img-fluid logo" alt="img">
                <h5 class="mb-1 p-2"><?= translate('choose username'); ?></h5>
                <p class="small text-white-50 mb-0"><?= translate('google alias help'); ?></p>
            </div>

            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')); ?></div>
            <?php endif; ?>

            <?php echo form_open(site_url('signup/googleAliasSubmit')); ?>
                <?= csrf_field(); ?>

                <div class="mb-2">
                    <label class="form-label"><?= translate('email'); ?></label>
                    <input type="text" class="form-control form-control-lg form-bingo" value="<?= esc($pending['email'] ?? ''); ?>" disabled>
                </div>

                <div class="mb-2">
                    <label class="form-label"><?= translate('name'); ?></label>
                    <input type="text" class="form-control form-control-lg form-bingo"
                           value="<?= esc(trim(($pending['firstname'] ?? '') . ' ' . ($pending['lastname'] ?? ''))); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label"><?= translate('username'); ?> / <?= translate('alias'); ?></label>
                    <input type="text" class="form-control form-control-lg form-bingo" name="username" id="username"
                           value="<?= esc($suggestedUsername ?? ''); ?>"
                           placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('username')); ?>"
                           autofocus autocomplete="off" required minlength="3">
                    <small class="text-white-50"><?= translate('suggested alias from your name'); ?></small>
                </div>

                <button type="submit" class="btn btn-primary btn-bingo d-block w-100">
                    <?= translate('create account'); ?>
                </button>
            <?= form_close(); ?>

            <div class="text-center mt-3">
                <a class="text-white-50" href="<?= site_url('signin'); ?>"><?= translate('login'); ?></a>
            </div>
        </div>
    </div>
</div>
