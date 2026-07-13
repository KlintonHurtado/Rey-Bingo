<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('date'); ?></th>
            <th><?= translate('bank'); ?></th>
            <th class="text-center"><?= translate('amount'); ?></th>
            <th><?= translate('reference'); ?></th>
            <th class="text-center"><?= translate('status'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($fundingRequests)) : ?>
            <?php foreach ($fundingRequests as $request) : ?>
                <tr>
                    <td><?= esc(date('d/m/Y H:i', strtotime($request['created_at']))) ?></td>
                    <td><?= esc($request['bank']) ?></td>
                    <td class="text-center"><?= systemGet('currency'); ?> <?= number_format((float) $request['amount'], 2) ?></td>
                    <td><?= esc($request['reference']) ?></td>
                    <td class="text-center"><?= $request['status_label'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="5" class="text-center py-3"><?= translate('no balance requests yet'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
