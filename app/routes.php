<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\EmailsController as AdminEmailsController;
use App\Controllers\Admin\EnrollmentsController as AdminEnrollmentsController;
use App\Controllers\Admin\LeadsController as AdminLeadsController;
use App\Controllers\Admin\MasterclassesController as AdminMasterclassesController;
use App\Controllers\Admin\PaymentsController as AdminPaymentsController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Admin\UsersController as AdminUsersController;
use App\Controllers\Dev\PaymentSimulatorController;
use App\Controllers\Public\AuthController;
use App\Controllers\Public\HomeController;
use App\Controllers\Public\LandingController;
use App\Controllers\Public\LeadController;
use App\Controllers\Public\MasterclassController;
use App\Controllers\Public\PageController;
use App\Controllers\User\AccessController;
use App\Controllers\User\CheckoutController;
use App\Controllers\User\DashboardController as UserDashboardController;
use App\Controllers\User\ProfileController;
use App\Controllers\Webhooks\MercadoPagoWebhookController;
use App\Controllers\Webhooks\StripeWebhookController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

$router = new Router();

// --- Público ---
$router->get('/', [HomeController::class, 'index']);
$router->get('/masterclass/revision-estructuras-post-sismo', [LandingController::class, 'showRevisionPostSismo']);
$router->post('/masterclass/revision-estructuras-post-sismo/lead', [LeadController::class, 'storeRevisionPostSismo'], [CsrfMiddleware::class]);
$router->get('/masterclasses/revision-estructuras-post-sismo', static function (): void {
    header('Location: ' . url('/masterclass/revision-estructuras-post-sismo'), true, 301);
    exit;
});
$router->get('/masterclasses', [MasterclassController::class, 'index']);
$router->get('/masterclasses/{slug}', [MasterclassController::class, 'show']);
$router->post('/masterclasses/{slug}/lead', [LeadController::class, 'store'], [CsrfMiddleware::class]);
$router->get('/gracias-temario', [LeadController::class, 'success']);
$router->get('/aviso-de-privacidad', [PageController::class, 'privacy']);
$router->get('/terminos-y-condiciones', [PageController::class, 'terms']);
$router->get('/contacto', [PageController::class, 'contact']);

// --- Auth ---
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/registro', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/registro', [AuthController::class, 'register'], [GuestMiddleware::class, CsrfMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [CsrfMiddleware::class]);
$router->get('/verificar-email', [AuthController::class, 'showVerifyNotice']);
$router->get('/verificar-email/{token}', [AuthController::class, 'verifyEmail']);
$router->post('/verificar-email/reenviar', [AuthController::class, 'resendVerification'], [CsrfMiddleware::class]);
$router->get('/recuperar-contrasena', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->post('/recuperar-contrasena', [AuthController::class, 'forgotPassword'], [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/restablecer-contrasena/{token}', [AuthController::class, 'showResetPassword']);
$router->post('/restablecer-contrasena/{token}', [AuthController::class, 'resetPassword'], [CsrfMiddleware::class]);

// --- Área privada usuario ---
$router->get('/mi-cuenta', [UserDashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/mi-cuenta/perfil', [ProfileController::class, 'show'], [AuthMiddleware::class]);
$router->post('/mi-cuenta/perfil', [ProfileController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/mi-cuenta/masterclasses/{slug}/acceso-zoom', [AccessController::class, 'zoom'], [AuthMiddleware::class]);

// --- Checkout / pagos ---
$router->get('/checkout/{slug}', [CheckoutController::class, 'show'], [AuthMiddleware::class]);
$router->post('/checkout/{slug}/stripe', [CheckoutController::class, 'payWithStripe'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/checkout/{slug}/mercadopago', [CheckoutController::class, 'payWithMercadoPago'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/pago/exito', [CheckoutController::class, 'returnSuccess'], [AuthMiddleware::class]);
$router->get('/pago/pendiente', [CheckoutController::class, 'returnPending'], [AuthMiddleware::class]);
$router->get('/pago/error', [CheckoutController::class, 'returnError'], [AuthMiddleware::class]);

// --- Webhooks de pago (públicos: sin login ni CSRF; autenticidad validada por firma) ---
$router->post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
$router->post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle']);

// --- Simulador de webhooks (solo funciona con APP_ENV=local; ver PaymentSimulatorController) ---
$router->get('/dev/pagos/{uuid}', [PaymentSimulatorController::class, 'showFakeCheckout']);
$router->post('/dev/pagos/{uuid}/simular', [PaymentSimulatorController::class, 'simulate'], [CsrfMiddleware::class]);

// --- Admin ---
$router->get('/admin', [AdminDashboardController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/usuarios', [AdminUsersController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/leads', [AdminLeadsController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/masterclasses', [AdminMasterclassesController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/masterclasses/{id}/zoom', [AdminMasterclassesController::class, 'editZoom'], [AdminMiddleware::class]);
$router->post('/admin/masterclasses/{id}/zoom', [AdminMasterclassesController::class, 'updateZoom'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/registros', [AdminEnrollmentsController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/pagos', [AdminPaymentsController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/emails', [AdminEmailsController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/configuracion', [AdminSettingsController::class, 'index'], [AdminMiddleware::class]);

return $router;
