<?php
$formatCardPreview = function($text) {
    if (!$text) return '';
    $friendlyMap = [
        'daily_bonus' => 'Bono Diario',
        'achievement_name' => 'Nombre del Logro',
        'achievement_description' => 'Descripción',
        'game_name' => 'Partida',
        'game_date' => 'Fecha',
        'game_time' => 'Hora',
        'carton_price' => 'Precio',
        'level_name' => 'Nivel',
        'level_benefits' => 'Beneficios',
    ];
    return preg_replace_callback('/{{\s*([a-zA-Z0-9_-]+)\s*}}/', function($m) use ($friendlyMap) {
        $label = $friendlyMap[$m[1]] ?? ucwords(str_replace('_', ' ', $m[1]));
        return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1 py-0 rounded" style="font-size: 0.68rem; font-weight: 600;">[' . esc($label) . ']</span>';
    }, esc($text));
};
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

<div class="container admin-stores-page admin-notifications-page pb-4">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">

            <!-- Card Principal -->
            <div class="card mb-3 admin-stores-card border-0 shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <div class="mb-3">
                        <h4 class="mb-0 text-dark fw-bold d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                            <i class="fa-duotone fa-solid fa-bell text-primary"></i>
                            Centro de Notificaciones
                        </h4>
                    </div>

                    <!-- Contenedor para mensajes de respuesta -->
                    <div id="notif-alert-container"></div>

                    <!-- Fila dividida: Buscador + Notificaciones Guardadas (Izquierda) y Pantalla de Notificación + Envío (Derecha) -->
                    <div class="row g-3 g-lg-4 mt-1">

                        <!-- COLUMNA IZQUIERDA: Buscador y Notificaciones Guardadas -->
                        <div class="col-lg-5 col-md-5">
                            <div class="p-3 rounded-4 h-100" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <!-- Encabezado lista -->
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="text-dark fw-bold mb-0 d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                                        <i class="fa-duotone fa-solid fa-bookmark text-primary"></i>
                                        Notificaciones Guardadas
                                    </h6>
                                    <span class="badge bg-primary-subtle text-primary fw-bold rounded-pill" id="templates-count-badge"><?= count($templates) ?></span>
                                </div>

                                <!-- Buscador -->
                                <div class="input-group mb-2 shadow-sm rounded-3 overflow-hidden border">
                                    <span class="input-group-text bg-white border-0 text-muted ps-3 py-1">
                                        <i class="fa-solid fa-magnifying-glass" style="font-size: 0.82rem;"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        id="template-search" 
                                        class="form-control border-0 py-1" 
                                        placeholder="Buscar notificación..." 
                                        style="box-shadow: none; font-size: 0.85rem;"
                                    >
                                    <button class="btn btn-white border-0 text-muted pe-3 py-1" type="button" id="btn-clear-search" style="display: none;">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <!-- Lista de Notificaciones Guardadas -->
                                <div id="templates-list-container" class="d-flex flex-column gap-2" style="max-height: 380px; overflow-y: auto; padding-right: 4px;">
                                    <?php if (empty($templates)) : ?>
                                        <div class="text-center py-4 text-muted small" id="templates-none-initial">
                                            <i class="fa-solid fa-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                            No tienes notificaciones guardadas aún.
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-create-template">
                                                    <i class="fa-solid fa-plus me-1"></i> Crear Primera
                                                </button>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <?php foreach ($templates as $idx => $tmpl) : ?>
                                            <div 
                                                class="saved-template-card p-2 px-3 rounded-3 border d-flex justify-content-between align-items-start gap-2 shadow-sm position-relative <?= $idx === 0 ? 'active-template' : '' ?>"
                                                id="tmpl-card-<?= $tmpl['id'] ?>"
                                                data-id="<?= $tmpl['id'] ?>"
                                                data-name="<?= esc($tmpl['name']) ?>"
                                                data-title="<?= esc($tmpl['title_template'] ?? '') ?>"
                                                data-message="<?= esc($tmpl['message_template'] ?? '') ?>"
                                                role="button"
                                                tabindex="0"
                                            >
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                        <strong class="text-dark text-truncate tmpl-card-name" style="font-size: 0.85rem;">
                                                            <?= esc($tmpl['name']) ?>
                                                        </strong>
                                                    </div>
                                                    <div class="text-primary small fw-semibold text-truncate tmpl-card-title mb-1" style="font-size: 0.78rem;">
                                                        <?= $formatCardPreview($tmpl['title_template'] ?? '') ?>
                                                    </div>
                                                    <p class="text-muted small mb-0 tmpl-card-message" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.74rem; line-height: 1.3;">
                                                        <?= $formatCardPreview($tmpl['message_template'] ?? '') ?>
                                                    </p>
                                                </div>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-sm text-danger p-0 ms-1 opacity-50 hover-opacity-100 btn-del-tmpl" 
                                                    data-id="<?= $tmpl['id'] ?>" 
                                                    title="Eliminar notificación"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    <i class="fa-solid fa-trash-can" style="font-size: 0.82rem;"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <div id="templates-empty-state" class="text-center py-4 text-muted small" style="display: none;">
                                        <i class="fa-solid fa-folder-open fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                        No se encontraron notificaciones guardadas.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Botón de Crear, Notificación y Botón de Enviar -->
                        <div class="col-lg-7 col-md-7">
                            <div class="p-3 p-md-4 rounded-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <!-- Barra Superior: Indicador a la izquierda y Botón de Crear Notificación a la derecha -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill fw-semibold small d-inline-flex align-items-center gap-1">
                                            <i class="fa-solid fa-bell"></i> Notificación en Pantalla
                                        </span>
                                        <span id="selected-template-name" class="text-muted small text-truncate d-none d-sm-inline" style="max-width: 200px;"></span>
                                    </div>
                                    <button 
                                        type="button" 
                                        class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modal-create-template"
                                        id="btn-open-create-modal"
                                        style="background: linear-gradient(145deg, #8767fa, #6236ff); border: 0;"
                                    >
                                        <i class="fa-solid fa-plus-circle"></i>
                                        <span>Crear Notificación</span>
                                    </button>
                                </div>

                                <!-- Formulario de Notificación (Área Central) -->
                                <form id="form-send-notification" autocomplete="off">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="segment" value="all">
                                    <input type="hidden" name="send_live" value="1">
                                    <input type="hidden" name="send_push" value="1">

                                    <div class="bg-white p-3 rounded-3 border mb-3 shadow-sm">
                                        <!-- Sección para ingresar valores de variables dinámicas (e.g. {{daily_bonus}}) -->
                                        <div id="dynamic-vars-wrapper" class="p-3 mb-3 rounded-3 border" style="background: #f8fafc; border-color: #cbd5e1 !important; display: none;">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <label class="form-label text-dark fw-bold small mb-0 d-flex align-items-center gap-2">
                                                    <i class="fa-duotone fa-solid fa-pen-to-square text-primary"></i>
                                                    <span>Datos a ingresar en la notificación:</span>
                                                </label>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem; font-weight: 600;">
                                                    Campos automáticos
                                                </span>
                                            </div>
                                            <div class="row g-2" id="dynamic-vars-grid">
                                                <!-- Inputs generados dinámicamente -->
                                            </div>
                                        </div>

                                        <!-- Título -->
                                        <div class="mb-3">
                                            <label for="notif-title" class="form-label text-dark small fw-bold d-flex justify-content-between mb-1">
                                                <span>Título de la Notificación: <span class="text-danger">*</span></span>
                                                <span id="title-char-count" class="text-muted small">0/80</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                class="form-control fw-bold" 
                                                id="notif-title" 
                                                name="title" 
                                                maxlength="80" 
                                                placeholder="Ej: 🎉 ¡Gran Pozo Acumulado Esta Noche!" 
                                                required
                                                style="font-size: 0.92rem; padding: 8px 12px;"
                                            >
                                        </div>

                                        <!-- Mensaje -->
                                        <div class="mb-1">
                                            <label for="notif-message" class="form-label text-dark small fw-bold d-flex justify-content-between mb-1">
                                                <span>Mensaje de la Notificación: <span class="text-danger">*</span></span>
                                                <span id="msg-char-count" class="text-muted small">0/280</span>
                                            </label>
                                            <textarea 
                                                class="form-control" 
                                                id="notif-message" 
                                                name="message" 
                                                rows="4" 
                                                maxlength="280" 
                                                placeholder="Escribe aquí el texto que se enviará en vivo y push a todos los jugadores..." 
                                                required
                                                style="font-size: 0.88rem; line-height: 1.45; padding: 10px 12px;"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <!-- Botón de enviar Notificación (Centrado y visible sin scroll) -->
                                    <div class="text-center pt-2">
                                        <button 
                                            type="button" 
                                            id="btn-send-now" 
                                            class="btn btn-primary fw-bold rounded-pill shadow-sm d-inline-flex align-items-center justify-content-center gap-2 px-5 py-2"
                                            style="background: linear-gradient(145deg, #8767fa, #6236ff); border: 0; min-width: 250px; font-size: 1rem;"
                                        >
                                            <i class="fa-solid fa-paper-plane" id="btn-send-icon"></i>
                                            <span id="btn-send-text">Enviar Notificación</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</div>

