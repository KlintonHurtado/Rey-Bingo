<?php
$user = $user ?? [];
$imagePath = $imagePath ?? site_url('assets/img/avatar.jpg');
$pushSubscriptionsCount = $pushSubscriptionsCount ?? 0;
$activeGamesCount = $activeGamesCount ?? 0;
$templates = $templates ?? [];
$notifications = $notifications ?? [];
$users = $users ?? [];
?>

<div class="admin-stores-scroll">
<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => 'notifications',
    'showHome' => true,
    'showStatistics' => false,
    'showUsers' => false,
]) ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>" title="Cerrar sesión">
    <i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i>
</a>

<div class="container admin-stores-page admin-notifications-page pb-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-11">

            <!-- Encabezado Principal y KPIs -->
            <div class="card mb-3 admin-stores-card border-0 shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 border-bottom pb-3">
                        <div>
                            <h4 class="mb-1 text-dark fw-bold d-flex align-items-center gap-2">
                                <i class="fa-duotone fa-solid fa-bell text-primary"></i>
                                Centro de Notificaciones
                            </h4>
                            <p class="text-muted small mb-0">
                                Personaliza, crea y guarda plantillas. Envía notificaciones Push a móviles y avisos en pantalla en vivo a los jugadores en tiempo real.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill">
                                <i class="fa-solid fa-bolt"></i> Live Realtime (Soketi)
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill">
                                <i class="fa-solid fa-mobile-screen-button"></i> <?= (int)$pushSubscriptionsCount ?> Dispositivos Push
                            </span>
                            <?php if ($activeGamesCount > 0) : ?>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill">
                                    <i class="fa-solid fa-gamepad text-warning"></i> <?= (int)$activeGamesCount ?> Partidas en Vivo
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contenedor para mensajes de respuesta -->
                    <div id="notif-alert-container"></div>

                    <!-- Fila dividida: Formulario (Izquierda) y Vista Previa + Plantillas (Derecha) -->
                    <div class="row g-4 mt-1">

                        <!-- COLUMNA IZQUIERDA: Formulario de Redacción y Envío -->
                        <div class="col-lg-7">
                            <div class="p-3 p-md-4 rounded-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <h5 class="text-dark fw-bold mb-0 d-flex align-items-center gap-2">
                                        <i class="fa-duotone fa-solid fa-paper-plane text-primary"></i>
                                        Redactar Notificación
                                    </h5>
                                    <span class="badge bg-primary-subtle text-primary fw-semibold small">Cualquier Texto</span>
                                </div>

                                <form id="form-send-notification" autocomplete="off">
                                    <?= csrf_field() ?>

                                    <!-- Selector Rápido de Plantillas -->
                                    <div class="mb-3">
                                        <label class="form-label text-dark small fw-semibold d-flex justify-content-between">
                                            <span><i class="fa-duotone fa-solid fa-bookmark text-warning me-1"></i> Cargar Plantilla Guardada:</span>
                                            <a href="#saved-templates-section" class="text-primary text-decoration-none small">Ver guardadas</a>
                                        </label>
                                        <select class="form-select" id="quick-template-select">
                                            <option value="">-- Selecciona una plantilla para rellenar rápido --</option>
                                            <?php foreach ($templates as $tmpl) : ?>
                                                <option 
                                                    value="<?= $tmpl['id'] ?>" 
                                                    data-title="<?= esc($tmpl['title_template'] ?? '') ?>" 
                                                    data-message="<?= esc($tmpl['message_template'] ?? '') ?>"
                                                    data-url="<?= esc($tmpl['action_url_template'] ?? '') ?>"
                                                >
                                                    <?= esc($tmpl['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Título -->
                                    <div class="mb-3">
                                        <label for="notif-title" class="form-label text-dark small fw-semibold d-flex justify-content-between">
                                            <span>Título de la Notificación: <span class="text-danger">*</span></span>
                                            <span id="title-char-count" class="text-muted small">0/80</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control fw-semibold" 
                                            id="notif-title" 
                                            name="title" 
                                            maxlength="80" 
                                            placeholder="Ej: 🎉 ¡Gran Pozo Acumulado Esta Noche!" 
                                            required
                                        >
                                    </div>

                                    <!-- Mensaje (Cualquier texto) -->
                                    <div class="mb-3">
                                        <label for="notif-message" class="form-label text-dark small fw-semibold d-flex justify-content-between">
                                            <span>Mensaje Personalizado: <span class="text-danger">*</span></span>
                                            <span id="msg-char-count" class="text-muted small">0/280</span>
                                        </label>
                                        <textarea 
                                            class="form-control" 
                                            id="notif-message" 
                                            name="message" 
                                            rows="4" 
                                            maxlength="280" 
                                            placeholder="Escribe aquí cualquier texto que desees enviar a los jugadores (anuncios, promociones, avisos en live, premios, etc.)..." 
                                            required
                                        ></textarea>
                                    </div>

                                    <!-- Destinatarios y Enlace -->
                                    <div class="row g-3 mb-3">
                                        <div class="col-sm-6">
                                            <label for="notif-segment" class="form-label text-dark small fw-semibold">
                                                <i class="fa-duotone fa-solid fa-users text-primary me-1"></i> Destinatarios:
                                            </label>
                                            <select class="form-select" id="notif-segment" name="segment">
                                                <option value="all" selected>📢 Todos los Jugadores</option>
                                                <option value="live_games">🎮 Jugadores en Salas Activas (Live)</option>
                                                <option value="pro">⭐ Usuarios Pro / VIP</option>
                                                <option value="specific">👤 Usuario Específico</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="notif-action-url" class="form-label text-dark small fw-semibold">
                                                <i class="fa-duotone fa-solid fa-link text-primary me-1"></i> Enlace de Acción (Opcional):
                                            </label>
                                            <input 
                                                type="url" 
                                                class="form-control" 
                                                id="notif-action-url" 
                                                name="action_url" 
                                                placeholder="<?= site_url('games') ?>"
                                            >
                                        </div>
                                    </div>

                                    <!-- Selector de usuario específico (oculto por defecto) -->
                                    <div class="mb-3" id="specific-user-wrapper" style="display: none;">
                                        <label for="notif-user-id" class="form-label text-dark small fw-semibold">
                                            Seleccionar Usuario Específico:
                                        </label>
                                        <select class="form-select" id="notif-user-id" name="user_id">
                                            <option value="">-- Buscar jugador --</option>
                                            <?php foreach ($users as $u) : ?>
                                                <option value="<?= $u['id'] ?>">
                                                    <?= esc($u['username']) ?> (<?= esc(trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''))) ?>) #<?= $u['id'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Canales de Entrega (Live + Push) -->
                                    <div class="p-3 mb-3 rounded-3" style="background: #ffffff; border: 1px solid #e2e8f0;">
                                        <label class="form-label text-dark small fw-bold mb-2 d-block">
                                            <i class="fa-duotone fa-solid fa-tower-broadcast text-warning me-1"></i> Canales de Envío Simultáneos:
                                        </label>
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <div class="form-check form-switch custom-notif-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="channel-live" name="send_live" value="1" checked>
                                                    <label class="form-check-label text-dark small ms-1" for="channel-live">
                                                        <strong class="d-block text-dark">Notificación en Live</strong>
                                                        <span class="text-muted" style="font-size: 0.74rem;">Aparece en pantalla en salas, partidas y sitio (WebSocket).</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-check form-switch custom-notif-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="channel-push" name="send_push" value="1" checked>
                                                    <label class="form-check-label text-dark small ms-1" for="channel-push">
                                                        <strong class="d-block text-dark">Notificación Push</strong>
                                                        <span class="text-muted" style="font-size: 0.74rem;">Llega a teléfonos y navegadores (Service Worker).</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Opción Guardar como Plantilla -->
                                    <div class="mb-4">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check-save-template" name="save_as_template" value="1">
                                            <label class="form-check-label text-dark small fw-semibold" for="check-save-template">
                                                <i class="fa-duotone fa-solid fa-floppy-disk text-primary me-1"></i> Guardar también como plantilla reutilizable
                                            </label>
                                        </div>
                                        <div id="template-name-wrapper" style="display: none;">
                                            <input 
                                                type="text" 
                                                class="form-control form-control-sm" 
                                                id="template-name" 
                                                name="template_name" 
                                                placeholder="Nombre de la plantilla (ej: Pozo Acumulado Fin de Semana)"
                                            >
                                        </div>
                                    </div>

                                    <!-- BOTÓN DE TIPO ENVIAR -->
                                    <button 
                                        type="button" 
                                        id="btn-send-now" 
                                        class="btn btn-primary btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm"
                                        style="background: linear-gradient(145deg, #8767fa, #6236ff); border: 0; padding: 12px 24px; font-size: 1rem;"
                                    >
                                        <i class="fa-solid fa-paper-plane" id="btn-send-icon"></i>
                                        <span id="btn-send-text">Enviar Notificación Ahora</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Vista Previa y Plantillas Guardadas -->
                        <div class="col-lg-5">
                            <div class="d-flex flex-column gap-3">

                                <!-- CARD: Previsualización Notificación en Pantalla Live -->
                                <div class="p-3 rounded-4 shadow-sm" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                            <i class="fa-solid fa-display text-primary me-1"></i> Vista Previa: Pantalla Live
                                        </span>
                                        <span class="badge bg-success" style="font-size: 0.65rem;">EN TIEMPO REAL</span>
                                    </div>
                                    
                                    <!-- Toast Mockup representando la pantalla del bingo -->
                                    <div class="p-3 rounded-3 shadow position-relative" style="background: linear-gradient(135deg, #231942, #1b0e36); border-left: 4px solid #8767fa; border: 1px solid rgba(255, 255, 255, 0.12);">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <h6 class="fw-bold mb-0 text-white" id="preview-live-title" style="font-size: 0.9rem;">
                                                🎉 ¡Gran Pozo Acumulado Esta Noche!
                                            </h6>
                                            <span class="text-white-50" style="font-size: 0.7rem;">Ahora</span>
                                        </div>
                                        <div class="text-white-50 small mb-2" id="preview-live-message" style="line-height: 1.35; font-size: 0.82rem;">
                                            Coloca aquí cualquier texto que desees enviar a los jugadores...
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center pt-1 border-top border-white-subtle" style="font-size: 0.7rem;">
                                            <span class="text-white-50"><i class="fa-regular fa-hand-point-right text-warning me-1"></i> Desliza para cerrar</span>
                                            <span class="text-info fw-semibold">Rey Bingo Live</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- CARD: Previsualización Notificación Push Móvil -->
                                <div class="p-3 rounded-4 shadow-sm" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                            <i class="fa-solid fa-mobile-screen text-primary me-1"></i> Vista Previa: Notificación Push
                                        </span>
                                        <span class="badge bg-info text-dark" style="font-size: 0.65rem;">CELULAR / NAVEGADOR</span>
                                    </div>
                                    
                                    <!-- Mobile Push Mockup (estilo smartphone limpio) -->
                                    <div class="p-2 px-3 rounded-3 shadow-sm position-relative" style="background: #ffffff; border: 1px solid #e2e8f0;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <img src="<?= site_url('assets/img/logo.png'); ?>" alt="logo" style="width: 18px; height: 18px; border-radius: 4px;" onerror="this.src='<?= site_url('assets/img/avatar.jpg') ?>';">
                                            <strong class="text-dark small" style="font-size: 0.75rem;">Rey Bingo</strong>
                                            <span class="text-muted ms-auto" style="font-size: 0.65rem;">ahora</span>
                                        </div>
                                        <div class="text-dark fw-semibold small mb-1" id="preview-push-title" style="font-size: 0.82rem;">
                                            🎉 ¡Gran Pozo Acumulado Esta Noche!
                                        </div>
                                        <div class="text-muted small" id="preview-push-message" style="font-size: 0.77rem; line-height: 1.3;">
                                            Coloca aquí cualquier texto que desees enviar a los jugadores...
                                        </div>
                                    </div>
                                </div>

                                <!-- CARD: Plantillas Guardadas ("Tenerlas ahí") -->
                                <div class="p-3 rounded-4 shadow-sm" id="saved-templates-section" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-dark fw-bold small text-uppercase" style="letter-spacing: 0.5px;">
                                            <i class="fa-duotone fa-solid fa-bookmark text-warning me-1"></i> Plantillas Guardadas
                                        </span>
                                        <span class="badge bg-primary rounded-pill" id="templates-count-badge"><?= count($templates) ?></span>
                                    </div>
                                    <p class="text-muted small mb-2" style="font-size: 0.75rem;">
                                        Pulsa <strong>Usar</strong> para cargar la plantilla en el formulario al instante.
                                    </p>

                                    <div class="d-flex flex-column gap-2" id="templates-list-container" style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                                        <?php if (empty($templates)) : ?>
                                            <div class="text-muted text-center py-3 small">
                                                No hay plantillas guardadas aún. Crea una activando "Guardar como plantilla".
                                            </div>
                                        <?php else : ?>
                                            <?php foreach ($templates as $tmpl) : ?>
                                                <div class="template-item p-2 rounded-3 d-flex justify-content-between align-items-center gap-2 shadow-sm" style="background: #ffffff; border: 1px solid #e2e8f0;" id="tmpl-card-<?= $tmpl['id'] ?>">
                                                    <div style="min-width: 0;">
                                                        <strong class="d-block text-dark text-truncate" style="font-size: 0.8rem;"><?= esc($tmpl['name']) ?></strong>
                                                        <span class="text-muted text-truncate d-block" style="font-size: 0.72rem;"><?= esc($tmpl['title_template'] ?? '') ?></span>
                                                    </div>
                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                        <button 
                                                            type="button" 
                                                            class="btn btn-sm btn-outline-primary py-0 px-2 btn-apply-tmpl" 
                                                            style="font-size: 0.72rem;"
                                                            data-title="<?= esc($tmpl['title_template'] ?? '') ?>"
                                                            data-message="<?= esc($tmpl['message_template'] ?? '') ?>"
                                                            data-url="<?= esc($tmpl['action_url_template'] ?? '') ?>"
                                                            title="Cargar al formulario"
                                                        >
                                                            Usar
                                                        </button>
                                                        <button 
                                                            type="button" 
                                                            class="btn btn-sm btn-outline-danger py-0 px-2 btn-del-tmpl" 
                                                            style="font-size: 0.72rem;"
                                                            data-id="<?= $tmpl['id'] ?>"
                                                            title="Eliminar plantilla"
                                                        >
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
</div>

