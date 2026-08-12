<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Masterclass</th>
                <th>Estado</th>
                <th>Pago</th>
                <th>Acceso otorgado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($enrollments)): ?>
                <tr><td colspan="6" class="text-muted">No hay registros.</td></tr>
            <?php else: ?>
                <?php foreach ($enrollments as $enrollment): ?>
                    <tr>
                        <td><?= e($enrollment['user_name']) ?><br><span class="text-muted" style="font-size:0.8rem;"><?= e($enrollment['user_email']) ?></span></td>
                        <td><?= e($enrollment['masterclass_name']) ?></td>
                        <td><span class="badge badge--<?= e($enrollment['status']) ?>"><?= e($enrollment['status']) ?></span></td>
                        <td>
                            <?php if (!empty($enrollment['payment_provider'])): ?>
                                <?= e(ucfirst((string) $enrollment['payment_provider'])) ?> ·
                                <?= e(number_format((float) $enrollment['payment_amount'], 2)) ?> <?= e($enrollment['payment_currency']) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($enrollment['access_granted_at'] ?? '—') ?></td>
                        <td><?= e($enrollment['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
