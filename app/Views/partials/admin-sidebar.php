<aside class="admin-sidebar">
    <div class="admin-sidebar__brand">
        <a href="<?= url('/admin') ?>">Likantor Admin</a>
    </div>
    <nav class="admin-nav" aria-label="Administración">
        <a href="<?= url('/admin') ?>" class="<?= is_active_path('/admin') && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/') ? 'is-active' : '' ?>">Dashboard</a>
        <a href="<?= url('/admin/usuarios') ?>" class="<?= is_active_path('/admin/usuarios') ? 'is-active' : '' ?>">Usuarios</a>
        <a href="<?= url('/admin/leads') ?>" class="<?= is_active_path('/admin/leads') ? 'is-active' : '' ?>">Leads</a>
        <a href="<?= url('/admin/masterclasses') ?>" class="<?= is_active_path('/admin/masterclasses') ? 'is-active' : '' ?>">Masterclasses</a>
        <a href="<?= url('/admin/registros') ?>" class="<?= is_active_path('/admin/registros') ? 'is-active' : '' ?>">Registros</a>
        <a href="<?= url('/admin/pagos') ?>" class="<?= is_active_path('/admin/pagos') ? 'is-active' : '' ?>">Pagos</a>
        <a href="<?= url('/admin/emails') ?>" class="<?= is_active_path('/admin/emails') ? 'is-active' : '' ?>">Emails</a>
        <a href="<?= url('/admin/configuracion') ?>" class="<?= is_active_path('/admin/configuracion') ? 'is-active' : '' ?>">Configuración</a>
    </nav>
    <div class="admin-sidebar__footer">
        <a href="<?= url('/') ?>">← Volver al sitio</a>
    </div>
</aside>
