<?php
$isUpdate = ! empty($isUpdate);
$userData = $userData ?? null;
$adminRoles = $adminRoles ?? [];
$canManageAdmins = ! empty($canManageAdmins);
$showAdminOption = $canManageAdmins || ($isUpdate && (int) ($userData['group'] ?? 0) === 1);
$currentGroup = $isUpdate ? (int) ($userData['group'] ?? 0) : 0;
$currentRoleId = $isUpdate ? (int) ($userData['admin_role_id'] ?? 0) : 0;
$rolesJson = [];
foreach ($adminRoles as $role) {
    $rid = (int) ($role['id'] ?? 0);
    $isSuper = (int) ($role['is_superadmin'] ?? 0) === 1;
    if ($isSuper && ! $canManageAdmins && (int) session()->get('admin_is_superadmin') !== 1) {
        continue;
    }
    $rolesJson[] = [
        'id' => $rid,
        'name' => (string) ($role['name'] ?? ''),
        'description' => (string) ($role['description'] ?? ''),
        'is_superadmin' => $isSuper ? 1 : 0,
        'permissions' => $role['permissions'] ?? [],
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
                        <?= $isUpdate ? translate('update user') : 'Crear usuario'; ?>
                    </h4>
                    <p class="text-muted small mb-0">Completa los datos y luego elige el grupo y permisos.</p>
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
                    <span>Grupo y permisos</span>
                </button>
            </div>

            <?= form_open(site_url('users/userSubmit'), ['enctype' => 'multipart/form-data', 'id' => 'user-page-form']); ?>
                <?= csrf_field() ?>
                <input type="hidden" name="user-id" id="user-id" value="<?= $isUpdate ? (int) $userData['id'] : ''; ?>">
                <input type="hidden" name="user-action" id="user-action" value="<?= $isUpdate ? 'update' : 'add'; ?>">
                <input type="hidden" name="page_mode" value="1">

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
                            <label class="form-label text-dark" for="document_expires_at"><?= translate('document expiry'); ?></label>
                            <input type="date" class="form-control form-control-lg form-bingo" name="document_expires_at" id="document_expires_at" value="<?= $isUpdate ? esc($userData['document_expires_at'] ?? '') : ''; ?>">
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
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="state"><?= translate('state'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="state" id="state" value="<?= $isUpdate ? esc($userData['state'] ?? '') : ''; ?>">
                        </div>

                        <div class="col-12 mt-2"><hr class="my-2"><h6 class="text-dark mb-2">Saldos iniciales (opcional)</h6></div>
                        <div class="col-md-4">
                            <label class="form-label text-dark" for="wallet_bonus"><?= translate('bonus balance'); ?></label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="wallet_bonus" id="wallet_bonus" value="<?= $isUpdate ? number_format((float) ($userData['wallet_bonus'] ?? 0), 2, '.', '') : '0.00'; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-dark" for="wallet_recharge"><?= translate('recharge balance'); ?></label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="wallet_recharge" id="wallet_recharge" value="<?= $isUpdate ? number_format((float) ($userData['wallet_recharge'] ?? 0), 2, '.', '') : '0.00'; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-dark" for="wallet_withdraw"><?= translate('withdraw balance'); ?></label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="wallet_withdraw" id="wallet_withdraw" value="<?= $isUpdate ? number_format((float) ($userData['wallet_withdraw'] ?? 0), 2, '.', '') : '0.00'; ?>">
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
                            <select class="form-control form-control-lg form-bingo" name="status" id="status">
                                <option value="1" <?= ! $isUpdate || (int) ($userData['status'] ?? 1) === 1 ? 'selected' : ''; ?>><?= translate('active'); ?></option>
                                <option value="0" <?= $isUpdate && (int) ($userData['status'] ?? 1) === 0 ? 'selected' : ''; ?>><?= translate('banned'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-primary btn-lg px-4" id="btn-user-next-step">
                            Siguiente: Grupo y permisos <i class="fa-duotone fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <div class="admin-user-step-pane" id="user-step-2">
                    <h6 class="text-dark mb-3">Grupo de acceso y permisos</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="group"><?= translate('group'); ?></label>
                            <select class="form-control form-control-lg form-bingo" name="group" id="group">
                                <option value="0" <?= $currentGroup === 0 ? 'selected' : ''; ?>><?= translate('player'); ?></option>
                                <?php if ($showAdminOption) : ?>
                                    <option value="1" <?= $currentGroup === 1 ? 'selected' : ''; ?>><?= translate('admin'); ?> / Staff</option>
                                <?php endif; ?>
                                <option value="2" <?= $currentGroup === 2 ? 'selected' : ''; ?>><?= translate('point of sale'); ?></option>
                                <option value="3" <?= $currentGroup === 3 ? 'selected' : ''; ?>><?= translate('operator'); ?></option>
                            </select>
                            <small class="text-muted">El grupo define el panel al que ingresa el usuario.</small>
                        </div>
                        <div class="col-md-6" id="admin-role-wrap" style="<?= $currentGroup === 1 ? '' : 'display:none;'; ?>">
                            <label class="form-label text-dark" for="admin_role_id">Rol de permisos <span class="text-danger">*</span></label>
                            <select class="form-control form-control-lg form-bingo" name="admin_role_id" id="admin_role_id">
                                <option value="">Selecciona un rol</option>
                                <?php foreach ($rolesJson as $role) : ?>
                                    <option value="<?= (int) $role['id']; ?>" <?= $currentRoleId === (int) $role['id'] ? 'selected' : ''; ?>>
                                        <?= esc($role['name']); ?><?= ! empty($role['is_superadmin']) ? ' (acceso total)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="admin_role_id-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark" for="is_reseller"><?= translate('point of sale'); ?> (flag)</label>
                            <select class="form-control form-control-lg form-bingo" name="is_reseller" id="is_reseller">
                                <option value="0" <?= ! $isUpdate || (int) ($userData['is_reseller'] ?? 0) === 0 ? 'selected' : ''; ?>><?= translate('no'); ?></option>
                                <option value="1" <?= $isUpdate && (int) ($userData['is_reseller'] ?? 0) === 1 ? 'selected' : ''; ?>><?= translate('yes'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div id="role-permissions-box" class="admin-role-permissions mt-3" style="<?= $currentGroup === 1 ? '' : 'display:none;'; ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <strong class="text-dark" id="role-permissions-title">Permisos del rol</strong>
                            <span class="badge bg-primary" id="role-permissions-count">0</span>
                        </div>
                        <p class="small text-muted mb-2" id="role-permissions-desc"></p>
                        <div class="admin-role-permissions-grid" id="role-permissions-grid">
                            <div class="text-muted small">Selecciona un rol Admin / Staff para ver sus permisos.</div>
                        </div>
                    </div>

                    <div id="non-admin-group-hint" class="alert alert-light border mt-3 mb-0" style="<?= $currentGroup === 1 ? 'display:none;' : ''; ?>">
                        <i class="fa-duotone fa-solid fa-circle-info me-1 text-primary"></i>
                        Este grupo usa su propio panel (jugador, punto de venta u operador). No requiere rol de permisos Admin.
                    </div>

                    <div class="d-flex justify-content-between gap-2 mt-4 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-lg" id="btn-user-prev-step">
                            <i class="fa-duotone fa-solid fa-arrow-left me-1"></i> Anterior
                        </button>
                        <button type="submit" class="btn btn-success btn-lg px-4" id="btn-user-save">
                            <i class="fa-duotone fa-solid fa-floppy-disk me-1"></i>
                            <?= $isUpdate ? translate('update') : 'Crear usuario'; ?>
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
    var roles = <?= json_encode($rolesJson, JSON_UNESCAPED_UNICODE); ?>;
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

    function renderRolePermissions() {
        var group = document.getElementById('group');
        var roleSelect = document.getElementById('admin_role_id');
        var wrap = document.getElementById('admin-role-wrap');
        var box = document.getElementById('role-permissions-box');
        var hint = document.getElementById('non-admin-group-hint');
        var grid = document.getElementById('role-permissions-grid');
        var title = document.getElementById('role-permissions-title');
        var count = document.getElementById('role-permissions-count');
        var desc = document.getElementById('role-permissions-desc');
        if (!group) return;

        var isAdmin = String(group.value) === '1';
        if (wrap) wrap.style.display = isAdmin ? '' : 'none';
        if (box) box.style.display = isAdmin ? '' : 'none';
        if (hint) hint.style.display = isAdmin ? 'none' : '';
        if (!isAdmin) {
            if (roleSelect) roleSelect.value = '';
            return;
        }

        var roleId = parseInt(roleSelect && roleSelect.value ? roleSelect.value : '0', 10) || 0;
        var role = roles.find(function (r) { return Number(r.id) === roleId; });
        if (!role) {
            if (title) title.textContent = 'Permisos del rol';
            if (count) count.textContent = '0';
            if (desc) desc.textContent = 'Selecciona un rol para ver exactamente qué podrá hacer.';
            if (grid) grid.innerHTML = '<div class="text-muted small">Selecciona un rol Admin / Staff para ver sus permisos.</div>';
            return;
        }

        if (title) title.textContent = 'Permisos: ' + role.name;
        if (desc) desc.textContent = role.description || (role.is_superadmin ? 'Acceso total al panel Admin.' : '');
        var perms = role.permissions || [];
        if (count) count.textContent = String(perms.length);
        if (!grid) return;

        if (!perms.length) {
            grid.innerHTML = '<div class="text-muted small">Este rol no tiene permisos asignados.</div>';
            return;
        }

        var byModule = {};
        perms.forEach(function (p) {
            var mod = p.module || 'General';
            if (!byModule[mod]) byModule[mod] = [];
            byModule[mod].push(p);
        });

        var html = '';
        Object.keys(byModule).forEach(function (mod) {
            html += '<div class="admin-role-module"><div class="admin-role-module-title">' + mod + '</div><ul>';
            byModule[mod].forEach(function (p) {
                html += '<li><i class="fa-duotone fa-solid fa-check text-success me-1"></i>' + (p.name || p.key) + '</li>';
            });
            html += '</ul></div>';
        });
        grid.innerHTML = html;
    }

    document.getElementById('btn-user-next-step')?.addEventListener('click', function () {
        if (!validateStep1()) {
            Toastify({
                text: 'Completa los datos requeridos del paso 1.',
                duration: 2800,
                gravity: 'top',
                position: 'right',
                style: { background: '#dc3545' }
            }).showToast();
            return;
        }
        goStep(2);
        renderRolePermissions();
    });
    document.getElementById('btn-user-prev-step')?.addEventListener('click', function () { goStep(1); });
    document.getElementById('group')?.addEventListener('change', renderRolePermissions);
    document.getElementById('admin_role_id')?.addEventListener('change', renderRolePermissions);
    document.getElementById('step-btn-1')?.addEventListener('click', function () { goStep(1); });
    document.getElementById('step-btn-2')?.addEventListener('click', function () {
        if (validateStep1()) {
            goStep(2);
            renderRolePermissions();
        }
    });

    renderRolePermissions();

    $(form).on('submit', function (e) {
        e.preventDefault();
        if (!validateStep1()) {
            goStep(1);
            return;
        }
        var group = $('#group').val();
        if (String(group) === '1' && !$('#admin_role_id').val()) {
            goStep(2);
            $('#admin_role_id-error').text('Debes elegir un rol de permisos.').removeClass('d-none');
            return;
        }
        $('#admin_role_id-error').addClass('d-none');

        var $btn = $('#btn-user-save');
        $btn.prop('disabled', true);
        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Toastify({
                        text: response.message || 'Usuario guardado',
                        duration: 2500,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#198754' }
                    }).showToast();
                    setTimeout(function () {
                        window.location.href = (typeof site_url !== 'undefined' ? site_url : '/') + 'games';
                    }, 700);
                } else {
                    goStep(1);
                    if (response.errors) {
                        $.each(response.errors, function (field, message) {
                            $('#' + field + '-error').text(message).removeClass('d-none');
                            $('#' + field).addClass('is-invalid');
                        });
                    }
                    Toastify({
                        text: response.message || 'Revisa los datos del formulario.',
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' }
                    }).showToast();
                }
            },
            error: function () {
                Toastify({
                    text: 'Error de servidor al guardar.',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
})();
</script>