<!-- Estilos específicos limpios y alineados al diseño de Rey Bingo -->
<style>
.admin-notifications-page .form-control,
.admin-notifications-page .form-select {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #1e293b !important;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 0.88rem;
    transition: all 0.2s ease;
}
.admin-notifications-page .form-control:focus,
.admin-notifications-page .form-select:focus {
    border-color: #6236ff !important;
    box-shadow: 0 0 0 3px rgba(98, 54, 255, 0.18) !important;
    background-color: #ffffff !important;
}
.admin-notifications-page .custom-notif-switch .form-check-input {
    width: 2.75rem;
    height: 1.4rem;
    cursor: pointer;
}
.admin-notifications-page .custom-notif-switch .form-check-input:checked {
    background-color: #6236ff;
    border-color: #6236ff;
}
</style>

<!-- Script interactivo de Notificaciones (Envío Live + Push, Vista Previa y Plantillas) -->
<script>
(function() {
    const inputTitle = document.getElementById('notif-title');
    const inputMsg = document.getElementById('notif-message');
    const inputUrl = document.getElementById('notif-action-url');
    const inputSegment = document.getElementById('notif-segment');
    const specificUserWrapper = document.getElementById('specific-user-wrapper');
    const checkSaveTmpl = document.getElementById('check-save-template');
    const tmplNameWrapper = document.getElementById('template-name-wrapper');
    const tmplNameInput = document.getElementById('template-name');
    const quickTmplSelect = document.getElementById('quick-template-select');
    const btnSend = document.getElementById('btn-send-now');
    const btnSendIcon = document.getElementById('btn-send-icon');
    const btnSendText = document.getElementById('btn-send-text');
    const alertBox = document.getElementById('notif-alert-container');

    const previewLiveTitle = document.getElementById('preview-live-title');
    const previewLiveMsg = document.getElementById('preview-live-message');
    const previewPushTitle = document.getElementById('preview-push-title');
    const previewPushMsg = document.getElementById('preview-push-message');
    const titleCount = document.getElementById('title-char-count');
    const msgCount = document.getElementById('msg-char-count');

    // 1. Actualización en tiempo real de previsualizaciones
    function updatePreviews() {
        const title = inputTitle.value.trim() || '🎉 ¡Gran Pozo Acumulado Esta Noche!';
        const msg = inputMsg.value.trim() || 'Coloca aquí cualquier texto que desees enviar a los jugadores...';

        previewLiveTitle.textContent = title;
        previewLiveMsg.textContent = msg;
        previewPushTitle.textContent = title;
        previewPushMsg.textContent = msg;

        titleCount.textContent = (inputTitle.value.length) + '/80';
        msgCount.textContent = (inputMsg.value.length) + '/280';
    }

    inputTitle.addEventListener('input', updatePreviews);
    inputMsg.addEventListener('input', updatePreviews);

    // 2. Control de destinatarios específicos
    inputSegment.addEventListener('change', function() {
        if (this.value === 'specific') {
            specificUserWrapper.style.display = 'block';
        } else {
            specificUserWrapper.style.display = 'none';
        }
    });

    // 3. Control de guardar como plantilla
    checkSaveTmpl.addEventListener('change', function() {
        if (this.checked) {
            tmplNameWrapper.style.display = 'block';
            tmplNameInput.focus();
        } else {
            tmplNameWrapper.style.display = 'none';
        }
    });

    // 4. Cargar plantilla rápida
    function applyTemplate(title, message, url) {
        if (title) inputTitle.value = title;
        if (message) inputMsg.value = message;
        if (url) inputUrl.value = url;
        updatePreviews();

        inputTitle.style.backgroundColor = '#ede9fe';
        inputMsg.style.backgroundColor = '#ede9fe';
        setTimeout(() => {
            inputTitle.style.backgroundColor = '#ffffff';
            inputMsg.style.backgroundColor = '#ffffff';
        }, 400);
    }

    quickTmplSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected && selected.value) {
            applyTemplate(
                selected.dataset.title,
                selected.dataset.message,
                selected.dataset.url
            );
        }
    });

    // Delegación de eventos para botones de plantillas
    document.addEventListener('click', function(e) {
        const btnApply = e.target.closest('.btn-apply-tmpl');
        if (btnApply) {
            applyTemplate(
                btnApply.dataset.title,
                btnApply.dataset.message,
                btnApply.dataset.url
            );
            window.scrollTo({ top: 120, behavior: 'smooth' });
            return;
        }

        const btnDel = e.target.closest('.btn-del-tmpl');
        if (btnDel) {
            const tmplId = btnDel.dataset.id;
            if (!confirm('¿Deseas eliminar esta plantilla guardada?')) return;

            btnDel.disabled = true;
            fetch('<?= site_url('notificationsAdmin/deleteTemplateAjax') ?>/' + tmplId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const card = document.getElementById('tmpl-card-' + tmplId);
                    if (card) card.remove();
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message || 'No se pudo eliminar la plantilla.');
                    btnDel.disabled = false;
                }
            })
            .catch(() => {
                showAlert('danger', 'Error de conexión al eliminar.');
                btnDel.disabled = false;
            });
        }
    });

    // 5. Alertas
    function showAlert(type, message, details) {
        let detailsHtml = '';
        if (details) {
            detailsHtml = `
                <div class="mt-2 pt-2 border-top border-secondary-subtle small d-flex flex-wrap gap-3">
                    <span><i class="fa-solid fa-users text-primary me-1"></i> Destinatarios: <strong>${details.users_targeted || 0}</strong></span>
                    <span><i class="fa-solid fa-bolt text-success me-1"></i> En vivo (Live): <strong>${details.live_sent || 'Activo'}</strong></span>
                    <span><i class="fa-solid fa-mobile-screen text-info me-1"></i> Dispositivos Push: <strong>${details.push_sent || 0}</strong></span>
                </div>
            `;
        }

        alertBox.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show border shadow-sm" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid ${type === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger'} fs-5"></i>
                    <div>
                        <strong>${message}</strong>
                        ${detailsHtml}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // 6. ENVÍO DE NOTIFICACIÓN
    btnSend.addEventListener('click', function(e) {
        e.preventDefault();

        const title = inputTitle.value.trim();
        const message = inputMsg.value.trim();
        const sendLive = document.getElementById('channel-live').checked ? 1 : 0;
        const sendPush = document.getElementById('channel-push').checked ? 1 : 0;

        if (!title) {
            showAlert('warning', 'Por favor ingresa un título para la notificación.');
            inputTitle.focus();
            return;
        }

        if (!message) {
            showAlert('warning', 'Por favor ingresa el mensaje a enviar.');
            inputMsg.focus();
            return;
        }

        if (sendLive === 0 && sendPush === 0) {
            showAlert('warning', 'Debes activar al menos un canal de entrega (Live o Push).');
            return;
        }

        btnSend.disabled = true;
        btnSendIcon.className = 'fa-solid fa-spinner fa-spin';
        btnSendText.textContent = 'Transmitiendo en Vivo y Push...';

        const formData = new FormData(document.getElementById('form-send-notification'));

        fetch('<?= site_url('notificationsAdmin/sendAjax') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnSend.disabled = false;
            btnSendIcon.className = 'fa-solid fa-paper-plane';
            btnSendText.textContent = 'Enviar Notificación Ahora';

            if (data.success) {
                showAlert('success', data.message, data.details);

                if (typeof window.showNotification === 'function') {
                    window.showNotification({
                        title: title,
                        message: message,
                        type: 'admin'
                    });
                }

                if (!checkSaveTmpl.checked) {
                    inputTitle.value = '';
                    inputMsg.value = '';
                    inputUrl.value = '';
                    updatePreviews();
                } else {
                    checkSaveTmpl.checked = false;
                    tmplNameWrapper.style.display = 'none';
                }
            } else {
                showAlert('danger', data.message || 'Error al enviar la notificación.');
            }
        })
        .catch(err => {
            console.error('Error al enviar notificación:', err);
            btnSend.disabled = false;
            btnSendIcon.className = 'fa-solid fa-paper-plane';
            btnSendText.textContent = 'Enviar Notificación Ahora';
            showAlert('danger', 'Ocurrió un error inesperado al conectar con el servidor.');
        });
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    updatePreviews();
})();
</script>