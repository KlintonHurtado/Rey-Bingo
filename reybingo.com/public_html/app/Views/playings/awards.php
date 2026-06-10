<div class="modal-dialog modal-dialog-centered max-w-70">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-trophy-star"></i> <?= translate('winners'); ?> <br /> <small><?= $game['description']; ?></small></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= translate('player'); ?></th>
                                    <th class="text-center"><?= translate('carton'); ?></th>
                                    <th class="text-center"><?= translate('modality'); ?></th>
                                    <th class="text-center"><?= translate('award'); ?></th>
                                    <?php if (session()->get('group') == 1) : ?>
                                    <th class="text-center"><?= translate('status'); ?></th>
                                    <th class="text-center"><?= translate('option'); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sings)) : ?>
                                    <?php foreach ($sings as $sing) : ?>
                                        <tr>
                                            <td>
                                                <strong><?= esc($sing['user_code']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= esc($sing['user_name']) ?></small>
                                            </td>
                                            <td class="text-center">C<?= esc($sing['serial']) ?></td>
                                           <td class="text-center"><?= esc($sing['modality_name']) ?></td>
                                            <td class="text-center"><?= systemGet('currency'); ?> <?= esc($sing['award_amount']) ?></td>
                                            <?php if (session()->get('group') == 1) : ?>
                                            <td class="text-center" id="award-<?= $sing['id'] ?>"><?= $sing['status'] ?></td>
                                            <td class="text-center">
                                                <?php if (($sing['status_raw'] ?? 0) == 1) : ?>
                                                    <a class="btn btn-primary btn-modal text-white" onclick="payawardSubmit('<?= $sing['id'] ?>', '<?= $sing['user_name'] ?>', '<?= $sing['award_amount'] ?>', 'pay');"><i class="fa-duotone fa-hand-holding-dollar"></i></a>
                                                <?php endif; ?>
                                                <!--  
                                                <div class="dropdown">
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-duotone fa-solid fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu" style="font-size: 0.8rem;">
                                                        <a class="dropdown-item" onclick="payawardSubmit('<?= $sing['id'] ?>', 'pay');"><i class="fa-duotone fa-solid fa-money-check-dollar-pen"></i> <?= translate('pay'); ?></a>
                                                        <a class="dropdown-item" onclick="payawardSubmit('<?= $sing['id'] ?>', 'earring');"><i class="fa-duotone fa-solid fa-eye-slash"></i> <?= translate('earring'); ?></a>
                                                    </div>
                                                </div>
                                                -->
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td <?php if (session()->get('group') == 1) : ?> colspan="6" <?php else : ?> colspan="5" <?php endif; ?> class="text-center"><?= translate('no data available'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (session()->get('group') == 0) : ?>
                        <?php if (isset($lastGame) && count($lastGame) > 0): ?>
                            <h5 class="text-center continue-text">Quieres continuar jugando?</h5>
                            <h6 class="text-center next-game-play mt-2 text-uppercase" data-game-date="<?= $lastGame["date"] ?> <?= $lastGame["time"] ?>"></h6>
                            <button type="button" class="btn btn-small btn-primary d-block w-50 btn-bingo bingo-bg-success card-button-buy continue-button-buy" id="card-button-buy-<?= $lastGame['id'] ?>" onclick="generateCartonsGet(<?= $lastGame['id'] ?>);"><?= translate('buy cartons'); ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var currency = '<?= systemGet('currency'); ?>';
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
                        console.error(`element with ID ${type}-${id} not found`);
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

    $(document).ready(function () {
        initializeNextGameCountdown();
    });

    // Función para inicializar el contador
    function initializeNextGameCountdown() {
        // Verificar si hay datos del próximo juego
        const nextGameSpan = document.querySelector('.next-game-play');
        const continueText = document.querySelector('.continue-text');
        const continueBuy = document.querySelector('.continue-button-buy');
        if (!nextGameSpan) return;
        
        // Obtener la fecha del juego desde el atributo data
        const gameDate = nextGameSpan.getAttribute('data-game-date');
        if (!gameDate) return;
        
        const targetDate = new Date(gameDate);
        const now = new Date();
        
        let interval;
        
        function updateCountdown() {
            const now = new Date();
            const timeDiff = targetDate - now;

            if (timeDiff <= 0) {
                nextGameSpan.textContent = '';
                continueText.textContent = '';
                continueBuy.remove();
                clearInterval(interval);
                return;
            }

            const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

            let text = '';
            if (days > 0) {
                text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${days} DÍA${days > 1 ? 'S' : ''} ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
            } else if (hours > 0) {
                text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
            } else {
                if (minutes === 0) {
                    const sec = Math.max(0, seconds);
                    text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${sec} SEGUNDO${sec === 1 ? '' : 'S'}`;
                } else {
                    text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MINUTO${minutes === 1 ? '' : 'S'}`;
                }
            }

            nextGameSpan.innerHTML = text;
        }

        updateCountdown();
        interval = setInterval(updateCountdown, 1000);
        
        // Guardar el intervalo para limpiarlo cuando se cierre el modal
        nextGameSpan.dataset.countdownInterval = interval;
    }

    // Limpiar el intervalo cuando se cierre el modal
    $(document).on('hidden.bs.modal', '#modalAwards', function () {
        const nextGameSpan = document.querySelector('.next-game-play');
        if (nextGameSpan && nextGameSpan.dataset.countdownInterval) {
            clearInterval(parseInt(nextGameSpan.dataset.countdownInterval));
        }
    });
</script>