<?php

declare(strict_types=1);

return [
    'from_email' => env('SENDGRID_FROM_EMAIL', ''),
    'from_name' => env('SENDGRID_FROM_NAME', 'Likantor Masterclasses'),
    'reply_to' => env('SENDGRID_REPLY_TO', ''),
    'api_key' => env('SENDGRID_API_KEY', ''),
    'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL', ''),
];
