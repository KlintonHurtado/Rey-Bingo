<div class="modal-dialog modal-dialog-centered max-w-80">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-trophy-star"></i> <?= translate('winners'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <!-- Filtros -->
            <div class="card mb-3">
                <div class="collapse show">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label for="roomFilterModal" class="form-label"><?= translate('room'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="roomFilterModal">
                                    <option value="all"><?= translate('all rooms'); ?></option>
                                    <?php if (!empty($rooms)): ?>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?= $room['id']; ?>"><?= esc($room['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="gameFilterModal" class="form-label"><?= translate('game'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="gameFilterModal">
                                    <option value="all"><?= translate('all games'); ?></option>
                                    <?php if (!empty($games)): ?>
                                        <?php foreach ($games as $game): ?>
                                            <option value="<?= $game['id']; ?>"><?= esc($game['description']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="playerFilterModal" class="form-label"><?= translate('user'); ?></label>
                                 <input type="text" class="form-control form-control-lg form-bingo" id="playerFilterModal" placeholder="<?= translate('search users'); ?>...">
                            </div>
                            <div class="col-md-2">
                                <label for="modalityFilterModal" class="form-label"><?= translate('modality'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="modalityFilterModal">
                                    <option value="all"><?= translate('all modalities'); ?></option>
                                    <?php if (!empty($modalities)): ?>
                                        <?php foreach ($modalities as $modality): ?>
                                            <option value="<?= $modality['id']; ?>"><?= translate($modality['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="statusFilterModal" class="form-label"><?= translate('status'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="statusFilterModal">
                                    <option value="all"><?= translate('all status'); ?></option>
                                    <option value="1"><?= translate('pending'); ?></option>
                                    <option value="2"><?= translate('paid'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div id="winners-list">
                        <!-- Contenido de la tabla se cargará aquí -->
                        <?php echo view('playings/winners_table_content', [
                            'sings' => $sings,
                            'showPagination' => $showPagination ?? false,
                            'currentPage' => $currentPage ?? 1,
                            'per_page' => $per_page ?? 10,
                            'totalRecords' => $totalRecords ?? 0,
                            'totalPages' => $totalPages ?? 1
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var currency = '<?= systemGet('currency'); ?>';

    function applyFiltersModal() {
        winnersGetPage(1);
    }

    function winnersGetPage(page) {
        var room = $('#roomFilterModal').val() || 'all';
        var game = $('#gameFilterModal').val() || 'all';
        var player = encodeURIComponent($('#playerFilterModal').val() || 'all');
        var modality = $('#modalityFilterModal').val() || 'all';
        var status = $('#statusFilterModal').val() || 'all';

        // Mostrar loading
        $("#winners-list").html('<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?= translate('loading'); ?>...</span></div><span class="ms-2"><?= translate('loading data'); ?>...</span></div>');

        $.ajax({
            url: '<?= site_url('boards/winnersListGet') ?>/' + room + '/' + game + '/' + player + '/' + modality + '/' + status + '/' + page,
            type: "GET",
            dataType: "html",
            success: function(data) {  
                $("#winners-list").html(data);
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                Toastify({
                    text: '<?= translate('there was an error in the request to the server.'); ?>',
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();

                $("#winners-list").html('<div class="alert alert-danger text-center"><?= translate('error loading data'); ?></div>');
            }
        });
    }

    // Aplicar filtros al presionar Enter en el campo de jugador
    $('#playerFilterModal').on('keypress', function(e) {
        if (e.which == 13) {
            applyFiltersModal();
        }
    });

    // Aplicar filtros al cambiar los selects
    $('#roomFilterModal, #gameFilterModal, #modalityFilterModal, #statusFilterModal').on('change', function() {
        applyFiltersModal();
    });

    function payawardSubmit(id, user, amount, action) {
        if (id != "") {
            Swal.fire({
                title: '<?= translate('do you want to continue?'); ?>',
                text: "<?= translate('with this action you will pay the amount {currency} {amount} to the player {user}.'); ?>" .replace("{currency}", currency)  .replace("{amount}", amount) .replace("{user}", user),
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: '<?= translate('yes, pay!'); ?>',
                cancelButtonText: '<?= translate('cancel'); ?>',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-danger'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const statusElement = document.getElementById(`award-${id}`);
                    if (!statusElement) {
                        console.error(`element with ID award-${id} not found`);
                        return;
                    }

                    fetch('<?= site_url('payments/payawardSubmit') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id, action }),
                    })

                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            switch (action) {
                                case 'pay':
                                    statusElement.innerHTML = '<span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> <?= translate('paid'); ?></span>';

                                    Toastify({
                                        text: "<?= translate('pay send successfully'); ?>",
                                        duration: 3000,
                                        gravity: "top",
                                        position: "right",
                                        style: { background: "#198754" },
                                        stopOnFocus: true
                                    }).showToast();
                                break;
                                case 'earring':
                                    statusElement.innerHTML = '<span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> <?= translate('pending'); ?></span>';
                                    
                                    Toastify({
                                        text: "<?= translate('payment marked as pending successfully'); ?>",
                                        duration: 3000,
                                        gravity: "top",
                                        position: "right",
                                        style: { background: "#198754" },
                                        stopOnFocus: true
                                    }).showToast();
                                    break;
                                default:
                                    console.warn(`unknown action: ${action}`);
                            }  
                        } else {
                            console.error('error updating status:', data.error);
                        }
                    })
                    .catch(error => {
                        console.error('request error:', error);
                    });
                }
            });
        }
    }
</script>
