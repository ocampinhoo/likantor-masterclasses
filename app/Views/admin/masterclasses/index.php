<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Slug</th>
                <th>Estado</th>
                <th>Precio</th>
                <th>Fecha evento</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($masterclasses)): ?>
                <tr><td colspan="6" class="text-muted">No hay Masterclasses. Importa el schema SQL con datos seed.</td></tr>
            <?php else: ?>
                <?php foreach ($masterclasses as $mc): ?>
                    <tr>
                        <td><?= (int) $mc['id'] ?></td>
                        <td><?= e($mc['name']) ?></td>
                        <td><a href="<?= url('/masterclasses/' . $mc['slug']) ?>" target="_blank"><?= e($mc['slug']) ?></a></td>
                        <td><span class="badge"><?= e($mc['status']) ?></span></td>
                        <td><?= e(number_format((float) $mc['price'], 2)) ?> <?= e($mc['currency']) ?></td>
                        <td><?= e($mc['event_starts_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
