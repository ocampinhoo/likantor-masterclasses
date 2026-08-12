<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Config;
use App\Core\Controller;
use App\Core\ErrorHandler;
use App\Repositories\EnrollmentRepository;
use App\Repositories\MasterclassRepository;
use App\Repositories\PaymentRepository;
use App\Services\AuthService;
use App\Services\MercadoPagoPaymentService;
use App\Services\PaymentService;
use App\Services\StripePaymentService;

/**
 * Flujo de checkout: el usuario (ya autenticado) elige una Masterclass y un
 * proveedor de pago. Este controlador únicamente CREA el intento de pago y
 * redirige al checkout hospedado del proveedor; la confirmación real llega
 * después, de forma asíncrona, por el webhook correspondiente.
 */
final class CheckoutController extends Controller
{
    public function show(string $slug): void
    {
        $masterclass = $this->resolveMasterclass($slug);
        $user = (new AuthService())->user();

        $enrollment = (new EnrollmentRepository())->findByUserAndMasterclass((int) $user['id'], (int) $masterclass['id']);

        if ($enrollment !== null && $enrollment['status'] === 'paid') {
            $this->flash('success', 'Ya cuentas con un lugar confirmado en esta Masterclass.');
            $this->redirect('/mi-cuenta');
        }

        $devMode = Config::app('env') === 'local';
        $stripe = new StripePaymentService();
        $mercadoPago = new MercadoPagoPaymentService();

        $this->view('user/checkout', [
            'title' => 'Inscripción — ' . $masterclass['name'],
            'masterclass' => $masterclass,
            'stripeAvailable' => $stripe->isConfigured() || $devMode,
            'mercadoPagoAvailable' => $mercadoPago->isConfigured() || $devMode,
            'devMode' => $devMode,
        ]);
    }

    public function payWithStripe(string $slug): void
    {
        $masterclass = $this->resolveMasterclass($slug);
        $user = (new AuthService())->user();
        $devMode = Config::app('env') === 'local';

        $stripe = new StripePaymentService();

        if (!$stripe->isConfigured() && !$devMode) {
            $this->flash('error', 'El pago con Stripe no está disponible en este momento. Intenta con Mercado Pago.');
            $this->redirect('/checkout/' . $slug);
        }

        try {
            $attempt = (new PaymentService())->createCheckoutAttempt(
                $user,
                $masterclass,
                'stripe',
                $this->resolveCheckoutAmount('stripe', $masterclass)
            );
        } catch (\RuntimeException) {
            $this->flash('success', 'Ya cuentas con un lugar confirmado en esta Masterclass.');
            $this->redirect('/mi-cuenta');
        }

        $payment = $attempt['payment'];

        if (!$stripe->isConfigured()) {
            // Solo alcanzable con APP_ENV=local: permite probar el flujo completo sin credenciales reales.
            $this->redirect('/dev/pagos/' . $payment['uuid']);
        }

        $result = $stripe->createCheckoutSession(
            (string) $payment['uuid'],
            (float) $payment['amount'],
            (string) $payment['currency'],
            (string) $masterclass['name'],
            (string) $user['email'],
            url('/pago/exito'),
            url('/pago/error')
        );

        if (!$result['success']) {
            ErrorHandler::log('Stripe checkout session failed', ['error' => $result['error'] ?? null]);
            $this->flash('error', 'No pudimos iniciar el pago con Stripe. Intenta de nuevo o usa Mercado Pago.');
            $this->redirect('/checkout/' . $slug);
        }

        (new PaymentService())->attachProviderPreference((int) $payment['id'], (string) $result['session_id']);

        $this->redirect((string) $result['url']);
    }

