<?php
$mc = $masterclass;
?>
<section class="section">
    <div class="container container--narrow">
        <h1>Inscripción</h1>
        <p class="section-intro">Estás a un paso de asegurar tu lugar en esta Masterclass.</p>

        <div class="card" style="margin-bottom:1.5rem;">
            <h2 style="margin-top:0;"><?= e($mc['name']) ?></h2>
            <?php if (!empty($mc['subtitle'])): ?>
                <p class="text-muted"><?= e($mc['subtitle']) ?></p>
            <?php endif; ?>
            <p><?= (int) $mc['duration_minutes'] ?> min · En vivo por Zoom</p>
            <p class="price-tag"><?= e(number_format((float) $mc['price'], 2)) ?> <?= e($mc['currency']) ?></p>
        </div>

        <?php if ($devMode): ?>
            <div class="flash" style="background:#fef3c7;color:#78350f;">
                <strong>Modo desarrollo:</strong> como no hay credenciales reales de Stripe/Mercado Pago
                configuradas, al continuar se abrirá un simulador local de pagos para probar el flujo
                completo (webhook incluido) sin conectarte a ningún proveedor real.
            </div>
        <?php endif; ?>

        <div class="disclaimer-box" style="margin-bottom:1.5rem;">
            <p style="margin:0;">
                <strong>Importante:</strong> tu lugar se confirma únicamente cuando el proveedor de pago
                notifica la aprobación (esto puede tardar unos segundos). No cierres esta ventana antes
                de completar el pago; si tu banco requiere autenticación adicional, complétala en la
                página del proveedor.
            </p>
        </div>

        <div class="checkout-methods" style="display:flex;flex-direction:column;gap:1rem;">
            <form action="<?= url('/checkout/' . $mc['slug'] . '/stripe') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--primary btn--block btn--lg" <?= $stripeAvailable ? '' : 'disabled' ?>>
                    Pagar con Stripe
                </button>
                <?php if (!$stripeAvailable): ?>
                    <p class="text-muted" style="font-size:0.85rem;">No disponible en este momento.</p>
                <?php endif; ?>
            </form>

            <form action="<?= url('/checkout/' . $mc['slug'] . '/mercadopago') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--outline btn--block btn--lg" <?= $mercadoPagoAvailable ? '' : 'disabled' ?>>
                    Pagar con Mercado Pago
                </button>
                <?php if (!$mercadoPagoAvailable): ?>
                    <p class="text-muted" style="font-size:0.85rem;">No disponible en este momento.</p>
                <?php endif; ?>
            </form>
        </div>

        <p class="text-muted" style="margin-top:1.5rem;font-size:0.85rem;">
            Pago seguro. No almacenamos datos de tu tarjeta: el pago se procesa directamente en la
            plataforma de Stripe o Mercado Pago.
        </p>
    </div>
</section>
