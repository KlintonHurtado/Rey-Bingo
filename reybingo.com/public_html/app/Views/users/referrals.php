<div class="modal-dialog modal-dialog-centered max-w-25 max-w-95-xs">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-qrcode"></i> Invita y gana</h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <div class="card mt-1">
                <div class="text-center pt-2">
                    <img src="<?= site_url('users/referralCode') ?>" alt="img" class="img-fluid w-50">
                </div>
        
                <div class="text-left p-2">
                    <small style="font-size: 0.9rem; font-weight: normal;">
                        ✅ Comparte tu enlace de invitación con amigos y familiares.<br>
                        ✅ Cuando alguien se registra con tu enlace y realiza la recarga mínima en nuestro sistema, recibirás tu recompensa de <strong><?= systemGet('rateReferrals') * 100 ?>%</strong> por referido.<br>
                        ✅ Cuantas más personas invites, más comisiones acumularás, las ganancias se acreditan directamente a tu billetera.
                    </small>

                    <div class="mt-2 p-2 rounded" style="background: rgba(98,54,255,0.12);">
                        <strong>Punto de venta / afiliado</strong>
                        <p class="small mb-1">Tu enlace para vender en tienda o compartir:</p>
                        <input type="text" class="form-control form-control-sm form-bingo mb-1" id="affiliate-link" readonly value="<?= site_url('signup/' . ($user['referred_code'] ?? $user['code'])); ?>">
                        <button type="button" class="btn btn-primary btn-bingo btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('affiliate-link').value);">Copiar enlace</button>
                    </div>
        
                    <?php if (!empty($lastGame)): ?>
                        <a href="https://api.whatsapp.com/send?text=🎉 ¡Regístrate en <?= APP_NAME; ?> 🎱 y participa en la ruleta de premios! 
                        Este 🕢 <?= translate_day($lastGame['date'] . ' ' . $lastGame['time']) ?> 
                        🗓️ <?= translate_date($lastGame['date']) ?>, 
                        tenemos partidas con 💵 *¡<?= systemGet('currency'); ?> <?= $total ?> en premios!* 
                        por solo *<?= systemGet('currency'); ?> <?= $lastGame['price'] ?>*. 
                        🌐 <?= site_url('signup'); ?>/<?= $user['referred_code'] ?>" 
                        target="_blank" 
                        class="btn btn-success btn-bingo mt-2">Compartir por WhatsApp</a>
                    <?php else: ?>
                        <a href="https://api.whatsapp.com/send?text=🎉 ¡Regístrate en <?= APP_NAME; ?> 🎱 y empieza a jugar con nosotros! 
                        👉 Crea tu cuenta gratis aquí: <?= site_url('signup'); ?>/<?= $user['referred_code'] ?>" 
                        target="_blank" 
                        class="btn btn-success btn-bingo mt-2">Compartir por WhatsApp</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

