<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingCount ?? 0,
    'activeNav' => 'prizes',
]) ?>

<div class="card store-panel-card h-100">
    <div class="card-body store-tab-body">
        <div class="store-tab-form">
            <h6 class="store-tab-form-title">
                <i class="fa-duotone fa-solid fa-money-bill-transfer"></i> Pagar Notas de Retiro en Efectivo
            </h6>

            <div class="store-tab-form-fields">
                <p class="small text-muted mb-3">
                    Ingresa el número de cédula del jugador y el código de retiro alfanumérico generado en su nota para validar y entregar el dinero en efectivo.
                </p>

                <input type="hidden" id="store-retire-id" value="">

                <div class="mb-2">
                    <label for="store-retire-document" class="form-label">Número de Cédula / Documento <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-bingo" id="store-retire-document" placeholder="Ingrese número de cédula del jugador..." autocomplete="off">
                </div>

                <div class="mb-3">
                    <label for="store-retire-code" class="form-label">Código de Retiro <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-primary"><i class="fa-duotone fa-solid fa-key"></i></span>
                        <input type="text" class="form-control form-bingo text-uppercase" id="store-retire-code" placeholder="Ej: RET-AB12CD" autocomplete="off" style="letter-spacing: 1.5px; font-weight: 600;">
                    </div>
                </div>

                <button type="button" class="btn btn-primary btn-bingo w-100 mb-3" id="store-retire-lookup-btn">
                    <i class="fa-duotone fa-solid fa-magnifying-glass"></i> Buscar y Validar Nota de Retiro
                </button>

                <div id="store-retire-lookup-hint" class="small text-muted text-center d-none mb-2">
                    <i class="fa-duotone fa-solid fa-spinner fa-spin me-1"></i> Validando nota de retiro...
                </div>
                <div id="store-retire-lookup-error" class="alert alert-danger py-2 px-3 small d-none mb-2"></div>

                <!-- Tarjeta de vista previa de la nota de retiro -->
                <div id="store-retire-preview" class="d-none mt-2">
                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; background: linear-gradient(135deg, rgba(37,99,235,0.06) 0%, rgba(37,99,235,0.02) 100%); border: 1px solid rgba(37,99,235,0.25) !important;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-warning text-dark mb-1">
                                        <i class="fa-solid fa-clock me-1"></i> Pendiente por Cobrar
                                    </span>
                                    <h6 class="mb-0 text-dark fw-bold" id="store-retire-player-name"></h6>
                                    <small class="text-muted">
                                        Cédula: <strong id="store-retire-preview-document"></strong> · 
                                        Código: <strong id="store-retire-preview-code" class="text-primary font-monospace"></strong>
                                    </small>
                                </div>
                            </div>
                            
                            <hr class="my-2" style="opacity: 0.15;">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Total a Entregar en Efectivo:</span>
                                <div class="text-success fw-bold fs-4" id="store-retire-preview-amount"></div>
                            </div>

                            <button type="button" class="btn btn-success btn-bingo w-100 py-2" id="store-retire-pay-btn">
                                <i class="fa-duotone fa-solid fa-hand-holding-dollar me-1"></i> Confirmar y Pagar Retiro en Efectivo
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="store-tab-history">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="store-tab-history-title mb-0">
                    <i class="fa-duotone fa-solid fa-clock-rotate-left"></i> Historial de Retiros Pagados
                </h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="storeRefreshRetires(1)">
                    <i class="fa-duotone fa-solid fa-rotate"></i> Actualizar
                </button>
            </div>
            <div class="store-table-wrap" id="store-prizes-list">
                <?= view('store/prizes_list', [
                    'retires' => [],
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'totalRecords' => 0,
                    'per_page' => 10,
                    'showPagination' => false,
                ]); ?>
            </div>
        </div>
    </div>
</div>

<?= view('store/partials/close') ?>

<?= view('store/partials/scripts_common') ?>

