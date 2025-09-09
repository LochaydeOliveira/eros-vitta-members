<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\AdminAuthController;
use App\Controllers\WebhookController;
use App\Controllers\ProductController;
use App\Controllers\AccessController;
use App\Controllers\DownloadController;
use App\Controllers\TestEmailController;
use App\Middleware\AuthMiddleware;

$router = new Router();

// Healthcheck
$router->get('/api/health', static function () {
    return [
        'status' => 'ok',
        'time' => date('c'),
    ];
});

// Teste de email (temporário)
$router->post('/api/test/email', [TestEmailController::class, 'send']);

// Auth usuário
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/password/forgot', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/password/reset', [AuthController::class, 'resetPassword']);
$router->get('/api/auth/me', AuthMiddleware::requireUser([AuthController::class, 'me']));

// Auth admin
$router->post('/api/admin/login', [AdminAuthController::class, 'login']);

// Produtos e acessos (usuário autenticado)
$router->get('/api/products', AuthMiddleware::requireUser([ProductController::class, 'list']));
$router->get('/api/accesses', AuthMiddleware::requireUser([AccessController::class, 'list']));

// Downloads protegidos
$router->post('/api/downloads/token', AuthMiddleware::requireUser([DownloadController::class, 'createToken']));
$router->get('/api/downloads/file', [DownloadController::class, 'streamByToken']);

// Webhook Hotmart
$router->post('/api/hotmart/webhook', [WebhookController::class, 'handle']);

$router->dispatch();
