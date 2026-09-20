<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? 0,
    'activeNav' => 'affiliates',
]) ?>

<?php
$storeUser = $store ?? $user ?? [];
$storeAffiliateLink = function_exists('bingo_store_affiliate_link') ? bingo_store_affiliate_link($storeUser) : site_url('signup');
$storeCode = $storeUser['referred_code'] ?? $storeUser['code'] ?? '';
$referredPlayers = $referredPlayers ?? [];
$referredCount = (int) ($referredCount ?? count($referredPlayers));
$appName = defined('APP_NAME') ? APP_NAME : 'Rey Bingo';
$whatsappShareText = rawurlencode("🎉 ¡Regístrate en {$appName} 🎱 y empieza a jugar!\n👉 Crea tu cuenta gratis con nuestro Punto de Venta aquí:\n{$storeAffiliateLink}");
$whatsappUrl = "https://api.whatsapp.com/send?text={$whatsappShareText}";
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
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-3" style="border-radius: 14px; background: linear-gradient(135deg, rgba(98,54,255,0.06) 0%, rgba(98,54,255,0.02) 100%); border: 1px solid rgba(98,54,255,0.18) !important;">
            <div class="row align-items-center g-3">
                <!-- QR Code Box -->
                <div class="col-12 col-md-auto text-center">
                    <div class="p-2 bg-white rounded-3 shadow-sm d-inline-block border">
                        <img src="<?= site_url('store/affiliateCode'); ?>" alt="Código QR Afiliados" class="img-fluid" style="width: 130px; height: 130px; object-fit: contain;">
                    </div>
                    <div class="mt-1">
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.72rem;">Escanea para registrarse</small>
                    </div>
                </div>

                <!-- Info & Full Link Box -->
                <div class="col-12 col-md">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary fw-semibold" style="font-size: 0.75rem;">
                            <i class="fa-duotone fa-solid fa-link me-1"></i> Tu Enlace Único de Afiliación
                        </span>
                        <?php if (! empty($storeCode)) : ?>
                        <span class="badge bg-light text-dark border fw-semibold" style="font-size: 0.75rem;">
                            Código: <strong class="text-primary"><?= esc($storeCode); ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>

                    <h6 class="fw-bold text-dark mb-1">Comparte este enlace con tus jugadores</h6>
                    <p class="text-muted small mb-2" style="font-size: 0.82rem; line-height: 1.4;">
                        Cualquier jugador que se registre a través de este link quedará automáticamente vinculado a tu Punto de Venta para todas sus recargas, retiros y comisiones GGR.
                    </p>

                    <!-- Caja con el enlace completo visible (sin cortes) -->
                    <div class="p-2 px-3 bg-white rounded border mb-2" style="word-break: break-all; word-wrap: break-word; font-family: monospace; font-size: 0.86rem; color: #4b2be0; background-color: #ffffff; border: 1px solid #dee2e6;">
                        <i class="fa-duotone fa-solid fa-globe me-1 text-muted"></i>
                        <span id="store-affiliate-link-text"><?= esc($storeAffiliateLink); ?></span>
                    </div>

                    <input type="hidden" id="store-affiliate-link-input" value="<?= esc($storeAffiliateLink); ?>">

                    <!-- Botones de Acción -->
                    <div class="d-flex flex-wrap gap-2 pt-1">
                        <button class="btn btn-primary btn-sm px-3" type="button" onclick="copyStoreAffiliateLinkMain();" style="background: #6236ff; border-color: #6236ff; font-weight: 600;">
                            <i class="fa-duotone fa-solid fa-copy me-1"></i> Copiar Enlace
                        </button>
                        <a href="<?= $whatsappUrl; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm px-3" style="background: #25D366; border-color: #25D366; font-weight: 600;">
                            <i class="fa-brands fa-whatsapp me-1"></i> Compartir por WhatsApp
                        </a>
                        <a href="<?= esc($storeAffiliateLink); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm px-3">
                            <i class="fa-duotone fa-solid fa-arrow-up-right-from-square me-1"></i> Probar Enlace
                        </a>
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
        const link = document.getElementById('store-affiliate-link-input')?.value || document.getElementById('store-affiliate-link-text')?.innerText;
        if (link) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(showCopySuccess, function() {
                    fallbackCopy(link);
                });
            } else {
                fallbackCopy(link);
            }
        }
    }

    function fallbackCopy(text) {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showCopySuccess();
    }

    function showCopySuccess() {
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
</script>

<?= view('store/partials/close') ?>
