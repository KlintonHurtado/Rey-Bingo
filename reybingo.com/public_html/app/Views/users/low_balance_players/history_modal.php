<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2">
                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i> <?= translate('low balance roulette history'); ?>
            </h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal">
                <i class="fa-duotone fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body pt-2">
            <div class="table-responsive low-balance-history-scroll" id="low-balance-history-list">
                <?= view('users/low_balance_players/history_list', ['history' => $history ?? []]); ?>
            </div>
        </div>
    </div>
</div>
