
<a class="btn btn-small btn-profile" href="<?= site_url('profile'); ?>"><img src="<?= $imagePath ?>" alt="img"></a>

<button type="button" class="btn btn-small btn-wallet btn-wallet-profile" onclick="paymentsGet();">
    <i class="fa-duotone fa-solid fa-wallet"></i>
</button>

<button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
    <?php if ($user['sounds'] == 1): ?>
        <i class="fa-duotone fa-solid fa-volume"></i>
    <?php else : ?>
        <i class="fa-duotone fa-solid fa-volume-slash"></i>
    <?php endif; ?>
</button>

<a class="btn btn-small btn-lock hidden" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>

<button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<button class="btn btn-small btn-statistics" onclick="statisticsView();"><i class="fa-duotone fa-chart-column"></i></button>

<button class="btn btn-small btn-game" onclick="awardsGameGet();"><i class="fa-duotone fa-solid fa-trophy-star"></i></button>

<button class="btn btn-small btn-gear" onclick="settingsGet();"><i class="fa-duotone fa-solid fa-gear"></i></button>

<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">
            <div class="card mb-3 admin-games-filters-card">
                <div class="collapse show">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-small btn-primary btn-modal-add text-white btn-add-new" onclick="gameAdd();"><i class="fa-duotone fa-solid fa-plus"></i></button>
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
</script>