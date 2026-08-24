<?php
$isUpdate = ! empty($isUpdate);
$userData = $userData ?? null;
$permissionsCatalog = $permissionsCatalog ?? bingo_assignable_permissions_catalog();
$permissionPresets = $permissionPresets ?? bingo_staff_permission_presets();
$selectedPermissionKeys = $selectedPermissionKeys ?? [];
$selectedMap = array_fill_keys(array_map('strval', $selectedPermissionKeys), true);

$permsByModule = [];
foreach ($permissionsCatalog as $perm) {
    $mod = (string) ($perm['module'] ?? 'General');
    $permsByModule[$mod][] = $perm;
}

$presetsJson = [];
foreach ($permissionPresets as $preset) {
    $presetsJson[] = [
        'slug' => (string) ($preset['slug'] ?? ''),
        'name' => (string) ($preset['name'] ?? ''),
        'description' => (string) ($preset['description'] ?? ''),
        'permissions' => array_values($preset['permissions'] ?? []),
    ];
}
?>
<div class="admin-user-form-scroll">
<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => '',
    'imagePath' => $imagePath ?? site_url('assets/img/avatar.jpg'),
    'showHome' => true,
    'showStatistics' => true,
    'showUsers' => true,
]) ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="container admin-user-form-page">
    <div class="card border-0 shadow-sm admin-user-form-card">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="mb-1 text-dark">
                        <i class="fa-duotone fa-solid fa-user-plus text-success me-2"></i>
                        <?= $isUpdate ? 'Editar staff Admin' : 'Crear staff Admin'; ?>
                    </h4>
                    <p class="text-muted small mb-0">Solo para personas que trabajan en el panel. Paso 1: datos. Paso 2: permisos exactos.</p>
                </div>
                <a href="<?= site_url('games'); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-duotone fa-solid fa-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="admin-user-steps mb-4">
                <button type="button" class="admin-user-step is-active" data-step-btn="1" id="step-btn-1">
                    <span class="admin-user-step-num">1</span>
                    <span>Datos del usuario</span>
                </button>
                <div class="admin-user-step-line"></div>
                <button type="button" class="admin-user-step" data-step-btn="2" id="step-btn-2">
                    <span class="admin-user-step-num">2</span>
                    <span>Permisos</span>
                </button>
            </div>

            <?= form_open(site_url('users/userSubmit'), ['enctype' => 'multipart/form-data', 'id' => 'user-page-form']); ?>
                <?= csrf_field() ?>
                <input type="hidden" name="user-id" id="user-id" value="<?= $isUpdate ? (int) $userData['id'] : ''; ?>">
                <input type="hidden" name="user-action" id="user-action" value="<?= $isUpdate ? 'update' : 'add'; ?>">
                <input type="hidden" name="page_mode" value="1">
                <input type="hidden" name="group" value="1">
                <input type="hidden" name="status" id="status-hidden" value="<?= $isUpdate ? (int) ($userData['status'] ?? 1) : 1; ?>">

                <div class="admin-user-step-pane is-active" id="user-step-1">
                    <h6 class="text-dark mb-3">Información personal y cuenta</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="firstname"><?= translate('first name'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="firstname" value="<?= $isUpdate ? esc($userData['firstname']) : ''; ?>" required>
                            <small id="firstname-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="lastname"><?= translate('last name'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="lastname" value="<?= $isUpdate ? esc($userData['lastname']) : ''; ?>" required>
                            <small id="lastname-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="username"><?= translate('username'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="username" id="username" value="<?= $isUpdate ? esc($userData['username']) : ''; ?>" required>
                            <small id="username-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="email"><?= translate('email'); ?></label>
                            <input type="email" class="form-control form-control-lg form-bingo" name="email" id="email" value="<?= $isUpdate ? esc($userData['email']) : ''; ?>" required>
                            <small id="email-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="phone"><?= translate('phone'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="phone" value="<?= $isUpdate ? esc($userData['phone']) : ''; ?>" required>
                            <small id="phone-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="document"><?= translate('document'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="document" id="document" value="<?= $isUpdate ? esc($userData['document']) : ''; ?>" required>
                            <small id="document-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="password">
                                <?= translate('password'); ?>
                                <?= ! $isUpdate ? '<span class="text-danger">*</span>' : '<small class="text-muted">(' . translate('leave empty to keep current') . ')</small>'; ?>
                            </label>
                            <input type="password" class="form-control form-control-lg form-bingo" name="password" id="password" autocomplete="new-password" <?= ! $isUpdate ? 'required' : ''; ?>>
                            <small id="password-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-dark" for="address_line"><?= translate('address'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="address_line" value="<?= $isUpdate ? esc($userData['address_line'] ?? '') : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="city"><?= translate('city'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="city" id="city" value="<?= $isUpdate ? esc($userData['city'] ?? '') : ''; ?>">
                        </div>

                        <div class="col-12 mt-2"><hr class="my-2"><h6 class="text-dark mb-2">Datos bancarios (opcional)</h6></div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="bank"><?= translate('bank'); ?></label>
                            <select class="form-control form-control-lg form-bingo" name="bank" id="bank">
                                <option value=""><?= translate('select a'); ?> <?= strtolower(translate('bank')); ?></option>
                                <?php
                                $banks = ['BANCO PICHINCHA','BANCO GUAYAQUIL','BANCO DEL PACIFICO','BANCO DEL AUSTRO','COOP. JEP','COOP. JARDIN AZUAYO','COOP. POLICIA NACIONAL','COOP. ALIANZA DEL VALLE','COOP. COOPERCO','COOP. MUSHUC RUNA'];
                                foreach ($banks as $b) :
                                ?>
                                    <option value="<?= esc($b); ?>" <?= $isUpdate && ($userData['bank'] ?? '') === $b ? 'selected' : ''; ?>><?= esc($b); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="account"><?= translate('account'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="account" id="account" value="<?= $isUpdate ? esc($userData['account'] ?? '') : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="account_type"><?= translate('account type'); ?></label>
                            <?php $accountType = bingo_normalize_account_type($isUpdate ? ($userData['account_type'] ?? '') : ''); ?>
                            <select class="form-control form-control-lg form-bingo" name="account_type" id="account_type">
                                <option value=""><?= translate('select a'); ?> <?= strtolower(translate('account type')); ?></option>
                                <option value="savings" <?= $accountType === 'savings' ? 'selected' : ''; ?>><?= translate('savings account'); ?></option>
                                <option value="checking" <?= $accountType === 'checking' ? 'selected' : ''; ?>><?= translate('checking account'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="status"><?= translate('status'); ?></label>
                            <select class="form-control form-control-lg form-bingo" id="status" onchange="document.getElementById('status-hidden').value=this.value;">
                                <option value="1" <?= ! $isUpdate || (int) ($userData['status'] ?? 1) === 1 ? 'selected' : ''; ?>><?= translate('active'); ?></option>
                                <option value="0" <?= $isUpdate && (int) ($userData['status'] ?? 1) === 0 ? 'selected' : ''; ?>><?= translate('banned'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-primary btn-lg px-4" id="btn-user-next-step">
                            Siguiente: Permisos <i class="fa-duotone fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <div class="admin-user-step-pane" id="user-step-2">
                    <h6 class="text-dark mb-2">Selecciona qué puede hacer este staff</h6>
                    <p class="small text-muted mb-3">
                        Puedes marcar permisos uno a uno, o usar un atajo (Soporte, Finanzas, Operaciones) y luego ajustar.
                    </p>

                    <div class="admin-perm-presets mb-3">
                        <?php foreach ($presetsJson as $preset) : ?>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary admin-perm-preset-btn"
                                data-preset="<?= esc($preset['slug']); ?>"
                                title="<?= esc($preset['description']); ?>"
                            >
                                <?= esc($preset['name']); ?>
                            </button>
                        <?php endforeach; ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-clear-perms">Limpiar</button>
                        <span class="badge bg-dark ms-1" id="selected-perms-count">0</span>
                    </div>
                    <small id="permission_keys-error" class="text-danger d-none d-block mb-2"></small>

                    <div class="admin-perm-grid">
                        <?php foreach ($permsByModule as $module => $items) : ?>
                            <div class="admin-perm-module">
                                <div class="admin-perm-module-head">
                                    <strong><?= esc($module); ?></strong>
                                    <button type="button" class="btn btn-link btn-sm p-0 admin-perm-toggle-module" data-module="<?= esc($module); ?>">Todos</button>
                                </div>
                                <ul class="admin-perm-list">
                                    <?php foreach ($items as $perm) :
                                        $key = (string) ($perm['key'] ?? '');
                                        $checked = isset($selectedMap[$key]);
                                        $isSensitive = ! empty($perm['sensitive']);
                                        ?>
                                        <li>
                                            <label class="admin-perm-item<?= $isSensitive ? ' is-sensitive' : ''; ?>">
                                                <input type="checkbox" class="form-check-input perm-checkbox" name="permission_keys[]" value="<?= esc($key); ?>" data-module="<?= esc($module); ?>" <?= $checked ? 'checked' : ''; ?>>
                                                <span>
                                                    <?= esc($perm['name'] ?? $key); ?>
                                                    <?php if ($isSensitive) : ?>
                                                        <em class="text-danger">sensible</em>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between gap-2 mt-4 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-lg" id="btn-user-prev-step">
                            <i class="fa-duotone fa-solid fa-arrow-left me-1"></i> Anterior
                        </button>
                        <button type="submit" class="btn btn-success btn-lg px-4" id="btn-user-save">
                            <i class="fa-duotone fa-solid fa-floppy-disk me-1"></i>
                            <?= $isUpdate ? 'Guardar staff' : 'Crear staff'; ?>
                        </button>
                    </div>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
</div>

<script>
(function () {
    var presets = <?= json_encode($presetsJson, JSON_UNESCAPED_UNICODE); ?>;
    var form = document.getElementById('user-page-form');
    if (!form) return;

    function goStep(step) {
        document.querySelectorAll('.admin-user-step-pane').forEach(function (el) {
            el.classList.toggle('is-active', el.id === 'user-step-' + step);
        });
        document.querySelectorAll('[data-step-btn]').forEach(function (btn) {
            btn.classList.toggle('is-active', String(btn.getAttribute('data-step-btn')) === String(step));
        });
        var scroller = document.querySelector('.admin-user-form-scroll');
        if (scroller) scroller.scrollTop = 0;
    }

    function updatePermCount() {
        var n = form.querySelectorAll('.perm-checkbox:checked').length;
        var badge = document.getElementById('selected-perms-count');
        if (badge) badge.textContent = String(n);
    }

    function setPermissions(keys) {
        var map = {};
        (keys || []).forEach(function (k) { map[String(k)] = true; });
        form.querySelectorAll('.perm-checkbox').forEach(function (cb) {
            cb.checked = !!map[cb.value];
        });
        updatePermCount();
    }

    function validateStep1() {
        var requiredIds = ['firstname', 'lastname', 'username', 'email', 'phone', 'document'];
        var ok = true;
        requiredIds.forEach(function (id) {
            var el = document.getElementById(id);
            var err = document.getElementById(id + '-error');
            if (!el) return;
            if (!String(el.value || '').trim()) {
                ok = false;
                if (err) {
                    err.textContent = 'Campo requerido';
                    err.classList.remove('d-none');
                }
                el.classList.add('is-invalid');
            } else {
                if (err) err.classList.add('d-none');
                el.classList.remove('is-invalid');
            }
        });
        var pass = document.getElementById('password');
        var action = document.getElementById('user-action');
        if (pass && action && action.value === 'add' && !String(pass.value || '').trim()) {
            ok = false;
            var perr = document.getElementById('password-error');
            if (perr) {
                perr.textContent = 'Contraseña requerida';
                perr.classList.remove('d-none');
            }
            pass.classList.add('is-invalid');
        }
        return ok;
    }

    document.getElementById('btn-user-next-step')?.addEventListener('click', function () {
        if (!validateStep1()) {
            Toastify({ text: 'Completa los datos requeridos del paso 1.', duration: 2800, gravity: 'top', position: 'right', style: { background: '#dc3545' } }).showToast();
            return;
        }
        goStep(2);
    });
    document.getElementById('btn-user-prev-step')?.addEventListener('click', function () { goStep(1); });
    document.getElementById('step-btn-1')?.addEventListener('click', function () { goStep(1); });
    document.getElementById('step-btn-2')?.addEventListener('click', function () {
        if (validateStep1()) goStep(2);
    });

    function markPresetActive(slug) {
        document.querySelectorAll('.admin-perm-preset-btn').forEach(function (b) {
            b.classList.toggle('is-active', slug && b.getAttribute('data-preset') === slug);
        });
    }
    document.querySelectorAll('.admin-perm-preset-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var slug = btn.getAttribute('data-preset');
            var preset = presets.find(function (p) { return p.slug === slug; });
            if (preset) {
                setPermissions(preset.permissions || []);
                markPresetActive(slug);
            }
        });
    });
    document.getElementById('btn-clear-perms')?.addEventListener('click', function () {
        setPermissions([]);
        markPresetActive(null);
    });
    form.querySelectorAll('.perm-checkbox').forEach(function (cb) {
        cb.addEventListener('change', updatePermCount);
    });
    document.querySelectorAll('.admin-perm-toggle-module').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mod = btn.getAttribute('data-module');
            var boxes = form.querySelectorAll('.perm-checkbox[data-module="' + mod + '"]');
            var allChecked = Array.prototype.every.call(boxes, function (b) { return b.checked; });
            boxes.forEach(function (b) { b.checked = !allChecked; });
            updatePermCount();
        });
    });
    updatePermCount();

    $(form).on('submit', function (e) {
        e.preventDefault();
        if (!validateStep1()) {
            goStep(1);
            return;
        }
        var checked = form.querySelectorAll('.perm-checkbox:checked').length;
        if (checked < 1) {
            goStep(2);
            $('#permission_keys-error').text('Selecciona al menos un permiso.').removeClass('d-none');
            return;
        }
        $('#permission_keys-error').addClass('d-none');

        var $btn = $('#btn-user-save');
        $btn.prop('disabled', true);
        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Toastify({ text: response.message || 'Usuario guardado', duration: 2500, gravity: 'top', position: 'right', style: { background: '#198754' } }).showToast();
                    setTimeout(function () {
                        window.location.href = (typeof site_url !== 'undefined' ? site_url : '/') + 'games';
                    }, 700);
                } else {
                    if (response.errors && response.errors.permission_keys) {
                        goStep(2);
                        $('#permission_keys-error').text(response.errors.permission_keys).removeClass('d-none');
                    } else {
                        goStep(1);
                        if (response.errors) {
                            $.each(response.errors, function (field, message) {
                                $('#' + field + '-error').text(message).removeClass('d-none');
                                $('#' + field).addClass('is-invalid');
                            });
                        }
                    }
                    Toastify({ text: response.message || 'Revisa el formulario.', duration: 3000, gravity: 'top', position: 'right', style: { background: '#dc3545' } }).showToast();
                }
            },
            error: function () {
                Toastify({ text: 'Error de servidor al guardar.', duration: 3000, gravity: 'top', position: 'right', style: { background: '#dc3545' } }).showToast();
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
})();
</script>
