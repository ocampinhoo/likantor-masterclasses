<?php $p = $payment; ?>
<section class="section">
    <div class="container container--narrow">
        <div class="flash" style="background:#fef3c7;color:#78350f;">
            <strong>Simulador de pago — solo APP_ENV=local.</strong>
            Esta pantalla reemplaza el checkout real de <?= e(ucfirst((string) $p['provider'])) ?> porque
            no hay credenciales configuradas. Al elegir un resultado, se enviará un webhook firmado
            (con el mismo código que procesaría un webhook real) al endpoint
            <code>/webhooks/<?= e($p['provider']) ?></code>.
        </div>

        <div class="card" style="margin:1.5rem 0;">
            <p style="margin:0 0 0.5rem;"><strong>Proveedor:</strong> <?= e(ucfirst((string) $p['provider'])) ?></p>
            <p style="margin:0 0 0.5rem;"><strong>Monto del checkout:</strong> <?= e(number_format((float) $p['amount'], 2)) ?> <?= e($p['currency']) ?></p>
            <p style="margin:0;"><strong>Referencia interna:</strong> <code><?= e($p['uuid']) ?></code></p>
        </div>

        <h2>Simular resultado del pago</h2>
        <div class="checkout-methods" style="display:flex;flex-direction:column;gap:0.75rem;max-width:360px;">
            <form action="<?= url('/dev/pagos/' . $p['uuid'] . '/simular') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="outcome" value="approved">
                <button type="submit" class="btn btn--primary btn--block">✔ Simular pago aprobado</button>
            </form>
            <form action="<?= url('/dev/pagos/' . $p['uuid'] . '/simular') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="outcome" value="pending">
                <button type="submit" class="btn btn--outline btn--block">⏳ Simular pago pendiente</button>
            </form>
            <form action="<?= url('/dev/pagos/' . $p['uuid'] . '/simular') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="outcome" value="failed">
                <button type="submit" class="btn btn--outline btn--block">✖ Simular pago rechazado</button>
            </form>
            <form action="<?= url('/dev/pagos/' . $p['uuid'] . '/simular') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="outcome" value="cancelled">
                <button type="submit" class="btn btn--outline btn--block">◐ Simular pago cancelado</button>
            </form>
            <form action="<?= url('/dev/pagos/' . $p['uuid'] . '/simular') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="outcome" value="refunded">
                <button type="submit" class="btn btn--outline btn--block">↩ Simular reembolso (tras aprobado)</button>
            </form>
        </div>

        <p class="text-muted" style="margin-top:1.5rem;font-size:0.85rem;">
            Requiere que <code>STRIPE_WEBHOOK_SECRET</code> / <code>MERCADOPAGO_WEBHOOK_SECRET</code> tengan
            algún valor en tu <code>.env</code> local (puede ser cualquier cadena inventada; no necesita ser
            una credencial real) para poder firmar el webhook simulado.
        </p>
    </div>
</section>
