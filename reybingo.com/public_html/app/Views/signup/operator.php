<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-6 col-xl-6">
            <div class="row">
                <div class="col">
                    <?= view('signup/partials/operator_form', [
                        'referrerName' => $referrerName ?? '',
                        'embedded' => false,
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
