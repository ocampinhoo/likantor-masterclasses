<section class="section">
    <div class="container container--narrow">
        <h1>Mi perfil</h1>
        <form action="<?= url('/mi-cuenta/perfil') ?>" method="POST" class="form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name">Nombre <span class="required">*</span></label>
                <input type="text" id="name" name="name" required maxlength="150" value="<?= e($user['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico <span class="required">*</span></label>
                <input type="email" id="email" name="email" required maxlength="255" value="<?= e($user['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="pronouns">Pronombres <span class="optional">(opcional)</span></label>
                <input type="text" id="pronouns" name="pronouns" maxlength="50" value="<?= e($user['pronouns'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="professional_title">Título académico/profesional <span class="optional">(opcional)</span></label>
                <input type="text" id="professional_title" name="professional_title" maxlength="150" value="<?= e($user['professional_title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="age">Edad <span class="optional">(opcional)</span></label>
                <input type="number" id="age" name="age" min="1" max="120" value="<?= e((string) ($user['age'] ?? '')) ?>">
            </div>
            <button type="submit" class="btn btn--primary">Guardar cambios</button>
        </form>
    </div>
</section>
