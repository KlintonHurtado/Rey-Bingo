<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? ($pendingCount ?? 0),
    'activeNav' => 'prizes',
]) ?>

<div class="card store-panel-card h-100">
    <div class="card-body store-tab-body">
        <div class="store-tab-form">
            <h6 class="store-tab-form-title">
                <i class="fa-duotone fa-solid fa-trophy-star"></i> <?= translate('pay prizes from store'); ?>
            </h6>

            <div class="store-tab-form-fields">
            <?php if ((float) ($walletSummary['recharge'] ?? 0) <= 0) : ?>
                <div class="alert alert-warning store-alert-compact small">
                    <?= translate('no store balance yet request admin approval'); ?>
                    <a href="<?= site_url('store/funding'); ?>" class="alert-link"><?= translate('request store balance'); ?></a>
                </div>
            <?php endif; ?>

            <input type="hidden" id="store-prize-player-id" value="">

            <label for="store-prize-lookup" class="form-label"><?= translate('player document number'); ?></label>
            <div class="store-search-row mb-2">
                <div class="store-form-field">
                    <input type="text" class="form-control form-bingo" id="store-prize-lookup" placeholder="<?= translate('enter player document number'); ?>" autocomplete="off">
                    <small id="store-prize-lookup-hint" class="text-muted d-none"><?= translate('searching player'); ?>...</small>
                    <small id="store-prize-lookup-error" class="text-danger d-none"></small>
                </div>
                <button type="button" class="btn btn-primary btn-bingo store-search-btn" id="store-prize-lookup-btn">
                    <i class="fa-duotone fa-solid fa-magnifying-glass"></i> <?= translate('search'); ?>
                </button>
            </div>

            <div id="store-prize-player-preview" class="store-player-preview d-none mb-2">
                <div class="store-player-preview-inner">
                    <div>
                        <strong id="store-prize-player-name"></strong>
                        <div class="small text-muted">
                            <span id="store-prize-player-code"></span> ·
                            <span id="store-prize-player-document"></span>
                        </div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block"><?= translate('current balance'); ?></small>
                        <strong><?= systemGet('currency'); ?> <span id="store-prize-player-wallet">0.00</span></strong>
                    </div>
                </div>
            </div>

            <div class="store-form-field store-form-field-full">
                <label for="store-prize-payable-amount" class="form-label"><?= translate('prize amount to pay'); ?></label>
                <input type="text" class="form-control form-bingo store-prize-payable-input" id="store-prize-payable-amount" value="0.00" placeholder="0.00" readonly disabled>
                <small id="store-prize-payable-detail" class="text-muted d-block mt-1"><?= translate('search and select a player first'); ?></small>
            </div>
            </div>
        </div>

        <div class="store-tab-history">
            <h6 class="store-tab-history-title">
                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i> <?= translate('player prizes history'); ?>
            </h6>
            <div class="store-table-wrap" id="store-prizes-list">
                <?= view('store/prizes_list', [
                    'sings' => [],
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'totalRecords' => 0,
                    'per_page' => 10,
                    'showPagination' => false,
                    'requiresPlayer' => true,
                ]); ?>
            </div>
        </div>
    </div>
</div>

<?= view('store/partials/close') ?>

<?= view('store/partials/scripts_common') ?>

