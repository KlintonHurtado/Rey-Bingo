<?php
$referrerName = $referrerName ?? '';
$storeRegistering = ! empty($storeRegistering);
$backUrl = $backUrl ?? site_url('store/affiliate');
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
                            'storeRegistering' => $storeRegistering,
                            'backUrl' => $backUrl,
                            'showSigninLink' => ! $storeRegistering,
                            'submitLabel' => translate('operator enter'),
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
