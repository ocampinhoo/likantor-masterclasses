<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;

final class PageController extends Controller
{
    public function privacy(): void
    {
        $this->view('public/privacy', [
            'title' => 'Aviso de privacidad',
            'meta' => [
                'title' => 'Aviso de privacidad',
                'description' => 'Aviso de privacidad de Likantor Ingeniería en Estructuras.',
                'canonical' => url('/aviso-de-privacidad'),
            ],
        ]);
    }

    public function terms(): void
    {
        $this->view('public/terms', [
            'title' => 'Términos y condiciones',
            'meta' => [
                'title' => 'Términos y condiciones',
                'description' => 'Términos y condiciones de uso de la plataforma Likantor Masterclasses.',
                'canonical' => url('/terminos-y-condiciones'),
            ],
        ]);
    }

    public function contact(): void
    {
        $this->view('public/contact', [
            'title' => 'Contacto',
            'meta' => [
                'title' => 'Contacto',
                'description' => 'Contacta a Likantor Ingeniería en Estructuras.',
                'canonical' => url('/contacto'),
            ],
        ]);
    }
}
