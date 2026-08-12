<?php

declare(strict_types=1);

return [
    'rate_limit_enabled' => filter_var(env('RATE_LIMIT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'csrf_token_name' => '_csrf_token',
    'password_min_length' => 10,
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 15,
    'register_max_attempts' => 5,
    'register_lockout_minutes' => 60,
    'password_reset_max_attempts' => 3,
    'password_reset_lockout_minutes' => 15,
    'email_verification_token_ttl_hours' => 24,
    'password_reset_token_ttl_minutes' => 60,
    'remember_me_ttl_days' => 30,
];
