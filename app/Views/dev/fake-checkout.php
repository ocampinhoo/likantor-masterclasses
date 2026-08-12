<?php $p = $payment; ?>
<section class="section">
    <div class="container container--narrow">
        <div class="flash" style="background:#fef3c7;color:#78350f;">
            <strong>Simulador de pago — solo APP_ENV=local.</strong>
            Esta pantalla reemplaza el checkout real de <?= e(ucfirst((string) $p['provider'])) ?> porque
            no hay credenciales configuradas. Al elegir un resultado, se enviará un webhook firmado
            al endpoint <code>/webhooks/<?= e($p['provider']) ?></code> (mismo código que producción).
        </div>

        <div class="card" style="margin:1.5rem 0;">
            <p style="margin:0 0 0.5rem;"><strong>Proveedor:</strong> <?= e(ucfirst((string) $p['provider'])) ?></p>
            <p style="margin:0 0 0.5rem;"><strong>Estado actual:</strong> <span class="badge badge--<?= e((string) $p['status']) ?>"><?= e((string) $p['status']) ?></span></p>
            <p style="margin:0 0 0.5rem;"><strong>Monto del checkout:</strong> <?= e(number_format((float) $p['amount'], 2)) ?> <?= e($p['currency']) ?></p>
            <p style="margin:0 0 0.5rem;"><strong>Referencia interna:</strong> <code><?= e($p['uuid']) ?></code></p>
            <?php if (!empty($lastEventId)): ?>
                <p style="margin:0;"><strong>Último event_id simulado:</strong> <code><?= e((string) $lastEventId) ?></code></p>
            <?php endif; ?>
        </div>

        <h2>Simular resultado del pago</h2>
        <div class="checkout-methods" style="display:flex;flex-direction:column;gap:0.75rem;max-width:420px;">
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

        <h2 style="margin-top:2rem;">Pruebas de seguridad / edge cases</h2>
        <p class="text-muted" style="font-size:0.9rem;">Estos envíos usan el mismo endpoint de webhook, pero alteran monto, moneda o event_id.</p>

        <form action="<?= url('/dev/pagos/' . $p['uuid'] . '/simular') ?>" method="POST" class="card" style="margin-top:1rem;max-width:420px;">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="outcome_adv">Outcome</label>
                <select id="outcome_adv" name="outcome" class="form-control">
                    <option value="approved">approved</option>
                    <option value="pending">pending</option>
                    <option value="failed">failed</option>
                    <option value="cancelled">cancelled</option>
                    <option value="refunded">refunded</option>
                </select>
            </div>
            <div class="form-group">
                <label for="override_amount">Monto del webhook (opcional)</label>
                <input type="text" id="override_amount" name="override_amount" class="form-control" placeholder="ej. 1.00 (incorrecto)">
            </div>
            <div class="form-group">
                <label for="override_currency">Moneda del webhook (opcional)</label>
                <input type="text" id="override_currency" name="override_currency" class="form-control" placeholder="ej. MXN (incorrecta)">
            </div>
            <div class="form-group">
                <label for="event_id">event_id fijo (opcional, para duplicados)</label>
                <input type="text" id="event_id" name="event_id" class="form-control" value="<?= e((string) ($lastEventId ?? '')) ?>" placeholder="ej. evt_dev_dup_test_1">
                <p class="form-hint">Envía dos veces el mismo event_id: el segundo debe responder <code>already processed</code>.</p>
            </div>
            <button type="submit" class="btn btn--secondary btn--block">Enviar webhook personalizado</button>
        </form>

        <p class="text-muted" style="margin-top:1.5rem;font-size:0.85rem;">
            Requiere <code>STRIPE_WEBHOOK_SECRET</code> / <code>MERCADOPAGO_WEBHOOK_SECRET</code> con
            cualquier cadena inventada en <code>.env</code> (no uses keys reales). Deja
            <code>STRIPE_SECRET_KEY</code> y <code>MERCADOPAGO_ACCESS_TOKEN</code> vacíos para forzar este simulador.
        </p>

        <p style="margin-top:1rem;">
            <a href="<?= url('/mi-cuenta') ?>">Ir a Mi cuenta</a>
            ·
            <a href="<?= url('/pago/exito') ?>">Ver página de retorno</a>
        </p>
    </div>
</section>
