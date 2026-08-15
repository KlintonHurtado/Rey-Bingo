<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? 0,
    'activeNav' => 'affiliates',
]) ?>

<?php
$storeUser = $store ?? $user ?? [];
$storeAffiliateLink = function_exists('bingo_store_affiliate_link') ? bingo_store_affiliate_link($storeUser) : site_url('signup');
$referredPlayers = $referredPlayers ?? [];
$referredCount = (int) ($referredCount ?? count($referredPlayers));
?>

<div class="card store-panel-card h-100" style="min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
    <div class="card-body p-3 store-movements-scroll-body" style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden;">
        <!-- Encabezado de la Sección -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="operator-panel-pane-icon" style="width: 38px; height: 38px; font-size: 1.15rem; background: rgba(98, 54, 255, 0.12); color: #6236ff; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                    <i class="fa-duotone fa-solid fa-users"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark" style="font-size: 1.1rem;">Panel de Afiliados a Jugadores</h5>
                    <p class="small text-muted mb-0" style="font-size: 0.80rem;">Comparte tu link o código QR para vincular jugadores a tu Punto de Venta y generar comisiones por sus actividades.</p>
                </div>
            </div>
            <div>
                <a href="<?= site_url('store/register'); ?>" target="_blank" class="btn btn-sm btn-primary" style="background: #6236ff; border-color: #6236ff; border-radius: 8px; padding: 6px 14px; font-size: 0.84rem;">
                    <i class="fa-duotone fa-solid fa-user-plus me-1"></i> Registrar Jugador Directamente
                </a>
            </div>
        </div>

        <!-- Tarjeta Principal de Enlace de Afiliado y Código QR -->
        <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 14px; background: linear-gradient(135deg, rgba(98,54,255,0.06) 0%, rgba(98,54,255,0.02) 100%); border: 1px solid rgba(98,54,255,0.18) !important;">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-3 text-center border-end-md">
                    <div class="p-2 bg-white rounded-3 shadow-sm d-inline-block border">
                        <img src="<?= site_url('store/affiliateCode'); ?>" alt="Código QR Afiliados" class="img-fluid" style="width: 140px; height: 140px; object-fit: contain;">
                    </div>
                    <div class="mt-1">
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.73rem;">Escanea para registrarse</small>
                    </div>
                </div>
                <div class="col-12 col-md-9">
                    <div class="ps-md-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary fw-semibold mb-2" style="font-size: 0.74rem;">
                            <i class="fa-duotone fa-solid fa-link me-1"></i> Tu Enlace Único de Afiliación
                        </span>
                        <h6 class="fw-bold text-dark mb-1">Comparte este enlace con tus jugadores</h6>
                        <p class="text-muted small mb-3" style="font-size: 0.82rem;">
                            Cualquier jugador que se registre a través de este link quedará automáticamente vinculado a tu Punto de Venta para todas sus recargas, retiros y comisiones GGR.
                        </p>

                        <div class="input-group mb-2" style="max-width: 650px;">
                            <span class="input-group-text bg-white text-muted"><i class="fa-duotone fa-solid fa-globe"></i></span>
                            <input type="text" class="form-control form-control-lg fw-semibold text-dark" id="store-affiliate-link-input" value="<?= esc($storeAffiliateLink); ?>" readonly style="font-size: 0.90rem; background: #fff;">
                            <button class="btn btn-primary px-3" type="button" onclick="copyStoreAffiliateLinkMain();" style="background: #6236ff; border-color: #6236ff;">
                                <i class="fa-duotone fa-solid fa-copy me-1"></i> Copiar Enlace
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Jugadores Vinculados -->
        <div class="card border-0 shadow-sm p-3" style="border-radius: 14px;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-duotone fa-solid fa-users text-primary me-1"></i> Jugadores Vinculados (<?= $referredCount; ?>)
                    </h6>
                    <small class="text-muted">Lista de jugadores registrados bajo tu enlace de afiliación</small>
                </div>
                <span class="badge bg-success py-1.5 px-2.5" style="font-size: 0.80rem;">
                    <?= $referredCount; ?> registrados
                </span>
            </div>

            <div class="store-table-wrap">
                <?= view('store/affiliate_referrals_list', [
                    'referredPlayers' => $referredPlayers,
                ]); ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function copyStoreAffiliateLinkMain() {
        const el = document.getElementById('store-affiliate-link-input');
        if (el) {
            el.select();
            document.execCommand('copy');
            if (typeof Toastify === 'function') {
                Toastify({
                    text: '¡Enlace de afiliado copiado al portapapeles!',
                    duration: 2500,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#198754' }
                }).showToast();
            } else {
                alert('¡Enlace de afiliado copiado al portapapeles!');
            }
        }
    }
</script>

<?= view('store/partials/close') ?>
