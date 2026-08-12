<?php
$copy = [
    'exito' => [
        'title' => '¡Gracias! Estamos confirmando tu pago',
        'text' => 'Tu pago fue enviado correctamente. La confirmación definitiva de tu lugar depende de la notificación (webhook) que envía el proveedor de pago, lo cual normalmente toma solo unos segundos. En cuanto se confirme, recibirás un correo con tu folio y podrás ver tu acceso en "Mi cuenta".',
    ],
    'pendiente' => [
        'title' => 'Tu pago está pendiente',
        'text' => 'Tu pago quedó en proceso de confirmación por parte del proveedor (esto es común con algunos métodos, como transferencias o pagos en efectivo). Te avisaremos por correo en cuanto se confirme. Tu lugar se activará automáticamente cuando eso ocurra.',
    ],
    'error' => [
        'title' => 'No pudimos completar tu pago',
        'text' => 'El intento de pago no se completó o fue cancelado. No se realizó ningún cargo confirmado. Puedes intentarlo de nuevo o elegir otro método de pago.',
    ],
][$variant] ?? ['title' => 'Estado de tu pago', 'text' => ''];
?>
<section class="section">
    <div class="container container--narrow text-center">
        <h1><?= e($copy['title']) ?></h1>
        <p class="section__lead" style="margin-inline:auto;"><?= e($copy['text']) ?></p>

        <?php if ($payment !== null): ?>
            <div class="card" style="text-align:left;margin:2rem auto;max-width:420px;">
                <p style="margin:0 0 0.5rem;"><strong>Masterclass:</strong> <?= e($payment['masterclass_name'] ?? '—') ?></p>
                <p style="margin:0 0 0.5rem;"><strong>Monto:</strong> <?= e(number_format((float) $payment['amount'], 2)) ?> <?= e($payment['currency']) ?></p>
                <p style="margin:0;"><strong>Estado actual:</strong> <span class="badge badge--<?= e($payment['status']) ?>"><?= e($payment['status']) ?></span></p>
            </div>
        <?php endif; ?>

        <div class="hero__actions" style="justify-content:center;">
            <a href="<?= url('/mi-cuenta') ?>" class="btn btn--primary btn--lg">Ir a mi cuenta</a>
            <?php if ($variant === 'error'): ?>
                <a href="<?= url('/masterclasses') ?>" class="btn btn--secondary">Ver Masterclasses</a>
            <?php endif; ?>
        </div>
    </div>
</section>
