<div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2 text-uppercase"><i class="fa-duotone fa-chart-column"></i> <?= APP_NAME; ?> <?= translate('statistics'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <div class="modal-body pt-0">
            <div class="card mb-3">
                <div class="collapse show" id="filtersCollapse">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-3 mb-1 hidden">
                                <label for="modulefilter" class="form-label"><?= translate('module'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="modulefilter" id="modulefilter">
                                    <option value="summary"><?= translate('all modules') ?></option>
                                    <?php foreach ($modules as $key => $module): ?>
                                        <option value="<?= esc($key) ?>"><?= esc($module) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php $today = date('Y-m-d'); ?>
                            <div class="col-md-4 mb-1 hidden">
                                <label for="gamedatefilter" class="form-label"><?= translate('date'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="gamedatefilter" id="gamedatefilter">
                                    <option value="all"><?= translate('all dates') ?></option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="gamestatusfilter" class="form-label"><?= translate('status'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="gamestatusfilter" id="gamestatusfilter">
                                    <?php foreach ($status as $key => $statu): ?>
                                        <option value="<?= esc($key) ?>"><?= esc($statu) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="gameroomfilter" class="form-label"><?= translate('room'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="gameroomfilter" id="gameroomfilter">
                                    <option value="all"><?= translate('all rooms') ?></option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?= esc($room['id']) ?>"><?= esc($room['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="awardfilter" class="form-label"><?= translate('award type'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="awardfilter" id="awardfilter">
                                    <option value="all"><?= translate('all awards') ?></option>
                                    <?php foreach ($modalities as $modality): ?>
                                        <option value="<?= esc($modality['id']) ?>"><?= esc($modality['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="gamefilter" class="form-label"><?= translate('game type'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="gamefilter" id="gamefilter">
                                    <option value="all"><?= translate('all types') ?></option>
                                    <?php foreach ($gameTypes as $key => $type): ?>
                                        <option value="<?= esc($key) ?>"><?= esc($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-8 mb-1">
                                <label for="startdate" class="form-label"><?= translate('date range'); ?></label>
                                <div class="input-group">
                                    <input type="date" class="form-control form-control-lg form-bingo" id="startdate" name="startdate" value="<?= date('Y-m-01') ?>">
                                    <input type="date" class="form-control form-control-lg form-bingo" id="enddate" name="enddate" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="col-md-3 mb-1 d-flex align-items-end hidden">
                                <button class="btn btn-primary w-100" onclick="statisticsGet()">
                                    <i class="fa-duotone fa-filter"></i> <?= translate('filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-3">
                <ul class="nav nav-tabs" id="statisticsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab" aria-controls="summary" aria-selected="true" data-module="summary"><?= translate('summary'); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="games-tab" data-bs-toggle="tab" data-bs-target="#games" type="button" role="tab" aria-controls="games" aria-selected="false" data-module="games"><?= translate('games'); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false" data-module="users"><?= translate('top players'); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="false" data-module="transactions"><?= translate('transactions'); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="roulette-tab" data-bs-toggle="tab" data-bs-target="#roulette" type="button" role="tab" aria-controls="roulette" aria-selected="false" data-module="roulette"><?= translate('roulette'); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="referrals-tab" data-bs-toggle="tab" data-bs-target="#referrals" type="button" role="tab" aria-controls="referrals" aria-selected="false" data-module="referrals"><?= translate('referrals'); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="players-tab" data-bs-toggle="tab" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false" data-module="players"><?= translate('users'); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="operators-tab" data-bs-toggle="tab" data-bs-target="#operators" type="button" role="tab" aria-controls="operators" aria-selected="false" data-module="operators">Operadores</button>
                    </li>
                </ul>
            </div>

            <div class="col-md-12 tab-content" id="statisticsTabContent">
                <div class="tab-pane fade show active" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                    <div id="summary-content" class="statistics-content"></div>
                </div>
                <div class="tab-pane fade" id="games" role="tabpanel" aria-labelledby="games-tab">
                    <div id="games-content" class="statistics-content"></div>
                </div>
                <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                    <div id="users-content" class="statistics-content"></div>
                </div>
                <div class="tab-pane fade" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                    <div id="transactions-content" class="statistics-content"></div>
                </div>
                <div class="tab-pane fade" id="roulette" role="tabpanel" aria-labelledby="roulette-tab">
                    <div id="roulette-content" class="statistics-content"></div>
                </div>
                <div class="tab-pane fade" id="referrals" role="tabpanel" aria-labelledby="referrals-tab">
                    <div id="referrals-content" class="statistics-content"></div>
                </div>
                <div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
                    <div id="players-content" class="statistics-content"></div>
                </div>
                <div class="tab-pane fade" id="operators" role="tabpanel" aria-labelledby="operators-tab">
                    <div id="operators-content" class="statistics-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Variable para evitar bucles infinitos
    var isUpdatingFromCode = false;
    
    $(document).ready(function() {
        // Cargar estadísticas iniciales
        statisticsGet();
        
        // Manejar cambio de tabs
        $('#statisticsTabs button').on('click', function (e) {
            e.preventDefault();
            
            if (!isUpdatingFromCode) {
                var tabId = $(this).attr('id').replace('-tab', '');
                var moduleValue = getModuleFromTab(tabId);
                
                // Actualizar el select de módulo sin disparar el evento change
                isUpdatingFromCode = true;
                $('#modulefilter').val(moduleValue);
                isUpdatingFromCode = false;
                
                // Cargar contenido del tab
                statisticsGet(tabId);
            }
        });
        
        // Manejar cambio en el select de módulo
        $('#modulefilter').on('change', function() {
            if (!isUpdatingFromCode) {
                var selectedModule = $(this).val();
                var targetTab = getTabFromModule(selectedModule);
                
                // Solo cambiar si el tab objetivo no está ya activo
                var currentActiveTab = $('.nav-link.active').attr('id').replace('-tab', '');
                
                if (currentActiveTab !== targetTab) {
                    // Activar el tab correspondiente
                    isUpdatingFromCode = true;
                    
                    // Usar Bootstrap para cambiar tabs correctamente
                    var targetTabElement = document.getElementById(targetTab + '-tab');
                    var tab = new bootstrap.Tab(targetTabElement);
                    tab.show();
                    
                    isUpdatingFromCode = false;
                    
                    // Cargar contenido
                    statisticsGet(targetTab);
                }
            }
        });
        
        // Manejar cambios en otros filtros
        $('#gamedatefilter, #gamestatusfilter, #gameroomfilter, #awardfilter, #gamefilter, #startdate, #enddate').on('change', function() {
            var activeTab = $('.nav-link.active').attr('id').replace('-tab', '');
            statisticsGet(activeTab);
        });
    });

    // Función para mapear módulos a tabs (SOLO UNA DIRECCIÓN)
    function getTabFromModule(module) {
        var moduleToTab = {
            'games': 'games',        // games va SOLO a summary
            'users': 'users',
            'players': 'players',
            'operators': 'operators',
            'transactions': 'transactions',
            'deposits': 'transactions',
            'retires': 'transactions',
            'roulette': 'roulette',
            'referrals': 'referrals'
        };
        
        return moduleToTab[module] || 'summary';
    }

    // Función para mapear tabs a módulos
    function getModuleFromTab(tab) {
        var tabToModule = {
            'summary': 'games',        // summary usa módulo games
            'games': 'games',          // games tab usa módulo games
            'users': 'users',
            'players': 'players',
            'operators': 'operators',
            'transactions': 'transactions',
            'roulette': 'roulette',
            'referrals': 'referrals'
        };
        
        return tabToModule[tab] || 'games';
    }

    function statisticsGet(activeTab = 'summary', extraParams = {}) {
    // Obtener valores de los filtros principales
    var modulefilter = $('#modulefilter').val() || 'games';
    var gamedatefilter = $('#gamedatefilter').val() || 'all';
    var gamestatusfilter = $('#gamestatusfilter').val() || 'all';
    var gameroomfilter = $('#gameroomfilter').val() || 'all';
    var awardfilter = $('#awardfilter').val() || 'all';
    var gamefilter = $('#gamefilter').val() || 'all';
    var startdate = $('#startdate').val() || '';
    var enddate = $('#enddate').val() || '';
    
    // Usar el módulo correspondiente al tab activo
    var actualModule = getModuleFromTab(activeTab);
    
    // Parámetros base para la petición
    var requestData = {
        gamefilter: gamefilter,
        startdate: startdate,
        enddate: enddate,
        activeTab: activeTab
    };
    
    // Agregar parámetros específicos según el tab activo
    switch(activeTab) {
        case 'players':
            // Parámetros específicos para gestión de usuarios
            requestData.search = extraParams.search || $('#searchUsers').val() || '';
            requestData.status = extraParams.status || $('#statusFilter').val() || 'all';
            requestData.group = extraParams.group || $('#groupFilter').val() || 'all';
            requestData.page = extraParams.page || $('#currentPage').val() || 1;
            requestData.per_page = extraParams.per_page || 20;
            break;

        case 'operators':
            // Parámetros específicos para operadores
            requestData.search = extraParams.search || $('#searchOperators').val() || '';
            requestData.status = extraParams.status || $('#statusFilterOperators').val() || 'all';
            requestData.page = extraParams.page || $('#currentPageOperators').val() || 1;
            requestData.per_page = extraParams.per_page || 10;
            break;
            
        case 'transactions':
            // Parámetros específicos para transacciones
            requestData.transaction_type = extraParams.transaction_type || $('#transactionTypeFilter').val() || 'all';
            requestData.transaction_status = extraParams.transaction_status || $('#transactionStatusFilter').val() || 'all';
            requestData.user_search = extraParams.user_search || $('#userSearchFilter').val() || '';
            requestData.amount_min = extraParams.amount_min || $('#amountMinFilter').val() || '';
            requestData.amount_max = extraParams.amount_max || $('#amountMaxFilter').val() || '';
            break;
            
        case 'users':
            // Parámetros específicos para top players
            requestData.order_by = extraParams.order_by || $('#orderByFilter').val() || 'wallet';
            requestData.limit = extraParams.limit || $('#limitFilter').val() || 50;
            break;
            
        case 'games':
            // Parámetros específicos para juegos
            requestData.game_search = extraParams.game_search || $('#gameSearchFilter').val() || '';
            requestData.winner_filter = extraParams.winner_filter || $('#winnerFilter').val() || 'all';
            break;
            
        case 'roulette':
            // Parámetros específicos para ruleta
            requestData.roulette_type = extraParams.roulette_type || $('#rouletteTypeFilter').val() || 'all';
            requestData.result_filter = extraParams.result_filter || $('#resultFilter').val() || 'all';
            break;
            
        case 'referrals':
            // Parámetros específicos para referidos
            requestData.referral_status = extraParams.referral_status || $('#referralStatusFilter').val() || 'all';
            requestData.commission_min = extraParams.commission_min || $('#commissionMinFilter').val() || '';
            break;
    }
    
    // Combinar con parámetros extra adicionales
    requestData = { ...requestData, ...extraParams };
    
    // Mostrar indicador de carga
    showLoadingIndicator(activeTab);
    
    // Realizar petición AJAX
    $.ajax({
        url: '<?= site_url('games/statisticsGet') ?>/' + actualModule + '/' + gamedatefilter + '/' + gamestatusfilter + '/' + gameroomfilter + '/' + awardfilter,
        type: "GET",
        data: requestData,
        dataType: 'html',
        timeout: 30000, // 30 segundos de timeout
        beforeSend: function(xhr) {
            // Agregar token CSRF si existe
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            }
        },
        success: function(data) {
            try {
                // Actualizar contenido del tab
                $("#" + activeTab + "-content").html(data);
                
                // Ocultar indicador de carga
                hideLoadingIndicator(activeTab);
                
                // Ejecutar callbacks específicos según el tab
                executeTabCallbacks(activeTab, requestData);
                
                // Actualizar URL si es necesario (para bookmarking)
                updateUrlParams(activeTab, requestData);
                
                // Mostrar mensaje de éxito si hay datos
                if (data.trim() !== '') {
                    console.log('Estadísticas cargadas correctamente para tab: ' + activeTab);
                }
                
            } catch (error) {
                console.error('Error procesando respuesta:', error);
                showErrorMessage(activeTab, 'Error procesando los datos recibidos');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en petición AJAX:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            hideLoadingIndicator(activeTab);
            
            var errorMessage = '';
            
            switch(xhr.status) {
                case 0:
                    errorMessage = '<?= translate('connection error. please check your internet connection.'); ?>';
                    break;
                case 404:
                    errorMessage = '<?= translate('requested resource not found.'); ?>';
                    break;
                case 500:
                    errorMessage = '<?= translate('internal server error. please try again later.'); ?>';
                    break;
                case 403:
                    errorMessage = '<?= translate('access denied. insufficient permissions.'); ?>';
                    break;
                case 408:
                    errorMessage = '<?= translate('request timeout. please try again.'); ?>';
                    break;
                default:
                    if (status === 'timeout') {
                        errorMessage = '<?= translate('request timeout. the server is taking too long to respond.'); ?>';
                    } else if (status === 'parsererror') {
                        errorMessage = '<?= translate('error parsing server response.'); ?>';
                    } else {
                        errorMessage = '<?= translate('there was an error in the request to the server.'); ?>';
                    }
            }
            
            // Mostrar error en el contenido del tab
            showErrorMessage(activeTab, errorMessage);
            
            // Mostrar toast de error
            Toastify({
                text: errorMessage,
                duration: 5000,
                gravity: "top",
                position: "right",
                style: { background: "#dc3545" },
                stopOnFocus: true,
                close: true
            }).showToast();
        },
        complete: function() {
            // Siempre ejecutar al completar (éxito o error)
            hideLoadingIndicator(activeTab);
        }
    });
}

// Función para mostrar indicador de carga
function showLoadingIndicator(activeTab) {
    var loadingHtml = `
        <div class="d-flex justify-content-center align-items-center py-5" id="loading-${activeTab}">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"><?= translate('loading'); ?>...</span>
            </div>
            <span class="ms-2"><?= translate('loading data'); ?>...</span>
        </div>
    `;
    $("#" + activeTab + "-content").html(loadingHtml);
}

// Función para ocultar indicador de carga
function hideLoadingIndicator(activeTab) {
    $("#loading-" + activeTab).remove();
}

// Función para mostrar mensajes de error
function showErrorMessage(activeTab, message) {
    var errorHtml = `
        <div class="alert alert-danger text-center py-4" role="alert">
            <i class="fa-duotone fa-exclamation-triangle fa-2x mb-3"></i>
            <h5><?= translate('error loading data'); ?></h5>
            <p class="mb-3">${message}</p>
            <button class="btn btn-outline-danger" onclick="statisticsGet('${activeTab}')">
                <i class="fa-duotone fa-refresh"></i> <?= translate('try again'); ?>
            </button>
        </div>
    `;
    $("#" + activeTab + "-content").html(errorHtml);
}

// Función para ejecutar callbacks específicos por tab
function executeTabCallbacks(activeTab, requestData) {
    switch(activeTab) {
        case 'players':
            // Inicializar eventos específicos de usuarios
            initializeUsersEvents();
            break;
            
        case 'games':
            // Inicializar gráficos de juegos si existen
            if (typeof initializeGameCharts === 'function') {
                initializeGameCharts();
            }
            break;
            
        case 'transactions':
            // Inicializar eventos de transacciones
            if (typeof initializeTransactionEvents === 'function') {
                initializeTransactionEvents();
            }
            break;
            
        case 'summary':
            // Inicializar gráficos del resumen
            if (typeof initializeSummaryCharts === 'function') {
                initializeSummaryCharts();
            }
            break;
    }
}

// Función para actualizar parámetros de URL (opcional)
function updateUrlParams(activeTab, requestData) {
    if (history.pushState) {
        var url = new URL(window.location);
        url.searchParams.set('tab', activeTab);
        
        // Agregar parámetros relevantes a la URL
        if (requestData.search && requestData.search !== '') {
            url.searchParams.set('search', requestData.search);
        } else {
            url.searchParams.delete('search');
        }
        
        if (requestData.page && requestData.page !== 1) {
            url.searchParams.set('page', requestData.page);
        } else {
            url.searchParams.delete('page');
        }
        
        window.history.replaceState({}, '', url);
    }
}

// Función para inicializar eventos específicos de usuarios
function initializeUsersEvents() {
    // Evento para búsqueda en tiempo real (con debounce)
    var searchTimeout;
    $('#searchUsers').off('input').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchUsers();
        }, 500); // Esperar 500ms después de que el usuario deje de escribir
    });
    
    // Eventos para filtros
    $('#statusFilter, #groupFilter').off('change').on('change', function() {
        filterUsers();
    });
    
    // Evento para paginación
    $(document).off('click', '.pagination a').on('click', '.pagination a', function(e) {
        e.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        if (page) {
            statisticsGet('players', { page: page });
        }
    });
}

// Función auxiliar para refrescar el tab actual
function refreshCurrentTab() {
    var activeTab = $('.nav-link.active').attr('id').replace('-tab', '');
    statisticsGet(activeTab);
}

// Función para limpiar todos los filtros
function clearAllFilters() {
    $('#searchUsers').val('');
    $('#statusFilter').val('all');
    $('#groupFilter').val('all');
    $('#gamestatusfilter').val('all');
    $('#gameroomfilter').val('all');
    $('#awardfilter').val('all');
    $('#gamefilter').val('all');
    
    // Refrescar tab actual
    refreshCurrentTab();
}

// Función para exportar datos (opcional)
function exportTabData(activeTab, format = 'excel') {
    var requestData = {
        export: true,
        format: format,
        activeTab: activeTab
    };
    
    // Agregar filtros actuales
    switch(activeTab) {
        case 'players':
            requestData.search = $('#searchUsers').val() || '';
            requestData.status = $('#statusFilter').val() || 'all';
            requestData.group = $('#groupFilter').val() || 'all';
            break;
    }
    
    // Crear formulario temporal para descarga
    var form = $('<form>', {
        method: 'POST',
        action: '<?= site_url('games/exportStatistics') ?>'
    });
    
    // Agregar campos del formulario
    $.each(requestData, function(key, value) {
        form.append($('<input>', {
            type: 'hidden',
            name: key,
            value: value
        }));
    });
    
    // Agregar token CSRF
    form.append($('<input>', {
        type: 'hidden',
        name: '<?= csrf_token() ?>',
        value: '<?= csrf_hash() ?>'
    }));
    
    // Enviar formulario
    form.appendTo('body').submit().remove();
}

// ── Global Helper for User Details Modal ───────────────────
if (typeof window.viewUser !== 'function') {
    window.viewUser = function(userId) {
        $.ajax({
            url: '<?= site_url('users/getUserDetails/'); ?>' + userId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.html) {
                    $('#userDetailsContent').html(response.html);
                    $('#modalUserDetails').modal('show');
                } else {
                    Toastify({
                        text: (response && response.error) ? response.error : "<?= translate('user not found'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                }
            },
            error: function() {
                Toastify({
                    text: "<?= translate('there was an error in the request to the server'); ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            }
        });
    };
}

if (typeof window.revokeUserKyc !== 'function') {
    window.revokeUserKyc = function(userId) {
        if (!confirm("<?= translate('confirm remove kyc verification'); ?>")) {
            return;
        }
        $.ajax({
            url: '<?= site_url('users/revokeKyc'); ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                user_id: userId,
                '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
            },
            success: function(response) {
                Toastify({
                    text: response.message || (response.success ? 'OK' : 'Error'),
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: response.success ? "#198754" : "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                if (response.success) {
                    window.viewUser(userId);
                }
            }
        });
    };
}

if (typeof window.saveDocumentExpiry !== 'function') {
    window.saveDocumentExpiry = function(userId) {
        var value = $('#admin-document-expires-at').val() || '';
        $.ajax({
            url: '<?= site_url('users/saveDocumentExpiry'); ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                user_id: userId,
                document_expires_at: value,
                '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
            },
            success: function(response) {
                Toastify({
                    text: response.message || (response.success ? 'OK' : 'Error'),
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: response.success ? "#198754" : "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                if (response.success) {
                    window.viewUser(userId);
                }
            }
        });
    };
}

if (typeof window.savePlayerWallets !== 'function') {
    window.savePlayerWallets = function(userId) {
        $.ajax({
            url: '<?= site_url('users/updatePlayerWallets'); ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                user_id: userId,
                wallet_bonus: $('#admin-wallet-bonus').val() || 0,
                wallet_recharge: $('#admin-wallet-recharge').val() || 0,
                wallet_withdraw: $('#admin-wallet-withdraw').val() || 0,
                '<?= csrf_token(); ?>': '<?= csrf_hash(); ?>'
            },
            success: function(response) {
                Toastify({
                    text: response.message || response.error || (response.success ? 'OK' : 'Error'),
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: response.success ? "#198754" : "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                if (response.success) {
                    window.viewUser(userId);
                }
            }
        });
    };
}
</script>
