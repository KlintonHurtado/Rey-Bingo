<div class="modal-dialog modal-dialog-centered max-w-40">
  <div class="modal-content bg-transparent border-0 shadow-none">
    <?php if (! empty($deposit['voucher']) && bingo_voucher_exists($deposit['voucher'])) : ?>
      <img src="<?= esc(bingo_voucher_url($deposit['voucher'])) ?>" alt="voucher" class="img-fluid rounded bg-white">
    <?php else : ?>
      <div class="alert alert-warning mb-0">Comprobante no disponible.</div>
    <?php endif; ?>
  </div>
</div>
