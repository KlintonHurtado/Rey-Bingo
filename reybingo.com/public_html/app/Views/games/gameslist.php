<?php 
    $showPagination = isset($totalPages) && $totalPages > 1;
?>

<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('description'); ?></th>
            <?php if (session()->get('group') == 1) : ?>
            <th class="text-center"><?= translate('players'); ?></th>
            <?php endif; ?>
            <th><?= translate('date'); ?></th>
            <th class="text-center"><?= translate('price'); ?></th>
            <th class="text-center"><?= translate('award'); ?></th>
            <th class="text-center"><?= translate('status'); ?></th>
            <th class="text-center"><?= translate('options'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($games)) : ?>
            <?php foreach ($games as $game) : ?>
                <?php
                    if ($game['percentage'] >= 90) {
                        $progressClass = 'bingo-bg-success';
                    } elseif ($game['percentage'] >= 70) {
                        $progressClass = 'bingo-bg-orange';
                    } elseif ($game['percentage'] >= 40) {
                        $progressClass = 'bingo-bg-info';
                    } else {
                        $progressClass = 'bingo-bg-primary';
                    }
                ?>
                <tr>
                    <td>
                        <strong><?= esc($game['room']) ?> </strong><br /> <?= esc($game['description']) ?>
                        <div id="game-progress-<?= esc($game['id']) ?>" class="progress" role="progressbar" aria-label="<?= translate('progress'); ?> <?= esc($game['description']) ?>" aria-valuenow="<?= esc($game['percentage']) ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar <?= $progressClass ?>" style="width: <?= esc($game['percentage']) ?>%; position: relative;">
                                <small class="progress-text" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 0.75em;"><?= esc($game['percentage']) ?>%</small>
                                <small class="progress-numbers" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.75em;"><?= esc($game['numbers_called']) ?>/75</small>
                            </div> 
                        </div>
                    </td>
                    <?php if (session()->get('group') == 1) : ?>
                    <td class="text-center" id="game-players-<?= esc($game['id']) ?>"><?= esc($game['players']) ?></td>
                    <?php endif; ?>
                    <td><?= esc(translate_day($game['date'] . ' ' . $game['time'])) ?> <br /> <?= esc(translate_date($game['date'])) ?></td>
                    <td class="text-center"><?= systemGet('currency'); ?> <?= number_format($game['price'], 2) ?></td>
                    <td class="text-center" id="game-total-<?= esc($game['id']) ?>"><?= systemGet('currency'); ?> <?= number_format($game['total'], 2) ?></td>
                    <td class="text-center" id="game-status-<?= esc($game['id']) ?>"><?= $game['status'] ?></td>
                    <td><?= $game['buttons'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" class="text-center"><?= translate('no games found'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($showPagination): ?>
    <div class="row mt-4">
        <div class="col-12 col-md text-center mt-2 mb-sm-3">
            <span class="text-muted">
                <?= translate('showing'); ?> 
                <?= ($currentPage - 1) * $per_page + 1; ?> - 
                <?= min($currentPage * $per_page, $totalRecords); ?> 
                <?= translate('of'); ?> <?= number_format($totalRecords); ?> <?= translate('games'); ?>
            </span>
        </div>
        <div class="col-12 col-md text-center">
            <nav class="d-flex justify-content-center align-items-center">
                <ul class="pagination mb-0">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="gameslistGetPage(<?= $currentPage - 1; ?>)">
                                «
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="gameslistGetPage(1)">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="javascript:void(0)" onclick="gameslistGetPage(<?= $i; ?>)">
                                <?= $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="gameslistGetPage(<?= $totalPages; ?>)"><?= $totalPages; ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="gameslistGetPage(<?= $currentPage + 1; ?>)">
                                »
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
<?php endif; ?>

<script type="text/javascript">
    function notifyMinPlayersRequired(message) {
        Toastify({
            text: message,
            duration: 4000,
            gravity: "top",
            position: "right",
            style: { background: "#dc3545" },
            stopOnFocus: true
        }).showToast();
    }

    function gameslistGetPage(page) {
        var date = $('#datefilter').val() || 'all';
        var room = $('#roomfilter').val() || 'all';
        var status = $('#statusfilter').val() || 'all';

        $.ajax({
            url: '<?= site_url('games/gameslistGet') ?>/' + date + '/' + room + '/' + status + '/' + page,
            type: "GET",
            success: function(data) {  
                $("#games-list").html(data);
            },
            error: function () {
                Toastify({
                    text: '<?= translate('there was an error in the request to the server.'); ?>',
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();

                $("#games-list").html('');
            }
        });
    }

    function awardsGet(id) {
        $("#modalAwards").load('<?= site_url('games/awardsGet') ?>/' + id);
        $('#modalAwards').modal('show');
    }

    function playersGet(id) {
        $("#modalPlayers").load('<?= site_url('games/playersGet') ?>/' + id);
        $('#modalPlayers').modal('show');
    }

    function gameGet(gameId) {
        fetch('<?= site_url('game') ?>/' + gameId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                Toastify({
                    text: data.message || "<?= translate('the game could not be started. try again'); ?>",
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            }
        })
        .catch(error => {
            console.error('request error:', error);
            Toastify({
                text: "<?= translate('there was an error processing your request. Please try again'); ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#dc3545" },
                stopOnFocus: true
            }).showToast();
        });
    }

    function liveGet(gameId) {
        fetch('<?= site_url('live') ?>/' + gameId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                Toastify({
                    text: data.message || "<?= translate('the game could not be started. try again'); ?>",
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            }
        })
        .catch(error => {
            console.error('request error:', error);
            Toastify({
                text: "<?= translate('there was an error processing your request. Please try again'); ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#dc3545" },
                stopOnFocus: true
            }).showToast();
        });
    }
</script>
