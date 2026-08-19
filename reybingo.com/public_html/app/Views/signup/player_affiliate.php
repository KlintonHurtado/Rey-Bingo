<?php
$referrerName = $referrerName ?? '';
?>
<div class="store-signup-scroll">
    <div class="container py-3">
        <div class="row d-flex justify-content-center">
            <div class="col-md-8 col-lg-7 col-xl-6">
                <div class="row">
                    <div class="col">
                        <?= view('signup/partials/player_form', [
                            'referrerName' => $referrerName,
                            'embedded' => false,
                            'storeRegistering' => false,
                            'backUrl' => site_url('signin'),
                            'showSigninLink' => true,
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
