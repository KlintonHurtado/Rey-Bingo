<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => 'audit',
    'imagePath' => $imagePath ?? site_url('assets/img/avatar.jpg'),
    'showHome' => true,
    'showStatistics' => true,
    'showUsers' => true,
]) ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="container admin-audit-page py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-3 p-md-4" id="financial-audit-body">
                    <?= view('games/statistics/audit', [
                        'audit_stats' => $audit_stats ?? [],
                        'standalone_audit' => true,
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
