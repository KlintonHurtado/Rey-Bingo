<div class="admin-audit-scroll">
<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => 'audit',
    'imagePath' => $imagePath ?? site_url('assets/img/avatar.jpg'),
    'showHome' => true,
    'showStatistics' => true,
    'showUsers' => true,
]) ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="container-fluid admin-audit-page px-2 px-md-3 px-xl-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm border-0 admin-audit-card">
                <div class="card-body p-2 p-md-3" id="financial-audit-body">
                    <?= view('games/statistics/audit', [
                        'audit_stats' => $audit_stats ?? [],
                        'standalone_audit' => true,
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
