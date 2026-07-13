<?php
$fields = $exportFields ?? [];
$defaultFields = array_keys($fields);
?>

<div class="modal-dialog modal-dialog-centered modal-lg user-export-modal">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2">
                <i class="fa-duotone fa-solid fa-file-arrow-down"></i> Exportar usuarios a Excel
            </h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal">
                <i class="fa-duotone fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-body pt-0">
            <form id="user-export-form" method="post" action="<?= site_url('users/exportRequiredFields'); ?>" target="_blank">
                <?= csrf_field() ?>

                <div class="card mb-3">
                    <div class="card-body p-3">
                        <h6 class="mb-2"><i class="fa-duotone fa-solid fa-users"></i> ¿Qué usuarios exportar?</h6>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="export_scope" id="export-scope-filtered" value="filtered" checked>
                            <label class="form-check-label" for="export-scope-filtered">
                                Según filtros actuales de la lista
                                <small class="text-muted d-block">Usa la búsqueda, estado y grupo que tienes en la tabla.</small>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="export_scope" id="export-scope-all" value="all">
                            <label class="form-check-label" for="export-scope-all">
                                Todos los usuarios
                                <small class="text-muted d-block">Incluye jugadores, admins y Puntos de venta activos en el sistema.</small>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="export_scope" id="export-scope-selected" value="selected">
                            <label class="form-check-label" for="export-scope-selected">
                                Usuario(s) específico(s)
                                <small class="text-muted d-block">Busca y elige uno o varios usuarios.</small>
                            </label>
                        </div>

                        <div id="export-selected-users-panel" class="mt-3 d-none">
                            <label for="export-user-search" class="form-label small">Buscar usuario</label>
                            <input type="text" class="form-control form-bingo" id="export-user-search" placeholder="Nombre, usuario, email, teléfono o documento..." autocomplete="off">
                            <div id="export-user-search-results" class="list-group mt-2 d-none"></div>
                            <div id="export-selected-users" class="d-flex flex-wrap gap-2 mt-2"></div>
                            <small id="export-selected-users-empty" class="text-muted">No has seleccionado usuarios.</small>
                        </div>

                        <input type="hidden" name="search" id="export-filter-search" value="">
                        <input type="hidden" name="status" id="export-filter-status" value="">
                        <input type="hidden" name="group" id="export-filter-group" value="">
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fa-duotone fa-solid fa-table-columns"></i> Campos a incluir</h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-light" id="export-fields-select-all">Todos</button>
                                <button type="button" class="btn btn-outline-light" id="export-fields-clear-all">Ninguno</button>
                            </div>
                        </div>

                        <div class="row g-2">
                            <?php foreach ($fields as $key => $field) : ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input export-field-checkbox"
                                            type="checkbox"
                                            name="fields[]"
                                            id="export-field-<?= esc($key); ?>"
                                            value="<?= esc($key); ?>"
                                            checked
                                        >
                                        <label class="form-check-label" for="export-field-<?= esc($key); ?>">
                                            <?= esc($field['label']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer user-export-modal-footer">
            <button type="button" class="btn btn-danger btn-bingo px-4" data-bs-dismiss="modal">
                <?= translate('cancel'); ?>
            </button>
            <button type="button" class="btn btn-primary btn-bingo px-4" id="user-export-submit-btn">
                <i class="fa-duotone fa-solid fa-file-arrow-down"></i> Sí, exportar
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const selectedUsers = new Map();

    function toggleSelectedPanel() {
        const isSelected = document.getElementById('export-scope-selected').checked;
        document.getElementById('export-selected-users-panel').classList.toggle('d-none', !isSelected);
    }

    document.querySelectorAll('input[name="export_scope"]').forEach(function(input) {
        input.addEventListener('change', toggleSelectedPanel);
    });

    document.getElementById('export-fields-select-all').addEventListener('click', function() {
        document.querySelectorAll('.export-field-checkbox').forEach(function(cb) {
            cb.checked = true;
        });
    });

    document.getElementById('export-fields-clear-all').addEventListener('click', function() {
        document.querySelectorAll('.export-field-checkbox').forEach(function(cb) {
            cb.checked = false;
        });
    });

    function renderSelectedUsers() {
        const container = document.getElementById('export-selected-users');
        const emptyHint = document.getElementById('export-selected-users-empty');
        container.innerHTML = '';

        selectedUsers.forEach(function(user, id) {
            const chip = document.createElement('span');
            chip.className = 'badge bg-primary p-2';
            chip.innerHTML = user.label + ' <button type="button" class="btn-close btn-close-white btn-sm ms-1" aria-label="Quitar"></button>';
            chip.querySelector('button').addEventListener('click', function() {
                selectedUsers.delete(id);
                renderSelectedUsers();
            });

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'user_ids[]';
            hidden.value = id;
            chip.appendChild(hidden);

            container.appendChild(chip);
        });

        emptyHint.classList.toggle('d-none', selectedUsers.size > 0);
    }

    let searchTimer = null;
    const searchInput = document.getElementById('export-user-search');
    const resultsBox = document.getElementById('export-user-search-results');

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
            return;
        }

        searchTimer = setTimeout(function() {
            $.get('<?= site_url('users/searchUsersForExport'); ?>', { q: query }, function(response) {
                resultsBox.innerHTML = '';

                if (!response.success || !response.users.length) {
                    resultsBox.innerHTML = '<div class="list-group-item text-muted">Sin resultados</div>';
                    resultsBox.classList.remove('d-none');
                    return;
                }

                response.users.forEach(function(user) {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = user.label;
                    item.addEventListener('click', function() {
                        selectedUsers.set(String(user.id), user);
                        renderSelectedUsers();
                        searchInput.value = '';
                        resultsBox.classList.add('d-none');
                        resultsBox.innerHTML = '';
                    });
                    resultsBox.appendChild(item);
                });

                resultsBox.classList.remove('d-none');
            }, 'json');
        }, 250);
    });

    document.getElementById('user-export-submit-btn').addEventListener('click', function() {
        const checkedFields = document.querySelectorAll('.export-field-checkbox:checked');
        if (!checkedFields.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona campos',
                text: 'Debes elegir al menos un campo para exportar.',
                showCancelButton: true,
                confirmButtonText: '<?= translate('yes'); ?>',
                cancelButtonText: '<?= translate('cancel'); ?>',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-danger'
                }
            });
            return;
        }

        const scope = document.querySelector('input[name="export_scope"]:checked')?.value || 'filtered';
        if (scope === 'selected' && selectedUsers.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona usuarios',
                text: 'Elige al menos un usuario o cambia el alcance de la exportación.',
                showCancelButton: true,
                confirmButtonText: '<?= translate('yes'); ?>',
                cancelButtonText: '<?= translate('cancel'); ?>',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-danger'
                }
            });
            return;
        }

        document.getElementById('export-filter-search').value = $('#searchUsers').length ? ($('#searchUsers').val() || '') : '';
        document.getElementById('export-filter-status').value = $('#statusFilter').length ? ($('#statusFilter').val() || 'all') : 'all';
        document.getElementById('export-filter-group').value = $('#groupFilter').length ? ($('#groupFilter').val() || 'all') : 'all';

        document.getElementById('user-export-form').submit();
    });
})();
</script>
