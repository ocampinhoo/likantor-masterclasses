<?php

declare(strict_types=1);

namespace App\Middleware;

abstract class Middleware
{
    abstract public function handle(): bool;
}
