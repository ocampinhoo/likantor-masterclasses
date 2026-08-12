<?php

declare(strict_types=1);

namespace App\Controllers\Dev;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Http;
use App\Repositories\PaymentRepository;
use App\Services\PaymentWebhookSimulator;

/**
 * Simulador local de webhooks de pago — SOLO accesible con APP_ENV=local.
 *
 * Permite probar el flujo completo (checkout -> webhook -> confirmación ->
 * activación de acceso -> email) sin credenciales reales de Stripe ni
 * Mercado Pago: construye un evento con la forma exacta que enviaría cada
 * proveedor y lo envía (vía HTTP, en loopback) al endpoint de webhook real,
 * firmado con el mismo secreto configurado en .env. Así se ejercita
 * literalmente el mismo código de validación de firma e idempotencia que se
 * usaría en producción.
 *
 * Esta clase se autoprotege: cualquier acción responde 404 si APP_ENV no es
 * 'local', para que nunca sea alcanzable en producción aunque la ruta llegara
 * a registrarse por error.
 */
final class PaymentSimulatorController extends Controller
{
    public function showFakeCheckout(string $uuid): void
    {
        $this->guardLocalEnv();

        $payment = (new PaymentRepository())->findByUuid($uuid);

        if ($payment === null) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'No encontrado']);
            return;
        }

        $this->view('dev/fake-checkout', [
            'title' => 'Simulador de pago (solo desarrollo)',
            'payment' => $payment,
        ]);
    }

    public function simulate(string $uuid): void
    {
        $this->guardLocalEnv();

        $payment = (new PaymentRepository())->findByUuid($uuid);

        if ($payment === null) {
            $this->redirect('/mi-cuenta');
        }

        $outcome = (string) $this->input('outcome', 'approved');
        $provider = (string) $payment['provider'];

        $event = PaymentWebhookSimulator::build($provider, $outcome, $payment);
        $response = Http::request('POST', url($event['path']), $event['headers'], $event['body'], 15);

        if ($response['status'] >= 200 && $response['status'] < 300) {
            $this->flash('success', 'Webhook simulado enviado (resultado: ' . strtoupper($outcome) . '). El estado se actualizó con el mismo código que procesa webhooks reales.');
        } else {
            $this->flash('error', 'El webhook simulado respondió con error (HTTP ' . $response['status'] . '). Revisa storage/logs/app.log para más detalle.');
        }

        $this->redirect('/pago/exito');
    }

    private function guardLocalEnv(): void
    {
        if (Config::app('env') !== 'local') {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'No encontrado']);
            exit;
        }
    }
}
