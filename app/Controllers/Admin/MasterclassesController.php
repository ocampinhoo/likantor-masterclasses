<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\MasterclassRepository;

final class MasterclassesController extends Controller
{
    public function index(): void
    {
        $masterclasses = (new MasterclassRepository())->all();

        $this->view('admin/masterclasses/index', [
            'title' => 'Masterclasses',
            'masterclasses' => $masterclasses,
        ], 'admin');
    }

    public function editZoom(string $id): void
    {
        $masterclass = (new MasterclassRepository())->findById((int) $id);

        if ($masterclass === null) {
            $this->flash('error', 'Masterclass no encontrada.');
            $this->redirect('/admin/masterclasses');
        }

        $this->view('admin/masterclasses/zoom', [
            'title' => 'Acceso Zoom',
            'masterclass' => $masterclass,
        ], 'admin');
    }

    public function updateZoom(string $id): void
    {
        $repo = new MasterclassRepository();
        $masterclassId = (int) $id;
        $masterclass = $repo->findById($masterclassId);

        if ($masterclass === null) {
            $this->flash('error', 'Masterclass no encontrada.');
            $this->redirect('/admin/masterclasses');
        }

        $zoomUrl = trim((string) $this->input('zoom_meeting_url', ''));
        $zoomMeetingId = trim((string) $this->input('zoom_meeting_id', ''));
        $zoomPasscode = trim((string) $this->input('zoom_passcode', ''));

        if ($zoomUrl !== '') {
            if (filter_var($zoomUrl, FILTER_VALIDATE_URL) === false) {
                $this->flash('error', 'La URL de Zoom no es válida.');
                $this->redirect('/admin/masterclasses/' . $masterclassId . '/zoom');
            }

            $scheme = strtolower((string) parse_url($zoomUrl, PHP_URL_SCHEME));

            if (!in_array($scheme, ['https', 'http'], true)) {
                $this->flash('error', 'La URL de Zoom debe usar http o https.');
                $this->redirect('/admin/masterclasses/' . $masterclassId . '/zoom');
            }
        }

        $repo->updateZoomAccess($masterclassId, [
            'zoom_meeting_url' => $zoomUrl !== '' ? $zoomUrl : null,
            'zoom_meeting_id' => $zoomMeetingId !== '' ? sanitize_string($zoomMeetingId, 50) : null,
            'zoom_passcode' => $zoomPasscode !== '' ? sanitize_string($zoomPasscode, 255) : null,
        ]);

        $this->flash('success', 'Datos de Zoom guardados correctamente.');
        $this->redirect('/admin/masterclasses/' . $masterclassId . '/zoom');
    }
}
