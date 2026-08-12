<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\MasterclassAccessService;

/**
 * Endpoint protegido de acceso a Zoom. No entrega la URL en HTML:
 * valida autorización server-side y redirige al enlace externo.
 */
final class AccessController extends Controller
{
    public function zoom(string $slug): void
    {
        $auth = new AuthService();
        $user = $auth->user();

        if ($user === null) {
            $this->redirect('/login');
        }

        // El user_id sale exclusivamente de la sesión autenticada.
        $result = (new MasterclassAccessService())->grantZoomAccess((int) $user['id'], $slug);

        if (!$result['allowed']) {
            $this->flash('error', $result['message'] ?? 'No tienes acceso a esta Masterclass.');
            $this->redirect('/mi-cuenta');
        }

        $this->redirect($result['url']);
    }
}
