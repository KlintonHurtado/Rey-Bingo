
<?= view('games/partials/admin_nav_cluster', [
    'imagePath' => $imagePath,
    'activeNav' => '',
    'showStatistics' => true,
    'showUsers' => true,
]) ?>

<?php if ((int) (session()->get('group') ?? 0) !== 1): ?>
<button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
    <?php if ($user['sounds'] == 1): ?>
        <i class="fa-duotone fa-solid fa-volume"></i>
    <?php else : ?>
        <i class="fa-duotone fa-solid fa-volume-slash"></i>
    <?php endif; ?>
</button>
<?php endif; ?>

<a class="btn btn-small btn-lock hidden" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>

<button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<button class="btn btn-small btn-game" onclick="awardsGameGet();"><i class="fa-duotone fa-solid fa-trophy-star"></i></button>

<?php if (function_exists('bingo_can') && bingo_can('settings.manage')) : ?>
<button class="btn btn-small btn-gear" onclick="settingsGet();"><i class="fa-duotone fa-solid fa-gear"></i></button>
<?php endif; ?>

<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">
            <div class="card mb-3 admin-games-filters-card">
                <div class="collapse show">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-end mb-2">
                            <?php if (! function_exists('bingo_can') || bingo_can('games.manage')) : ?>
                            <button type="button" class="btn btn-small btn-primary btn-modal-add text-white btn-add-new" onclick="gameAdd();"><i class="fa-duotone fa-solid fa-plus"></i></button>
                            <?php endif; ?>
                        </div>
                        <div class="row g-2">
                            <?php $today = date('Y-m-d'); ?>
                            <div class="col-md-4">
                                <label for="datefilter" class="form-label"><?= translate('date'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="datefilter" id="datefilter" onchange="gameslistGet();">
                                    <option value="all"><?= translate('all dates') ?></option>
                                    <?php if (!empty($dates)) : ?>
                                        <?php foreach ($dates as $date): ?>
                                            <option value="<?= esc($date) ?>" <?= ($date === $today) ? 'selected' : '' ?>>
                                                <?= esc(translate_date($date)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <option value="0"><?= translate('there are no active games'); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="roomfilter" class="form-label"><?= translate('room'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="roomfilter" id="roomfilter">
                                    <option value="all"><?= translate('all rooms') ?></option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?= esc($room['id']) ?>"><?= esc($room['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="statusfilter" class="form-label"><?= translate('status'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="statusfilter" id="statusfilter">
                                    <?php foreach ($status as $key => $statu): ?>
                                        <option value="<?= esc($key) ?>"><?= esc($statu) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive" id="games-list"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        gameslistGet();
    });

    $('#datefilter, #roomfilter, #statusfilter').on('change', function() {
        gameslistGet();
    });

    function gameslistGet(page = 1) {
        var date = $('#datefilter').val();
        var room = $('#roomfilter').val();
        var status = $('#statusfilter').val();

        // Mostrar loading
        $("#games-list").html('<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?= translate('loading'); ?>...</span></div><span class="ms-2"><?= translate('loading data'); ?>...</span></div>');

        $.ajax({
            url: '<?= site_url('games/gameslistGet') ?>/' + date + '/' + room + '/' + status + '/' + page,
            type: "GET",
            success: function(data) {  
                $("#games-list").html(data);
            },
            error: function () {
                Toastify({
                    text: '<?= translate('there was an error in the request to the server.'); ?>',
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();

                $("#games-list").html('');
            }
        });
    }

    // Modificar los event listeners para resetear a la página 1 cuando cambien los filtros
    $('#datefilter, #roomfilter, #statusfilter').on('change', function() {
        gameslistGet(1); // Siempre ir a la página 1 cuando cambien los filtros
    });

    function updateGame(gameId) {
        $("#modalAddgame").load(site_url + 'games/add/' + gameId, function() {
            $('#modalAddgame').modal('show');
        });
    }

    function deleteGame(gameId) {
        if (gameId != "") {
            Swal.fire({
                title: '<?= translate('do you want to continue?'); ?>',
                text: '<?= translate('this action will delete the selected data and it cannot be recovered again.'); ?>',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: '<?= translate('yes, delete!'); ?>',
                cancelButtonText: '<?= translate('cancel'); ?>',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-danger'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= site_url('games/deleteGame') ?>',
                        method: 'POST',
                        data: {
                            game_id: gameId,
                            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                
                                gameslistGet();

                                Toastify({
                                    text: response.message,
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#198754" },
                                    stopOnFocus: true
                                }).showToast();
                            } else {
                                Toastify({
                                    text: response.error,
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#dc3545" },
                                    stopOnFocus: true
                                }).showToast();
                            }
                        },
                        error: function() {
                            Toastify({
                                text: "<?= translate('there was an error in the request to the server'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
                        }
                    });
                }
            });
        }
    }

    let editingRow = null;

    function editRow(button) {
        let row = button.closest("tr");
        editingRow = row;

        let modalityId = row.querySelector("select[name='modality[]']").value;
        let amountVal = row.querySelector("input[name='amount[]']").value;
        let observationVal = row.querySelector("input[name='observation[]']").value;

        $("#modal-modality").val(modalityId);
        $("#modal-amount").val(amountVal);
        $("#modal-observation").val(observationVal);

        $("#modalAddmodality").modal("show");
    }

    function manageGameCode(gameId, currentCode) {
        if (!gameId) return;
        var hasCode = currentCode && String(currentCode).trim() !== '';
        var safeCode = hasCode ? String(currentCode).replace(/"/g, '&quot;') : '';

        var htmlContent = `
            <div class="text-start p-2">
                <p class="text-muted small mb-3">
                    Al asignar un <strong>código de acceso</strong> a esta partida, será obligatorio que los jugadores lo ingresen antes de comprar cartones (con saldo real o bono) o ingresar a jugar.
                </p>
                <label class="form-label fw-bold small text-dark mb-1">Clave / Código de acceso:</label>
                <div class="input-group mb-2">
                    <span class="input-group-text bg-light border"><i class="fa-solid fa-key text-warning"></i></span>
                    <input type="text" id="swal-game-access-code" class="form-control form-control-lg fw-bold" placeholder="EJ: VIP2026, SALA1" value="${safeCode}" maxlength="50" style="text-transform: uppercase; letter-spacing: 1.5px;" autofocus>
                </div>
                <small class="text-muted d-block" style="font-size: 0.8rem;">
                    * Para que la partida vuelva a ser <strong>pública</strong>, borra el código y guarda, o presiona "Hacer Pública".
                </small>
            </div>
        `;

        Swal.fire({
            title: hasCode ? '🔒 Partida Protegida' : '🔑 Asignar Código de Acceso',
            html: htmlContent,
            icon: hasCode ? 'info' : 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Código',
            cancelButtonText: 'Cancelar',
            showDenyButton: hasCode,
            denyButtonText: '<i class="fa-solid fa-lock-open me-1"></i> Quitar Código (Hacer Pública)',
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-secondary',
                denyButton: 'btn btn-danger me-2'
            },
            buttonsStyling: false,
            didOpen: () => {
                const input = document.getElementById('swal-game-access-code');
                if (input) {
                    setTimeout(() => input.focus(), 150);
                    input.addEventListener('keyup', function(e) {
                        if (e.key === 'Enter') {
                            Swal.clickConfirm();
                        }
                    });
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var codeVal = ($('#swal-game-access-code').val() || '').trim();
                saveGameAccessCode(gameId, codeVal);
            } else if (result.isDenied) {
                saveGameAccessCode(gameId, '');
            }
        });
    }

    function saveGameAccessCode(gameId, code) {
        $.ajax({
            url: '<?= site_url('games/setAccessCode') ?>',
            method: 'POST',
            data: {
                game_id: gameId,
                access_code: code,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Toastify({
                        text: res.message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#198754" },
                        stopOnFocus: true
                    }).showToast();
                    if (typeof gameslistGet === 'function') {
                        gameslistGet();
                    } else if (typeof gameslistGetPage === 'function') {
                        gameslistGetPage(1);
                    }
                } else {
                    Toastify({
                        text: res.message || 'Error al guardar el código',
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                }
            },
            error: function() {
                Toastify({
                    text: 'Error de conexión con el servidor',
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            }
        });
    }

    window.manageGameCode = manageGameCode;
    window.saveGameAccessCode = saveGameAccessCode;
</script>