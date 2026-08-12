<?php
$counts = $summary['counts'] ?? [];
$revenue = $summary['revenue_by_currency'] ?? [];
?>
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) ($summary['total_sales'] ?? 0) ?></span>
        <span class="stat-card__label">Total de ventas (aprobadas)</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) ($counts['approved'] ?? 0) ?></span>
        <span class="stat-card__label">Pagos aprobados</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) ($counts['pending'] ?? 0) ?></span>
        <span class="stat-card__label">Pendientes</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) ($counts['failed'] ?? 0) + (int) ($counts['cancelled'] ?? 0) + (int) ($counts['unknown'] ?? 0) ?></span>
        <span class="stat-card__label">Fallidos / cancelados</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) ($counts['refunded'] ?? 0) + (int) ($counts['chargeback'] ?? 0) ?></span>
        <span class="stat-card__label">Reembolsos / contracargos</span>
    </div>
</div>

<div class="card" style="margin-bottom:2rem;">
    <h2 style="margin-top:0;">Ingresos por moneda</h2>
    <p class="text-muted" style="margin-bottom:1rem;">
        Los ingresos nunca se suman entre monedas distintas: cada moneda se reporta por separado.
    </p>
    <?php if (empty($revenue)): ?>
        <p class="text-muted">Aún no hay pagos aprobados.</p>
    <?php else: ?>
        <div class="stats-grid" style="margin-bottom:0;">
            <?php foreach ($revenue as $currency => $amount): ?>
                <div class="stat-card">
                    <span class="stat-card__value"><?= e(number_format((float) $amount, 2)) ?></span>
                    <span class="stat-card__label"><?= e($currency) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<h2>Pagos recientes</h2>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Usuario</th>
                <th>Masterclass</th>
                <th>Proveedor</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Creado</th>
                <th>Webhook</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="8" class="text-muted">No hay pagos registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><code>LKT-<?= e(strtoupper(substr(str_replace('-', '', (string) $payment['uuid']), 0, 8))) ?></code></td>
                        <td><?= e($payment['user_name']) ?><br><span class="text-muted" style="font-size:0.8rem;"><?= e($payment['user_email']) ?></span></td>
                        <td><?= e($payment['masterclass_name']) ?></td>
                        <td><?= e(ucfirst((string) $payment['provider'])) ?></td>
                        <td><?= e(number_format((float) $payment['amount'], 2)) ?> <?= e($payment['currency']) ?></td>
                        <td><span class="badge badge--<?= e($payment['status']) ?>"><?= e($payment['status']) ?></span></td>
                        <td><?= e($payment['created_at']) ?></td>
                        <td><?= e($payment['webhook_received_at'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
