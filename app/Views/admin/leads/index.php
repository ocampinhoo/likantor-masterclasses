<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Masterclass</th>
                <th>Fuente</th>
                <th>Campaña</th>
                <th>UTM origen / medio</th>
                <th>Interacciones</th>
                <th>IP</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leads)): ?>
                <tr><td colspan="10" class="text-muted">No hay leads.</td></tr>
            <?php else: ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?= (int) $lead['id'] ?></td>
                        <td><?= e($lead['name']) ?></td>
                        <td><?= e($lead['email']) ?></td>
                        <td><?= e($lead['masterclass_name'] ?? '—') ?></td>
                        <td><?= e($lead['source']) ?></td>
                        <td><?= e($lead['campaign'] ?? '—') ?></td>
                        <td>
                            <?= e($lead['utm_source'] ?? '—') ?>
                            <?php if (!empty($lead['utm_medium'])): ?>
                                / <?= e($lead['utm_medium']) ?>
                            <?php endif; ?>
                            <?php if (!empty($lead['utm_campaign'])): ?>
                                <br><span class="text-muted" style="font-size:0.8rem;"><?= e($lead['utm_campaign']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) ($lead['interactions_count'] ?? 0) ?></td>
                        <td><?= e(format_ip($lead['ip_address'] ?? null)) ?></td>
                        <td><?= e($lead['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
