<style>
    .legal-page {
        max-width: 920px;
        margin: 1.5rem auto 3rem;
        padding: 0 1rem;
        padding-top: 90px;
    }
    body:has(.player-nav-cluster .btn-legal-player) .legal-page {
        padding-top: 190px;
    }
    .legal-page__card {
        background: rgba(255, 255, 255, 0.96);
        border-radius: 18px;
        box-shadow: 0 12px 40px rgba(24, 10, 84, 0.18);
        padding: 1.5rem 1.25rem 2rem;
        color: #2d3748;
    }
    .legal-page__nav {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .legal-page__nav a {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        background: rgba(98, 54, 255, 0.1);
        color: #6236ff;
    }
    .legal-page__nav a.is-active {
        background: linear-gradient(145deg, #8767fa, #6236ff);
        color: #fff;
    }
    .legal-page__title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #3b1f9c;
        margin-bottom: 0.35rem;
    }
    .legal-page__meta {
        color: #718096;
        font-size: 0.85rem;
        margin-bottom: 1.25rem;
    }
    .legal-page__body {
        line-height: 1.65;
        font-size: 0.98rem;
    }
    .legal-page__body h1,
    .legal-page__body h2,
    .legal-page__body h3 {
        color: #3b1f9c;
        margin-top: 1.25rem;
    }
    .legal-page__body ul {
        padding-left: 1.25rem;
    }
    .legal-page__body a {
        color: #6236ff;
    }
    @media (min-width: 768px) {
        .legal-page__card {
            padding: 2rem 2.25rem 2.5rem;
        }
        .legal-page__title {
            font-size: 1.9rem;
        }
    }
</style>

<?php if (session()->get('logged_in') && (int) session()->get('group') === 0) : ?>
    <?= view('playings/partials/player_nav_cluster', [
        'mode' => 'home',
        'legalActive' => true,
        'imagePath' => $imagePath ?? site_url('assets/img/avatar.jpg'),
    ]); ?>
<?php elseif (session()->get('logged_in') && (int) session()->get('group') === 1) : ?>
    <a class="btn btn-small btn-home" href="<?= site_url('games'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>
<?php else : ?>
    <a class="btn btn-small btn-home" href="<?= site_url('signin'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>
<?php endif; ?>

<div class="legal-page">
    <div class="legal-page__card">
        <div class="legal-page__nav">
            <a href="<?= site_url('terminos'); ?>" class="<?= ($active ?? '') === 'terms' ? 'is-active' : '' ?>">
                <i class="fa-duotone fa-solid fa-file-contract"></i> <?= translate('terms and conditions'); ?>
            </a>
            <a href="<?= site_url('promociones'); ?>" class="<?= ($active ?? '') === 'promotions' ? 'is-active' : '' ?>">
                <i class="fa-duotone fa-solid fa-gift"></i> <?= translate('promotions'); ?>
            </a>
            <?php if (! session()->get('logged_in')) : ?>
                <a href="<?= site_url('signup'); ?>">
                    <i class="fa-duotone fa-solid fa-user-plus"></i> <?= translate('create account'); ?>
                </a>
                <a href="<?= site_url('signin'); ?>">
                    <i class="fa-duotone fa-solid fa-right-to-bracket"></i> <?= translate('login'); ?>
                </a>
            <?php endif; ?>
        </div>

        <h1 class="legal-page__title"><?= esc($title); ?></h1>
        <?php if (! empty($updatedAt)) : ?>
            <div class="legal-page__meta">
                <?= translate('last updated'); ?>: <?= esc(date('d/m/Y H:i', strtotime($updatedAt))); ?>
            </div>
        <?php endif; ?>

        <div class="legal-page__body">
            <?= $html; ?>
        </div>
    </div>
</div>