<!-- MODAL: Crear Notificación Guardada -->
<div class="modal fade" id="modal-create-template" tabindex="-1" aria-labelledby="modalCreateTmplLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalCreateTmplLabel">
                    <i class="fa-duotone fa-solid fa-plus-circle text-primary"></i>
                    Crear Notificación Guardada
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modal-alert-container"></div>
                <form id="form-create-modal-template" autocomplete="off">
                    <div class="mb-3">
                        <label for="modal-tmpl-name" class="form-label text-dark small fw-bold">
                            Nombre de Referencia: <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="modal-tmpl-name" 
                            placeholder="Ej: Pozo Acumulado Fin de Semana" 
                            required
                        >
                        <div class="form-text small">Nombre para identificarla en tu lista izquierda.</div>
                    </div>
                    <div class="mb-3">
                        <label for="modal-tmpl-title" class="form-label text-dark small fw-bold">
                            Título de la Notificación: <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control fw-semibold" 
                            id="modal-tmpl-title" 
                            maxlength="80" 
                            placeholder="Ej: 🎉 ¡Gran Pozo Acumulado Esta Noche!" 
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label for="modal-tmpl-message" class="form-label text-dark small fw-bold">
                            Mensaje: <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            class="form-control" 
                            id="modal-tmpl-message" 
                            rows="4" 
                            maxlength="280" 
                            placeholder="Escribe el mensaje de la notificación..." 
                            required
                        ></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Cancelar</button>
                <button 
                    type="button" 
                    id="btn-save-modal-template" 
                    class="btn btn-primary fw-bold px-4 d-inline-flex align-items-center gap-2"
                    style="background: linear-gradient(145deg, #8767fa, #6236ff); border: 0;"
                >
                    <i class="fa-solid fa-floppy-disk" id="btn-save-modal-icon"></i>
                    <span id="btn-save-modal-text">Guardar Notificación</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos limpios y alineados al diseño de Rey Bingo -->
