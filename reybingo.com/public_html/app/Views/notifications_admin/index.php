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
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <div>
                            <h4 class="mb-1 text-white fw-bold d-flex align-items-center gap-2">
                                <i class="fa-duotone fa-solid fa-bell-ring text-warning"></i>
                                Centro de Notificaciones
                            </h4>
                            <p class="text-white-50 small mb-0">
                                Personaliza, crea y guarda mensajes. Envía notificaciones Push a celulares y avisos en Live a todos los jugadores en tiempo real.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill">
                                <i class="fa-solid fa-bolt"></i> Live Realtime (Soketi)
                            </span>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill">
                                <i class="fa-solid fa-mobile-screen-button"></i> <?= (int)$pushSubscriptionsCount ?> Dispositivos Push
                            </span>
                            <?php if ($activeGamesCount > 0) : ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 d-inline-flex align-items-center gap-1 rounded-pill">
                                    <i class="fa-solid fa-gamepad"></i> <?= (int)$activeGamesCount ?> Partidas en Vivo
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alertas de estado dinámicas -->
                    <div id="notif-alert-container"></div>

                    <!-- Fila dividida: Formulario Emisor (Izquierda) y Vista Previa + Plantillas (Derecha) -->
                    <div class="row g-4 mt-1">

                        <!-- COLUMNA IZQUIERDA: Formulario de Redacción y Envío -->
                        <div class="col-lg-7">
                            <div class="p-3 p-md-4 rounded-4" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.09);">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                                        <i class="fa-duotone fa-solid fa-paper-plane text-primary"></i>
                                        Redactar Notificación
                                    </h5>
                                    <span class="badge bg-primary-subtle text-primary small">Cualquier Texto</span>
                                </div>

                                <form id="form-send-notification" autocomplete="off">
                                    <?= csrf_field() ?>

                                    <!-- Selector Rápido de Plantillas ("Tenerlas ahí") -->
                                    <div class="mb-3">
                                        <label class="form-label text-white-50 small fw-semibold d-flex justify-content-between">
                                            <span><i class="fa-duotone fa-solid fa-bookmark text-warning me-1"></i> Cargar Plantilla Guardada:</span>
                                            <a href="#saved-templates-section" class="text-info text-decoration-none small">Ver todas las plantillas</a>
                                        </label>
                                        <select class="form-select form-bingo" id="quick-template-select">
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
                                        <label for="notif-title" class="form-label text-white-50 small fw-semibold d-flex justify-content-between">
                                            <span>Título de la Notificación: <strong class="text-danger">*</strong></span>
                                            <span id="title-char-count" class="text-white-50 small">0/80</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control form-bingo fw-semibold" 
                                            id="notif-title" 
                                            name="title" 
                                            maxlength="80" 
                                            placeholder="Ej: 🎉 ¡Gran Pozo Acumulado Esta Noche!" 
                                            required
                                        >
                                    </div>

                                    <!-- Mensaje (Cualquier texto) -->
                                    <div class="mb-3">
                                        <label for="notif-message" class="form-label text-white-50 small fw-semibold d-flex justify-content-between">
                                            <span>Mensaje Personalizado: <strong class="text-danger">*</strong></span>
                                            <span id="msg-char-count" class="text-white-50 small">0/280</span>
                                        </label>
                                        <textarea 
                                            class="form-control form-bingo" 
                                            id="notif-message" 
                                            name="message" 
                                            rows="4" 
                                            maxlength="280" 
                                            placeholder="Coloca aquí cualquier texto que desees enviar a los jugadores (anuncios, promociones, avisos de juego en live, premios, etc.)..." 
                                            required
                                        ></textarea>
                                    </div>

                                    <!-- Destinatarios y URL en fila -->
                                    <div class="row g-3 mb-3">
                                        <div class="col-sm-6">
                                            <label for="notif-segment" class="form-label text-white-50 small fw-semibold">
                                                <i class="fa-duotone fa-solid fa-users me-1 text-info"></i> Destinatarios:
                                            </label>
                                            <select class="form-select form-bingo" id="notif-segment" name="segment">
                                                <option value="all" selected>📢 Todos los Jugadores</option>
                                                <option value="live_games">🎮 Jugadores en Salas Activas (Live)</option>
                                                <option value="pro">⭐ Usuarios Pro / VIP</option>
                                                <option value="specific">👤 Usuario Específico</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="notif-action-url" class="form-label text-white-50 small fw-semibold">
                                                <i class="fa-duotone fa-solid fa-link me-1 text-info"></i> Enlace de Acción (Opcional):
                                            </label>
                                            <input 
                                                type="url" 
                                                class="form-control form-bingo" 
                                                id="notif-action-url" 
                                                name="action_url" 
                                                placeholder="<?= site_url('games') ?>"
                                            >
                                        </div>
                                    </div>

                                    <!-- Selector de usuario específico (oculto por defecto) -->
                                    <div class="mb-3" id="specific-user-wrapper" style="display: none;">
                                        <label for="notif-user-id" class="form-label text-white-50 small fw-semibold">
                                            Seleccionar Usuario Específico:
                                        </label>
                                        <select class="form-select form-bingo" id="notif-user-id" name="user_id">
                                            <option value="">-- Buscar jugador --</option>
                                            <?php foreach ($users as $u) : ?>
                                                <option value="<?= $u['id'] ?>">
                                                    <?= esc($u['username']) ?> (<?= esc(trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''))) ?>) #<?= $u['id'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Canales de Entrega (Live + Push) -->
                                    <div class="p-3 mb-3 rounded-3" style="background: rgba(0, 0, 0, 0.25); border: 1px dashed rgba(255, 255, 255, 0.15);">
                                        <label class="form-label text-white small fw-bold mb-2 d-block">
                                            <i class="fa-duotone fa-solid fa-tower-broadcast text-warning me-1"></i> Canales de Envío Simultáneos:
                                        </label>
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <div class="form-check form-switch custom-notif-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="channel-live" name="send_live" value="1" checked>
                                                    <label class="form-check-label text-white small ms-1" for="channel-live">
                                                        <strong>Notificación en Live</strong>
                                                        <span class="d-block text-white-50" style="font-size: 0.73rem;">Aparece en pantalla en salas, partidas y sitio (WebSocket).</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-check form-switch custom-notif-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="channel-push" name="send_push" value="1" checked>
                                                    <label class="form-check-label text-white small ms-1" for="channel-push">
                                                        <strong>Notificación Push</strong>
                                                        <span class="d-block text-white-50" style="font-size: 0.73rem;">Llega a teléfonos y navegadores (Service Worker).</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Opción Guardar como Plantilla ("Tenerlas ahí") -->
                                    <div class="mb-4">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="check-save-template" name="save_as_template" value="1">
                                            <label class="form-check-label text-white small" for="check-save-template">
                                                <i class="fa-duotone fa-solid fa-floppy-disk text-info me-1"></i> Guardar también este texto como plantilla reutilizable
                                            </label>
                                        </div>
                                        <div id="template-name-wrapper" style="display: none;">
                                            <input 
                                                type="text" 
                                                class="form-control form-bingo form-control-sm" 
                                                id="template-name" 
                                                name="template_name" 
                                                placeholder="Nombre de la plantilla (ej: Aviso Pozo Millonario, Promo Viernes, etc.)"
                                            >
                                        </div>
                                    </div>

                                    <!-- BOTÓN DE TIPO ENVIAR -->
                                    <button 
                                        type="button" 
                                        id="btn-send-now" 
                                        class="btn btn-primary btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow"
                                        style="background: linear-gradient(135deg, #f43f5e, #be123c); border: 0; padding: 12px 24px; font-size: 1.05rem;"
                                    >
                                        <i class="fa-solid fa-paper-plane" id="btn-send-icon"></i>
                                        <span id="btn-send-text">Enviar Notificación Ahora</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Vista Previa en Vivo y Plantillas Rápidas -->
                        <div class="col-lg-5">
                            <div class="d-flex flex-column gap-3">

                                <!-- CARD: Previsualización en Vivo de la Notificación en Live -->
                                <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.09);">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                            <i class="fa-solid fa-display text-success me-1"></i> Vista Previa: Pantalla Live
                                        </span>
                                        <span class="badge bg-success" style="font-size: 0.65rem;">EN TIEMPO REAL</span>
                                    </div>
                                    
                                    <!-- Toast Mockup idéntico a las notificaciones emergentes de Rey Bingo -->
                                    <div class="notif-preview-toast p-3 rounded-3 shadow-lg position-relative" style="background: rgba(30, 20, 60, 0.95); border-left: 4px solid #f43f5e; border: 1px solid rgba(244, 63, 94, 0.35);">
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
                                <div class="p-3 rounded-4" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.09);">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-white-50 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                            <i class="fa-solid fa-mobile text-info me-1"></i> Vista Previa: Notificación Push
                                        </span>
                                        <span class="badge bg-info" style="font-size: 0.65rem;">CELULAR / NAVEGADOR</span>
                                    </div>
                                    
                                    <!-- Mobile Push Mockup -->
                                    <div class="p-2 px-3 rounded-3 shadow position-relative" style="background: #1e1b2e; border: 1px solid rgba(255, 255, 255, 0.12);">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <img src="<?= site_url('assets/img/logo.png'); ?>" alt="logo" style="width: 18px; height: 18px; border-radius: 4px;" onerror="this.src='<?= site_url('assets/img/avatar.jpg') ?>';">
                                            <strong class="text-white small" style="font-size: 0.75rem;">Rey Bingo</strong>
                                            <span class="text-white-50 ms-auto" style="font-size: 0.65rem;">ahora</span>
                                        </div>
                                        <div class="text-white fw-semibold small mb-1" id="preview-push-title" style="font-size: 0.82rem;">
                                            🎉 ¡Gran Pozo Acumulado Esta Noche!
                                        </div>
                                        <div class="text-white-50 small" id="preview-push-message" style="font-size: 0.77rem; line-height: 1.3;">
                                            Coloca aquí cualquier texto que desees enviar a los jugadores...
                                        </div>
                                    </div>
                                </div>

                                <!-- CARD: Plantillas Guardadas ("Tenerlas ahí") -->
                                <div class="p-3 rounded-4" id="saved-templates-section" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.09);">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-white fw-bold small text-uppercase" style="letter-spacing: 0.5px;">
                                            <i class="fa-duotone fa-solid fa-bookmark text-warning me-1"></i> Plantillas Guardadas
                                        </span>
                                        <span class="badge bg-secondary" id="templates-count-badge"><?= count($templates) ?></span>
                                    </div>
                                    <p class="text-white-50 small mb-2" style="font-size: 0.75rem;">
                                        Haz clic en <strong>Usar</strong> para cargarlas al formulario y enviarlas cuando desees.
                                    </p>

                                    <div class="d-flex flex-column gap-2" id="templates-list-container" style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                                        <?php if (empty($templates)) : ?>
                                            <div class="text-white-50 text-center py-3 small">
                                                No hay plantillas guardadas aún. Crea una activando "Guardar como plantilla".
                                            </div>
                                        <?php else : ?>
                                            <?php foreach ($templates as $tmpl) : ?>
                                                <div class="template-item p-2 rounded-3 d-flex justify-content-between align-items-center gap-2" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06);" id="tmpl-card-<?= $tmpl['id'] ?>">
                                                    <div style="min-width: 0;">
                                                        <strong class="d-block text-white text-truncate" style="font-size: 0.8rem;"><?= esc($tmpl['name']) ?></strong>
                                                        <span class="text-white-50 text-truncate d-block" style="font-size: 0.72rem;"><?= esc($tmpl['title_template'] ?? '') ?></span>
                                                    </div>
                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                        <button 
                                                            type="button" 
                                                            class="btn btn-sm btn-outline-info py-0 px-2 btn-apply-tmpl" 
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

            <!-- TABLA: Historial de Notificaciones Recientes -->
            <div class="card mb-3 admin-stores-card border-0 shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-white fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="fa-duotone fa-solid fa-clock-rotate-left text-warning"></i>
                            Historial de Notificaciones Recientes
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-3" id="btn-refresh-history">
                            <i class="fa-solid fa-rotate me-1"></i> Actualizar
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0" id="table-notifications-history" style="font-size: 0.83rem;">
                            <thead>
                                <tr class="text-white-50 border-bottom border-white-subtle">
                                    <th style="width: 50px;">ID</th>
                                    <th>Título</th>
                                    <th>Mensaje</th>
                                    <th>Destinatario</th>
                                    <th>Fecha y Hora</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="history-tbody">
                                <?php if (empty($notifications)) : ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-white-50">
                                            No se han registrado notificaciones recientes.
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($notifications as $n) : ?>
                                        <tr>
                                            <td class="text-white-50 fw-semibold">#<?= $n['id'] ?></td>
                                            <td>
                                                <strong class="text-white"><?= esc($n['title']) ?></strong>
                                            </td>
                                            <td class="text-white-50" style="max-width: 320px;">
                                                <div class="text-truncate" title="<?= esc($n['message']) ?>">
                                                    <?= esc($n['message']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($n['username'])) : ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                        <?= esc($n['username']) ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        Todos
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-white-50">
                                                <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($n['sent_at'])) : ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="fa-solid fa-check me-1"></i> Enviada
                                                    </span>
                                                <?php else : ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                        Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</div>

