<?php
$stats = $userStats ?? ['user_spend' => 0, 'total_prizes' => 0, 'manual_credits' => 0];
$currency = systemGet('currency');
$wrapperClass = $wrapperClass ?? 'user-accreditation-stats';
?>
<div class="row g-2 mb-2 <?= esc($wrapperClass) ?>">
    <div class="col-sm-4">
        <div class="card bingo-bg-primary text-white m-0 h-100">
            <div class="card-body text-center p-2">
                <small>Depósitos aprobados</small>
                <div class="fw-bold"><?= $currency ?> <span class="stat-manual-credits"><?= number_format((float) ($stats['manual_credits'] ?? 0), 2) ?></span></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card bingo-bg-danger text-white m-0 h-100">
            <div class="card-body text-center p-2">
                <small>Montos gastados</small>
                <div class="fw-bold"><?= $currency ?> <span class="stat-user-spend"><?= number_format((float) ($stats['user_spend'] ?? 0), 2) ?></span></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card bingo-bg-success text-white m-0 h-100">
            <div class="card-body text-center p-2">
                <small>Total de premios</small>
                <div class="fw-bold"><?= $currency ?> <span class="stat-total-prizes"><?= number_format((float) ($stats['total_prizes'] ?? 0), 2) ?></span></div>
            </div>
        </div>
    </div>
</div>
