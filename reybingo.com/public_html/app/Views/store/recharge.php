<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? 0,
    'activeNav' => 'recharge',
]) ?>

<?php $currency = esc(systemGet('currency')); ?>

<div class="card store-panel-card h-100">
    <div class="card-body store-tab-body">
        <div class="store-tab-form">
            <h6 class="store-tab-form-title"><i class="fa-duotone fa-solid fa-bolt"></i> <?= translate('recharge player'); ?></h6>

            <div class="store-affiliate-stats store-recharge-stats mb-3">
                <div class="store-affiliate-stat">
                    <span class="store-balance-label"><?= translate('recharge commissions earned'); ?></span>
                    <strong id="store-recharge-commission-total"><?= $currency ?> <?= number_format((float) ($rechargeCommissionTotal ?? 0), 2); ?></strong>
                </div>
            </div>

            <?php if ((float) ($walletSummary['recharge'] ?? 0) <= 0) : ?>
                <div class="alert alert-warning store-alert-compact small">
                    <?= translate('no store balance yet request admin approval'); ?>
                    <a href="<?= site_url('store/funding'); ?>" class="alert-link"><?= translate('request store balance'); ?></a>
                </div>
            <?php endif; ?>

            <?php echo form_open(site_url('store/rechargeSubmit'), ['id' => 'store-recharge-form']); ?>
                <?= csrf_field() ?>
                <input type="hidden" name="player_id" id="store-player-id" value="">

                <label for="store-lookup" class="form-label"><?= translate('player document number'); ?></label>
                <div class="store-search-row mb-2">
                    <div class="store-form-field">
                        <input type="text" class="form-control form-bingo" id="store-lookup" placeholder="<?= translate('enter player document number'); ?>" autocomplete="off">
                        <small id="store-lookup-hint" class="text-muted d-none"><?= translate('searching player'); ?>...</small>
                        <small id="store-lookup-error" class="text-danger d-none"></small>
                    </div>
                    <button type="button" class="btn btn-primary btn-bingo store-search-btn" id="store-lookup-btn">
                        <i class="fa-duotone fa-solid fa-magnifying-glass"></i> <?= translate('search'); ?>
                    </button>
                </div>

                <div id="store-player-preview" class="store-player-preview d-none mb-2">
                    <div class="store-player-preview-inner">
                        <div>
                            <strong id="store-player-name"></strong>
                            <div class="small text-muted">
                                <span id="store-player-code"></span> ·
                                <span id="store-player-document"></span>
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block"><?= translate('current balance'); ?></small>
                            <strong><?= systemGet('currency'); ?> <span id="store-player-wallet">0.00</span></strong>
                        </div>
                    </div>
                </div>

                <div class="store-form-grid">
                    <div class="store-form-field">
                        <label for="store-amount" class="form-label"><?= translate('amount'); ?></label>
                        <input type="number" class="form-control form-bingo" name="amount" id="store-amount" min="0.01" step="0.01" placeholder="0.00" autocomplete="off">
                        <small id="amount-error" class="text-danger d-none"></small>
                    </div>
                    <div class="store-form-field">
                        <label for="store-reference" class="form-label"><?= translate('reference'); ?> <span class="text-muted">(<?= translate('optional'); ?>)</span></label>
                        <input type="text" class="form-control form-bingo" name="reference" id="store-reference" placeholder="<?= translate('reference'); ?>" autocomplete="off">
                        <small id="reference-error" class="text-danger d-none"></small>
                    </div>
                </div>

                <div class="store-form-actions">
                    <button type="submit" class="btn btn-primary btn-bingo" id="store-recharge-btn" disabled>
                        <i class="fa-duotone fa-solid fa-bolt"></i> <?= translate('recharge player'); ?>
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>

        <div class="store-tab-history">
            <h6 class="store-tab-history-title"><i class="fa-duotone fa-solid fa-clock-rotate-left"></i> <?= translate('recent player recharges'); ?></h6>
            <div class="store-table-wrap" id="store-recharges-list">
                <?= view('store/rechargeslist', ['recharges' => $recharges ?? []]); ?>
            </div>
        </div>
    </div>
</div>

<?= view('store/partials/close') ?>

<?= view('store/partials/scripts_common') ?>

