<div class="row mt-4">
    <div class="col">
        <h4><?= translate('operational kpis'); ?></h4>
    </div>
</div>

<?php
$ops = $ops ?? [
    'approved_recharges' => 0,
    'bonuses_granted' => 0,
    'bonus_released' => 0,
    'cartons_granted' => 0,
    'cartons_released' => 0,
    'total_prizes' => 0,
    'total_retires' => 0,
    'active_instant' => 0,
    'active_day' => 0,
    'active_month' => 0,
    'active_day_date' => date('Y-m-d'),
    'active_month_key' => date('Y-m'),
];
$currency = systemGet('currency');
?>

<div class="card mt-2 mb-3">
    <div class="row g-0">
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-success text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('total approved recharges'); ?></h6>
                    <h4 class="mb-0"><?= esc($currency); ?> <?= number_format((float) $ops['approved_recharges'], 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-primary text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('total bonuses granted'); ?></h6>
                    <h4 class="mb-0"><?= esc($currency); ?> <?= number_format((float) $ops['bonuses_granted'], 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-info text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('total bonus released to recharge'); ?></h6>
                    <h4 class="mb-0"><?= esc($currency); ?> <?= number_format((float) $ops['bonus_released'], 2); ?></h4>
                    <small class="opacity-75"><?= translate('bonus spent on cartons'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-warning text-dark m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('total cartons granted'); ?></h6>
                    <h4 class="mb-0"><?= number_format((int) $ops['cartons_granted']); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-orange text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('total cartons released'); ?></h6>
                    <h4 class="mb-0"><?= number_format((int) $ops['cartons_released']); ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-0">
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-danger text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('total prizes paid'); ?></h6>
                    <h4 class="mb-0"><?= esc($currency); ?> <?= number_format((float) $ops['total_prizes'], 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-dark text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('total approved retires'); ?></h6>
                    <h4 class="mb-0"><?= esc($currency); ?> <?= number_format((float) $ops['total_retires'], 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-secondary text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('users active now'); ?></h6>
                    <h4 class="mb-0"><?= number_format((int) $ops['active_instant']); ?></h4>
                    <small class="opacity-75"><?= translate('last 15 minutes'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-primary text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('users active today'); ?></h6>
                    <h4 class="mb-0"><?= number_format((int) $ops['active_day']); ?></h4>
                    <a class="btn btn-sm btn-light mt-2"
                       href="<?= site_url('games/exportActiveUsers/day?date=' . urlencode((string) $ops['active_day_date'])); ?>">
                        <i class="fa-duotone fa-solid fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card bingo-bg-success text-white m-2 h-100">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1"><?= translate('users active this month'); ?></h6>
                    <h4 class="mb-0"><?= number_format((int) $ops['active_month']); ?></h4>
                    <small class="opacity-75 d-block"><?= translate('unique users not daily sum'); ?></small>
                    <a class="btn btn-sm btn-light mt-2"
                       href="<?= site_url('games/exportActiveUsers/month?month=' . urlencode((string) $ops['active_month_key'])); ?>">
                        <i class="fa-duotone fa-solid fa-file-excel"></i> Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col">
        <h4><?= translate('general statistics'); ?></h4>
    </div>
</div>

<div class="card mt-2">
    <div class="row">
        <!-- Card de Usuarios -->
        <div class="col-12 col-md">
            <div class="card bingo-bg-primary text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="card-title mb-2"><?= translate('total players'); ?></h6>
                        <?php if (isset($users_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($users_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-info"></i>
                                    <small class="text-info fw-bold">+<?= $users_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-danger"></i>
                                    <small class="text-danger fw-bold"><?= $users_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-0"><?= number_format($total_users); ?></h4>
                    <?php if (isset($previous_users) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                        <small class="opacity-75">
                            <?= translate('previous period'); ?>: <?= number_format($previous_users); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card de Wallets -->
        <div class="col-12 col-md">
            <div class="card bingo-bg-success text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="card-title mb-2"><?= translate('total in wallets'); ?></h6>
                        <?php if (isset($wallet_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($wallet_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-warning"></i>
                                    <small class="text-warning fw-bold">+<?= $wallet_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-danger"></i>
                                    <small class="text-danger fw-bold"><?= $wallet_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-0"><?= systemGet('currency'); ?> <?= number_format($total_wallet, 2); ?></h4>
                    <?php if (isset($previous_wallet) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                        <small class="opacity-75">
                            <?= translate('previous period'); ?>: <?= systemGet('currency'); ?> <?= number_format($previous_wallet, 2); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card de Games -->
        <div class="col-12 col-md">
            <div class="card bingo-bg-info text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="card-title mb-2"><?= translate('total games'); ?></h6>
                        <?php if (isset($games_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($games_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-warning"></i>
                                    <small class="text-warning fw-bold">+<?= $games_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-danger"></i>
                                    <small class="text-danger fw-bold"><?= $games_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-0"><?= number_format($total_games); ?></h4>
                    <?php if (isset($previous_games) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                        <small class="opacity-75">
                            <?= translate('previous period'); ?>: <?= number_format($previous_games); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md">
            <div class="card bingo-bg-warning text-dark m-2">
                <div class="card-body pb-2">
                    <div class="row">
                        <h6 class="card-title text-center mb-2"><?= translate('total cartons'); ?></h6>
                        <!-- Columna izquierda - Vendidos -->
                        <div class="col-6 text-center border-end border-muted">
                            <small class="fw-bold d-block"><?= translate('sold'); ?></small>
                            <h5 class="mb-0 text-success"><?= number_format($sold_cartons); ?></h5>
                        </div>
                        
                        <!-- Columna derecha - No vendidos -->
                        <div class="col-6 text-center">
                            <small class="fw-bold d-block"><?= translate('unsold'); ?></small>
                            <h5 class="mb-0 text-danger"><?= number_format($unsold_cartons); ?></h5>
                        </div>
                    </div>
                    
                    <!-- Total en la parte inferior -->
                    <div class="row border-top border-muted">
                        <div class="col-12 pt-1 text-center">
                            <h4 class="mb-0"><?= number_format($total_cartons); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="row">
        <!-- Card de Awards -->
        <div class="col-12 col-md">
            <div class="card bingo-bg-danger text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title"><?= translate('total in awards'); ?></h5>
                        <?php if (isset($awards_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($awards_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-warning"></i>
                                    <small class="text-warning fw-bold">+<?= $awards_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-info"></i>
                                    <small class="text-info fw-bold"><?= $awards_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 class="card-text mb-0"><?= systemGet('currency'); ?> <?= number_format($total_awards, 2); ?></h2>
                    <div class="d-flex justify-content-between align-items-end">
                        <span><?= number_format($total_games); ?> <?= translate('games'); ?></span>
                        <?php if (isset($previous_awards) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <small class="opacity-75 text-end">
                                <?= translate('previous'); ?>: <?= systemGet('currency'); ?> <?= number_format($previous_awards, 2); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Deposits -->
        <div class="col-12 col-md">
            <div class="col card bingo-bg-secondary text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title"><?= translate('total in deposits'); ?></h5>
                        <?php if (isset($deposits_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($deposits_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-success"></i>
                                    <small class="text-success fw-bold">+<?= $deposits_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-danger"></i>
                                    <small class="text-danger fw-bold"><?= $deposits_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 class="card-text mb-0"><?= systemGet('currency'); ?> <?= number_format($total_deposits, 2); ?></h2>
                    <div class="d-flex justify-content-between align-items-end">
                        <span><?= number_format($count_deposits); ?> <?= translate('transactions'); ?></span>
                        <?php if (isset($previous_deposits) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <small class="opacity-75 text-end">
                                <?= translate('previous'); ?>: <?= systemGet('currency'); ?> <?= number_format($previous_deposits, 2); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Retires -->
        <div class="col-12 col-md">
            <div class="col card bingo-bg-dark text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title"><?= translate('total in retires'); ?></h5>
                        <?php if (isset($retires_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($retires_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-warning"></i>
                                    <small class="text-warning fw-bold">+<?= $retires_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-success"></i>
                                    <small class="text-success fw-bold"><?= $retires_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 class="card-text mb-0"><?= systemGet('currency'); ?> <?= number_format($total_retires, 2); ?></h2>
                    <div class="d-flex justify-content-between align-items-end">
                        <span><?= number_format($count_retires); ?> <?= translate('transactions'); ?></span>
                        <?php if (isset($previous_retires) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <small class="opacity-75 text-end">
                                <?= translate('previous'); ?>: <?= systemGet('currency'); ?> <?= number_format($previous_retires, 2); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Roulettes -->
        <div class="col-12 col-md">
            <div class="card bingo-bg-orange text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title"><?= translate('total in roulette'); ?></h5>
                        <?php if (isset($roulette_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($roulette_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-warning"></i>
                                    <small class="text-warning fw-bold">+<?= $roulette_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-danger"></i>
                                    <small class="text-danger fw-bold"><?= $roulette_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 class="card-text mb-0"><?= systemGet('currency'); ?> <?= number_format($total_roulette, 2); ?></h2>
                    <div class="d-flex justify-content-between align-items-end">
                        <span><?= number_format($count_roulette); ?> <?= translate('transactions'); ?></span>
                        <?php if (isset($previous_roulette) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <small class="opacity-75 text-end">
                                <?= translate('previous'); ?>: <?= systemGet('currency'); ?> <?= number_format($previous_roulette, 2); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Referrals -->
        <div class="col-12 col-md">
            <div class="card bingo-bg-purple text-white m-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title"><?= translate('total referrals'); ?></h5>
                        <?php if (isset($referrals_change_percent) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <div class="text-end">
                                <?php if ($referrals_trend == 'up'): ?>
                                    <i class="fas fa-arrow-up text-success"></i>
                                    <small class="text-success fw-bold">+<?= $referrals_change_percent; ?>%</small>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-danger"></i>
                                    <small class="text-danger fw-bold"><?= $referrals_change_percent; ?>%</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 class="card-text mb-0"><?= systemGet('currency'); ?> <?= number_format($total_referrals, 2); ?></h2>
                    <div class="d-flex justify-content-between align-items-end">
                        <span><?= number_format($count_referrals); ?> <?= translate('transactions'); ?></span>
                        <?php if (isset($previous_referrals) && ($dateFilter != 'all' || ($startDate && $endDate))): ?>
                            <small class="opacity-75 text-end">
                                <?= translate('previous'); ?>: <?= systemGet('currency'); ?> <?= number_format($previous_referrals, 2); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12 col-md">
        <div class="card m-2">
            <div class="card-header pt-3">
                <h5><?= translate('games by status'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($games_by_status)): ?>
                    <canvas id="gamesByStatusChartPie" width="100%" height="100px"></canvas>
                <?php else: ?>
                    <p class="text-center"><?= translate('no data available'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-md">
        <div class="card m-2">
            <div class="card-header pt-3">
                <h5><?= translate('games by type'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($games_by_type)): ?>
                    <canvas id="gamesByTypeChart" width="100%" height="100px"></canvas>
                <?php else: ?>
                    <p class="text-center"><?= translate('no data available'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // === Gráfico de juegos por estado ===
    var ctxStatus = document.getElementById('gamesByStatusChartPie');
    var gamesByStatusChart = new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: [
                ' <?= translate('unstarted'); ?>',
                ' <?= translate('initiated'); ?>',
                ' <?= translate('finished'); ?>',
                ' <?= translate('earring'); ?>'
            ],
            datasets: [{
                label: '',
                data: [
                    <?= isset($games_by_status['unstarted']) ? $games_by_status['unstarted'] : 0 ?>,
                    <?= isset($games_by_status['initiated']) ? $games_by_status['initiated'] : 0 ?>,
                    <?= isset($games_by_status['finished']) ? $games_by_status['finished'] : 0 ?>,
                    <?= isset($games_by_status['earring']) ? $games_by_status['earring'] : 0 ?>
                ],
                backgroundColor: [
                    '#17a2b8',
                    '#007bff',
                    '#28a745',
                    '#ffc107'
                ],
                borderColor: [
                    '#17a2b8',
                    '#007bff',
                    '#28a745',
                    '#ffc107'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: false,
                    text: ''
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let total = context.chart._metasets[0].total;
                            let value = context.parsed;
                            let percentage = ((value / total) * 100).toFixed(1) + '%';
                            return context.label + ': ' + value + ' (' + percentage + ')';
                        }
                    }
                }
            }
        }
    });

    // === Gráfico de juegos por tipo ===
    var ctxType = document.getElementById('gamesByTypeChart');
    var gamesByTypeChart = new Chart(ctxType, {
        type: 'bar',
        data: {
            labels: [
                '<?= translate('automatic'); ?>',
                '<?= translate('manual'); ?>',
                '<?= translate('live'); ?>',
                '<?= translate('video'); ?>'
            ],
            datasets: [{
                label: '',
                data: [
                    <?= isset($games_by_type['automatic']) ? $games_by_type['automatic'] : 0 ?>,
                    <?= isset($games_by_type['manual']) ? $games_by_type['manual'] : 0 ?>,
                    <?= isset($games_by_type['live']) ? $games_by_type['live'] : 0 ?>,
                    <?= isset($games_by_type['video']) ? $games_by_type['video'] : 0 ?>
                ],
                backgroundColor: [
                    '#007bff',
                    '#ffc107',
                    '#17a2b8',
                    '#dc3545'
                ],
                borderColor: [
                    '#007bff',
                    '#ffc107',
                    '#17a2b8',
                    '#dc3545'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false,
                    position: 'bottom',
                },
                title: {
                    display: false,
                    text: ''
                }
            }
        }
    });
</script>
