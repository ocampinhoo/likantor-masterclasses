<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Config;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Utm;
use App\Repositories\MasterclassRepository;
use App\Services\LeadService;

final class LeadController extends Controller
{
    private const LANDING_SLUG = 'revision-estructuras-post-sismo';
    private const RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const RATE_LIMIT_WINDOW_SECONDS = 600; // 10 minutos

    public function storeRevisionPostSismo(): void
    {
        $this->storeLead(self::LANDING_SLUG, '/masterclass/' . self::LANDING_SLUG . '#temario');
    }

    public function store(string $slug): void
    {
        $this->storeLead($slug, '/masterclasses/' . $slug . '#temario');
    }

    public function success(): void
    {
        $data = $_SESSION['_lead_success'] ?? null;
        unset($_SESSION['_lead_success']);

        $masterclassName = is_array($data) ? ($data['masterclass_name'] ?? null) : null;
        $masterclassUrl = is_array($data) && !empty($data['masterclass_url'])
            ? $data['masterclass_url']
            : url('/masterclass/' . self::LANDING_SLUG);

        $this->view('public/lead-success', [
            'title' => 'Solicitud recibida',
            'masterclassName' => $masterclassName,
            'masterclassUrl' => $masterclassUrl,
            'meta' => [
                'title' => 'Solicitud recibida — Likantor',
                'description' => 'Hemos recibido tu solicitud de información.',
                'canonical' => url('/gracias-temario'),
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    private function storeLead(string $slug, string $redirectPath): void
    {
        $masterclass = $this->resolveMasterclass($slug);

        if ($masterclass === null) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Masterclass no encontrada']);
            return;
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (RateLimiter::tooManyAttempts('lead_form_' . $ip, self::RATE_LIMIT_MAX_ATTEMPTS, self::RATE_LIMIT_WINDOW_SECONDS)) {
            $this->rememberInput([
                'name' => sanitize_string((string) $this->input('name')),
                'email' => sanitize_email((string) $this->input('email')),
            ]);
            $this->flash('error', 'Demasiadas solicitudes desde tu conexión. Intenta de nuevo en unos minutos.');
            $this->redirect($redirectPath);
        }

        $utm = $this->captureUtm();

        $result = (new LeadService())->capture(
            [
                'name' => $this->input('name'),
                'email' => $this->input('email'),
                'privacy' => $this->input('privacy'),
                'website' => $this->input('website'), // honeypot anti-bot
            ],
            [
                'masterclass' => $masterclass,
                'source' => 'syllabus_form',
                'campaign' => $this->input('campaign'),
                'utm' => $utm,
                'ip' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );

        if (!$result['success']) {
            $this->rememberInput([
                'name' => sanitize_string((string) $this->input('name')),
                'email' => sanitize_email((string) $this->input('email')),
            ]);
            $this->flash('error', $result['message'] ?? 'No pudimos procesar tu solicitud. Intenta de nuevo.');
            $this->redirect($redirectPath);
        }

        $this->clearOldInput();

        $_SESSION['_lead_success'] = [
            'masterclass_name' => $masterclass['name'] ?? null,
            'masterclass_url' => url('/masterclass/' . ($masterclass['slug'] ?? $slug)),
        ];

        $this->redirect('/gracias-temario');
    }

    /**
     * Lee los UTM enviados en el formulario (hidden inputs) y, si faltan,
     * recurre a los capturados previamente en sesión al llegar a la landing.
     *
     * @return array<string, string|null>
     */
    private function captureUtm(): array
    {
        $sessionUtm = Utm::fromSession();
        $result = [];

        foreach (Utm::KEYS as $key) {
            $posted = $this->input($key);
            $result[$key] = is_string($posted) && $posted !== ''
                ? sanitize_string($posted, 150)
                : $sessionUtm[$key];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMasterclass(string $slug): ?array
    {
        try {
            $fromDb = (new MasterclassRepository())->findBySlug($slug);

            if ($fromDb !== null) {
                return $fromDb;
            }
        } catch (\Throwable) {
            // Fallback a config estática
        }

        $fallback = Config::get('landing');

        return $fallback[$slug] ?? null;
    }
}