<script type="text/javascript">
    let storeRetiresPage = 1;
    let storeRetireSelected = null;
    const storeCurrency = '<?= esc(systemGet('currency'), 'js'); ?>';
    const storeCsrfName = '<?= csrf_token(); ?>';
    let storeCsrfHash = '<?= csrf_hash(); ?>';

    function storeAttachCsrf(payload) {
        payload[storeCsrfName] = storeCsrfHash;
        return payload;
    }

    function storeUpdateCsrf(response) {
        if (response && response.csrfHash) {
            storeCsrfHash = response.csrfHash;
        }
    }

    function storeRefreshRetires(page) {
        storeRetiresPage = page || 1;
        $.get('<?= site_url('store/prizesListGet'); ?>', {
            page: storeRetiresPage
        }, function(html) {
            $('#store-prizes-list').html(html);
        });
    }

    function storeClearRetireSelection() {
        storeRetireSelected = null;
        $('#store-retire-id').val('');
        $('#store-retire-preview').addClass('d-none');
        $('#store-retire-lookup-error').addClass('d-none').text('');
    }

    function storeShowRetirePreview(retire) {
        storeRetireSelected = retire;
        $('#store-retire-id').val(retire.id);
        $('#store-retire-player-name').text(retire.player_name || 'Jugador');
        $('#store-retire-preview-document').text(retire.document);
        $('#store-retire-preview-code').text(retire.code);
        $('#store-retire-preview-amount').text(storeCurrency + ' ' + parseFloat(retire.amount).toFixed(2));
        $('#store-retire-preview').removeClass('d-none');
        $('#store-retire-lookup-error').addClass('d-none').text('');
    }

    function storeLookupRetire() {
        const documentVal = $('#store-retire-document').val().trim();
        const codeVal = $('#store-retire-code').val().trim();

        $('#store-retire-lookup-error').addClass('d-none').text('');

        if (!documentVal && !codeVal) {
            $('#store-retire-lookup-error').text('Ingrese la cédula y/o el código de retiro.').removeClass('d-none');
            return;
        }

        $('#store-retire-lookup-hint').removeClass('d-none');
        $('#store-retire-lookup-btn').prop('disabled', true);

        $.post('<?= site_url('store/lookupRetireNote'); ?>', storeAttachCsrf({
            document: documentVal,
            code: codeVal
        }), function(response) {
            storeUpdateCsrf(response);
            if (response && response.success && response.retire) {
                storeShowRetirePreview(response.retire);
            } else {
                storeClearRetireSelection();
                const msg = (response && response.message) ? response.message : 'No se encontró ninguna nota de retiro pendiente con los datos ingresados.';
                $('#store-retire-lookup-error').text(msg).removeClass('d-none');
            }
        }, 'json').fail(function() {
            storeClearRetireSelection();
            $('#store-retire-lookup-error').text('Error de conexión con el servidor.').removeClass('d-none');
        }).always(function() {
            $('#store-retire-lookup-hint').addClass('d-none');
            $('#store-retire-lookup-btn').prop('disabled', false);
        });
    }

    $('#store-retire-lookup-btn').on('click', function() {
        storeLookupRetire();
    });

    $('#store-retire-document, #store-retire-code').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            storeLookupRetire();
        }
    });

    $('#store-retire-pay-btn').on('click', function() {
        if (!storeRetireSelected || !storeRetireSelected.id) {
            return;
        }

        const retire = storeRetireSelected;
        const amountFormatted = storeCurrency + ' ' + parseFloat(retire.amount).toFixed(2);

        Swal.fire({
            title: '¿Confirmar Pago en Efectivo?',
            html: '¿Confirmas que vas a entregar <strong>' + amountFormatted + '</strong> en efectivo al jugador <strong>' + (retire.player_name || '') + '</strong> (Cédula: ' + retire.document + ')?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Sí, pagar retiro',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            const $btn = $('#store-retire-pay-btn');
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando pago...');

            $.post('<?= site_url('store/payRetireSubmit'); ?>', storeAttachCsrf({
                retire_id: retire.id,
                code: retire.code,
                document: retire.document
            }), function(res) {
                storeUpdateCsrf(res);
                if (res && res.success) {
                    storeClearRetireSelection();
                    $('#store-retire-document').val('');
                    $('#store-retire-code').val('');

                    Swal.fire({
                        title: '¡Retiro Pagado!',
                        text: res.message || 'El retiro fue pagado exitosamente en efectivo.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });

                    if (typeof res.store_balance !== 'undefined' && res.store_balance !== null) {
                        storeUpdateBalance(res.store_balance);
                    }

                    storeRefreshRetires(1);
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: (res && res.message) ? res.message : 'No se pudo procesar el pago del retiro.',
                        icon: 'error'
                    });
                }
            }, 'json').fail(function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Hubo un problema al procesar la solicitud en el servidor.',
                    icon: 'error'
                });
            }).always(function() {
                $btn.prop('disabled', false).html('<i class="fa-duotone fa-solid fa-hand-holding-dollar me-1"></i> Confirmar y Pagar Retiro en Efectivo');
            });
        });
    });

    function storePrizesGetPage(page) {
        storeRefreshRetires(page);
    }

    $(function() {
        storeRefreshRetires(1);
    });
</script>
