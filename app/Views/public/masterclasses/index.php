<section class="section">
    <div class="container">
        <h1>Masterclasses</h1>
        <p class="section-intro">Explora nuestras Masterclasses disponibles.</p>

        <?php if (empty($masterclasses)): ?>
            <p class="text-muted">No hay Masterclasses publicadas en este momento.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($masterclasses as $mc): ?>
                    <article class="card">
                        <h2><?= e($mc['name']) ?></h2>
                        <p><?= e($mc['instructor_name']) ?></p>
                        <p class="card__price"><?= e(number_format((float) $mc['price'], 2)) ?> <?= e($mc['currency']) ?></p>
                        <a href="<?= url('/masterclasses/' . $mc['slug']) ?>" class="btn btn--primary">Ver Masterclass</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
