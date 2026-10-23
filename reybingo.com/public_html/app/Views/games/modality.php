<div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 500px;">
    <div class="modal-content">
        <div class="modal-header pb-2">
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

                <!-- Imagen representativa de la modalidad -->
                <div class="col-md-12 mb-2">
                    <label class="form-label mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="fa-duotone fa-image text-primary me-1"></i> Imagen de la modalidad <small class="text-muted">(opcional)</small></span>
                        <span id="modal-modality-img-status" class="badge bg-secondary-subtle text-muted" style="font-size: 0.72rem;">Sin imagen</span>
                    </label>
                    
                    <div class="modality-upload-dropzone p-3 text-center position-relative" style="border: 2px dashed rgba(98, 54, 255, 0.35); border-radius: 14px; background: rgba(98, 54, 255, 0.03); cursor: pointer; transition: all 0.2s ease;">
                        <input type="file" id="modal-modality-file" accept="image/*" class="d-none" onchange="previewModalityImage(event)">
                        <input type="hidden" id="modal-modality-image" value="">
                        
                        <!-- Estado vacío -->
                        <div id="modality-upload-placeholder" onclick="document.getElementById('modal-modality-file').click();">
                            <i class="fa-duotone fa-cloud-arrow-up fs-2 text-primary mb-1 d-block"></i>
                            <span class="d-block fw-bold text-primary" style="font-size: 0.88rem;">Subir imagen de la modalidad</span>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Haz clic para seleccionar (PNG, JPG, WEBP). Se adapta a la tarjeta de modalidades.</small>
                        </div>
                        
                        <!-- Preview de imagen seleccionada -->
                        <div id="modality-upload-preview-wrap" class="d-none position-relative text-center">
                            <div class="d-inline-block position-relative" style="padding: 4px; background: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.12);">
                                <img id="modality-upload-preview" src="" alt="Vista previa" style="max-height: 110px; max-width: 100%; object-fit: contain; border-radius: 8px; display: block; margin: 0 auto;">
                            </div>
                            <div class="mt-2 d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="document.getElementById('modal-modality-file').click();" style="font-size: 0.75rem; border-radius: 12px;">
                                    <i class="fa-duotone fa-pen me-1"></i> Cambiar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeModalityImage();" style="font-size: 0.75rem; border-radius: 12px;">
                                    <i class="fa-duotone fa-trash me-1"></i> Quitar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-2 mb-3" id="addModality"><?= translate('add'); ?></button>
                </div>
            </div>

            <div id="modalModalitiesList" class="mt-2" style="display:none;">
                <hr class="my-2">
                <h6 class="mb-2"><i class="fa-duotone fa-solid fa-list"></i> <?= translate('game modalities'); ?></h6>
                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center mb-0" id="modalModalitiesTable">
                        <thead>
                            <tr>
                                <th><?= translate('modality'); ?></th>
                                <th><?= translate('award'); ?></th>
                                <th style="width:90px;"><?= translate('options'); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function previewModalityImage(event) {
        const file = event.target.files && event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                const maxDim = 800;
                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                const compressedDataUrl = canvas.toDataURL('image/png', 0.88);

                document.getElementById('modal-modality-image').value = compressedDataUrl;
                document.getElementById('modality-upload-preview').src = compressedDataUrl;
                document.getElementById('modality-upload-placeholder').classList.add('d-none');
                document.getElementById('modality-upload-preview-wrap').classList.remove('d-none');

                const statusBadge = document.getElementById('modal-modality-img-status');
                if (statusBadge) {
                    statusBadge.textContent = 'Imagen seleccionada';
                    statusBadge.className = 'badge bg-success-subtle text-success';
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function removeModalityImage() {
        const fileInput = document.getElementById('modal-modality-file');
        if (fileInput) fileInput.value = '';
        const imgInput = document.getElementById('modal-modality-image');
        if (imgInput) imgInput.value = 'REMOVE';
        const preview = document.getElementById('modality-upload-preview');
        if (preview) preview.src = '';

        const placeholder = document.getElementById('modality-upload-placeholder');
        if (placeholder) placeholder.classList.remove('d-none');
        const previewWrap = document.getElementById('modality-upload-preview-wrap');
        if (previewWrap) previewWrap.classList.add('d-none');

        const statusBadge = document.getElementById('modal-modality-img-status');
        if (statusBadge) {
            statusBadge.textContent = 'Sin imagen';
            statusBadge.className = 'badge bg-secondary-subtle text-muted';
        }
    }

    window.ModalityManager = {
        addModality: function() {
            let modality = document.getElementById("modal-modality");
            let amount = document.getElementById("modal-amount");
            let observation = document.getElementById("modal-observation");
            let image = document.getElementById("modal-modality-image") ? document.getElementById("modal-modality-image").value : '';

            this.clearErrors();

            if (!modality || !modality.value) {
                this.showError("modality-error", "<?= translate('please select a modality'); ?>");
                return;
            }

            if (!amount || !amount.value || parseFloat(amount.value) <= 0) {
                this.showError("amount-error", "<?= translate('please enter a valid amount'); ?>");
                return;
            }

            if (!window.GameManager.editingRow && this.isModalityDuplicated(modality.value)) {
                this.showError("modality-error", "<?= translate('this modality has already been added'); ?>");
                return;
            }

            const awardSelect = document.getElementById('award');
            const selectedAward = awardSelect ? awardSelect.value : '2';
            let symbol = '';
            if (selectedAward === '1') symbol = '%';
            else if (selectedAward === '2') symbol = "<?= systemGet('currency'); ?>";

            if (window.GameManager.editingRow) {
                this.updateExistingRow(modality, amount, observation, symbol, image);
            } else {
                this.createNewRow(modality, amount, observation, symbol, image);
            }

            this.clearForm();
            this.updateSelectOptions();
            this.refreshModalList();
        },

        setImage: function(imageVal) {
            const imgInput = document.getElementById('modal-modality-image');
            const preview = document.getElementById('modality-upload-preview');
            const placeholder = document.getElementById('modality-upload-placeholder');
            const previewWrap = document.getElementById('modality-upload-preview-wrap');
            const statusBadge = document.getElementById('modal-modality-img-status');

            if (imageVal && imageVal !== 'REMOVE') {
                if (imgInput) imgInput.value = imageVal;
                let fullSrc = imageVal.startsWith('data:') ? imageVal : ('<?= site_url('uploads/modalities/') ?>' + imageVal);
                if (preview) preview.src = fullSrc;
                if (placeholder) placeholder.classList.add('d-none');
                if (previewWrap) previewWrap.classList.remove('d-none');
                if (statusBadge) {
                    statusBadge.textContent = 'Imagen configurada';
                    statusBadge.className = 'badge bg-primary-subtle text-primary';
                }
            } else {
                removeModalityImage();
            }
        },

        updateExistingRow: function(modality, amount, observation, symbol, image) {
            const parentRow = window.GameManager.editingRow;
            const currentImg = (image !== undefined && image !== null) ? image : (parentRow.dataset.image || '');

            let thumbHtml = '';
            if (currentImg && currentImg !== 'REMOVE') {
                let src = currentImg.startsWith('data:') ? currentImg : ('<?= site_url('uploads/modalities/') ?>' + currentImg);
                thumbHtml = `<img src="${src}" style="width: 26px; height: 26px; object-fit: cover; border-radius: 4px; vertical-align: middle;" class="me-1 border">`;
            }

            parentRow.cells[0].innerHTML = thumbHtml + modality.options[modality.selectedIndex].text +
                `<select class='hidden' name="modality[]">
                    <option value="${modality.value}" selected>${modality.options[modality.selectedIndex].text}</option>
                </select>
                <input type="hidden" name="modality_image[]" value="${currentImg.replace(/"/g, '&quot;')}">` +
                (parentRow.dataset.awardId ? `<input type="hidden" name="award_id[]" value="${parentRow.dataset.awardId}">` : '');

            parentRow.cells[1].innerHTML = `${parseFloat(amount.value).toFixed(2)} ${symbol} 
                <input type="hidden" name="amount[]" value="${parseFloat(amount.value).toFixed(2)}">
                <input type="hidden" name="observation[]" value="${observation.value.replace(/"/g, '&quot;')}">`;

            parentRow.dataset.modalityId = modality.value;
            parentRow.dataset.modalityName = modality.options[modality.selectedIndex].text;
            parentRow.dataset.amount = parseFloat(amount.value).toFixed(2);
            parentRow.dataset.observation = observation.value;
            parentRow.dataset.image = currentImg;

            window.GameManager.editingRow = null;
        },

        createNewRow: function(modality, amount, observation, symbol, image) {
            window.GameManager.awardsCount++;
            const table = document.querySelector("#modalityTable tbody");
            const row = document.createElement('tr');
            const currentImg = image || '';

            let thumbHtml = '';
            if (currentImg && currentImg !== 'REMOVE') {
                let src = currentImg.startsWith('data:') ? currentImg : ('<?= site_url('uploads/modalities/') ?>' + currentImg);
                thumbHtml = `<img src="${src}" style="width: 26px; height: 26px; object-fit: cover; border-radius: 4px; vertical-align: middle;" class="me-1 border">`;
            }
            
            row.dataset.modalityId = modality.value;
            row.dataset.modalityName = modality.options[modality.selectedIndex].text;
            row.dataset.amount = parseFloat(amount.value).toFixed(2);
            row.dataset.observation = observation.value;
            row.dataset.image = currentImg;

            row.innerHTML = `
                <td>
                    ${thumbHtml}${modality.options[modality.selectedIndex].text}
                    <select class='hidden' name="modality[]">
                        <option value="${modality.value}" selected>${modality.options[modality.selectedIndex].text}</option>
                    </select>
                    <input type="hidden" name="modality_image[]" value="${currentImg.replace(/"/g, '&quot;')}">
                </td>
                <td>
                    ${parseFloat(amount.value).toFixed(2)} ${symbol} 
                    <input type="hidden" name="amount[]" value="${parseFloat(amount.value).toFixed(2)}">
                    <input type="hidden" name="observation[]" value="${observation.value.replace(/"/g, '&quot;')}">
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-info btn-sm" onclick="editRow(this);" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;">
                            <i class="fa-duotone fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this);" style="width: 40px; height: 40px; font-size: 1rem; margin: auto;">
                            <i class="fa-duotone fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            
            table.appendChild(row);
        },

        isModalityDuplicated: function(selectedModality) {
            const modalities = document.querySelectorAll('#modalityTable tbody select[name="modality[]"]');
            
            for (let modalitySelect of modalities) {
                if (modalitySelect.closest('tr') === window.GameManager.editingRow) {
                    continue;
                }
                
                if (modalitySelect.value === selectedModality) {
                    return true;
                }
            }
            
            return false;
        },

        updateSelectOptions: function() {
            const selectedModalities = new Set();
            
            document.querySelectorAll('#modalityTable tbody select[name="modality[]"]').forEach(select => {
                if (select.value && select.closest('tr') !== window.GameManager.editingRow) {
                    selectedModalities.add(select.value);
                }
            });

            const modalitySelect = document.getElementById("modal-modality");
            if (modalitySelect) {
                Array.from(modalitySelect.options).forEach(option => {
                    if (option.value) {
                        option.disabled = selectedModalities.has(option.value);
                    }
                });
            }
        },

        refreshModalList: function() {
            const modalTbody = document.querySelector("#modalModalitiesTable tbody");
            const parentRows = document.querySelectorAll('#modalityTable tbody tr');
            const listContainer = document.getElementById('modalModalitiesList');
            
            if (!modalTbody) return;

            modalTbody.innerHTML = '';

            if (parentRows.length === 0) {
                if (listContainer) listContainer.style.display = 'none';
                return;
            }

            if (listContainer) listContainer.style.display = 'block';

            const awardSelect = document.getElementById('award');
            const selectedAward = awardSelect ? awardSelect.value : '2';
            let symbol = '';
            if (selectedAward === '1') symbol = '%';
            else if (selectedAward === '2') symbol = "<?= systemGet('currency'); ?>";

            parentRows.forEach(function(parentRow) {
                const name = parentRow.dataset.modalityName || '';
                const amount = parentRow.dataset.amount || '0.00';
                const modalityId = parentRow.dataset.modalityId || '';
                const img = parentRow.dataset.image || '';

                let thumbHtml = '';
                if (img && img !== 'REMOVE') {
                    let src = img.startsWith('data:') ? img : ('<?= site_url('uploads/modalities/') ?>' + img);
                    thumbHtml = `<img src="${src}" style="width: 22px; height: 22px; object-fit: cover; border-radius: 4px; vertical-align: middle;" class="me-1 border">`;
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${thumbHtml}${name}</td>
                    <td>${parseFloat(amount).toFixed(2)} ${symbol}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-info btn-sm modal-edit-row" data-modality-id="${modalityId}" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                <i class="fa-duotone fa-solid fa-pen"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm modal-remove-row" data-modality-id="${modalityId}" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                <i class="fa-duotone fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                modalTbody.appendChild(tr);
            });
        },

        showError: function(elementId, message) {
            const errorElement = document.getElementById(elementId);
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.remove('d-none');
            }
        },

        clearErrors: function() {
            document.querySelectorAll('.text-danger').forEach(el => {
                el.classList.add('d-none');
                el.textContent = '';
            });
            document.querySelectorAll('.form-control').forEach(el => {
                el.classList.remove('is-invalid');
            });
        },

        clearForm: function() {
            const modality = document.getElementById("modal-modality");
            const amount = document.getElementById("modal-amount");
            const observation = document.getElementById("modal-observation");

            if (modality) modality.value = "";
            if (amount) amount.value = "0.00";
            if (observation) observation.value = "";
            removeModalityImage();
        },

        formatNumber: function(input) {
            if (input.value) {
                input.value = parseFloat(input.value.replace(/,/g, "")).toFixed(2);
            } else {
                input.value = '0.00';
            }
        },

        init: function() {
            const self = this;

            const addButton = document.getElementById("addModality");
            if (addButton) {
                addButton.onclick = () => this.addModality();
            }

            const formatInputs = document.querySelectorAll('.format');
            formatInputs.forEach(input => {
                input.addEventListener('change', () => this.formatNumber(input));
                input.addEventListener('blur', () => this.formatNumber(input));
            });

            this.updateSelectOptions();
            this.refreshModalList();

            const modalitySelect = document.getElementById("modal-modality");
            if (modalitySelect) {
                modalitySelect.addEventListener('change', () => {
                    document.getElementById("modality-error").classList.add('d-none');
                });
            }

            const amountInput = document.getElementById("modal-amount");
            if (amountInput) {
                amountInput.addEventListener('input', () => {
                    document.getElementById("amount-error").classList.add('d-none');
                });
            }

            document.getElementById('modalModalitiesTable').addEventListener('click', function(e) {
                const editBtn = e.target.closest('.modal-edit-row');
                if (editBtn) {
                    const modalityId = editBtn.dataset.modalityId;
                    const parentRow = document.querySelector('#modalityTable tbody tr[data-modality-id="' + modalityId + '"]');
                    if (parentRow) {
                        window.GameManager.editingRow = parentRow;
                        const modSelect = document.getElementById("modal-modality");
                        const amountInput = document.getElementById("modal-amount");
                        const obsInput = document.getElementById("modal-observation");
                        if (modSelect) modSelect.value = modalityId;
                        if (amountInput) amountInput.value = parentRow.dataset.amount;
                        if (obsInput) obsInput.value = parentRow.dataset.observation || '';
                        self.setImage(parentRow.dataset.image || '');
                        self.updateSelectOptions();
                        modSelect.focus();
                    }
                }

                const removeBtn = e.target.closest('.modal-remove-row');
                if (removeBtn) {
                    const modalityId = removeBtn.dataset.modalityId;
                    const parentRow = document.querySelector('#modalityTable tbody tr[data-modality-id="' + modalityId + '"]');
                    if (parentRow) parentRow.remove();
                    self.updateSelectOptions();
                    self.refreshModalList();
                }
            });

            this.setupRealTimeValidation();
        },

        setupRealTimeValidation: function() {
            const modalitySelect = document.getElementById("modal-modality");
            const amountInput = document.getElementById("modal-amount");

            if (modalitySelect) {
                modalitySelect.addEventListener('change', () => {
                    if (modalitySelect.value) {
                        modalitySelect.classList.remove('is-invalid');
                        document.getElementById("modality-error").classList.add('d-none');
                    }
                });
            }

            if (amountInput) {
                amountInput.addEventListener('input', () => {
                    if (amountInput.value && parseFloat(amountInput.value) > 0) {
                        amountInput.classList.remove('is-invalid');
                        document.getElementById("amount-error").classList.add('d-none');
                    }
                });
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        if (window.ModalityManager) {
            window.ModalityManager.init();
        }
    });

    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            if (window.ModalityManager) {
                window.ModalityManager.init();
            }
        });
    }

    window.addModality = function() {
        if (window.ModalityManager) {
            window.ModalityManager.addModality();
        }
    };

    window.updateSelectOptions = function() {
        if (window.ModalityManager) {
            window.ModalityManager.updateSelectOptions();
        }
    };

    if (typeof $ !== 'undefined') {
        $(document).on('change blur', '.format', function() {
            if (window.ModalityManager) {
                window.ModalityManager.formatNumber(this);
            }
        });
    }

    setTimeout(function() {
        const addButton = document.getElementById("addModality");
        if (addButton && !addButton.onclick) {
            addButton.addEventListener('click', function() {
                if (window.ModalityManager) {
                    window.ModalityManager.addModality();
                }
            });
        }
    }, 100);
</script>
