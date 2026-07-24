<a class="btn btn-small btn-home" href="<?= site_url('games'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

<style>
    .legal-admin {
        max-width: 1100px;
        margin: 1rem auto 2.5rem;
        padding: 0 1rem;
        padding-top: 70px;
    }
    .legal-admin__card {
        background: rgba(255, 255, 255, 0.97);
        border-radius: 18px;
        box-shadow: 0 12px 40px rgba(24, 10, 84, 0.16);
        padding: 1.25rem 1rem 1.75rem;
        color: #2d3748;
    }
    .legal-admin__header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: center;
        margin-bottom: 1rem;
    }
    .legal-admin__header h1 {
        font-size: 1.35rem;
        font-weight: 800;
        color: #3b1f9c;
        margin: 0;
    }
    .legal-admin__links a {
        margin-right: 0.5rem;
        font-weight: 600;
        color: #6236ff;
        text-decoration: none;
    }
    .legal-admin .form-label {
        font-weight: 700;
        color: #3b1f9c;
    }
    .legal-admin .tox-tinymce {
        border-radius: 12px !important;
    }
    @media (min-width: 768px) {
        .legal-admin__card {
            padding: 1.75rem 1.75rem 2rem;
        }
    }
</style>

<div class="legal-admin">
    <div class="legal-admin__card">
        <div class="legal-admin__header">
            <div>
                <h1><i class="fa-duotone fa-solid fa-scale-balanced"></i> <?= translate('legal content'); ?></h1>
                <small class="text-muted">
                    <?= translate('edit terms and promotions description'); ?>
                    <?php if (! empty($termsUpdatedAt)) : ?>
                        · <?= translate('last updated'); ?>: <?= esc(date('d/m/Y H:i', strtotime($termsUpdatedAt))); ?>
                    <?php endif; ?>
                </small>
            </div>
            <div class="legal-admin__links">
                <a href="<?= site_url('terminos'); ?>" target="_blank" rel="noopener"><?= translate('view terms'); ?></a>
                <a href="<?= site_url('promociones'); ?>" target="_blank" rel="noopener"><?= translate('view promotions'); ?></a>
            </div>
        </div>

        <form id="legal-admin-form" method="post" action="<?= site_url('legal/adminSubmit'); ?>">
            <?= csrf_field(); ?>

            <div class="mb-3">
                <label class="form-label" for="termsRequireAccept"><?= translate('require terms acceptance on signup'); ?></label>
                <select class="form-control form-control-lg form-bingo" name="termsRequireAccept" id="termsRequireAccept">
                    <option value="1" <?= ($termsRequireAccept ?? '1') === '1' ? 'selected' : ''; ?>><?= translate('active'); ?></option>
                    <option value="0" <?= ($termsRequireAccept ?? '1') === '0' ? 'selected' : ''; ?>><?= translate('inactive'); ?></option>
                </select>
                <small class="text-muted"><?= translate('require terms acceptance help'); ?></small>
            </div>

            <div class="mb-4">
                <label class="form-label" for="termsHtml"><?= translate('terms and conditions'); ?></label>
                <textarea name="termsHtml" id="termsHtml" rows="14"><?= esc($termsHtml ?? '', 'html'); ?></textarea>
                <small id="termsHtml-error" class="text-danger d-none"></small>
            </div>

            <div class="mb-4">
                <label class="form-label" for="promotionsHtml"><?= translate('promotions'); ?></label>
                <textarea name="promotionsHtml" id="promotionsHtml" rows="14"><?= esc($promotionsHtml ?? '', 'html'); ?></textarea>
                <small id="promotionsHtml-error" class="text-danger d-none"></small>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-bingo" id="legal-save-btn">
                    <i class="fa-duotone fa-solid fa-floppy-disk"></i> <?= translate('save'); ?>
                </button>
                <a href="<?= site_url('games'); ?>" class="btn btn-secondary"><?= translate('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.4/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    function initEditors() {
        if (typeof tinymce === 'undefined') {
            return;
        }
        tinymce.remove('#termsHtml');
        tinymce.remove('#promotionsHtml');
        tinymce.init({
            selector: '#termsHtml, #promotionsHtml',
            height: 360,
            menubar: false,
            plugins: 'lists link table code',
            toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 15px; }',
            branding: false,
            convert_urls: false
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditors);
    } else {
        initEditors();
    }

    $('#legal-admin-form').on('submit', function (e) {
        e.preventDefault();
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        var button = $('#legal-save-btn');
        var original = button.html();
        button.prop('disabled', true).html('<i class="fa-duotone fa-spinner-third fa-spin"></i>');
        $('.text-danger').addClass('d-none').text('');

        $.ajax({
            url: '<?= site_url('legal/adminSubmit'); ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            if (response.success) {
                Toastify({
                    text: response.message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#198754' },
                    stopOnFocus: true
                }).showToast();
            } else if (response.errors) {
                $.each(response.errors, function (field, message) {
                    $('#' + field + '-error').text(message).removeClass('d-none');
                });
            } else {
                Toastify({
                    text: response.message || '<?= translate('error updating settings'); ?>',
                    duration: 4000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' },
                    stopOnFocus: true
                }).showToast();
            }
        }).fail(function () {
            Toastify({
                text: '<?= translate('there was an error in the request to the server'); ?>',
                duration: 3000,
                gravity: 'top',
                position: 'right',
                style: { background: '#dc3545' },
                stopOnFocus: true
            }).showToast();
        }).always(function () {
            button.prop('disabled', false).html(original);
        });
    });
})();
</script>
