<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) $stats['users'] ?></span>
        <span class="stat-card__label">Usuarios</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) $stats['masterclasses'] ?></span>
        <span class="stat-card__label">Masterclasses</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) $stats['enrollments_paid'] ?> / <?= (int) $stats['enrollments'] ?></span>
        <span class="stat-card__label">Registros pagados</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) $stats['leads'] ?></span>
        <span class="stat-card__label">Leads</span>
    </div>
</div>

<p class="text-muted admin-note">Panel administrativo — Likantor Masterclasses</p>
