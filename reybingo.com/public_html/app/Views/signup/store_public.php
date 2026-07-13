<?php
$referrerName = $referrerName ?? '';
?>
<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-8 col-lg-7 col-xl-6">
            <div class="row">
                <div class="col">
                    <?= view('signup/partials/store_form', [
                        'referrerName' => $referrerName,
                        'referrerType' => 'operator',
                        'embedded' => false,
                        'showSigninLink' => true,
                        'submitLabel' => translate('create'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
