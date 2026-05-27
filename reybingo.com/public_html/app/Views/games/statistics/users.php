<div class="row mt-4">
    <div class="col-md-12">
        <h4><?= translate('top statistics'); ?></h4>
        <?php if (session()->get('group') == 1): ?>
            <a href="<?= site_url('users/exportRequiredFields'); ?>" class="btn btn-sm btn-primary btn-bingo float-end">
                <i class="fa-duotone fa-solid fa-file-arrow-down"></i> Exportar usuarios (campos requeridos)
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-2">
    <div class="row">
        <div class="col-12 col-md">
            <div class="card bingo-bg-primary text-white m-2">
                <div class="card-body">
                    <h5 class="card-title"><?= translate('total players'); ?></h5>
                    <h2 class="card-text"><?= number_format($total_users_tab ?? 0); ?></h2>
                </div>
            </div>
        </div>
            
        <div class="col-12 col-md">
            <div class="card bingo-bg-success text-white m-2">
                <div class="card-body">
                    <h5 class="card-title"><?= translate('total in wallets'); ?></h5>
                    <h2 class="card-text"><?= systemGet('currency'); ?> <?= number_format($total_wallet_tab ?? 0, 2); ?></h2>
                </div>
            </div>
        </div>

        <div class="col-12 col-md">
            <div class="card bingo-bg-info text-white m-2">
                <div class="card-body">
                    <h5 class="card-title"><?= translate('average wallet balance'); ?></h5>
                    <h2 class="card-text"><?= systemGet('currency'); ?> <?= number_format(($total_users_tab ?? 0) > 0 ? ($total_wallet_tab ?? 0) / $total_users_tab : 0, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12 col-md">
        <div class="card m-2">
            <div class="card-header pt-3">
                <h5><?= translate('players by group'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($users_by_group)): ?>
                    <canvas id="usersByGroupChart" width="100%" height="100px"></canvas>
                <?php else: ?>
                    <p class="text-center"><?= translate('no data available'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md">
        <div class="card m-2 h-97">
            <div class="card-header pt-3">
                <h5><?= translate('top 10 players by wallet balance'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?= translate('name'); ?></th>
                                <th><?= translate('username'); ?></th>
                                <th class="text-center"><?= translate('wallet'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($top_users_by_wallet)): ?>
                                <?php foreach ($top_users_by_wallet as $user): ?>
                                <tr>
                                    <td><?= $user['firstname'] . ' ' . $user['lastname']; ?></td>
                                    <td><?= $user['username']; ?></td>
                                    <td class="text-center"><?= systemGet('currency'); ?> <?= number_format($user['wallet'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center"><?= translate('no users found'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md">
        <div class="card m-2">
            <div class="card-header pt-3">
                <h5><?= translate('top 10 most active players'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?= translate('name'); ?></th>
                                <th><?= translate('username'); ?></th>
                                <th><?= translate('phone'); ?></th>
                                <th><?= translate('email'); ?></th>
                                <th class="text-center"><?= translate('cartons'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($top_active_users)): ?>
                                <?php foreach ($top_active_users as $user): ?>
                                <tr>
                                    <td><?= $user['firstname'] . ' ' . $user['lastname']; ?></td>
                                    <td><?= $user['username']; ?></td>
                                    <td><?= $user['phone']; ?></td>
                                    <td><?= $user['email']; ?></td>
                                    <td class="text-center"><?= number_format($user['cartons']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?= translate('no users found'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Gráfico de usuarios por grupo
    var ctxGroup = document.getElementById('usersByGroupChart').getContext('2d');
    var usersByGroupChart = new Chart(ctxGroup, {
        type: 'pie',
        data: {
            labels: [
                '<?= translate('administrators'); ?>',
                '<?= translate('players'); ?>'
            ],
            datasets: [{
                data: [
                    <?= $users_by_group['admin'] ?? 0; ?>,
                    <?= $users_by_group['player'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#dc3545',
                    '#007bff'
                ]
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
</script>
