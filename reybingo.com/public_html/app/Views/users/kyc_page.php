<a class="btn btn-small btn-home" href="<?= site_url('profile'); ?>"><i class="fa-duotone fa-solid fa-arrow-left"></i></a>

<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?= view('users/kyc_content', ['user' => $user]); ?>
        </div>
    </div>
</div>
