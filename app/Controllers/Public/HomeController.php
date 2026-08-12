<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Repositories\MasterclassRepository;

final class HomeController extends Controller
{
    public function index(): void
    {
        $masterclasses = [];

        try {
            $masterclasses = (new MasterclassRepository())->published();
        } catch (\Throwable) {
            // BD no disponible aún — mostrar home sin masterclasses
        }

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Likantor — Ingeniería en Estructuras',
            'alternateName' => 'LIKANTOR',
            'url' => url('/'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Guadalajara',
                'addressRegion' => 'Jalisco',
                'addressCountry' => 'MX',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->view('public/home', [
            'title' => 'Likantor — Ingeniería en Estructuras',
            'masterclasses' => $masterclasses,
            'meta' => [
                'title' => 'Likantor — Ingeniería en Estructuras',
                'description' => 'Likantor Ingeniería en Estructuras — diseño, análisis y construcción de estructuras de concreto y acero en Guadalajara, México. Masterclasses educativas en línea.',
                'canonical' => url('/'),
                'extra_css' => ['pages/home.css'],
                'json_ld' => $jsonLd,
            ],
        ]);
    }
}