<script type="text/javascript">
    function storeRefreshRecharges() {
        $.get('<?= site_url('store/rechargesListGet') ?>', function(html) {
            $('#store-recharges-list').html(html);
        });
    }

    $(function() {
        let selectedPlayer = null;
        let lookupTimer = null;
        let lookupRequest = null;
        const hasBalance = <?= (float) ($walletSummary['recharge'] ?? 0) > 0 ? 'true' : 'false' ?>;
        const minDocumentDigits = 6;
        const currencyLabel = '<?= esc(systemGet('currency'), 'js'); ?>';

        function storeUpdateRechargeCommissionTotal(response) {
            if (typeof response.recharge_commission_total === 'undefined') {
                return;
            }

            $('#store-recharge-commission-total').text(
                currencyLabel + ' ' + parseFloat(response.recharge_commission_total || 0).toFixed(2)
            );
        }

        function storeUpdateRechargeButton() {
            $('#store-recharge-btn').prop('disabled', !selectedPlayer || !hasBalance);
        }

        function storeNormalizeDocument(value) {
            return String(value || '').replace(/\D/g, '');
        }

        function storeClearPlayerSelection() {
            selectedPlayer = null;
            $('#store-player-id').val('');
            $('#store-player-preview').addClass('d-none');
            storeUpdateRechargeButton();
        }

        function storeShowPlayer(response) {
            selectedPlayer = response.player;
            $('#store-player-id').val(response.player.id);
            $('#store-player-name').text(response.player.firstname + ' ' + response.player.lastname);
            $('#store-player-code').text(response.player.code);
            $('#store-player-document').text(response.player.document);
            $('#store-player-wallet').text(response.player.wallet);
            $('#store-player-preview').removeClass('d-none');
            storeUpdateRechargeButton();
            $('#store-lookup-error').addClass('d-none').text('');
        }

        function storeLookupPlayer() {
            const query = $('#store-lookup').val().trim();
            const digits = storeNormalizeDocument(query);

            $('#store-lookup-error').addClass('d-none').text('');

            if (!query) {
                storeClearPlayerSelection();
                return;
            }

            if (digits.length < minDocumentDigits) {
                storeClearPlayerSelection();
                return;
            }

            if (lookupRequest) {
                lookupRequest.abort();
            }

            $('#store-lookup-hint').removeClass('d-none');
            $('#store-lookup-btn').prop('disabled', true);

            lookupRequest = $.post('<?= site_url('store/lookupPlayer') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                query: query
            }, function(response) {
                if (!response.success) {
                    storeClearPlayerSelection();
                    $('#store-lookup-error').text(response.message || '<?= esc(translate('player not found'), 'js'); ?>').removeClass('d-none');
                    return;
                }

                storeShowPlayer(response);
            }, 'json').fail(function(xhr, status) {
                if (status === 'abort') {
                    return;
                }
                storeClearPlayerSelection();
                $('#store-lookup-error').text('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>').removeClass('d-none');
            }).always(function() {
                lookupRequest = null;
                $('#store-lookup-hint').addClass('d-none');
                $('#store-lookup-btn').prop('disabled', false);
            });
        }

        $('#store-lookup-btn').on('click', function() {
            const query = $('#store-lookup').val().trim();

            if (!query) {
                $('#store-lookup-error').text('<?= esc(translate('enter player document number'), 'js'); ?>').removeClass('d-none');
                return;
            }

            storeLookupPlayer();
        });

        $('#store-lookup').on('input', function() {
            clearTimeout(lookupTimer);

            const query = $(this).val().trim();
            const digits = storeNormalizeDocument(query);

            if (!query || digits.length < minDocumentDigits) {
                storeClearPlayerSelection();
                $('#store-lookup-error').addClass('d-none').text('');
                $('#store-lookup-hint').addClass('d-none');
                return;
            }

            lookupTimer = setTimeout(storeLookupPlayer, 450);
        });

        $('#store-lookup').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(lookupTimer);
                $('#store-lookup-btn').trigger('click');
            }
        });

        $('#store-recharge-form').on('submit', function(e) {
            e.preventDefault();
            storeClearErrors('#store-recharge-form');

            if (!selectedPlayer || !$('#store-player-id').val()) {
                storeShowToast('<?= esc(translate('search and select a player first'), 'js'); ?>', 'error');
                return;
            }

            if (!hasBalance) {
                storeShowToast('<?= esc(translate('insufficient store balance request admin first'), 'js'); ?>', 'error');
                return;
            }

            const $btn = $('#store-recharge-btn');
            $btn.prop('disabled', true);

            $.ajax({
                url: '<?= site_url('store/rechargeSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        storeShowToast(response.message, 'success');
                        $('#store-amount').val('');
                        $('#store-reference').val('');
                        if (typeof response.store_balance !== 'undefined') {
                            storeUpdateBalance(response.store_balance);
                        }
                        storeUpdateRechargeCommissionTotal(response);
                        storeRefreshRecharges();
                    } else if (response.errors) {
                        $.each(response.errors, function(field, message) {
                            $('#' + field + '-error').text(message).removeClass('d-none');
                            $('#store-' + field + ', #' + field).addClass('is-invalid');
                        });
                        if (response.message) {
                            storeShowToast(response.message, 'error');
                        }
                    } else if (response.message) {
                        storeShowToast(response.message, 'error');
                    }
                },
                error: function() {
                    storeShowToast('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>', 'error');
                },
                complete: function() {
                    storeUpdateRechargeButton();
                }
            });
        });
    });
</script>
