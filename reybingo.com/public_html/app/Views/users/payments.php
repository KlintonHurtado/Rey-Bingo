<div class="modal-dialog modal-dialog-centered games max-w-80">
    <div class="modal-content">
        
        <!-- HEADER -->
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2">
                <i class="fa-duotone fa-solid fa-wallet"></i>
                <?php if (session()->get('group') == 1) : ?>
                    <?= translate('deposits, retires, payments and transfers'); ?>
                <?php else :
                    $user = wallet_service()->normalizeUser($user);
                    $walletTotal = wallet_total($user);
                    ?>
                    <?= translate('my wallet'); ?> <?= systemGet('currency'); ?>
                    <span class="available-wallet"><?= number_format($walletTotal, 2); ?></span>
                <?php endif; ?>
            </h6>
            <button type="button" class="btn-close me-1" data-bs-dismiss="modal" aria-label="close">
                <i class="fa-duotone fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <!-- BODY -->
        <div class="modal-body pt-0">

            <!-- BOTONES ACCIONES -->
            <?php if (session()->get('group') == 0) : ?>
                <div class="text-center">
                    <?php if (systemGet('activateDeposit') == 1) : ?>
                        <button type="button" class="btn btn-primary btn-bingo inline" onclick="depositGet();">
                            <i class="fa-duotone fa-arrow-down-to-bracket"></i> <?= translate('deposit'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if (systemGet('activateRetire') == 1) : ?>
                        <button type="button" class="btn btn-primary btn-bingo inline" onclick="retireGet();">
                            <i class="fa-duotone fa-arrow-up-from-bracket"></i> <?= translate('retire'); ?>
                        </button>
                    <?php endif; ?>
                    <?php /* P2P oculto para jugadores */ ?>
                    <?php if (session()->get('group') == 0) : ?>
                        <button type="button" class="btn btn-primary btn-bingo inline" onclick="settingswalletGet();">
                            <i class="fa-duotone fa-solid fa-building-columns"></i> <?= translate('my bank'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <hr class="my-2">

                <?php
                    $user = wallet_service()->normalizeUser($user);
                    $walletTotal = wallet_total($user);
                ?>
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <div class="card bingo-bg-success text-white m-0">
                            <div class="card-body text-center p-2">
                                <small><?= translate('deposit'); ?></small>
                                <div class="fw-bold"><?= systemGet('currency'); ?> <span class="wallet-recharge-value"><?= number_format($user['wallet_recharge'], 2); ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card text-white m-0" style="background: #DD3A35;">
                            <div class="card-body text-center p-2">
                                <small><?= translate('retire'); ?></small>
                                <div class="fw-bold"><?= systemGet('currency'); ?> <span class="wallet-withdraw-value"><?= number_format($user['wallet_withdraw'], 2); ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card bingo-bg-bonus m-0">
                            <div class="card-body text-center p-2">
                                <small>Bono</small>
                                <div class="fw-bold"><?= systemGet('currency'); ?> <span class="wallet-bonus-value"><?= number_format($user['wallet_bonus'], 2); ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-center small mb-2">Total: <strong><?= systemGet('currency'); ?> <span class="available-wallet wallet-total-value"><?= number_format($walletTotal, 2); ?></span></strong></p>
            <?php endif; ?>

            <!-- TARJETAS DE ESTADÍSTICAS -->
            <?php if (session()->get('group') == 1 && !empty($statistics)) : ?>
                <div class="card mb-3">
                    <div class="row" id="statistics-cards">
                        <div class="col-sm-12 col-md-3">
                            <div class="card bingo-bg-primary text-white m-2">
                                <div class="card-body text-center">
                                    <h2 class="card-text" id="stat-amount"><?= systemGet('currency'); ?> <?= number_format($statistics['total_amount'], 2); ?></h2>
                                    <span id="stat-total"><?= number_format($statistics['total_transactions']); ?></span> 
                                    <span><?= translate('transactions'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-3">
                            <div class="card bingo-bg-success text-white m-2">
                                <div class="card-body text-center">
                                    <h2 class="card-text" id="stat-deposits-amount"><?= systemGet('currency'); ?> <?= number_format($statistics['deposits']['amount'], 2); ?></h2>
                                    <span id="stat-deposits"><?= number_format($statistics['deposits']['count']); ?></span> 
                                    <span><?= translate('deposits'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-3">
                            <div class="card bingo-bg-danger text-white m-2">
                                <div class="card-body text-center">
                                    <h2 class="card-text" id="stat-retires-amount"><?= systemGet('currency'); ?> <?= number_format($statistics['retires']['amount'], 2); ?></h2>
                                    <span id="stat-retires"><?= number_format($statistics['retires']['count']); ?></span> 
                                    <span><?= translate('retires'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-3">
                            <div class="card bingo-bg-orange text-white m-2">
                                <div class="card-body text-center">
                                    <h2 class="card-text" id="stat-payments-amount"><?= systemGet('currency'); ?> <?= number_format($statistics['payments']['amount'], 2); ?></h2>
                                    <span id="stat-payments"><?= number_format($statistics['payments']['count']); ?></span> 
                                    <span><?= translate('payments'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (session()->get('group') == 1 && !empty($adminKpis)) : ?>
                <div class="row g-2 mb-2">
                    <div class="col-sm-4">
                        <div class="card bingo-bg-primary text-white m-0">
                            <div class="card-body text-center p-2">
                                <small>Acreditación manual</small>
                                <div class="fw-bold"><?= systemGet('currency'); ?> <?= number_format($adminKpis['manual_credits'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bingo-bg-danger text-white m-0">
                            <div class="card-body text-center p-2">
                                <small>Gasto de usuarios</small>
                                <div class="fw-bold"><?= systemGet('currency'); ?> <?= number_format($adminKpis['user_spend'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bingo-bg-success text-white m-0">
                            <div class="card-body text-center p-2">
                                <small>Total de premios</small>
                                <div class="fw-bold"><?= systemGet('currency'); ?> <?= number_format($adminKpis['total_prizes'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FILTROS -->
            <div class="card mb-3">
                <div class="collapse show" id="filtersCollapse">
                    <div class="card-body p-3">
                        <?php if (session()->get('group') == 1) : ?>
                            <button type="button" class="btn btn-small btn-primary btn-modal-add text-white float-end mt-4 btn-add-new" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= translate('deposit'); ?>" onclick="depositGet();"><i class="fa-duotone fa-solid fa-plus"></i></button>
                        <?php endif; ?>
                        <div class="row g-2">
                            <!-- Búsqueda general -->
                            <div class="col-md-6 <?php if (session()->get('group') == 0) : ?> hidden <?php endif; ?>">
                                <label class="form-label small"><?= translate('search'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" id="search" name="search" placeholder="<?= translate('reference, bank, user'); ?>..." value="<?= esc($filters['search'] ?? ''); ?>">
                            </div>
                            
                            <!-- Tipo de transacción -->
                            <div class="col-md-3">
                                <label class="form-label small"><?= translate('transaction type'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="type" name="type">
                                    <option value="all" <?= ($filters['type'] ?? 'all') == 'all' ? 'selected' : ''; ?>><?= translate('all types'); ?></option>
                                    <option value="deposit" <?= ($filters['type'] ?? '') == 'deposit' ? 'selected' : ''; ?>><?= translate('deposit'); ?></option>
                                    <option value="retire" <?= ($filters['type'] ?? '') == 'retire' ? 'selected' : ''; ?>><?= translate('retire'); ?></option>
                                    <option value="transfer" <?= ($filters['type'] ?? '') == 'transfer' ? 'selected' : ''; ?>><?= translate('transfer'); ?></option>
                                    <option value="payment" <?= ($filters['type'] ?? '') == 'payment' ? 'selected' : ''; ?>><?= translate('payment'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Estado -->
                            <div class="col-md-3">
                                <label class="form-label small"><?= translate('status'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="status" name="status">
                                    <option value="all" <?= ($filters['status'] ?? 'all') == 'all' ? 'selected' : ''; ?>><?= translate('all status'); ?></option>
                                    <option value="1" <?= ($filters['status'] ?? '') == '1' ? 'selected' : ''; ?>><?= translate('pending'); ?></option>
                                    <option value="2" <?= ($filters['status'] ?? '') == '2' ? 'selected' : ''; ?>><?= translate('approved'); ?></option>
                                    <option value="0" <?= ($filters['status'] ?? '') == '0' ? 'selected' : ''; ?>><?= translate('rejected'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Usuario (solo para admin) -->
                            <?php if (session()->get('group') == 1 && !empty($users)) : ?>
                            <div class="col-md-6">
                                <label class="form-label small"><?= translate('user'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="user_id" name="user_id">
                                    <option value="all" <?= ($filters['user_id'] ?? 'all') == 'all' ? 'selected' : ''; ?>><?= translate('all users'); ?></option>
                                    <?php foreach ($users as $userOption) : ?>
                                    <option value="<?= $userOption['id']; ?>" <?= ($filters['user_id'] ?? '') == $userOption['id'] ? 'selected' : ''; ?>>
                                        <?= esc($userOption['code'] . ' - ' . $userOption['firstname'] . ' ' . $userOption['lastname']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Fecha desde -->
                            <div class="col-md-3">
                                <label class="form-label small"><?= translate('from date'); ?></label>
                                <input type="date" class="form-control form-control-lg form-bingo" id="date_from" name="date_from" value="<?= esc($filters['date_from'] ?? ''); ?>">
                            </div>
                            
                            <!-- Fecha hasta -->
                            <div class="col-md-3">
                                <label class="form-label small"><?= translate('to date'); ?></label>
                                <input type="date" class="form-control form-control-lg form-bingo" id="date_to" name="date_to" value="<?= esc($filters['date_to'] ?? ''); ?>">
                            </div>
                            
                            <!-- Registros por página -->
                            <div class="col-lg-1 col-md-2 col-sm-6 hidden">
                                <label class="form-label small"><?= translate('per page'); ?></label>
                                <select class="form-control form-control-lg form-bingo" id="per_page" name="per_page">
                                    <option value="10" <?= ($filters['per_page'] ?? 15) == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="15" <?= ($filters['per_page'] ?? 15) == 15 ? 'selected' : ''; ?>>15</option>
                                    <option value="25" <?= ($filters['per_page'] ?? 15) == 25 ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?= ($filters['per_page'] ?? 15) == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?= ($filters['per_page'] ?? 15) == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3 hidden">
                            <div class="col-12">
                                <button type="button" class="btn btn-primary btn-sm" id="apply-filters">
                                    <i class="fa fa-search"></i> <?= translate('apply filters'); ?>
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="clear-filters">
                                    <i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('clear filters'); ?>
                                </button>
                                <button type="button" class="btn btn-success btn-sm" id="export-data">
                                    <i class="fa fa-download"></i> <?= translate('export'); ?>
                                </button>
                                <span class="text-muted small ms-3" id="results-count">
                                    <?= translate('showing'); ?> <span id="showing-count"><?= count($payments); ?></span> 
                                    <?= translate('of'); ?> <span id="total-count"><?= count($payments); ?></span> 
                                    <?= translate('results'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LOADER -->
            <div class="card d-none" id="loading-spinner">
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden"><?= translate('loading'); ?>...</span>
                        </div>
                        <span class="ms-2"><?= translate('loading data'); ?>...</span>
                    </div>
                </div>
            </div>

            <!-- TABLA -->
            <div class="card" id="payments-list">
                <div class="card-body">
                    <div class="table-responsive" id="payments-table-container">
                        <table class="table table-striped table-hover table-sm mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center"><?= translate('type'); ?></th>
                                    <th><?= translate('reference'); ?></th>
                                    <?php if (session()->get('group') == 1) : ?>
                                    <th><?= translate('holder'); ?></th>
                                    <?php endif; ?>
                                    <th><?= translate('information'); ?></th>
                                    <th class="text-center"><?= translate('amount'); ?></th>
                                    <th class="text-center"><?= translate('date'); ?></th>
                                    <th class="text-center"><?= translate('status'); ?></th>
                                    <?php if (session()->get('group') == 1) : ?>
                                    <th class="text-center"><?= translate('options'); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="payments-tbody">
                                <?php if (!empty($payments)) : ?>
                                    <?php foreach ($payments as $payment) : ?>
                                    <tr data-id="<?= $payment['id']; ?>" data-type="<?= $payment['type']; ?>">
                                        <td class="text-center">
                                            <?php
                                                $typeIcons = [
                                                    'deposit' => '<i class="fa-duotone fa-solid fa-arrow-down-to-line text-success"></i>',
                                                    'retire' => '<i class="fa-duotone fa-solid fa-arrow-up-from-bracket icon-danger"></i>',
                                                    'transfer' => '<i class="fa-duotone fa-solid fa-arrow-right-arrow-left text-info"></i>',
                                                    'payment' => '<i class="fa-duotone fa-solid fa-credit-card text-primary"></i>',
                                                    'bonus' => '<i class="fa-duotone fa-solid fa-gift icon-bonus"></i>',
                                                ];

                                                echo $typeIcons[$payment['type']] ?? '<i class="fa-duotone fa-solid fa-circle-question"></i>';
                                            ?>
                                            <br>
                                            <small class="text-muted"><?= esc($payment['type_Tra'] ?? translate($payment['type'])); ?></small>
                                        </td>
                                        <td>
                                            <strong><?= esc($payment['reference']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?= $payment['date_formatted']; ?></small>
                                        </td>
                                        <?php if (session()->get('group') == 1) : ?>
                                        <td>
                                            <strong><?= esc($payment['user_code']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?= esc($payment['user_name']); ?></small>
                                        </td>
                                        <?php endif; ?>
                                        <td><?= $payment['bank']; ?></td>
                                        <td class="text-center">
                                            <?php if ($payment['type'] == 'retire'): ?>
                                                <!-- Retiro normal -->
                                                <strong class="icon-danger">
                                                    -<?= systemGet('currency'); ?> <?= number_format($payment['amount'], 2); ?>
                                                </strong>
                                            <?php elseif ($payment['type'] == 'transfer'): ?>
                                                <!-- Transferencia: negativo y positivo -->
                                                <div>
                                                    <?php if (session()->get('group') == 1) : ?>
                                                        <strong class="icon-danger d-block">
                                                            -<?= systemGet('currency'); ?> <?= number_format($payment['amount'], 2); ?>
                                                        </strong>
                                                        <strong class="text-success d-block">
                                                            +<?= systemGet('currency'); ?> <?= number_format($payment['amount'], 2); ?>
                                                        </strong>
                                                    <?php else: ?>
                                                        <?php if (session()->get('id') != $payment['user_id']) : ?>
                                                            <strong class="icon-danger d-block">
                                                                -<?= systemGet('currency'); ?> <?= number_format($payment['amount'], 2); ?>
                                                            </strong>
                                                        <?php else: ?>
                                                            <strong class="text-success d-block">
                                                                +<?= systemGet('currency'); ?> <?= number_format($payment['amount'], 2); ?>
                                                            </strong>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($payment['type'] == 'bonus'): ?>
                                                <strong class="text-bonus">
                                                    +<?= systemGet('currency'); ?> <?= number_format($payment['amount'], 2); ?>
                                                </strong>
                                            <?php else: ?>
                                                <!-- Otro tipo de ingreso -->
                                                <strong class="text-success">
                                                    +<?= systemGet('currency'); ?> <?= number_format($payment['amount'], 2); ?>
                                                </strong>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <small><?= $payment['created_at']; ?></small>
                                        </td>
                                        <td class="text-center" id="<?= esc($payment['type']) ?>-<?= $payment['id'] ?>">
                                            <span class="status-badge" data-status="<?= $payment['status_raw']; ?>">
                                                <?= $payment['status_formatted']; ?>
                                            </span>
                                        </td>
                                        <?php if (session()->get('group') == 1) : ?>
                                        <td class="text-center">
                                            <a class="btn btn-primary btn-modal text-white" onclick="requestGet('<?= $payment['type'] ?>', '<?= $payment['id'] ?>');"><i class="fa-duotone fa-solid fa-eye"></i></a>
                                            <!--  
                                            <?php if ($payment['type'] !== 'transfer') : ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($payment['status_raw'] != 1) : ?>
                                                <button type="button" class="btn btn-success btn-sm" 
                                                        onclick="updateStatus('<?= $payment['type']; ?>', <?= $payment['id']; ?>, 1)"
                                                        title="<?= translate('approve'); ?>">
                                                    <i class="fa-duotone fa-solid fa-check-double"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($payment['status_raw'] != 2) : ?>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="updateStatus('<?= $payment['type']; ?>', <?= $payment['id']; ?>, 2)"
                                                        title="<?= translate('reject'); ?>">
                                                    <i class="fa-duotone fa-solid fa-xmark"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($payment['type'] == 'deposit' && $payment['status_raw'] != 3) : ?>
                                                <button type="button" class="btn btn-info btn-sm" 
                                                        onclick="updateStatus('<?= $payment['type']; ?>', <?= $payment['id']; ?>, 3)"
                                                        title="<?= translate('verify'); ?>">
                                                    <i class="fa fa-shield-check"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php else : ?>
                                            <span class="text-muted small"><?= translate('approved'); ?></span>
                                            <?php endif; ?>
                                            -->
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr id="not-list">
                                        <td colspan="<?= session()->get('group') == 1 ? '8' : '6'; ?>" class="text-center pt-2">
                                            <div class="text-muted">
                                                <?= translate('no transactions found'); ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($pagination) && $pagination['total_pages'] > 1) : ?>
                        <div class="row mt-4">
                            <div class="col-12 col-md text-center mt-2 mb-sm-3">
                                <span class="text-muted">
                                    <?= translate('page'); ?> <?= $pagination['current_page']; ?> <?= translate('of'); ?> <?= $pagination['total_pages']; ?>
                                    (<?= number_format($pagination['total']); ?> <?= translate('total records'); ?>)
                                </span>
                            </div>
                            <div class="col-12 col-md text-center">
                                <nav class="d-flex justify-content-center align-items-center">
                                    <ul class="pagination">
                                        <!-- Primera página -->
                                        <li class="page-item <?= !$pagination['has_previous'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="javascript:void(0);" data-page="1" <?= !$pagination['has_previous'] ? 'tabindex="-1"' : ''; ?>>
                                                <i class="fa fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Página anterior -->
                                        <li class="page-item <?= !$pagination['has_previous'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="javascript:void(0);" data-page="<?= $pagination['previous_page']; ?>" <?= !$pagination['has_previous'] ? 'tabindex="-1"' : ''; ?>>
                                                <i class="fa fa-angle-left"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Páginas numeradas -->
                                        <?php
                                        $start = max(1, $pagination['current_page'] - 2);
                                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                        
                                        for ($i = $start; $i <= $end; $i++) :
                                        ?>
                                        <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="javascript:void(0);" data-page="<?= $i; ?>"><?= $i; ?></a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <!-- Página siguiente -->
                                        <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="javascript:void(0);" data-page="<?= $pagination['next_page']; ?>" <?= !$pagination['has_next'] ? 'tabindex="-1"' : ''; ?>>
                                                <i class="fa fa-angle-right"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Última página -->
                                        <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="javascript:void(0);" data-page="<?= $pagination['total_pages']; ?>" <?= !$pagination['has_next'] ? 'tabindex="-1"' : ''; ?>>
                                                <i class="fa fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Variables globales
        let currentFilters = {};
        let isLoading = false;

        // Inicializar filtros desde la URL o valores por defecto
        initializeFilters();

        // Event listeners para filtros
        setupFilterEvents();

        // Event listeners para paginación
        setupPaginationEvents();

        // Event listeners para colapsar filtros
        setupCollapseEvents();

        /**
         * Inicializar filtros
         */
        function initializeFilters() {
            currentFilters = {
                search: $('#search').val() || '',
                type: $('#type').val() || 'all',
                status: $('#status').val() || 'all',
                user_id: $('#user_id').val() || 'all',
                date_from: $('#date_from').val() || '',
                date_to: $('#date_to').val() || '',
                per_page: parseInt($('#per_page').val()) || 15,
                page: 1
            };
        }

        /**
         * Configurar eventos de filtros
         */
        function setupFilterEvents() {
            // Aplicar filtros
            $('#apply-filters').on('click', function() {
                applyFilters();
            });

            // Limpiar filtros
            $('#clear-filters').on('click', function() {
                clearFilters();
            });

            // Exportar datos
            $('#export-data').on('click', function() {
                exportData();
            });

            // Filtro en tiempo real para búsqueda (con debounce)
            let searchTimeout;
            $('#search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    applyFilters();
                }, 500);
            });

            // Cambios en selects
            $('#type, #status, #user_id, #per_page').on('change', function() {
                applyFilters();
            });

            // Cambios en fechas
            $('#date_from, #date_to').on('change', function() {
                applyFilters();
            });

            // Enter en campos de texto
            $('#search').on('keypress', function(e) {
                if (e.which === 13) {
                    applyFilters();
                }
            });
        }

        /**
         * Configurar eventos de paginación
         */
        function setupPaginationEvents() { 
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();

                if ($(this).parent().hasClass('disabled') || isLoading) {
                    return;
                }

                const page = parseInt($(this).data('page'));
                if (page && page !== currentFilters.page) {
                    currentFilters.page = page;
                    loadData();

                    // 🔹 Quitar active de todos
                    $('.page-link').parent().removeClass('active');

                    // 🔹 Agregar active solo al actual
                    $(this).parent().addClass('active');
                }
            });
        }

        /**
         * Configurar eventos de colapso
         */
        function setupCollapseEvents() {
            $('#filtersCollapse').on('show.bs.collapse', function() {
                $('#filter-toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });

            $('#filtersCollapse').on('hide.bs.collapse', function() {
                $('#filter-toggle-icon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });
        }

        /**
         * Aplicar filtros
         */
        function applyFilters() {
            // Actualizar filtros actuales
            currentFilters = {
                search: $('#search').val().trim(),
                type: $('#type').val(),
                status: $('#status').val(),
                user_id: $('#user_id').val(),
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val(),
                per_page: parseInt($('#per_page').val()),
                page: 1 // Reset a la primera página
            };

            // Validar fechas
            if (currentFilters.date_from && currentFilters.date_to) {
                if (new Date(currentFilters.date_from) > new Date(currentFilters.date_to)) {
                    showAlert('error', '<?= translate("from date cannot be greater than to date"); ?>');
                    return;
                }
            }

            loadData();
        }

        /**
         * Limpiar filtros
         */
        function clearFilters() {
            $('#search').val('');
            $('#type').val('all');
            $('#status').val('all');
            $('#user_id').val('all');
            $('#date_from').val('');
            $('#date_to').val('');
            $('#per_page').val('15');

            currentFilters = {
                search: '',
                type: 'all',
                status: 'all',
                user_id: 'all',
                date_from: '',
                date_to: '',
                per_page: 15,
                page: 1
            };

            loadData();
        }

        /**
         * Cargar datos via AJAX
         */
        function loadData() {
            if (isLoading) return;

            isLoading = true;
            showLoading(true);

            $.ajax({
                url: '<?= site_url('payments/paymentsAjax'); ?>',
                type: 'GET',
                data: currentFilters,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateTablePayment(response.payments);
                        updateStatistics(response.statistics);
                        updatePagination(response.pagination);
                        updateResultsCount(response.total, response.payments.length);
                    } else {
                        showAlert('error', response.error || '<?= translate("error loading data"); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showAlert('error', '<?= translate("error loading data"); ?>');
                },
                complete: function() {
                    isLoading = false;
                    showLoading(false);
                }
            });
        }

        /**
         * Mostrar/ocultar loading
         */
        function showLoading(show) {
            if (show) {
                $('#loading-spinner').removeClass('d-none');
                $('#payments-list').addClass('d-none');
                $('#payments-table-container').addClass('opacity-50');
            } else {
                $('#loading-spinner').addClass('d-none');
                $('#payments-list').removeClass('d-none');
                $('#payments-table-container').removeClass('opacity-50');
            }
        }

        /**
         * Actualizar tabla
         */
        function updateTablePayment(payments) {
            const tbody = $('#payments-tbody');
            tbody.empty();

            if (payments.length === 0) {
                const colspan = <?= session()->get('group') == 1 ? '8' : '6'; ?>;
                tbody.append(`
                    <tr id="not-list">
                        <td colspan="${colspan}" class="text-center pt-2">
                            <div class="text-muted">
                                <?= translate('no transactions found'); ?>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }

            payments.forEach(function(payment) {
                const typeIcons = {
                    'deposit': '<i class="fa-duotone fa-solid fa-arrow-down-to-line text-success"></i>',
                    'retire': '<i class="fa-duotone fa-solid fa-arrow-up-from-bracket icon-danger"></i>',
                    'transfer': '<i class="fa-duotone fa-solid fa-arrow-right-arrow-left text-info"></i>',
                    'payment': '<i class="fa-duotone fa-solid fa-credit-card text-primary"></i>',
                    'bonus': '<i class="fa-duotone fa-solid fa-gift icon-bonus"></i>'
                };

                const typeIcon = typeIcons[payment.type] || '<i class="fa-duotone fa-solid fa-circle-question text-warning"></i>';
                const amountClass = payment.type === 'retire' ? 'icon-danger' : (payment.type === 'bonus' ? 'text-bonus' : 'text-success');
                const amountSign = payment.type === 'retire' ? '-' : '+';

                let row = `
                    <tr data-id="${payment.id}" data-type="${payment.type}">
                        <td class="text-center">
                            ${typeIcon}
                            <br>
                            <small class="text-muted">${payment.type_Tra}</small>
                        </td>
                        <td>
                            <strong>${escapeHtml(payment.reference)}</strong>
                            <br>
                            <small class="text-muted">${payment.date_formatted}</small>
                        </td>
                `;

                <?php if (session()->get('group') == 1) : ?>
                row += `
                        <td>
                            <strong>${escapeHtml(payment.user_code)}</strong>
                            <br>
                            <small class="text-muted">${escapeHtml(payment.user_name)}</small>
                        </td>
                `;
                <?php endif; ?>

                var user_id = '<?= session()->get('id'); ?>';

                let amountHtml = '';

                if (payment.type === 'retire') {
                    amountHtml = `
                        <strong class="icon-danger">
                            -<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                        </strong>
                    `;
                } else if (payment.type === 'transfer') {
                    <?php if (session()->get('group') == 1) : ?>
                        amountHtml = `
                            <div>
                                <strong class="icon-danger d-block">
                                    -<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                                </strong>
                                <strong class="text-success d-block">
                                    +<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                                </strong>
                            </div>
                        `;
                    <?php else: ?>
                        if (user_id != payment.user_id) {
                            amountHtml = `
                                <div>
                                    <strong class="icon-danger d-block">
                                        -<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                                    </strong>
                                </div>
                            `;
                        } else {
                            amountHtml = `
                                <div>
                                    <strong class="text-success d-block">
                                        +<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                                    </strong>
                                </div>
                            `;
                        }
                    <?php endif; ?>
                } else if (payment.type === 'bonus') {
                    amountHtml = `
                        <strong class="text-bonus">
                            +<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                        </strong>
                    `;
                } else {
                    amountHtml = `
                        <strong class="text-success">
                            +<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                        </strong>
                    `;
                }

                row += `
                    <td>${payment.bank}</td>
                    <td class="text-center">
                        ${amountHtml}
                    </td>
                    <td class="text-center">
                        <small>${payment.created_at}</small>
                    </td>
                    <td class="text-center" id="${payment.type}-${payment.id}">
                        <span class="status-badge" data-status="${payment.status_raw}">
                            ${payment.status_formatted}
                        </span>
                    </td>
                `;

                <?php if (session()->get('group') == 1) : ?>
                if (payment.type !== 'transfer') {
                    row += `<td class="text-center">`;
                    row += `<div class="btn-group btn-group-sm" role="group">`;
                    
                    /*if (payment.status_raw != 1) {
                        row += `
                            <button type="button" class="btn btn-success btn-sm" 
                                    onclick="updateStatus('${payment.type}', ${payment.id}, 1)"
                                    title="<?= translate('approve'); ?>">
                                <i class="fa-duotone fa-solid fa-check-double"></i>
                            </button>
                        `;
                    }*/

                    row += `
                        <a class="btn btn-primary btn-modal text-white" onclick="requestGet('${payment.type}', ${payment.id}, 1)"><i class="fa-duotone fa-solid fa-eye"></i></a>
                    `;
                    
                    /*if (payment.status_raw != 2) {
                        row += `
                            <button type="button" class="btn btn-danger btn-sm" 
                                    onclick="updateStatus('${payment.type}', ${payment.id}, 2)"
                                    title="<?= translate('reject'); ?>">
                                <i class="fa-duotone fa-solid fa-xmark"></i>
                            </button>
                        `;
                    }*/
                    
                    /*if (payment.type === 'deposit' && payment.status_raw != 3) {
                        row += `
                            <button type="button" class="btn btn-info btn-sm" 
                                    onclick="updateStatus('${payment.type}', ${payment.id}, 3)"
                                    title="<?= translate('verify'); ?>">
                                <i class="fa fa-shield-check"></i>
                            </button>
                        `;
                    }*/
                    
                    row += `</div></td>`;
                } else {
                    row += `<td class="text-center"><span class="text-muted small"><?= translate('approved'); ?></span></td>`;
                }
                <?php endif; ?>

                row += `</tr>`;
                tbody.append(row);
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            if (isNaN(date)) return ''; // Si la fecha no es válida

            let day = date.getDate();
            let month = date.getMonth() + 1; // Enero = 0
            let year = date.getFullYear();

            // Asegurar dos dígitos
            day = day < 10 ? '0' + day : day;
            month = month < 10 ? '0' + month : month;

            return `${day}/${month}/${year}`;
        }

        /**
         * Actualizar estadísticas
         */
        function updateStatistics(statistics) {
            <?php if (session()->get('group') == 1) : ?>
            if (statistics) {
                $('#stat-total').text(formatNumber(statistics.total_transactions));
                $('#stat-amount').text('<?= systemGet('currency'); ?> ' + formatNumber(statistics.total_amount));
                $('#stat-deposits').text(formatNumber(statistics.deposits.count));
                $('#stat-deposits-amount').text('<?= systemGet('currency'); ?> ' + formatNumber(statistics.deposits.amount));
                $('#stat-retires').text(formatNumber(statistics.retires.count));
                $('#stat-retires-amount').text('<?= systemGet('currency'); ?> ' + formatNumber(statistics.retires.amount));
                $('#stat-payments').text(formatNumber(statistics.payments.count));
                $('#stat-payments-amount').text('<?= systemGet('currency'); ?> ' + formatNumber(statistics.payments.amount));
            }
            <?php endif; ?>
        }

        /**
         * Actualizar paginación
         */
        function updatePagination(pagination) {
            const container = $('#pagination-container');
            
            if (!pagination || pagination.total_pages <= 1) {
                container.hide();
                return;
            }

            let paginationHtml = `
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item ${!pagination.has_previous ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0);" data-page="1" ${!pagination.has_previous ? 'tabindex="-1"' : ''}>
                            <i class="fa fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item ${!pagination.has_previous ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0);" data-page="${pagination.previous_page}" ${!pagination.has_previous ? 'tabindex="-1"' : ''}>
                            <i class="fa fa-angle-left"></i>
                        </a>
                    </li>
            `;

            const start = Math.max(1, pagination.current_page - 2);
            const end = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = start; i <= end; i++) {
                paginationHtml += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            paginationHtml += `
                    <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0);" data-page="${pagination.next_page}" ${!pagination.has_next ? 'tabindex="-1"' : ''}>
                            <i class="fa fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0);" data-page="${pagination.total_pages}" ${!pagination.has_next ? 'tabindex="-1"' : ''}>
                            <i class="fa fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        <?= translate('page'); ?> ${pagination.current_page} <?= translate('of'); ?> ${pagination.total_pages}
                        (${formatNumber(pagination.total)} <?= translate('total records'); ?>)
                    </small>
                </div>
            `;

            container.html(paginationHtml).show();
        }

        /**
         * Actualizar contador de resultados
         */
        function updateResultsCount(total, showing) {
            $('#total-count').text(formatNumber(total));
            $('#showing-count').text(formatNumber(showing));
        }

        /**
         * Exportar datos
         */
        function exportData() {
            const params = new URLSearchParams(currentFilters);
            params.append('export', 'csv');
            
            const url = '<?= site_url('payments/export'); ?>?' + params.toString();
            window.open(url, '_blank');
        }

        /**
         * Funciones de utilidad
         */
        function formatNumber(num) {
            return parseFloat(num).toFixed(2);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES');
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('es-ES');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showAlert(type, message) {
            // Implementar sistema de alertas según tu framework
            if (type === 'error') {
                console.error(message);
                alert(message); // Reemplazar con tu sistema de notificaciones
            } else {
                console.log(message);
            }
        }
    });

    /**
     * Función global para actualizar estado (solo admin)
     */
    <?php if (session()->get('group') == 1) : ?>
    function updateStatus(type, id, status) {
        if (!confirm('<?= translate("are you sure you want to change the status"); ?>?')) {
            return;
        }

        $.ajax({
            url: '<?= site_url('payments/updateStatus'); ?>',
            type: 'POST',
            data: {
                type: type,
                id: id,
                status: status,
                <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Recargar datos
                    $(document).ready(function() {
                        // Trigger reload
                        $('#apply-filters').trigger('click');
                    });
                    
                    // Mostrar mensaje de éxito
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.error || '<?= translate("error updating status"); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating status:', error);
                showAlert('error', '<?= translate("error updating status"); ?>');
            }
        });
    }
    <?php endif; ?>
</script>