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
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Controllers\AdminProductController;
use App\Controllers\AdminUserController;
 

$router = new Router();

// Healthcheck
$router->get('/api/health', static function () {
    return [
        'status' => 'ok',
        'time' => date('c'),
    ];
});


// Auth usuário
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/password/forgot', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/password/reset', [AuthController::class, 'resetPassword']);
$router->get('/api/auth/me', AuthMiddleware::requireUser([AuthController::class, 'me']));

// Auth admin
$router->post('/api/admin/login', [AdminAuthController::class, 'login']);

// Admin - produtos
$router->get('/api/admin/products', AdminMiddleware::requireAdmin([AdminProductController::class, 'list']));
$router->post('/api/admin/products', AdminMiddleware::requireAdmin([AdminProductController::class, 'create']));
$router->put('/api/admin/products', AdminMiddleware::requireAdmin([AdminProductController::class, 'update'])) ;
$router->post('/api/admin/products/update', AdminMiddleware::requireAdmin([AdminProductController::class, 'update'])) ;
$router->post('/api/admin/products/deactivate', AdminMiddleware::requireAdmin([AdminProductController::class, 'deactivate'])) ;

// Admin - usuários
$router->get('/api/admin/users', AdminMiddleware::requireAdmin([AdminUserController::class, 'list']));
$router->post('/api/admin/users/block', AdminMiddleware::requireAdmin([AdminUserController::class, 'block']));
$router->post('/api/admin/users/unblock', AdminMiddleware::requireAdmin([AdminUserController::class, 'unblock']));
$router->post('/api/admin/users/reset-password', AdminMiddleware::requireAdmin([AdminUserController::class, 'resetPassword']));

// Produtos e acessos (usuário autenticado)
$router->get('/api/products', AuthMiddleware::requireUser([ProductController::class, 'list']));
$router->get('/api/accesses', AuthMiddleware::requireUser([AccessController::class, 'list']));

// Downloads protegidos
$router->post('/api/downloads/token', AuthMiddleware::requireUser([DownloadController::class, 'createToken']));
$router->get('/api/downloads/file', [DownloadController::class, 'streamByToken']);

// Webhook Hotmart
$router->post('/api/hotmart/webhook', [WebhookController::class, 'handle']);

 

$router->dispatch();