<style>
.admin-notifications-page {
    max-width: 1100px;
}
.admin-notifications-page .form-control,
.admin-notifications-page .form-select {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #1e293b !important;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.88rem;
    transition: all 0.2s ease;
}
.admin-notifications-page .form-control:focus,
.admin-notifications-page .form-select:focus {
    border-color: #6236ff !important;
    box-shadow: 0 0 0 3px rgba(98, 54, 255, 0.18) !important;
    background-color: #ffffff !important;
}
.saved-template-card {
    transition: all 0.2s ease;
    cursor: pointer;
    background: #ffffff;
    border-color: #e2e8f0 !important;
}
.saved-template-card:hover {
    transform: translateY(-2px);
    border-color: #c4b5fd !important;
    box-shadow: 0 4px 12px rgba(98, 54, 255, 0.08) !important;
}
.saved-template-card.active-template {
    border: 2px solid #6236ff !important;
    background-color: #f5f3ff !important;
    box-shadow: 0 4px 14px rgba(98, 54, 255, 0.16) !important;
}
.hover-opacity-100:hover {
    opacity: 1 !important;
}
</style>

<!-- Script interactivo de Notificaciones con Soporte para Campos Dinámicos de Variables -->
<script>
(function() {
    const inputTitle = document.getElementById('notif-title');
    const inputMsg = document.getElementById('notif-message');
    const btnSend = document.getElementById('btn-send-now');
    const btnSendIcon = document.getElementById('btn-send-icon');
    const btnSendText = document.getElementById('btn-send-text');
    const alertBox = document.getElementById('notif-alert-container');

    const titleCount = document.getElementById('title-char-count');
    const msgCount = document.getElementById('msg-char-count');

    const templateSearch = document.getElementById('template-search');
    const btnClearSearch = document.getElementById('btn-clear-search');
    const templatesContainer = document.getElementById('templates-list-container');
    const templatesCountBadge = document.getElementById('templates-count-badge');
    const templatesEmptyState = document.getElementById('templates-empty-state');
    const selectedTemplateName = document.getElementById('selected-template-name');

    // Contenedores de Campos Dinámicos de Variables
    const dynamicVarsWrapper = document.getElementById('dynamic-vars-wrapper');
    const dynamicVarsGrid = document.getElementById('dynamic-vars-grid');

    // Elementos del Modal
    const btnSaveModal = document.getElementById('btn-save-modal-template');
    const modalName = document.getElementById('modal-tmpl-name');
    const modalTitle = document.getElementById('modal-tmpl-title');
    const modalMsg = document.getElementById('modal-tmpl-message');
    const modalAlert = document.getElementById('modal-alert-container');

    // Diccionario amigable para nombres de variables
    const friendlyLabels = {
        'daily_bonus': 'Bono Diario (puntos / saldo)',
        'achievement_name': 'Nombre del Logro',
        'achievement_description': 'Descripción del Logro',
        'game_name': 'Nombre de la Partida / Sala',
        'game_date': 'Fecha de la Partida',
        'game_time': 'Hora de la Partida',
        'carton_price': 'Precio del Cartón',
        'level_name': 'Nombre del Nivel',
        'level_benefits': 'Beneficios del Nivel',
        'username': 'Nombre de Usuario',
        'amount': 'Monto ($)',
        'points': 'Puntos',
        'code': 'Código de Promoción'
    };

    // Valores por defecto sugeridos para rellenar automáticamente
    const smartDefaultValues = {
        'daily_bonus': '100',
        'achievement_name': 'Primer Bingo',
        'achievement_description': 'Por ganar tu primera partida en Rey Bingo',
        'game_name': 'Sala Oro',
        'game_date': 'Hoy',
        'game_time': '20:00',
        'carton_price': '$1.00',
        'level_name': 'Platino',
        'level_benefits': 'Cartones especiales y bonificaciones',
        'amount': '10.00',
        'points': '50',
        'code': 'BINGO'
    };

    let currentRawTitle = '';
    let currentRawMessage = '';

    function formatVarLabel(key) {
        if (friendlyLabels[key]) return friendlyLabels[key];
        return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    // 1. Extraer nombres de variables {{...}}
    function extractVariables(text) {
        if (!text) return [];
        const matches = text.match(/{{\s*([a-zA-Z0-9_-]+)\s*}}/g) || [];
        const vars = [];
        matches.forEach(m => {
            const v = m.replace(/[{}]/g, '').trim();
            if (v && !vars.includes(v)) {
                vars.push(v);
            }
        });
        return vars;
    }

    // 2. Renderizar inputs de variables para que el usuario no técnico los rellene
    function renderDynamicVariables(vars) {
        if (!dynamicVarsGrid || !dynamicVarsWrapper) return;
        dynamicVarsGrid.innerHTML = '';

        if (!vars || vars.length === 0) {
            dynamicVarsWrapper.style.display = 'none';
            return;
        }

        dynamicVarsWrapper.style.display = 'block';

        vars.forEach(v => {
            const label = formatVarLabel(v);
            const defaultVal = smartDefaultValues[v] || '';
            const col = document.createElement('div');
            col.className = vars.length === 1 ? 'col-12' : 'col-sm-6';
            col.innerHTML = `
                <div>
                    <label class="form-label text-dark fw-bold mb-1" style="font-size: 0.76rem;">
                        <i class="fa-solid fa-pen text-primary me-1" style="font-size: 0.72rem;"></i> ${escapeHtml(label)}:
                    </label>
                    <input 
                        type="text" 
                        class="form-control form-control-sm var-input" 
                        data-var="${escapeHtml(v)}" 
                        value="${escapeHtml(defaultVal)}" 
                        placeholder="Ingresa ${escapeHtml(label)}..."
                        style="font-size: 0.84rem; padding: 6px 10px; border-color: #cbd5e1;"
                    >
                </div>
            `;
            dynamicVarsGrid.appendChild(col);
        });

        // Escuchar cambios en cada campo dinámico
        dynamicVarsGrid.querySelectorAll('.var-input').forEach(inp => {
            inp.addEventListener('input', applyVariableReplacements);
        });
    }

    // 3. Reemplazar las variables dinámicas en el título y mensaje en tiempo real
    function applyVariableReplacements() {
        let compiledTitle = currentRawTitle;
        let compiledMessage = currentRawMessage;

        const inputs = dynamicVarsGrid ? dynamicVarsGrid.querySelectorAll('.var-input') : [];
        inputs.forEach(inp => {
            const v = inp.dataset.var;
            const typedVal = inp.value.trim();
            const val = typedVal !== '' ? typedVal : (smartDefaultValues[v] || `{{${v}}}`);
            const regex = new RegExp(`{{\\s*${v}\\s*}}`, 'g');
            compiledTitle = compiledTitle.replace(regex, val);
            compiledMessage = compiledMessage.replace(regex, val);
        });

        inputTitle.value = compiledTitle;
        inputMsg.value = compiledMessage;
        updateCharCounts();
    }

    // 4. Actualización de contadores
    function updateCharCounts() {
        if (titleCount) titleCount.textContent = (inputTitle.value.length) + '/80';
        if (msgCount) msgCount.textContent = (inputMsg.value.length) + '/280';
    }

    inputTitle.addEventListener('input', updateCharCounts);
    inputMsg.addEventListener('input', updateCharCounts);

    // 5. Selección de plantilla guardada
    function selectTemplate(cardEl) {
        if (!cardEl) return;
        templatesContainer.querySelectorAll('.saved-template-card').forEach(c => c.classList.remove('active-template'));
        cardEl.classList.add('active-template');

        currentRawTitle = cardEl.dataset.title || '';
        currentRawMessage = cardEl.dataset.message || '';
        const name = cardEl.dataset.name || '';

        // Detectar variables en título y mensaje
        const titleVars = extractVariables(currentRawTitle);
        const msgVars = extractVariables(currentRawMessage);
        const combinedVars = Array.from(new Set([...titleVars, ...msgVars]));

        renderDynamicVariables(combinedVars);
        applyVariableReplacements();

        if (selectedTemplateName) {
            selectedTemplateName.textContent = name ? '• ' + name : '';
        }

        inputTitle.style.backgroundColor = '#ede9fe';
        inputMsg.style.backgroundColor = '#ede9fe';
        setTimeout(() => {
            inputTitle.style.backgroundColor = '#ffffff';
            inputMsg.style.backgroundColor = '#ffffff';
        }, 300);
    }

    // 6. Eventos sobre las tarjetas de la lista (Click para cargar o eliminar)
    templatesContainer.addEventListener('click', function(e) {
        const delBtn = e.target.closest('.btn-del-tmpl');
        if (delBtn) {
            e.stopPropagation();
            const tmplId = delBtn.dataset.id;
            if (!confirm('¿Deseas eliminar esta notificación guardada?')) return;

            delBtn.disabled = true;
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
                    if (card) {
                        const wasActive = card.classList.contains('active-template');
                        card.remove();
                        updateCountBadge();
                        if (wasActive) {
                            const firstRemaining = templatesContainer.querySelector('.saved-template-card');
                            if (firstRemaining) selectTemplate(firstRemaining);
                        }
                    }
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message || 'No se pudo eliminar la notificación.');
                    delBtn.disabled = false;
                }
            })
            .catch(() => {
                showAlert('danger', 'Error de conexión al eliminar.');
                delBtn.disabled = false;
            });
            return;
        }

        const card = e.target.closest('.saved-template-card');
        if (card) {
            selectTemplate(card);
        }
    });

    // 7. Buscador en tiempo real de notificaciones guardadas
    function filterTemplates() {
        const q = (templateSearch.value || '').trim().toLowerCase();
        if (btnClearSearch) {
            btnClearSearch.style.display = q ? 'inline-block' : 'none';
        }

        const cards = templatesContainer.querySelectorAll('.saved-template-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const title = (card.dataset.title || '').toLowerCase();
            const msg = (card.dataset.message || '').toLowerCase();

            if (!q || name.includes(q) || title.includes(q) || msg.includes(q)) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (templatesEmptyState) {
            templatesEmptyState.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
        }
    }

    if (templateSearch) {
        templateSearch.addEventListener('input', filterTemplates);
    }
    if (btnClearSearch) {
        btnClearSearch.addEventListener('click', function() {
            templateSearch.value = '';
            filterTemplates();
            templateSearch.focus();
        });
    }

    function updateCountBadge() {
        const total = templatesContainer.querySelectorAll('.saved-template-card').length;
        if (templatesCountBadge) {
            templatesCountBadge.textContent = total;
        }
    }

    // 8. Guardar Nueva Notificación desde el Modal
    if (btnSaveModal) {
        btnSaveModal.addEventListener('click', function() {
            const name = modalName.value.trim();
            const title = modalTitle.value.trim();
            const message = modalMsg.value.trim();

            if (!name) {
                modalAlert.innerHTML = '<div class="alert alert-warning py-2 small mb-3">Por favor ingresa un nombre de referencia.</div>';
                modalName.focus();
                return;
            }
            if (!title) {
                modalAlert.innerHTML = '<div class="alert alert-warning py-2 small mb-3">Por favor ingresa el título de la notificación.</div>';
                modalTitle.focus();
                return;
            }
            if (!message) {
                modalAlert.innerHTML = '<div class="alert alert-warning py-2 small mb-3">Por favor ingresa el mensaje de la notificación.</div>';
                modalMsg.focus();
                return;
            }

            btnSaveModal.disabled = true;
            document.getElementById('btn-save-modal-icon').className = 'fa-solid fa-spinner fa-spin';

            const postData = new URLSearchParams();
            postData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            postData.append('name', name);
            postData.append('title', title);
            postData.append('message', message);

            fetch('<?= site_url('notificationsAdmin/saveTemplateAjax') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: postData.toString()
            })
            .then(res => res.json())
            .then(data => {
                btnSaveModal.disabled = false;
                document.getElementById('btn-save-modal-icon').className = 'fa-solid fa-floppy-disk';

                if (data.success) {
                    const tmpl = data.template;
                    const initialNone = document.getElementById('templates-none-initial');
                    if (initialNone) initialNone.remove();

                    // Insertar tarjeta arriba de la lista con formato amigable
                    const newCard = document.createElement('div');
                    newCard.className = 'saved-template-card p-2 px-3 rounded-3 border d-flex justify-content-between align-items-start gap-2 shadow-sm position-relative mb-2';
                    newCard.id = 'tmpl-card-' + tmpl.id;
                    newCard.dataset.id = tmpl.id;
                    newCard.dataset.name = tmpl.name;
                    newCard.dataset.title = tmpl.title_template;
                    newCard.dataset.message = tmpl.message_template;
                    newCard.setAttribute('role', 'button');
                    newCard.setAttribute('tabindex', '0');

                    const previewTitle = formatClientPreview(tmpl.title_template);
                    const previewMsg = formatClientPreview(tmpl.message_template);

                    newCard.innerHTML = `
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                <strong class="text-dark text-truncate tmpl-card-name" style="font-size: 0.85rem;">${escapeHtml(tmpl.name)}</strong>
                            </div>
                            <div class="text-primary small fw-semibold text-truncate tmpl-card-title mb-1" style="font-size: 0.78rem;">
                                ${previewTitle}
                            </div>
                            <p class="text-muted small mb-0 tmpl-card-message" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.74rem; line-height: 1.3;">
                                ${previewMsg}
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm text-danger p-0 ms-1 opacity-50 hover-opacity-100 btn-del-tmpl" data-id="${tmpl.id}" title="Eliminar notificación" onclick="event.stopPropagation();">
                            <i class="fa-solid fa-trash-can" style="font-size: 0.82rem;"></i>
                        </button>
                    `;

                    templatesContainer.prepend(newCard);
                    updateCountBadge();
                    selectTemplate(newCard);

                    // Cerrar modal
                    const modalEl = document.getElementById('modal-create-template');
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        bsModal.hide();
                    } else if (typeof $ !== 'undefined') {
                        $('#modal-create-template').modal('hide');
                    }

                    // Limpiar formulario de modal
                    modalName.value = '';
                    modalTitle.value = '';
                    modalMsg.value = '';
                    modalAlert.innerHTML = '';

                    showAlert('success', '¡Notificación guardada y lista en pantalla para enviar!');
                } else {
                    modalAlert.innerHTML = `<div class="alert alert-danger py-2 small mb-3">${data.message || 'Error al guardar la notificación.'}</div>`;
                }
            })
            .catch(err => {
                console.error(err);
                btnSaveModal.disabled = false;
                document.getElementById('btn-save-modal-icon').className = 'fa-solid fa-floppy-disk';
                modalAlert.innerHTML = '<div class="alert alert-danger py-2 small mb-3">Error de conexión al guardar.</div>';
            });
        });
    }

    function formatClientPreview(text) {
        if (!text) return '';
        return escapeHtml(text).replace(/{{\s*([a-zA-Z0-9_-]+)\s*}}/g, function(match, v) {
            const label = formatVarLabel(v);
            return `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1 py-0 rounded" style="font-size: 0.68rem; font-weight: 600;">[${escapeHtml(label)}]</span>`;
        });
    }

    // 9. Alertas en pantalla
    function showAlert(type, message, details) {
        let detailsHtml = '';
        if (details) {
            detailsHtml = `
                <div class="mt-2 pt-2 border-top border-secondary-subtle small d-flex flex-wrap gap-3">
                    <span><i class="fa-solid fa-users text-primary me-1"></i> Destinatarios: <strong>Todos los Jugadores (${details.users_targeted || 0})</strong></span>
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

    // 10. ENVÍO DE NOTIFICACIÓN
    btnSend.addEventListener('click', function(e) {
        e.preventDefault();

        const title = inputTitle.value.trim();
        const message = inputMsg.value.trim();

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

        btnSend.disabled = true;
        btnSendIcon.className = 'fa-solid fa-spinner fa-spin';
        btnSendText.textContent = 'Transmitiendo...';

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
            btnSendText.textContent = 'Enviar Notificación';

            if (data.success) {
                showAlert('success', data.message, data.details);

                if (typeof window.showNotification === 'function') {
                    window.showNotification({
                        title: title,
                        message: message,
                        type: 'admin'
                    });
                }
            } else {
                showAlert('danger', data.message || 'Error al enviar la notificación.');
            }
        })
        .catch(err => {
            console.error('Error al enviar notificación:', err);
            btnSend.disabled = false;
            btnSendIcon.className = 'fa-solid fa-paper-plane';
            btnSendText.textContent = 'Enviar Notificación';
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

    // Cargar automáticamente la primera plantilla al inicio si existe
    const firstCard = templatesContainer.querySelector('.saved-template-card');
    if (firstCard) {
        selectTemplate(firstCard);
    } else {
        updateCharCounts();
    }
})();
</script>