    public function payWithMercadoPago(string $slug): void
    {
        $masterclass = $this->resolveMasterclass($slug);
        $user = (new AuthService())->user();
        $devMode = Config::app('env') === 'local';

        $mercadoPago = new MercadoPagoPaymentService();

        if (!$mercadoPago->isConfigured() && !$devMode) {
            $this->flash('error', 'El pago con Mercado Pago no está disponible en este momento. Intenta con Stripe.');
            $this->redirect('/checkout/' . $slug);
        }

        try {
            $attempt = (new PaymentService())->createCheckoutAttempt(
                $user,
                $masterclass,
                'mercadopago',
                $this->resolveCheckoutAmount('mercadopago', $masterclass)
            );
        } catch (\RuntimeException) {
            $this->flash('success', 'Ya cuentas con un lugar confirmado en esta Masterclass.');
            $this->redirect('/mi-cuenta');
        }

        $payment = $attempt['payment'];

        if (!$mercadoPago->isConfigured()) {
            // Solo alcanzable con APP_ENV=local: permite probar el flujo completo sin credenciales reales.
            $this->redirect('/dev/pagos/' . $payment['uuid']);
        }

        $result = $mercadoPago->createPreference(
            (string) $payment['uuid'],
            (float) $payment['amount'],
            (string) $payment['currency'],
            (string) $masterclass['name'],
            (string) $user['email'],
            url('/pago/exito'),
            url('/pago/pendiente'),
            url('/pago/error'),
            url('/webhooks/mercadopago')
        );

        if (!$result['success']) {
            ErrorHandler::log('Mercado Pago preference failed', ['error' => $result['error'] ?? null]);
            $this->flash('error', 'No pudimos iniciar el pago con Mercado Pago. Intenta de nuevo o usa Stripe.');
            $this->redirect('/checkout/' . $slug);
        }

        (new PaymentService())->attachProviderPreference((int) $payment['id'], (string) $result['preference_id']);

        $this->redirect((string) $result['init_point']);
    }

    public function returnSuccess(): void
    {
        $this->renderReturn('exito');
    }

    public function returnPending(): void
    {
        $this->renderReturn('pendiente');
    }

    public function returnError(): void
    {
        $this->renderReturn('error');
    }

    private function renderReturn(string $variant): void
    {
        $user = (new AuthService())->user();
        $payment = null;

        try {
            $payment = (new PaymentRepository())->findLatestForUser((int) $user['id']);
        } catch (\Throwable) {
            // BD no disponible; se muestra la página igualmente con mensaje genérico.
        }

        $this->view('user/payment-return', [
            'title' => 'Estado de tu pago',
            'variant' => $variant,
            'payment' => $payment,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMasterclass(string $slug): array
    {
        $masterclass = (new MasterclassRepository())->findBySlug($slug);

        if ($masterclass === null || !in_array($masterclass['status'], ['published', 'registration_closed', 'live'], true)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'No encontrado']);
            exit;
        }

        return $masterclass;
    }

    /**
     * Resuelve el monto/moneda que se solicitarán realmente en el checkout de
     * un proveedor dado. Por defecto es igual al precio comercial de la
     * Masterclass; para Mercado Pago puede sobreescribirse explícitamente vía
     * configuración cuando la cuenta no pueda liquidar en la moneda comercial
     * (nunca se realiza una conversión automática en código).
     *
     * @param array<string, mixed> $masterclass
     * @return array{amount: float, currency: string}
     */
    private function resolveCheckoutAmount(string $provider, array $masterclass): array
    {
        $commercialAmount = (float) $masterclass['price'];
        $commercialCurrency = (string) $masterclass['currency'];

        if ($provider === 'mercadopago') {
            $mpConfig = Config::get('payments')['mercadopago'] ?? [];
            $overrideCurrency = trim((string) ($mpConfig['checkout_currency'] ?? ''));
            $overrideAmount = (string) ($mpConfig['checkout_amount'] ?? '');

            if ($overrideCurrency !== '' && $overrideAmount !== '' && is_numeric($overrideAmount)) {
                return ['amount' => (float) $overrideAmount, 'currency' => strtoupper($overrideCurrency)];
            }
        }

        return ['amount' => $commercialAmount, 'currency' => $commercialCurrency];
    }
}
