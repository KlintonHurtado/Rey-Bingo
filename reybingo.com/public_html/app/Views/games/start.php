<?= view('playings/partials/player_nav_cluster', [
    'mode' => 'avatar',
    'imagePath' => $imagePath,
    'showWonCartons' => false,
]); ?>

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

<button class="btn btn-small btn-gear" onclick="settingsGet();"><i class="fa-duotone fa-solid fa-gear"></i></button>

<button class="btn btn-small btn-game" onclick="awardsGameGet();"><i class="fa-duotone fa-solid fa-trophy-star"></i></button>

<div class="start-section">
    <div class="game-section">
        <div class="container">
            <div class="row d-flex justify-content-center">
                <div class="col-md-8 col-xl-8">
                    <div class="row">
                        <div class="col">
                            <div class="text-center">
                                <h5 class="mb-0 p-2"><?= translate('add game'); ?></h5>
                            </div>
                
                            <?php echo form_open(site_url() . 'games/gameSubmit', array('enctype' => 'multipart/form-data', 'id' => 'game-form'));?>
                            
                                <?= csrf_field() ?>
                                
                                <div class="row">
                                    <div class="col-md-7 mb-1">
                                        <label for="description" class="form-label"><?= translate('description'); ?></label>
                                        <input type="text" class="form-control form-control-lg form-bingo" name="description" id="description" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('description')); ?>" autofocus autocomplete="off">
                                        <small id="description-error" class="text-danger d-none"></small>
                                    </div>

                                    <div class="col-md-5 mb-1">
                                        <label for="price" class="form-label"><?= translate('price'); ?> <?= translate('of the'); ?> <?= translate('carton'); ?></label>
                                        <input type="number" class="form-control form-control-lg form-bingo format" name="price" id="price" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('price')); ?> <?= translate('of the'); ?> <?= translate('carton'); ?>" autofocus autocomplete="off" value="0.25" min="0.01" max="9999" step="0.01" inputmode="decimal">
                                        <small id="price-error" class="text-danger d-none"></small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-1">
                                        <label for="date" class="form-label"><?= translate('date'); ?></label>
                                        <input type="date" class="form-control form-control-lg form-bingo" name="date" id="date" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('date')); ?>" autofocus autocomplete="off" value="<?php echo date('Y-m-d'); ?>">
                                        <small id="date-error" class="text-danger d-none"></small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-1">
                                        <label for="time" class="form-label"><?= translate('hour'); ?></label>
                                        <input type="time" class="form-control form-control-lg form-bingo" name="time" id="time" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('hour')); ?>" autofocus autocomplete="off" value="<?php echo date('H:i'); ?>">
                                        <small id="time-error" class="text-danger d-none"></small>
                                    </div>
                                
                                    <div class="col-md-12 pt-3">
                                        <h1 class="h6 mt-2 mb-0"><?= translate('game modalities'); ?> <button type="button" class="btn btn-small btn-primary float-end" data-bs-toggle="modal" data-bs-target="#modalModality" style="position: relative;top: -10px; width: 40px; height: 40px; font-size: 1rem;"> <i class="fa-duotone fa-solid fa-plus"></i></button></h1>
                                        <hr />
                                    </div>

                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-striped text-center" id="modalityTable">
                                                <thead>
                                                    <tr>
                                                        <th><?= translate('modality'); ?></th>
                                                        <th><?= translate('award'); ?></th>
                                                        <th><?= translate('option'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-small btn-primary d-block w-50 btn-bingo mt-3" id="game-button"><?= translate('start'); ?></button>
                                    </div>
                                </div>

                                <div class="modal fade" id="modalModality" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered max-w-45">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-chess-board"></i> <?= translate('add modality'); ?></h6>
                                                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
                                            </div>
                                            <div class="modal-body pt-0">
                                                <div class="row">
                                                    <div class="col-md-8 mb-1">
                                                        <label for="modal-modality" class="form-label"><?= translate('modality'); ?></label>
                                                        <select class='form-control form-control-lg form-bingo' id="modal-modality">
                                                            <option value=""><?= translate('modality'); ?></option>
                                                            <?php foreach ($modalities as $modality): ?>
                                                                <option value="<?= $modality['id'] ?>"><?= translate($modality['name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <small id="modality-error" class="text-danger d-none"></small>
                                                    </div>

                                                    <div class="col-md-4 mb-1">
                                                        <label for="modal-amount" class="form-label"><?= translate('award'); ?></label>
                                                        <input type="number" class="form-control form-control-lg form-bingo format" id="modal-amount" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('amount')); ?>" autocomplete="off" value="0.00">
                                                        <small id="amount-error" class="text-danger d-none"></small>
                                                    </div>

                                                    <div class="col-md-12 mb-1">
                                                        <label for="modal-observation" class="form-label"><?= translate('observation'); ?></label>
                                                        <textarea class="form-control form-control-lg form-bingo" id="modal-observation" rows="2" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('observation')); ?>"></textarea>
                                                    </div>

                                                    <div class="col-md-12">
                                                        <button type="button" class="btn btn-small btn-primary d-block w-50 btn-bingo mt-3" id="addModality"><?= translate('add'); ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?= form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function gamesGet() {
        $("#games").load('<?= site_url('games/gamesGet') ?>');
        $('#games').modal('show');
    }

    function paymentsGet() {
        $("#payments").load('<?= site_url('payments/paymentsGet') ?>');
        $('#payments').modal('show');
    }

    function settingsGet() {
        $("#settings").load('<?= site_url('home/settingsGet') ?>');
        $('#settings').modal('show');
    }

    function GoToPage(page) {
        $.ajax({
            url: '<?= site_url(); ?>' + page,
            success: function (data) {
                if (page != 'logout') {
                    $('#content-page').html(data);
                } else {
                    window.location.href = page;
                }
            }
        });
    }

    let awardsCount = 1;

    document.getElementById("addModality").addEventListener("click", function() {
        let modality = document.getElementById("modal-modality");
        let amount = document.getElementById("modal-amount");
        let observation = document.getElementById("modal-observation");

        if (!modality || modality.value === "") {
            Toastify({
                text: "<?= translate('please select a modality'); ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#dc3545" },
                stopOnFocus: true
            }).showToast();
            return;
        }

        if (isModalityDuplicated(modality.value)) {
            Toastify({
                text: "<?= translate('this modality has already been selected. Choose another'); ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#dc3545" },
                stopOnFocus: true
            }).showToast();
            return;
        }

        if (amount.value === "") {
            Toastify({
                text: "<?= translate('please ingrese a amount'); ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#dc3545" },
                stopOnFocus: true
            }).showToast();
            return;
        }

        awardsCount++;

        let table = document.querySelector("#modalityTable tbody");
        let row = `<tr>
            <td>${modality.options[modality.selectedIndex].text}
                <select class='hidden' name="modality[]" id="modality${awardsCount}">
                    <option value="${modality.value}" selected>${modality.options[modality.selectedIndex].text}</option>
                </select>
            </td>
            <td><?= systemGet('currency'); ?> ${amount.value} 
                <input type="hidden" name="amount[]" value="${amount.value}">
                <input type="hidden" name="observation[]" value="${observation.value}">
            </td>
            <td>
                <button type="button" class="btn btn-small btn-danger  btn-sm" onclick="removeRow(this);" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-minus"></i></button>
            </td>
        </tr>`;

        table.insertAdjacentHTML("beforeend", row);

        modality.value = "";
        amount.value = "0.00";
        observation.value = "";

        $("#modalModality").modal("hide");

        updateSelectOptions();
    });

    $(document).ready(function() {
        
        gamesGet();

        $('#game-form').attr('novalidate', 'novalidate');

        $('#game-form').on('submit', function(e) {
            e.preventDefault();
    
            var button = $('#game-button');
            button.prop("disabled", true);
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            var modalities = $('select[name="modality[]"]');
            var hasValidModality = false;

            modalities.each(function () {
                if ($(this).val().trim() !== '') {
                    hasValidModality = true;
                }
            });

            if (!hasValidModality) {
                Toastify({
                    text: "<?= translate('you must add at least one modality'); ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                button.prop("disabled", false);
                return;
            }
    
            $.ajax({
                url: '<?= site_url('games/gameSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(field, message) {
                                $('#' + field + '-error').text(message).removeClass('d-none');
                                $('#' + field).addClass('is-invalid');
                            });
                        }
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
                },
                complete: function() {
                    button.prop("disabled", false);
                }
            });
        });
    });

    function isModalityDuplicated(selectedModality) {
        const modalities = document.querySelectorAll(`#modalityTable tbody select[name="modality[]"]`);
        
        for (let modality of modalities) {
            if (modality.value === selectedModality) {
                return true;
            }
        }
        
        return false;
    }

    function updateSelectOptions() {
        const selectedModalities = new Set(
            Array.from(document.querySelectorAll(`#modalityTable tbody select[name="modality[]"]`)).map(select => select.value)
        );

        document.querySelectorAll("#modal-modality option").forEach(option => {
            option.disabled = selectedModalities.has(option.value);
        });
    }

    function removeRow(button) {
        button.closest("tr").remove();
        updateSelectOptions();
    }

    $('#toggle-soundtrack-button').click(function() {
        if (soundtrack && !soundtrack.paused) {
            soundtrack.pause();
            $(this).html('<i class="fa-duotone fa-solid fa-volume-slash"></i>');
        } else {
            if (!soundtrack) {
                soundtrack = new Audio('<?= site_url('assets/sounds/soundtrack.mp3') ?>');
                soundtrack.volume = 0.1;
                soundtrack.play();
                soundtrack.addEventListener('ended', function() {
                    this.currentTime = 0;
                    this.play();
                });
            } else {
                soundtrack.play();
            }
            $(this).html('<i class="fa-duotone fa-solid fa-volume"></i>');
        }
    });

    function RemoveVolume() {
        $.ajax({
            url: '<?= site_url('playings/volumeSubmit') ?>',
            method: 'POST',
            success: function(data) {
                if (data.status === 'success') {
                    console.log("sound disabled successfully.");
                } else {
                    console.log("error sending request.");
                }
            },
            error: function(error) {
                console.log("error in the request.");
            }
        });
    }

    $('.format').change(function() {
        if (this.value) {
            const parsed = parseFloat(String(this.value).replace(/,/g, ''));
            this.value = Number.isFinite(parsed) ? parsed.toFixed(2) : '0.25';
        } else {
            $(this).val('0.25');
        }
    });
</script>