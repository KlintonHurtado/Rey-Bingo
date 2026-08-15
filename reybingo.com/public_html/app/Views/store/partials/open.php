<?php
helper(['bingo', 'affiliate_ggr']);
$isOperatorActing = $isOperatorActing ?? (bingo_is_operator() && bingo_get_acting_store_id() > 0);
$storeUser = $user ?? null;
if (!$storeUser || empty($storeUser)) {
    $storeId = bingo_get_effective_store_id();
    if ($storeId > 0) {
        $storeUser = (new \App\Models\UsersModel())->find($storeId);
    }
}
$storeUser = $storeUser ?? [];
$storeAffiliateLink = function_exists('bingo_store_affiliate_link') ? bingo_store_affiliate_link($storeUser) : site_url('signup');
$storeCode = $storeUser['code'] ?? $storeUser['username'] ?? '';
?>
<a class="btn btn-small btn-profile" href="<?= site_url('profile'); ?>"><img src="<?= $imagePath ?>" alt="img"></a>

<a class="btn btn-small btn-lock" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="store-panel-fit">
    <div class="store-panel-shell">
        <aside class="store-panel-sidebar">
            <div class="card store-panel-card store-panel-sidebar-card">
                <div class="card-body">
                    <?php $storeDisplayName = trim(bingo_store_display_name($storeUser)); ?>
                    <h5 class="store-sidebar-title mb-1">
                        <i class="fa-duotone fa-solid fa-store"></i>
                        <?= $storeDisplayName !== '' ? esc($storeDisplayName) : translate('store panel'); ?>
                    </h5>
                    <?php if ($storeDisplayName !== '') : ?>
                        <p class="store-sidebar-subtitle small text-muted mb-3"><?= translate('store panel'); ?></p>
                    <?php else : ?>
                        <div class="mb-3"></div>
                    <?php endif; ?>
<?php
$currency = systemGet('currency') ?? '$';
?>
                    <div class="store-sidebar-stats">
                        <div class="store-balance-sidebar">
                            <span class="store-balance-label"><?= translate('available store balance'); ?></span>
                            <strong class="store-balance-amount"><?= $currency; ?> <?= number_format((float) ($walletSummary['recharge'] ?? 0), 2) ?></strong>
                        </div>
                    </div>

                    <?php if ($isOperatorActing) : ?>
                        <a
                            href="<?= site_url('operator/leaveStore'); ?>"
                            class="store-panel-back-btn"
                            title="<?= translate('back to operator panel'); ?>"
                        >
                            <i class="fa-duotone fa-solid fa-arrow-left"></i>
                            <span><?= translate('go back'); ?></span>
                        </a>
                    <?php endif; ?>

                    <div class="store-panel-sidebar-divider"></div>

                    <ul class="nav store-panel-tabs store-panel-nav">
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'recharge' ? 'active' : '' ?>" href="<?= site_url('store/recharge'); ?>">
                                <i class="fa-duotone fa-solid fa-mobile-screen"></i>
                                <span><?= translate('recharge player by document'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'register' ? 'active' : '' ?>" href="<?= site_url('store/register'); ?>">
                                <i class="fa-duotone fa-solid fa-user-plus"></i>
                                <span>Afiliar / Registrar Jugador</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'affiliate' ? 'active' : '' ?>" href="<?= site_url('store/affiliate'); ?>">
                                <i class="fa-duotone fa-solid fa-percent"></i>
                                <span><?= translate('store commissions'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'prizes' ? 'active' : '' ?>" href="<?= site_url('store/prizes'); ?>">
                                <i class="fa-duotone fa-solid fa-money-bill-transfer"></i>
                                <span>Pagar Retiros</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'movements' ? 'active' : '' ?>" href="<?= site_url('store/movements'); ?>">
                                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i>
                                <span>Movimientos</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Widget Link de Afiliados a Jugadores en Sidebar -->
                    <div class="store-sidebar-affiliate-box mt-3 p-2.5" style="background: rgba(98, 54, 255, 0.05); border: 1px solid rgba(98, 54, 255, 0.15); border-radius: 12px;">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <span class="fw-bold text-dark" style="font-size: 0.76rem;">
                                <i class="fa-duotone fa-solid fa-link text-primary me-1"></i> Link de Afiliados
                            </span>
                            <button type="button" class="btn btn-link p-0 text-primary" data-bs-toggle="modal" data-bs-target="#modalStoreAffiliateLinkSidebar" title="Ver Código QR" style="font-size: 0.78rem; text-decoration: none;">
                                <i class="fa-duotone fa-solid fa-qrcode"></i> QR
                            </button>
                        </div>
                        <p class="small text-muted mb-2" style="font-size: 0.70rem; line-height: 1.25;">Comparte este link para registrar y afiliar jugadores a tu tienda:</p>
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" class="form-control form-control-sm" id="sidebar-store-affiliate-link" value="<?= esc($storeAffiliateLink); ?>" readonly style="font-size: 0.72rem; background: #fff;">
                            <button class="btn btn-primary btn-sm" type="button" onclick="copySidebarStoreAffiliateLink();" title="Copiar enlace" style="font-size: 0.72rem; padding: 3px 8px;">
                                <i class="fa-duotone fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Modal QR de Afiliados en Sidebar -->
        <div class="modal fade" id="modalStoreAffiliateLinkSidebar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark"><i class="fa-duotone fa-solid fa-qrcode text-primary me-2"></i> Mi Enlace de Afiliado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-4">
                        <p class="text-muted small mb-3">Comparte este código o código QR para que los jugadores se registren bajo tu red.</p>
                        <div class="mb-3">
                            <img src="<?= site_url('store/affiliateCode'); ?>" alt="QR" class="img-fluid border p-2 rounded shadow-sm" style="max-width: 180px;">
                        </div>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="modal-sidebar-affiliate-url-input" value="<?= esc($storeAffiliateLink); ?>" readonly>
                            <button class="btn btn-primary" type="button" onclick="copyModalSidebarAffiliateLink();">
                                <i class="fa-duotone fa-solid fa-copy me-1"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            function copySidebarStoreAffiliateLink() {
                const el = document.getElementById('sidebar-store-affiliate-link');
                if (el) {
                    el.select();
                    document.execCommand('copy');
                    if (typeof Toastify === 'function') {
                        Toastify({
                            text: '¡Link de afiliado copiado!',
                            duration: 2500,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#198754' }
                        }).showToast();
                    } else {
                        alert('¡Link de afiliado copiado al portapapeles!');
                    }
                }
            }

            function copyModalSidebarAffiliateLink() {
                const el = document.getElementById('modal-sidebar-affiliate-url-input');
                if (el) {
                    el.select();
                    document.execCommand('copy');
                    if (typeof Toastify === 'function') {
                        Toastify({
                            text: '¡Link de afiliado copiado!',
                            duration: 2500,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#198754' }
                        }).showToast();
                    } else {
                        alert('¡Link de afiliado copiado al portapapeles!');
                    }
                }
            }
        </script>

        <main class="store-panel-page-content">
