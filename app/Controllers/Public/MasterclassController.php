<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Utm;
use App\Repositories\MasterclassRepository;

final class MasterclassController extends Controller
{
    private MasterclassRepository $masterclasses;

    public function __construct()
    {
        $this->masterclasses = new MasterclassRepository();
    }

    public function index(): void
    {
        $items = $this->masterclasses->mapWithoutSensitiveAccessFields(
            $this->masterclasses->published()
        );

        $this->view('public/masterclasses/index', [
            'title' => 'Masterclasses',
            'masterclasses' => $items,
        ]);
    }

    public function show(string $slug): void
    {
        Utm::captureFromRequest();

        $masterclass = $this->masterclasses->findBySlug($slug);

        if ($masterclass === null || !in_array($masterclass['status'], ['published', 'registration_closed', 'live', 'completed'], true)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Masterclass no encontrada']);
            return;
        }

        $this->view('public/masterclasses/show', [
            'title' => $masterclass['name'],
            'masterclass' => $this->masterclasses->withoutSensitiveAccessFields($masterclass),
            'utm' => Utm::fromSession(),
        ]);
    }
}
