<?php

declare(strict_types=1);

return [
    // Ventana de tolerancia anti-replay para firmas de webhooks (segundos).
    'webhook_tolerance_seconds' => 300,

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN', ''),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY', ''),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET', ''),
        // Mercado Pago solo puede liquidar en la moneda local de la cuenta del
        // vendedor. Si tu cuenta no puede procesar la moneda comercial de la
        // Masterclass (USD), define aquí explícitamente la moneda/monto que se
        // enviará al checkout. NUNCA se calculan conversiones automáticas en
        // código: es una decisión de negocio explícita y visible en config.
        // Deja ambos vacíos para intentar usar la misma moneda/precio comercial.
        'checkout_currency' => env('MERCADOPAGO_CHECKOUT_CURRENCY', ''),
        'checkout_amount' => env('MERCADOPAGO_CHECKOUT_AMOUNT', ''),
    ],

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],
];
