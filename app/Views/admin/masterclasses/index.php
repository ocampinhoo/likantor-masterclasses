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
                <th>Zoom</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($masterclasses)): ?>
                <tr><td colspan="8" class="text-muted">No hay Masterclasses. Importa el schema SQL con datos seed.</td></tr>
            <?php else: ?>
                <?php foreach ($masterclasses as $mc): ?>
                    <?php
                    $hasZoom = !empty($mc['zoom_meeting_url']);
                    ?>
                    <tr>
                        <td><?= (int) $mc['id'] ?></td>
                        <td><?= e($mc['name']) ?></td>
                        <td><a href="<?= url('/masterclasses/' . $mc['slug']) ?>" target="_blank"><?= e($mc['slug']) ?></a></td>
                        <td><span class="badge"><?= e($mc['status']) ?></span></td>
                        <td><?= e(number_format((float) $mc['price'], 2)) ?> <?= e($mc['currency']) ?></td>
                        <td><?= e($mc['event_starts_at']) ?></td>
                        <td>
                            <?php if ($hasZoom): ?>
                                <span class="badge badge--paid">Configurado</span>
                            <?php else: ?>
                                <span class="badge">Sin URL</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn--secondary btn--sm" href="<?= url('/admin/masterclasses/' . (int) $mc['id'] . '/zoom') ?>">
                                Editar Zoom
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
