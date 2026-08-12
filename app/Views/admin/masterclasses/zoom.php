<?php
/** @var array<string, mixed> $masterclass */
?>
<div class="admin-panel">
    <p>
        <a href="<?= url('/admin/masterclasses') ?>">&larr; Volver a Masterclasses</a>
    </p>

    <h2><?= e($masterclass['name'] ?? '') ?></h2>
    <p class="text-muted">
        Configura el acceso Zoom creado manualmente. Estos datos no se muestran en la landing pública;
        solo se entregan a usuarios con inscripción pagada a través de un enlace protegido.
    </p>

    <form method="POST" action="<?= url('/admin/masterclasses/' . (int) $masterclass['id'] . '/zoom') ?>" class="form" style="max-width:36rem;">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="zoom_meeting_url">Zoom URL</label>
            <input
                type="url"
                id="zoom_meeting_url"
                name="zoom_meeting_url"
                class="form-control"
                value="<?= e((string) ($masterclass['zoom_meeting_url'] ?? '')) ?>"
                placeholder="https://zoom.us/j/..."
                autocomplete="off"
            >
            <p class="form-hint">URL completa de la reunión (preferible https). Puede incluir el passcode en la query si Zoom lo genera así.</p>
        </div>

        <div class="form-group">
            <label for="zoom_meeting_id">Zoom Meeting ID</label>
            <input
                type="text"
                id="zoom_meeting_id"
                name="zoom_meeting_id"
                class="form-control"
                value="<?= e((string) ($masterclass['zoom_meeting_id'] ?? '')) ?>"
                maxlength="50"
                autocomplete="off"
            >
        </div>

        <div class="form-group">
            <label for="zoom_passcode">Zoom Passcode</label>
            <input
                type="text"
                id="zoom_passcode"
                name="zoom_passcode"
                class="form-control"
                value="<?= e((string) ($masterclass['zoom_passcode'] ?? '')) ?>"
                maxlength="255"
                autocomplete="off"
            >
            <p class="form-hint">Referencia interna. El acceso de alumnos se hace con la Zoom URL vía endpoint protegido.</p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Guardar acceso Zoom</button>
        </div>
    </form>
</div>
