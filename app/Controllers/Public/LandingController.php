<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Utm;
use App\Repositories\MasterclassRepository;

final class LandingController extends Controller
{
    private const SLUG = 'revision-estructuras-post-sismo';

    public function showRevisionPostSismo(): void
    {
        Utm::captureFromRequest();

        $masterclass = $this->resolveMasterclass();

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'EducationEvent',
            'name' => 'Revisión de Estructuras Post-Sismo',
            'description' => 'Masterclass educativa sobre evaluación visual post-sismo impartida por Fernando Robledo (Likantor).',
            'startDate' => '2026-09-17T18:30:00-06:00',
            'endDate' => '2026-09-17T21:30:00-06:00',
            'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => [
                '@type' => 'VirtualLocation',
                'url' => url('/masterclass/revision-estructuras-post-sismo'),
            ],
            'organizer' => [
                '@type' => 'Organization',
                'name' => 'Likantor — Ingeniería en Estructuras',
                'url' => url('/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => '65',
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'url' => url('/registro'),
            ],
            'performer' => [
                '@type' => 'Person',
                'name' => 'Fernando Robledo',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->view('public/landing/revision-post-sismo', [
            'title' => 'Revisión de Estructuras Post-Sismo',
            'masterclass' => $masterclass,
            'utm' => Utm::fromSession(),
            'meta' => [
                'title' => 'Revisión de Estructuras Post-Sismo — Masterclass en vivo',
                'description' => 'Masterclass educativa de 3 horas con Fernando Robledo (Likantor). Aprende a identificar señales post-sismo que ameritan atención. 17 sep 2026 · 6:30 PM CDMX · 65 USD.',
                'canonical' => url('/masterclass/revision-estructuras-post-sismo'),
                'og_type' => 'website',
                'og_image' => url('/assets/img/og-landing.svg'),
                'body_class' => 'page-landing has-sticky-cta',
                'header_variant' => 'landing',
                'extra_css' => ['pages/landing.css'],
                'load_timezone' => true,
                'sticky_cta' => true,
                'sticky_cta_url' => url('/registro'),
                'sticky_cta_text' => 'Reservar mi lugar',
                'json_ld' => $jsonLd,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMasterclass(): array
    {
        try {
            $fromDb = (new MasterclassRepository())->findBySlug(self::SLUG);

            if ($fromDb !== null && in_array($fromDb['status'], ['published', 'registration_closed', 'live', 'completed'], true)) {
                return (new MasterclassRepository())->withoutSensitiveAccessFields($fromDb);
            }
        } catch (\Throwable) {
            // Fallback a config estática
        }

        $fallback = Config::get('landing');

        return $fallback[self::SLUG] ?? $fallback[array_key_first($fallback)];
    }
}
