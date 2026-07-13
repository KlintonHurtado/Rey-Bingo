<?php
$referrerName = $referrerName ?? '';
$referrerType = $referrerType ?? 'store';
$storeRegistering = ! empty($storeRegistering);
$operatorRegistering = ! empty($operatorRegistering);
$backUrl = $backUrl ?? site_url('store/affiliate');
$backLabel = $backLabel ?? ($operatorRegistering
    ? translate('back to operator panel')
    : translate('back to store panel'));
?>
<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-8 col-lg-7 col-xl-6">
            <div class="row">
                <div class="col">
                    <?= view('signup/partials/store_form', [
                        'referrerName' => $referrerName,
                        'referrerType' => $referrerType,
                        'embedded' => false,
                        'storeRegistering' => $storeRegistering,
                        'operatorRegistering' => $operatorRegistering,
                        'backUrl' => $backUrl,
                        'backLabel' => $backLabel,
                        'showSigninLink' => ! $storeRegistering && ! $operatorRegistering,
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