<!-- Estilos específicos adicionales para el Centro de Notificaciones -->
<style>
.admin-notifications-page .form-bingo {
    background: rgba(18, 14, 38, 0.75) !important;
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.2s ease;
}
.admin-notifications-page .form-bingo:focus {
    border-color: #f43f5e !important;
    box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.25) !important;
    background: rgba(18, 14, 38, 0.95) !important;
}
.admin-notifications-page .custom-notif-switch .form-check-input {
    width: 2.75rem;
    height: 1.4rem;
    cursor: pointer;
}
.admin-notifications-page .custom-notif-switch .form-check-input:checked {
    background-color: #f43f5e;
    border-color: #f43f5e;
}
.notif-preview-toast {
    animation: previewFloat 3s ease-in-out infinite alternate;
}
@keyframes previewFloat {
    from { transform: translateY(0); }
    to { transform: translateY(-3px); }
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

    // 4. Cargar plantilla rápida desde select o lista
    function applyTemplate(title, message, url) {
        if (title) inputTitle.value = title;
        if (message) inputMsg.value = message;
        if (url) inputUrl.value = url;
        updatePreviews();

        // Efecto visual
        inputTitle.style.backgroundColor = 'rgba(244, 63, 94, 0.15)';
        inputMsg.style.backgroundColor = 'rgba(244, 63, 94, 0.15)';
        setTimeout(() => {
            inputTitle.style.backgroundColor = '';
            inputMsg.style.backgroundColor = '';
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
        // Usar plantilla
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

        // Eliminar plantilla
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

    // 5. Función de alertas
    function showAlert(type, message, details) {
        let detailsHtml = '';
        if (details) {
            detailsHtml = `
                <div class="mt-2 pt-2 border-top border-white-subtle small d-flex flex-wrap gap-3">
                    <span><i class="fa-solid fa-users me-1"></i> Destinatarios: <strong>${details.users_targeted || 0}</strong></span>
                    <span><i class="fa-solid fa-bolt me-1"></i> En vivo (Live): <strong>${details.live_sent || 'Activo'}</strong></span>
                    <span><i class="fa-solid fa-mobile-screen me-1"></i> Dispositivos Push: <strong>${details.push_sent || 0}</strong></span>
                </div>
            `;
        }

        alertBox.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show border-0 shadow-sm" role="alert">
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

    // 6. ENVÍO DE NOTIFICACIÓN MEDIANTE EL BOTÓN
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
            showAlert('warning', 'Por favor ingresa el texto del mensaje a enviar.');
            inputMsg.focus();
            return;
        }

        if (sendLive === 0 && sendPush === 0) {
            showAlert('warning', 'Debes activar al menos un canal de entrega (Live o Push).');
            return;
        }

        // Estado cargando
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

                // Si también se disparó en pantalla local para el admin si tiene audio habilitado
                if (typeof window.showNotification === 'function') {
                    window.showNotification({
                        title: title,
                        message: message,
                        type: 'admin'
                    });
                }

                // Limpiar campos si no se guardó como plantilla fija
                if (!checkSaveTmpl.checked) {
                    inputTitle.value = '';
                    inputMsg.value = '';
                    inputUrl.value = '';
                    updatePreviews();
                } else {
                    checkSaveTmpl.checked = false;
                    tmplNameWrapper.style.display = 'none';
                    tmplNameInput.value = '';
                }

                // Recargar historial
                refreshHistory();
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

    // 7. Refrescar historial
    function refreshHistory() {
        fetch('<?= site_url('notificationsAdmin/historyAjax') ?>', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.notifications) {
                const tbody = document.getElementById('history-tbody');
                if (!tbody) return;

                if (data.notifications.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-white-50">No hay notificaciones registradas.</td></tr>`;
                    return;
                }

                let rowsHtml = '';
                data.notifications.forEach(n => {
                    const recipient = n.username ? `<span class="badge bg-info-subtle text-info border border-info-subtle">${escapeHtml(n.username)}</span>` : `<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Todos</span>`;
                    const dateFormatted = new Date(n.created_at).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const statusBadge = n.sent_at ? `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i> Enviada</span>` : `<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pendiente</span>`;

                    rowsHtml += `
                        <tr>
                            <td class="text-white-50 fw-semibold">#${n.id}</td>
                            <td><strong class="text-white">${escapeHtml(n.title)}</strong></td>
                            <td class="text-white-50" style="max-width: 320px;">
                                <div class="text-truncate" title="${escapeHtml(n.message)}">${escapeHtml(n.message)}</div>
                            </td>
                            <td>${recipient}</td>
                            <td class="text-white-50">${dateFormatted}</td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = rowsHtml;
            }
        })
        .catch(e => console.warn('Error refreshing history:', e));
    }

    document.getElementById('btn-refresh-history').addEventListener('click', function() {
        this.disabled = true;
        refreshHistory();
        setTimeout(() => { this.disabled = false; }, 800);
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

    // Inicializar contadores
    updatePreviews();
})();
</script>