<a class="btn btn-small btn-home" href="<?= site_url('games'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

<style>
/* ─── Wrapper ─────────────────────────────────────────── */
.kyc-portal-wrapper {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 68px 16px 30px 16px;
    display: flex;
    flex-direction: column;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.kyc-portal-wrapper::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
}
/* ─── Flash alert ─────────────────────────────────────── */
.kyc-flash {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(220, 255, 230, 0.95);
    border: 1.5px solid #48bb78;
    border-radius: 10px;
    padding: 10px 16px;
    margin-bottom: 14px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #276749;
    backdrop-filter: blur(6px);
}
.kyc-flash .kyc-flash-close {
    margin-left: auto;
    cursor: pointer;
    color: #276749;
    font-size: 1rem;
    background: none; border: none; padding: 0;
}
/* ─── Panel container ─────────────────────────────────── */
.kyc-panel {
    background: rgba(60, 20, 140, 0.55);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.35);
    border: 1.5px solid rgba(255,255,255,0.15);
    backdrop-filter: blur(16px);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
/* ─── Tabs ─────────────────────────────────────────────── */
.kyc-tabs {
    display: flex;
    border-bottom: 1.5px solid rgba(255,255,255,0.12);
    background: rgba(50, 15, 120, 0.45);
}
.kyc-tab {
    flex: 1;
    padding: 14px 10px;
    font-size: 0.9rem;
    font-weight: 600;
    color: rgba(255,255,255,0.55);
    text-align: center;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
}
.kyc-tab:hover { color: #fff; }
.kyc-tab.active {
    color: #fff;
    border-bottom: 3px solid #ff3fa4;
    background: rgba(255,255,255,0.07);
}
.kyc-tab-count {
    display: inline-block;
    background: rgba(255,255,255,0.15);
    color: #fff;
    border-radius: 20px;
    padding: 1px 9px;
    font-size: 0.78rem;
    font-weight: 700;
    margin-left: 5px;
}
.kyc-tab.active .kyc-tab-count {
    background: #ff3fa4;
    color: #fff;
}
/* ─── Search bar ─────────────────────────────────────── */
.kyc-search-bar {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    background: rgba(40, 10, 100, 0.3);
}
.kyc-search-input {
    width: 100%;
    max-width: 420px;
    border: 1.5px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 7px 14px 7px 38px;
    font-size: 0.87rem;
    outline: none;
    background: rgba(255,255,255,0.12) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 12px center;
    color: #fff;
    transition: border-color 0.2s;
}
.kyc-search-input::placeholder { color: rgba(255,255,255,0.5); }
.kyc-search-input:focus { border-color: #ff3fa4; background: rgba(255,255,255,0.18); }
/* ─── Content area ───────────────────────────────────── */
.kyc-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: rgba(30, 5, 80, 0.25);
}
/* ─── Empty state ─────────────────────────────────────── */
.kyc-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 56px 20px;
    text-align: center;
}
.kyc-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    color: rgba(255,255,255,0.8);
    font-size: 1.7rem;
}
.kyc-empty h5 {
    font-size: 1.2rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 8px;
}
.kyc-empty p {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.55);
    margin: 0;
}
/* ─── Cards grid ─────────────────────────────────────── */
.kyc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 14px;
}
.kyc-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 3px 14px rgba(0,0,0,0.10);
    display: flex;
    flex-direction: column;
    border: 1.5px solid #f0f0f0;
    transition: transform 0.15s, box-shadow 0.15s;
}
.kyc-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0,0,0,0.15);
}
.kyc-card-top {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 11px 6px 11px;
}
.kyc-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: #ede8ff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem; color: #6c3cdb; flex-shrink: 0;
}
.kyc-avatar img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.kyc-user-info { flex: 1; min-width: 0; }
.kyc-user-name {
    font-weight: 700; font-size: 0.86rem; color: #1a1a2e;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.kyc-user-code { font-size: 0.74rem; color: #6c757d; }
.kyc-status-badge {
    display: inline-block; font-size: 0.68rem; font-weight: 700;
    border-radius: 20px; padding: 2px 9px; margin: 0 11px 7px 11px;
}
.kyc-status-pending  { background: #fff3cd; color: #856404; }
.kyc-status-verified { background: #d4edda; color: #155724; }
.kyc-status-rejected { background: #f8d7da; color: #721c24; }
.kyc-doc-toggle {
    font-size: 0.72rem; color: #888; cursor: pointer;
    padding: 0 11px 7px 11px; display: flex; align-items: center; gap: 4px;
    background: none; border: none; text-align: left;
}
.kyc-doc-toggle:hover { color: #6c3cdb; }
.kyc-extra-details { font-size: 0.75rem; color: #555; padding: 0 11px 7px 11px; }
.kyc-docs-row { display: flex; gap: 6px; padding: 0 10px 8px 10px; }
.kyc-doc-thumb { flex: 1; text-align: center; }
.kyc-doc-thumb-label { font-size: 0.67rem; font-weight: 600; color: #777; margin-bottom: 3px; }
.kyc-doc-thumb img {
    width: 100%; height: 66px; object-fit: cover;
    border-radius: 6px; border: 1.5px solid #e0e0e0;
    cursor: zoom-in; transition: border-color 0.2s;
}
.kyc-doc-thumb img:hover { border-color: #6c3cdb; }
.kyc-obs-area { padding: 0 10px 4px 10px; }
.kyc-obs-area textarea {
    font-size: 0.75rem; border-radius: 8px; resize: none;
    border: 1.5px solid #e0e0e0; padding: 5px 8px; width: 100%;
}
.kyc-obs-area textarea:focus { border-color: #6c3cdb; box-shadow: none; outline: none; }
.kyc-card-actions { display: flex; gap: 6px; padding: 6px 10px 10px 10px; margin-top: auto; }
.kyc-card-actions .btn { flex: 1; font-size: 0.78rem; font-weight: 700; padding: 6px 4px; border-radius: 8px; }
/* hide inactive tab panels */
.kyc-tab-panel { display: none; }
.kyc-tab-panel.active { display: block; }
</style>

<div class="kyc-portal-wrapper">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="kyc-flash" id="kycFlash">
            <i class="fa-solid fa-circle-check"></i>
            <?= session()->getFlashdata('success'); ?>
            <button class="kyc-flash-close" onclick="document.getElementById('kycFlash').remove()"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <div class="kyc-panel">

        <!-- TABS -->
        <div class="kyc-tabs" role="tablist">
            <button class="kyc-tab active" onclick="switchTab('pending', this)">
                Pendientes <span class="kyc-tab-count"><?= count($pending); ?></span>
            </button>
            <button class="kyc-tab" onclick="switchTab('verified', this)">
                Aprobadas <span class="kyc-tab-count"><?= count($verified); ?></span>
            </button>
            <button class="kyc-tab" onclick="switchTab('rejected', this)">
                Rechazadas <span class="kyc-tab-count"><?= count($rejected); ?></span>
            </button>
        </div>

        <!-- SEARCH BAR -->
        <div class="kyc-search-bar">
            <input type="text" id="kycSearchInput" class="kyc-search-input" placeholder="Buscar por nombre..." oninput="filterCards(this.value)">
        </div>

        <!-- CONTENT -->
        <div class="kyc-content">

            <?php
                $groups = [
                    'pending'  => $pending,
                    'verified' => $verified,
                    'rejected' => $rejected,
                ];
                $emptyMessages = [
                    'pending'  => 'No hay solicitudes pendientes en este momento.',
                    'verified' => 'No hay solicitudes aprobadas aún.',
                    'rejected' => 'No hay solicitudes rechazadas.',
                ];
            ?>

            <?php foreach ($groups as $tabKey => $users): ?>
                <div class="kyc-tab-panel <?= $tabKey === 'pending' ? 'active' : ''; ?>" id="tab-<?= $tabKey; ?>">

                    <?php if (empty($users)): ?>
                        <div class="kyc-empty">
                            <div class="kyc-empty-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <h5><?= $emptyMessages[$tabKey]; ?></h5>
                            <p>Utilice los filtros superiores para refinar la búsqueda</p>
                        </div>
                    <?php else: ?>
                        <div class="kyc-grid" id="grid-<?= $tabKey; ?>">
                            <?php foreach ($users as $u): ?>
                                <div class="kyc-card" data-name="<?= strtolower(esc($u['firstname'] . ' ' . $u['lastname'])); ?>">
                                    <!-- Avatar + Nombre -->
                                    <div class="kyc-card-top">
                                        <div class="kyc-avatar">
                                            <?php $avatarPath = WRITEPATH . 'uploads/users/' . ($u['image'] ?? ''); ?>
                                            <?php if (!empty($u['image']) && file_exists($avatarPath)): ?>
                                                <img src="<?= site_url('uploads/users/' . $u['image']); ?>" alt="avatar">
                                            <?php else: ?>
                                                <i class="fa-duotone fa-solid fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="kyc-user-info">
                                            <div class="kyc-user-name" title="<?= esc($u['firstname'] . ' ' . $u['lastname']); ?>">
                                                <?= esc($u['firstname'] . ' ' . $u['lastname']); ?>
                                            </div>
                                            <div class="kyc-user-code"><?= esc($u['code']); ?></div>
                                        </div>
                                    </div>

                                    <!-- Estado badge -->
                                    <span class="kyc-status-badge kyc-status-<?= $tabKey; ?>">
                                        <?php if ($tabKey === 'pending'): ?>
                                            <i class="fa-solid fa-clock me-1"></i> Pendiente
                                        <?php elseif ($tabKey === 'verified'): ?>
                                            <i class="fa-solid fa-check me-1"></i> Aprobado
                                        <?php else: ?>
                                            <i class="fa-solid fa-times me-1"></i> Rechazado
                                        <?php endif; ?>
                                    </span>

                                    <!-- Toggle detalles -->
                                    <button class="kyc-doc-toggle" onclick="toggleDetails(this)">
                                        <i class="fa-solid fa-file-id-card"></i> Ver detalles del documento
                                    </button>
                                    <div class="kyc-extra-details d-none">
                                        <div><i class="fa-solid fa-envelope me-1"></i> <?= esc($u['email']); ?></div>
                                        <?php if (!empty($u['document'])): ?>
                                            <div><i class="fa-solid fa-id-card me-1"></i> Doc: <?= esc($u['document']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($u['kyc_observations'])): ?>
                                            <div class="mt-1"><i class="fa-solid fa-comment-dots me-1"></i> <?= esc($u['kyc_observations']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Imágenes -->
                                    <div class="kyc-docs-row">
                                        <div class="kyc-doc-thumb">
                                            <div class="kyc-doc-thumb-label">Frente</div>
                                            <a href="javascript:void(0);" onclick="showImageModal('<?= site_url('uploads/kyc/' . $u['kyc_front']); ?>', 'Frente del documento')">
                                                <img src="<?= site_url('uploads/kyc/' . $u['kyc_front']); ?>" alt="Frente">
                                            </a>
                                        </div>
                                        <div class="kyc-doc-thumb">
                                            <div class="kyc-doc-thumb-label">Reverso</div>
                                            <a href="javascript:void(0);" onclick="showImageModal('<?= site_url('uploads/kyc/' . $u['kyc_back']); ?>', 'Reverso del documento')">
                                                <img src="<?= site_url('uploads/kyc/' . $u['kyc_back']); ?>" alt="Reverso">
                                            </a>
                                        </div>
                                        <?php if (! empty($u['kyc_selfie'])): ?>
                                        <div class="kyc-doc-thumb">
                                            <div class="kyc-doc-thumb-label">Selfie</div>
                                            <a href="javascript:void(0);" onclick="showImageModal('<?= site_url('uploads/kyc/' . $u['kyc_selfie']); ?>', 'Selfie con documento en la barbilla')">
                                                <img src="<?= site_url('uploads/kyc/' . $u['kyc_selfie']); ?>" alt="Selfie">
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Formulario (solo visible en Pendientes) -->
                                    <?php if ($tabKey === 'pending'): ?>
                                    <form action="<?= site_url('kycAdmin/review/' . $u['id']); ?>" method="post">
                                        <?= csrf_field(); ?>
                                        <div class="kyc-obs-area">
                                            <textarea name="kyc_observations" rows="2" placeholder="Observaciones o motivo de rechazo..."></textarea>
                                        </div>
                                        <div class="kyc-card-actions">
                                            <button type="submit" name="action" value="verified" class="btn btn-success">
                                                <i class="fa-solid fa-check me-1"></i>Aprobar
                                            </button>
                                            <button type="submit" name="action" value="rejected" class="btn btn-danger">
                                                <i class="fa-solid fa-times me-1"></i>Rechazar
                                            </button>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- empty search result placeholder -->
                        <div class="kyc-empty d-none" id="empty-search-<?= $tabKey; ?>">
                            <div class="kyc-empty-icon">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <h5>Sin resultados</h5>
                            <p>No se encontró ningún usuario con ese nombre</p>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>

        </div><!-- /kyc-content -->
    </div><!-- /kyc-panel -->
</div><!-- /kyc-portal-wrapper -->

<!-- Modal imagen -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="imageModalLabel">Documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="close"><i class="fa-duotone fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body text-center p-4">
        <img id="modalImage" src="" alt="Vista previa" class="img-fluid rounded shadow-sm" style="max-height: 80vh;">
      </div>
    </div>
  </div>
</div>

<script>
// ── Tab switching ────────────────────────────────────────
var currentTab = 'pending';
function switchTab(key, btn) {
    document.querySelectorAll('.kyc-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.kyc-tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + key).classList.add('active');
    currentTab = key;
    document.getElementById('kycSearchInput').value = '';
    filterCards('');
}

// ── Search / filter ──────────────────────────────────────
function filterCards(query) {
    var q = query.toLowerCase().trim();
    var panel = document.getElementById('tab-' + currentTab);
    if (!panel) return;
    var grid  = panel.querySelector('.kyc-grid');
    var emptySearch = panel.querySelector('[id^="empty-search-"]');
    if (!grid) return;
    var cards = grid.querySelectorAll('.kyc-card');
    var visible = 0;
    cards.forEach(function(card) {
        var name = card.getAttribute('data-name') || '';
        if (!q || name.includes(q)) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });
    if (emptySearch) {
        emptySearch.classList.toggle('d-none', visible > 0);
    }
}

// ── Toggle detail rows ───────────────────────────────────
function toggleDetails(btn) {
    var card = btn.closest('.kyc-card');
    var details = card.querySelector('.kyc-extra-details');
    var isHidden = details.classList.contains('d-none');
    details.classList.toggle('d-none', !isHidden);
    btn.innerHTML = isHidden
        ? '<i class="fa-solid fa-chevron-up"></i> Ocultar detalles'
        : '<i class="fa-solid fa-file-id-card"></i> Ver detalles del documento';
}

// ── Image modal ──────────────────────────────────────────
function showImageModal(src, title) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModalLabel').innerText = title;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}
</script>