<script type="text/javascript">
    let storePrizesPage = 1;
    let storePrizeSelectedPlayer = null;
    let storePrizeLookupTimer = null;
    let storePrizeLookupRequest = null;
    const storePrizeMinDocumentDigits = 6;
    const storePrizeCurrency = '<?= esc(systemGet('currency'), 'js'); ?>';

    function storeUpdatePrizeSummary(summary) {
        summary = summary || { count: 0, total_formatted: '0.00', items: [] };
        const count = parseInt(summary.count, 10) || 0;
        const total = summary.total_formatted || '0.00';

        $('#store-prize-payable-amount').val(total);

        if (!$('#store-prize-player-id').val()) {
            $('#store-prize-payable-detail').text('<?= esc(translate('search and select a player first'), 'js'); ?>');
            return;
        }

        if (count === 0) {
            $('#store-prize-payable-detail').text('<?= esc(translate('no pending prizes to pay'), 'js'); ?>');
            return;
        }

        let detail = count === 1
            ? '<?= esc(translate('one pending prize to pay'), 'js'); ?>'
            : count + ' <?= esc(translate('pending prizes to pay'), 'js'); ?>';

        if (Array.isArray(summary.items) && summary.items.length > 0) {
            const lines = summary.items.slice(0, 3).map(function(item) {
                return item.game + ' · ' + item.modality + ': ' + storePrizeCurrency + ' ' + item.amount_formatted;
            });
            if (summary.items.length > 3) {
                lines.push('+' + (summary.items.length - 3) + ' <?= esc(translate('more'), 'js'); ?>');
            }
            detail += ' — ' + lines.join(' | ');
        }

        $('#store-prize-payable-detail').text(detail);
    }

    function storeLoadPrizeSummary() {
        const playerId = $('#store-prize-player-id').val() || '';

        if (!playerId) {
            storeUpdatePrizeSummary();
            return;
        }

        $.get('<?= site_url('store/playerPrizeSummaryGet'); ?>', {
            player_id: playerId,
            status: '1'
        }, function(response) {
            if (response.success) {
                storeUpdatePrizeSummary(response.summary || {});
            }
        }, 'json');
    }

    function storeRefreshPrizes(page) {
        storePrizesPage = page || 1;
        const playerId = $('#store-prize-player-id').val() || '';

        if (!playerId) {
            $('#store-prizes-list').html('<div class="text-center py-4 text-muted"><?= esc(translate('search and select a player first'), 'js'); ?></div>');
            storeUpdatePrizeSummary();
            return;
        }

        storeLoadPrizeSummary();

        $.get('<?= site_url('store/prizesListGet'); ?>', {
            page: storePrizesPage,
            player_id: playerId,
            status: '1'
        }, function(html) {
            $('#store-prizes-list').html(html);
        });
    }

    function storePayAwardSubmit(id, userName, amount) {
        Swal.fire({
            title: '<?= esc(translate('pay prize'), 'js'); ?>',
            html: '<?= esc(translate('confirm pay prize to'), 'js'); ?> <strong>' + userName + '</strong><br><?= esc(systemGet('currency'), 'js'); ?> <strong>' + amount + '</strong>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<?= esc(translate('yes'), 'js'); ?>',
            cancelButtonText: '<?= esc(translate('cancel'), 'js'); ?>'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            fetch('<?= site_url('store/payAwardSubmit'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id: id, action: 'pay' })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    storeShowToast(data.message || '<?= esc(translate('prize paid successfully from store'), 'js'); ?>', 'success');
                    if (typeof data.store_balance !== 'undefined' && data.store_balance !== null) {
                        storeUpdateBalance(data.store_balance);
                    }
                    storeRefreshPrizes(storePrizesPage);
                } else {
                    storeShowToast(data.error || data.message || '<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>', 'error');
                }
            })
            .catch(function() {
                storeShowToast('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>', 'error');
            });
        });
    }

    function storePrizesGetPage(page) {
        storeRefreshPrizes(page);
    }

    $(function() {
        function storeNormalizeDocument(value) {
            return String(value || '').replace(/\D/g, '');
        }

        function storeClearPrizePlayerSelection() {
            storePrizeSelectedPlayer = null;
            $('#store-prize-player-id').val('');
            $('#store-prize-player-preview').addClass('d-none');
            $('#store-prize-payable-amount').val('0.00').prop('disabled', true);
            storeUpdatePrizeSummary();
            storeRefreshPrizes(1);
        }

        function storeShowPrizePlayer(response) {
            storePrizeSelectedPlayer = response.player;
            $('#store-prize-player-id').val(response.player.id);
            $('#store-prize-player-name').text(response.player.firstname + ' ' + response.player.lastname);
            $('#store-prize-player-code').text(response.player.code);
            $('#store-prize-player-document').text(response.player.document);
            $('#store-prize-player-wallet').text(response.player.wallet);
            $('#store-prize-player-preview').removeClass('d-none');
            $('#store-prize-payable-amount').prop('disabled', false);
            $('#store-prize-lookup-error').addClass('d-none').text('');
            storeUpdatePrizeSummary(response.prizes_summary || {});
            storeRefreshPrizes(1);
        }

        function storeLookupPrizePlayer() {
            const query = $('#store-prize-lookup').val().trim();
            const digits = storeNormalizeDocument(query);

            $('#store-prize-lookup-error').addClass('d-none').text('');

            if (!query) {
                storeClearPrizePlayerSelection();
                return;
            }

            if (digits.length < storePrizeMinDocumentDigits) {
                storeClearPrizePlayerSelection();
                return;
            }

            if (storePrizeLookupRequest) {
                storePrizeLookupRequest.abort();
            }

            $('#store-prize-lookup-hint').removeClass('d-none');
            $('#store-prize-lookup-btn').prop('disabled', true);

            storePrizeLookupRequest = $.post('<?= site_url('store/lookupPlayer'); ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash(); ?>',
                query: query
            }, function(response) {
                if (!response.success) {
                    storeClearPrizePlayerSelection();
                    $('#store-prize-lookup-error').text(response.message || '<?= esc(translate('player not found'), 'js'); ?>').removeClass('d-none');
                    return;
                }

                storeShowPrizePlayer(response);
            }, 'json').fail(function(xhr, status) {
                if (status === 'abort') {
                    return;
                }
                storeClearPrizePlayerSelection();
                $('#store-prize-lookup-error').text('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>').removeClass('d-none');
            }).always(function() {
                storePrizeLookupRequest = null;
                $('#store-prize-lookup-hint').addClass('d-none');
                $('#store-prize-lookup-btn').prop('disabled', false);
            });
        }

        $('#store-prize-lookup-btn').on('click', function() {
            const query = $('#store-prize-lookup').val().trim();

            if (!query) {
                $('#store-prize-lookup-error').text('<?= esc(translate('enter player document number'), 'js'); ?>').removeClass('d-none');
                return;
            }

            storeLookupPrizePlayer();
        });

        $('#store-prize-lookup').on('input', function() {
            clearTimeout(storePrizeLookupTimer);

            const query = $(this).val().trim();
            const digits = storeNormalizeDocument(query);

            if (!query || digits.length < storePrizeMinDocumentDigits) {
                storeClearPrizePlayerSelection();
                $('#store-prize-lookup-error').addClass('d-none').text('');
                $('#store-prize-lookup-hint').addClass('d-none');
                return;
            }

            storePrizeLookupTimer = setTimeout(storeLookupPrizePlayer, 450);
        });

        $('#store-prize-lookup').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(storePrizeLookupTimer);
                $('#store-prize-lookup-btn').trigger('click');
            }
        });

        storeUpdatePrizeSummary();
    });
</script>
