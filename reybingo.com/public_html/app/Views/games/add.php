<div class="modal-dialog modal-dialog-centered max-w-60" id="modalAddgame">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2" id="game-modal-title"><i class="fa-duotone fa-solid fa-gamepad"></i> <?= translate('add game'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url() . 'games/gameSubmit', array('enctype' => 'multipart/form-data', 'id' => 'game-form'));?>
                <?= csrf_field() ?>
                <input type="hidden" id="game-id" name="game-id" value="">
                <input type="hidden" id="game-action" name="game-action" value="add">
                
                <div class="row">
                    <div class="col-md-4 mb-1">
                        <label for="room" class="form-label"><?= translate('game room'); ?></label>
                        <select class='form-control form-control-lg form-bingo' name="room" id="room">
                            <option value=""><?= translate('game room'); ?></option>
                            <?php foreach ($gamerooms as $gameroom): ?>
                                <option value="<?= $gameroom['id'] ?>"><?= $gameroom['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="room-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-8 mb-1">
                        <label for="description" class="form-label"><?= translate('description'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="description" id="description" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('description')); ?>" autofocus autocomplete="off">
                        <small id="description-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 mb-1">
                        <label for="price" class="form-label"><?= translate('price of the carton'); ?></label>
                        <input type="number" class="form-control form-control-lg form-bingo format" name="price" id="price" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('price')); ?>" autocomplete="off" value="0.00">
                        <small id="price-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 mb-1">
                        <label for="min_players" class="form-label"><?= translate('minimum players to start'); ?></label>
                        <input type="number" class="form-control form-control-lg form-bingo" name="min_players" id="min_players" min="1" max="9999" step="1" placeholder="10" autocomplete="off" value="10">
                        <small class="text-muted"><?= translate('the game can only start when there are more players than this number'); ?></small>
                        <small id="min_players-error" class="text-danger d-none"></small>
                    </div>
                    
                    <div class="col-md-4 mb-1">
                        <label for="date" class="form-label"><?= translate('date'); ?></label>
                        <input type="date" class="form-control form-control-lg form-bingo" name="date" id="date" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('date')); ?>" autocomplete="off" value="<?php echo date('Y-m-d'); ?>">
                        <small id="date-error" class="text-danger d-none"></small>
                    </div>
                    
                    <div class="col-md-4 mb-1">
                        <label for="time" class="form-label"><?= translate('hour'); ?></label>
                        <input type="time" class="form-control form-control-lg form-bingo" name="time" id="time" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('hour')); ?>" autocomplete="off" value="<?php echo date('H:i'); ?>">
                        <small id="time-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-1">
                        <label for="award" class="form-label"><?= translate('type of award'); ?></label>
                        <select class='form-control form-control-lg form-bingo' name="award" id="award">
                            <option value="1"><?= translate('accumulated'); ?></option>
                            <option value="2"><?= translate('amount'); ?></option>
                        </select>
                        <small id="type-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-1">
                        <label for="type" class="form-label"><?= translate('type of game'); ?></label>
                        <select class='form-control form-control-lg form-bingo' name="type" id="type" onchange="typeGame();">
                            <option value="1"><?= translate('automatic'); ?></option>
                            <option value="2"><?= translate('manual'); ?></option>
                            <option value="3"><?= translate('live'); ?></option>
                        </select>
                        <small id="type-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6">
                        <div class="col-md-12 mb-1 text-center">
                            <label for="coverfileInput" class="form-label ps-0"><?= translate('front'); ?></label>
                            
                            <div class="cover position-relative d-inline-block">
                                <!-- Imagen -->
                                <img id="coverImage" src="<?= site_url('uploads/covers/image.jpg'); ?>" alt="cover" class="img-fluid img-thumbnail mx-auto d-block">

                                <!-- Botón Editar -->
                                <label for="coverfileInput" class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 img-button"><i class="fa-duotone fa-solid fa-plus"></i></label>
                                <input type="file" id="coverfileInput" accept="image/*" class="d-none" onchange="previewcoverImage(event)">

                                <!-- Botón Eliminar (oculto si no hay imagen) -->
                                <button type="button" id="removeCoverBtn" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 m-2 img-button d-none" onclick="removeCoverImage()"><i class="fa-duotone fa-trash"></i></button>

                                <input type="hidden" id="cover_image_input" name="cover" value="<?= esc($cover) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="col-md-12 mb-1">
                            <label for="reset" class="form-label"><?= translate('reset in each modality'); ?></label>
                            <select class='form-control form-control-lg form-bingo' name="reset" id="reset">
                                <option value="2"><?= translate('not'); ?></option>
                                <option value="1"><?= translate('yes'); ?></option>
                            </select>
                            <small id="reset-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-12 mb-1" id="type-url" style="display: none;">
                            <label for="url" class="form-label"><?= translate('url'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="url" id="url" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('url')); ?>" autofocus autocomplete="off">
                            <small id="url-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-12 mb-1" id="type-video" style="display: none;">
                            <label for="video" class="form-label"><?= translate('video'); ?></label>
                            <input type="file" class="form-control form-control-lg form-bingo" name="video" id="video" accept="video/mp4,video/avi,video/mov,video/wmv" onchange="handleVideoUpload(event)">
                            <small id="video-error" class="text-danger d-none"></small>
                            
                            <!-- Barra de progreso -->
                            <div class="progress mt-2" id="video-progress" style="display: none;">
                                <div class="progress-bar" role="progressbar" style="width: 0%" id="video-progress-bar">0%</div>
                            </div>
                            
                            <!-- Preview del video -->
                            <div class="mt-2" id="video-preview" style="display: none;">
                                <video width="100%" height="200" controls id="video-player">
                                    <source src="" type="video/mp4">
                                    Tu navegador no soporta el elemento de video.
                                </video>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <span id="video-name"></span> - 
                                        <span id="video-size"></span> - 
                                        <span id="video-duration"></span>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Campo oculto para almacenar el nombre del archivo subido -->
                            <input type="hidden" id="uploaded-video-name" name="uploaded_video_name" value="">
                        </div>
                    </div>
                
                    <div class="col-md-12 pt-3">
                        <h1 class="h6 mt-2 mb-0"><?= translate('game modalities'); ?> 
                            <button type="button" class="btn btn-primary float-end" onclick="modalityAdd();" style="position: relative;top: -10px; width: 40px; height: 40px; font-size: 1rem;"> 
                                <i class="fa-duotone fa-solid fa-plus"></i>
                            </button>
                        </h1>
                        <hr />
                    </div>

                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped text-center" id="modalityTable">
                                <thead>
                                    <tr>
                                        <th><?= translate('modality'); ?></th>
                                        <th><?= translate('award'); ?></th>
                                        <th><?= translate('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-12 d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-2" style="width: 200px; display: inline-block;" id="add-button"><?= translate('add'); ?></button>
                        <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-2" style="width: 200px; display: inline-block;" id="game-button"><?= translate('start'); ?></button>
                    </div>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    window.GameManager = window.GameManager || {
        editingRow: null,
        awardsCount: 1
    };

    function addRowToTable(rowData, isExisting = false) {
        const awardSelect = document.getElementById('award');
        const selectedAward = awardSelect ? awardSelect.value : '2';
        let symbol = '';
        if (selectedAward === '1') symbol = '%';
        else if (selectedAward === '2') symbol = "<?= systemGet('currency'); ?>";

        const tbody = document.querySelector("#modalityTable tbody");
        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td>
                ${rowData.modality_name}
                <select class='hidden' name="modality[]">
                    <option value="${rowData.modality_id}" selected>${rowData.modality_name}</option>
                </select>
                ${isExisting ? `<input type="hidden" name="award_id[]" value="${rowData.award_id}">` : ''}
            </td>
            <td>
                ${parseFloat(rowData.amount).toFixed(2)} ${symbol}
                <input type="hidden" name="amount[]" value="${parseFloat(rowData.amount).toFixed(2)}">
                <input type="hidden" name="observation[]" value="${(rowData.observation || '').replace(/"/g, '"')}">
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-info btn-sm" onclick="editRow(this);" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-pen"></i></button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this);" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;"><i class="fa-duotone fa-solid fa-trash"></i></button>
                </div>
            </td>
        `;

        tr.dataset.modalityId = rowData.modality_id;
        tr.dataset.modalityName = rowData.modality_name;
        tr.dataset.amount = parseFloat(rowData.amount).toFixed(2);
        tr.dataset.observation = rowData.observation || '';
        if (isExisting) tr.dataset.awardId = rowData.award_id;

        tbody.appendChild(tr);
    }

    function editRow(btn) {
        const row = btn.closest("tr");
        window.GameManager.editingRow = row;

        // Obtener datos de la fila
        const modalityId = row.dataset.modalityId;
        const amount = row.dataset.amount;
        const observation = row.dataset.observation || '';

        // Cargar modal de modalidades
        modalityAdd(() => {
            // Llenar campos con datos existentes después de cargar el modal
            setTimeout(() => {
                const modalitySelect = document.getElementById("modal-modality");
                const amountInput = document.getElementById("modal-amount");
                const observationInput = document.getElementById("modal-observation");
                
                if (modalitySelect) modalitySelect.value = modalityId;
                if (amountInput) amountInput.value = amount;
                if (observationInput) observationInput.value = observation;
                
                // Actualizar opciones disponibles
                if (window.ModalityManager && window.ModalityManager.updateSelectOptions) {
                    window.ModalityManager.updateSelectOptions();
                }
            }, 200);
        });
    }

    function removeRow(btn) {
        btn.closest("tr").remove();
        if (window.ModalityManager && window.ModalityManager.updateSelectOptions) {
            window.ModalityManager.updateSelectOptions();
        }
    }

    function modalityAdd(callback) {
        $("#modalAddmodality").load(site_url + 'games/addmodality', function() {
            $('#modalAddmodality').modal('show');
            if (callback) callback();
        });
    }

    function typeGame() {
        const typeSelect = document.getElementById('type');
        const urlDiv = document.getElementById('type-url');
        const videoDiv = document.getElementById('type-video');

        if (!typeSelect || !urlDiv || !videoDiv) return;

        const selectedType = typeSelect.value;
        urlDiv.style.display = 'none';
        videoDiv.style.display = 'none';

        if (selectedType === '3') {
            urlDiv.style.display = 'block';
        } else if (selectedType === '4') {
            videoDiv.style.display = 'block';
        }
    }

    function previewcoverImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('coverImage');
            output.src = reader.result;
            document.getElementById('cover_image_input').value = reader.result;

            // Mostrar botón eliminar
            document.getElementById('removeCoverBtn').classList.remove('d-none');
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function removeCoverImage() {
        document.getElementById('coverImage').src = "<?= site_url('uploads/covers/image.jpg'); ?>";
        document.getElementById('cover_image_input').value = ""; 
        document.getElementById('coverfileInput').value = "";  

        // Ocultar botón eliminar
        document.getElementById('removeCoverBtn').classList.add('d-none');
    }

    // Al cargar si ya hay imagen, mostrar botón eliminar
    window.addEventListener("DOMContentLoaded", () => {
        if (document.getElementById('coverImage').src) {
            document.getElementById('removeCoverBtn').classList.remove('d-none');
        }
    });

    function validateHasModality() {
        var modalities = document.querySelectorAll('select[name="modality[]"]');
        var hasValidModality = false;
        modalities.forEach(function(select) {
            if ((select.value || '').trim() !== '') hasValidModality = true;
        });
        return hasValidModality;
    }

    function submitAddGame(form) {
        var button = $('#add-button');
        button.prop("disabled", true);

        $('.text-danger').addClass('d-none').text('');
        $('.form-control').removeClass('is-invalid');

        if (!validateHasModality()) {
            Toastify({
                text: "<?= translate('you must add at least one modality'); ?>",
                duration: 3000, gravity: "top", position: "right",
                style: { background: "#dc3545" }, stopOnFocus: true
            }).showToast();
            button.prop("disabled", false);
            return;
        }

        $.ajax({
            url: '<?= site_url('games/addgameSubmit') ?>',
            method: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalAddgame').modal('hide');

                    let newDate = response.date;
                    let newDateText = response.dateText;
                    let $dateFilter = $('#datefilter');

                    $dateFilter.find("option[value='0']").remove();
                    if ($dateFilter.find("option[value='" + newDate + "']").length === 0) {
                        $dateFilter.append($("<option>", { value: newDate, text: newDateText }));
                    }
                    $dateFilter.val(newDate);

                    if (typeof gameslistGet === 'function') gameslistGet();

                    Toastify({
                        text: response.message,
                        duration: 3000, gravity: "top", position: "right",
                        style: { background: "#198754" }, stopOnFocus: true
                    }).showToast();
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
                    duration: 3000, gravity: "top", position: "right",
                    style: { background: "#dc3545" }, stopOnFocus: true
                }).showToast();
            },
            complete: function() {
                button.prop("disabled", false);
            }
        });
    }

    function submitStartGame(form) {
        var button = $('#game-button');
        button.prop("disabled", true);

        $('.text-danger').addClass('d-none').text('');
        $('.form-control').removeClass('is-invalid');

        if (!validateHasModality()) {
            Toastify({
                text: "<?= translate('you must add at least one modality'); ?>",
                duration: 3000, gravity: "top", position: "right",
                style: { background: "#dc3545" }, stopOnFocus: true
            }).showToast();
            button.prop("disabled", false);
            return;
        }

        $.ajax({
            url: '<?= site_url('games/startgameSubmit') ?>',
            method: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    if (response.message) {
                        Toastify({
                            text: response.message,
                            duration: 4000, gravity: "top", position: "right",
                            style: { background: "#dc3545" }, stopOnFocus: true
                        }).showToast();
                    }
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
                    duration: 3000, gravity: "top", position: "right",
                    style: { background: "#dc3545" }, stopOnFocus: true
                }).showToast();
            },
            complete: function() {
                button.prop("disabled", false);
            }
        });
    }

    $(document).ready(function() {
        $(document).on('change', '.format', function() {
            if (this.value) {
                this.value = parseFloat(this.value.replace(/,/g, "")).toFixed(2);
            } else {
                $(this).val('0.00');
            }
        });

        typeGame();

        <?php if (isset($isUpdate) && $isUpdate && $gameData): ?>
            $('#game-id').val('<?= $gameData['id'] ?>');
            $('#game-action').val('update');
            $('#room').val('<?= esc($gameData['room']) ?>');
            $('#description').val('<?= esc($gameData['description']) ?>');
            $('#price').val('<?= esc($gameData['price']) ?>');
            $('#min_players').val('<?= esc($gameData['min_players'] ?? 10) ?>');
            $('#date').val('<?= esc($gameData['date']) ?>');
            $('#time').val('<?= date('H:i', strtotime($gameData['time'])) ?>');
            $('#award').val('<?= esc($gameData['award']) ?>');
            $('#type').val('<?= esc($gameData['type']) ?>');
            $('#url').val('<?= esc($gameData['url']) ?>');
            $('#coverImage').attr('src', '<?= esc($cover) ?>');
            $('#game-modal-title').html('<i class="fa-duotone fa-solid fa-gamepad"></i> <?= translate('update game'); ?>');
            $('#add-button').text('<?= translate('update'); ?>');

            typeGame();

            const awardsExisting = <?= json_encode($awards ?? []) ?>;
            if (awardsExisting && awardsExisting.length) {
                awardsExisting.forEach(a => {
                    addRowToTable({
                        award_id: a.id,
                        modality_id: a.modality,
                        modality_name: a.modality_name, 
                        amount: a.amount,
                        observation: a.observation
                    }, true);
                });
            }
        <?php endif; ?>

        $('#game-form').on('submit', function(e) {
            e.preventDefault();
            var clickedButton = e.originalEvent.submitter.id;

            if (clickedButton === 'add-button') {
                submitAddGame(this);
            } else if (clickedButton === 'game-button') {
                submitStartGame(this);
            }
        });
    });

    window.VideoManager = {
        maxSize: 50 * 1024 * 1024, // 50MB en bytes
        allowedTypes: ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'],
        currentUpload: null,

        // Función para manejar la selección de video
        handleVideoSelection: function(event) {
            const file = event.target.files[0];
            if (!file) {
                this.clearVideoPreview();
                return;
            }

            // Validar tipo de archivo
            if (!this.allowedTypes.includes(file.type)) {
                this.showVideoError('<?= translate("invalid video format. allowed formats: mp4, avi, mov, wmv"); ?>');
                this.clearVideoInput();
                return;
            }

            // Validar tamaño
            if (file.size > this.maxSize) {
                const maxSizeMB = this.maxSize / (1024 * 1024);
                this.showVideoError(`<?= translate("video file is too large. maximum size allowed"); ?>: ${maxSizeMB}MB`);
                this.clearVideoInput();
                return;
            }

            // Mostrar información del archivo
            this.showVideoInfo(file);
            
            // Crear preview
            this.createVideoPreview(file);
            
            // Subir archivo inmediatamente
            this.uploadVideo(file);
        },

        // Función para mostrar información del video
        showVideoInfo: function(file) {
            const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
            document.getElementById('video-name').textContent = file.name;
            document.getElementById('video-size').textContent = `${sizeInMB} MB`;
        },

        // Función para crear preview del video
        createVideoPreview: function(file) {
            const videoPlayer = document.getElementById('video-player');
            const videoPreview = document.getElementById('video-preview');
            
            if (videoPlayer && videoPreview) {
                const url = URL.createObjectURL(file);
                videoPlayer.src = url;
                
                // Obtener duración cuando se carguen los metadatos
                videoPlayer.addEventListener('loadedmetadata', () => {
                    const duration = this.formatDuration(videoPlayer.duration);
                    document.getElementById('video-duration').textContent = duration;
                });
                
                videoPreview.style.display = 'block';
            }
        },

        // Función para formatear duración
        formatDuration: function(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        },

        // Función para subir video via AJAX
        uploadVideo: function(file) {
            const formData = new FormData();
            formData.append('video', file);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            const progressBar = document.getElementById('video-progress-bar');
            const progressContainer = document.getElementById('video-progress');
            
            if (progressContainer) {
                progressContainer.style.display = 'block';
            }

            // Cancelar upload anterior si existe
            if (this.currentUpload) {
                this.currentUpload.abort();
            }

            this.currentUpload = new XMLHttpRequest();

            // Configurar progreso
            this.currentUpload.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    if (progressBar) {
                        progressBar.style.width = percentComplete + '%';
                        progressBar.textContent = Math.round(percentComplete) + '%';
                    }
                }
            });

            // Configurar respuesta
            this.currentUpload.addEventListener('load', () => {
                if (this.currentUpload.status === 200) {
                    try {
                        const response = JSON.parse(this.currentUpload.responseText);
                        if (response.success) {
                            document.getElementById('uploaded-video-name').value = response.filename;
                            this.showVideoSuccess('<?= translate("video uploaded successfully"); ?>');
                        } else {
                            this.showVideoError(response.message || '<?= translate("error uploading video"); ?>');
                        }
                    } catch (e) {
                        this.showVideoError('<?= translate("error processing server response"); ?>');
                    }
                } else {
                    this.showVideoError('<?= translate("error uploading video to server"); ?>');
                }
                
                if (progressContainer) {
                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                    }, 2000);
                }
            });

            // Configurar error
            this.currentUpload.addEventListener('error', () => {
                this.showVideoError('<?= translate("network error while uploading video"); ?>');
                if (progressContainer) {
                    progressContainer.style.display = 'none';
                }
            });

            // Enviar request
            this.currentUpload.open('POST', '<?= site_url("games/uploadVideo") ?>');
            this.currentUpload.send(formData);
        },

        // Función para mostrar errores
        showVideoError: function(message) {
            const errorElement = document.getElementById('video-error');
            const videoInput = document.getElementById('video');
            
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.remove('d-none');
            }
            
            if (videoInput) {
                videoInput.classList.add('is-invalid');
            }

            // Mostrar toast de error
            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: message,
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            }
        },

        // Función para mostrar éxito
        showVideoSuccess: function(message) {
            const errorElement = document.getElementById('video-error');
            const videoInput = document.getElementById('video');
            
            if (errorElement) {
                errorElement.classList.add('d-none');
            }
            
            if (videoInput) {
                videoInput.classList.remove('is-invalid');
            }

            // Mostrar toast de éxito
            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#198754" },
                    stopOnFocus: true
                }).showToast();
            }
        },

        // Función para limpiar input
        clearVideoInput: function() {
            const videoInput = document.getElementById('video');
            if (videoInput) {
                videoInput.value = '';
            }
            this.clearVideoPreview();
        },

        // Función para limpiar preview
        clearVideoPreview: function() {
            const videoPreview = document.getElementById('video-preview');
            const videoPlayer = document.getElementById('video-player');
            const uploadedVideoName = document.getElementById('uploaded-video-name');
            
            if (videoPreview) {
                videoPreview.style.display = 'none';
            }
            
            if (videoPlayer) {
                videoPlayer.src = '';
            }
            
            if (uploadedVideoName) {
                uploadedVideoName.value = '';
            }
            
            // Limpiar información
            ['video-name', 'video-size', 'video-duration'].forEach(id => {
                const element = document.getElementById(id);
                if (element) element.textContent = '';
            });
        }
    };

    function handleVideoUpload(event) {
        if (window.VideoManager) {
            window.VideoManager.handleVideoSelection(event);
        }
    }
</script>
