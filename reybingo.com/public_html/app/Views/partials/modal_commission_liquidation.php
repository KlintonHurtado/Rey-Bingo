<?php
$currency = systemGet('currency') ?? '$';
?>
<!-- Modal de Liquidación y Pago de Comisiones Mensuales -->
<div class="modal fade" id="modalCommissionLiquidation" tabindex="-1" aria-labelledby="modalCommissionLiquidationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header py-3 text-white" style="background: linear-gradient(135deg, #198754 0%, #157347 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="fs-4">
                        <i class="fa-duotone fa-solid fa-money-bill-transfer"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="modalCommissionLiquidationTitle">
                            Liquidación y Pago de Comisiones
                        </h6>
                        <small class="text-white-50" id="liq-period-label">Período de liquidación</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Indicador de Carga -->
                <div id="liq-modal-loading" class="text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="small text-muted mt-2">Cargando datos contables y bancarios...</p>
                </div>

                <!-- Contenido Principal de Liquidación -->
                <div id="liq-modal-content" class="d-none">
                    <!-- Fila de Identificación de Usuario -->
                    <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 12px; background: #ffffff;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" id="liq-avatar-icon" style="width: 48px; height: 48px; background: #198754; font-size: 1.3rem;">
                                    <i class="fa-duotone fa-solid fa-user-tie"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" id="liq-user-name">-</h6>
                                    <div class="small text-muted">
                                        <span id="liq-user-username">@-</span> &bull; Cédula: <span id="liq-user-document" class="fw-semibold">-</span> &bull; Código: <span id="liq-user-code" class="fw-semibold text-primary">-</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-primary fs-6 py-1 px-3" id="liq-user-role">Operador</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Tarjetas de Resumen de Comisiones Acumuladas -->
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: #ffffff; border-left: 4px solid #198754 !important;">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Comisión GGR</small>
                                <strong class="text-success fs-6"><?= esc($currency); ?> <span id="liq-card-ggr">0.00</span></strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: #ffffff; border-left: 4px solid #0dcaf0 !important;">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Comisión Recargas</small>
                                <strong class="text-info fs-6"><?= esc($currency); ?> <span id="liq-card-recharges">0.00</span></strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: #ffffff; border-left: 4px solid #ffc107 !important;">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Comisión Retiros</small>
                                <strong class="text-warning text-dark fs-6"><?= esc($currency); ?> <span id="liq-card-retires">0.00</span></strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: linear-gradient(135deg, rgba(98,54,255,0.12) 0%, rgba(98,54,255,0.04) 100%); border-left: 4px solid #6236ff !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.75rem; color: #6236ff !important;">Total Comisiones</small>
                                <strong class="fs-5" style="color: #6236ff;"><?= esc($currency); ?> <span id="liq-card-total">0.00</span></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta de Datos Bancarios del Beneficiario -->
                    <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 12px; background: #ffffff; border: 1px solid rgba(13,110,253,0.15) !important;">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                            <i class="fa-duotone fa-solid fa-building-columns text-primary"></i> Datos Bancarios Registrados para Transferencia:
                        </h6>
                        <div class="row g-2 small text-dark">
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Banco:</span>
                                <strong id="liq-bank-name" class="fs-6 text-dark">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Tipo de Cuenta:</span>
                                <strong id="liq-bank-type" class="text-dark">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Número de Cuenta:</span>
                                <strong id="liq-bank-account" class="fs-6 font-monospace text-primary">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block">Titular / Teléfono:</span>
                                <span id="liq-bank-holder" class="fw-semibold text-dark">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Liquidación -->
                    <form id="formCommissionLiquidation" onsubmit="return false;">
                        <input type="hidden" id="liq-input-user-id" value="0">
                        
                        <div class="card border-0 shadow-sm p-3" style="border-radius: 12px; background: #ffffff;">
                            <div class="row g-3">
                                <!-- Modo de Liquidación -->
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.88rem;">
                                        Método de Pago / Liquidación:
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="form-check p-3 border rounded h-100" style="cursor: pointer; background: rgba(25,135,84,0.04); border-color: rgba(25,135,84,0.3) !important;">
                                                <input class="form-check-input" type="radio" name="liq_settlement_type" id="liq-type-transfer" value="bank_transfer" checked>
                                                <label class="form-check-label ms-2" for="liq-type-transfer" style="cursor: pointer;">
                                                    <strong class="d-block text-success"><i class="fa-duotone fa-solid fa-money-check-dollar me-1"></i> Transferencia Bancaria</strong>
                                                    <small class="text-muted">Realizarás transferencia a su cuenta bancaria. Marca las comisiones como pagadas y emite comprobante.</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check p-3 border rounded h-100" style="cursor: pointer; background: rgba(13,110,253,0.04); border-color: rgba(13,110,253,0.3) !important;">
                                                <input class="form-check-input" type="radio" name="liq_settlement_type" id="liq-type-wallet" value="credit_balance">
                                                <label class="form-check-label ms-2" for="liq-type-wallet" style="cursor: pointer;">
                                                    <strong class="d-block text-primary"><i class="fa-duotone fa-solid fa-wallet me-1"></i> Acreditar a Saldo Recargable</strong>
                                                    <small class="text-muted">Suma el dinero a su saldo de recargas para que pueda seguir vendiendo a jugadores.</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Monto a Liquidar -->
                                <div class="col-md-6">
                                    <label for="liq-input-amount" class="form-label fw-bold text-dark mb-1" style="font-size: 0.88rem;">Monto a Liquidar:</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold"><?= esc($currency); ?></span>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-lg fw-bold text-success" id="liq-input-amount" value="0" placeholder="0.00" autocomplete="off" required>
                                    </div>
                                    <small class="text-danger fw-semibold">Inicia en 0. Ingresa manualmente el monto exacto a liquidar para evitar errores.</small>
                                </div>

                                <!-- Referencia de Transferencia -->
                                <div class="col-md-6">
                                    <label for="liq-input-reference" class="form-label fw-bold text-dark mb-1" style="font-size: 0.88rem;">Ref. / N° de Comprobante:</label>
                                    <input type="text" class="form-control form-control-lg" id="liq-input-reference" placeholder="Ej: TRF-982347..." autocomplete="off">
                                    <small class="text-muted">Opcional para comprobante de banco.</small>
                                </div>

                                <!-- Observaciones -->
                                <div class="col-12">
                                    <label for="liq-input-notes" class="form-label fw-bold text-dark mb-1" style="font-size: 0.88rem;">Notas u Observaciones (Opcional):</label>
                                    <input type="text" class="form-control" id="liq-input-notes" placeholder="Ej: Liquidación mensual correspondiente al período..." autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-success px-4 py-2 fw-bold" id="btn-submit-commission-liquidation" onclick="submitCommissionLiquidation();">
                                <i class="fa-duotone fa-solid fa-badge-check me-1"></i> Confirmar Liquidación y Pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    let liqSuccessCallback = null;

    window.openCommissionLiquidationModal = function(userId, callback) {
        liqSuccessCallback = callback || null;
        const $modal = $('#modalCommissionLiquidation');
        $('#liq-modal-loading').removeClass('d-none');
        $('#liq-modal-content').addClass('d-none');
        $('#liq-input-user-id').val(userId);
        $('#btn-submit-commission-liquidation').prop('disabled', false);

        $modal.modal('show');

        $.ajax({
            url: '<?= site_url('users/getCommissionLiquidationInfo/'); ?>' + userId,
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    $('#liq-modal-loading').addClass('d-none');
                    $('#liq-modal-content').removeClass('d-none');

                    $('#liq-period-label').text(res.period_label || 'Período actual');
                    $('#liq-user-name').text(res.name || 'Usuario');
                    $('#liq-user-username').text('@' + (res.username || ''));
                    $('#liq-user-document').text(res.document || 'No registrado');
                    $('#liq-user-code').text(res.code || '-');
                    $('#liq-user-role').text(res.role_label || 'Punto de Venta');

                    if (res.is_operator) {
                        $('#liq-user-role').removeClass('bg-info').addClass('bg-primary');
                        $('#liq-avatar-icon').html('<i class="fa-duotone fa-solid fa-user-tie"></i>').css('background', '#6236ff');
                    } else {
                        $('#liq-user-role').removeClass('bg-primary').addClass('bg-info text-dark');
                        $('#liq-avatar-icon').html('<i class="fa-duotone fa-solid fa-store"></i>').css('background', '#198754');
                    }

                    // Métricas
                    $('#liq-card-ggr').text(parseFloat(res.ggr_commission || 0).toFixed(2));
                    $('#liq-card-recharges').text(parseFloat(res.recharge_commission || 0).toFixed(2));
                    $('#liq-card-retires').text(parseFloat(res.withdraw_commission || 0).toFixed(2));
                    $('#liq-card-total').text(parseFloat(res.total_pending_commissions || 0).toFixed(2));

                    // Datos Bancarios
                    $('#liq-bank-name').text(res.bank_name || 'No registrado');
                    $('#liq-bank-type').text(res.account_type || 'No especificado');
                    $('#liq-bank-account').text(res.account_number || 'No registrado');
                    $('#liq-bank-holder').text((res.name || '-') + (res.phone ? ' &bull; Tel: ' + res.phone : ''));

                    // Formulario: monto inicia en 0 para evitar liquidaciones accidentales
                    $('#liq-input-amount').val('0');
                    $('#liq-input-reference').val('');
                    $('#liq-input-notes').val('');
                    $('#liq-type-transfer').prop('checked', true);
                } else {
                    $modal.modal('hide');
                    Toastify({
                        text: (res && res.message) ? res.message : 'Error al obtener información de comisiones.',
                        duration: 3500,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' }
                    }).showToast();
                }
            },
            error: function() {
                $modal.modal('hide');
                Toastify({
                    text: 'Error de conexión con el servidor.',
                    duration: 3500,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
            }
        });
    };

    window.submitCommissionLiquidation = function() {
        const userId = $('#liq-input-user-id').val();
        const amount = parseFloat($('#liq-input-amount').val() || 0);
        const settlementType = $('input[name="liq_settlement_type"]:checked').val();
        const reference = $('#liq-input-reference').val().trim();
        const notes = $('#liq-input-notes').val().trim();

        if (amount <= 0 || isNaN(amount)) {
            Toastify({
                text: 'Por favor ingrese un monto válido mayor a 0.',
                duration: 3000,
                gravity: 'top',
                position: 'right',
                style: { background: '#dc3545' }
            }).showToast();
            $('#liq-input-amount').focus();
            return;
        }

        const typeLabel = settlementType === 'credit_balance'
            ? 'LIQUIDACION COMISIONES (acreditar a saldo recargable)'
            : 'LIQUIDACION COMISIONES (transferencia bancaria)';

        Swal.fire({
            title: '¿Confirmar Liquidación de Comisiones?',
            html: `
                <div class="text-start p-2">
                    <p class="mb-2"><span class="badge bg-success">LIQUIDACION COMISIONES</span></p>
                    <p class="mb-1"><strong>Monto a Liquidar:</strong> <span class="text-success fs-5 fw-bold"><?= esc($currency); ?> ${amount.toFixed(2)}</span></p>
                    <p class="mb-1"><strong>Método:</strong> ${typeLabel}</p>
                    ${reference ? `<p class="mb-0"><strong>Referencia:</strong> ${reference}</p>` : ''}
                    <p class="small text-muted mt-2 mb-0">Esto no es una acreditación normal de saldo: es un pago/liquidación de comisiones.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, Liquidar y Pagar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
        }).then((result) => {
            if (result.isConfirmed) {
                const $btn = $('#btn-submit-commission-liquidation');
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando...');

                $.ajax({
                    url: '<?= site_url('users/settleUserCommissionSubmit'); ?>',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        amount: amount,
                        settlement_type: settlementType,
                        reference: reference,
                        notes: notes,
                        <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
                    },
                    dataType: 'json',
                    success: function(res) {
                        $btn.prop('disabled', false).html('<i class="fa-duotone fa-solid fa-badge-check me-1"></i> Confirmar Liquidación y Pago');
                        if (res && res.success) {
                            $('#modalCommissionLiquidation').modal('hide');
                            Swal.fire({
                                title: '¡Liquidación Exitosa!',
                                text: res.message || 'La liquidación ha sido registrada exitosamente.',
                                icon: 'success',
                                confirmButtonColor: '#198754'
                            });

                            if (typeof liqSuccessCallback === 'function') {
                                liqSuccessCallback(res);
                            }
                        } else {
                            Toastify({
                                text: (res && res.message) ? res.message : 'Error al procesar la liquidación.',
                                duration: 4000,
                                gravity: 'top',
                                position: 'right',
                                style: { background: '#dc3545' }
                            }).showToast();
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html('<i class="fa-duotone fa-solid fa-badge-check me-1"></i> Confirmar Liquidación y Pago');
                        Toastify({
                            text: 'Error en la respuesta del servidor.',
                            duration: 3500,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#dc3545' }
                        }).showToast();
                    }
                });
            }
        });
    };
</script>
