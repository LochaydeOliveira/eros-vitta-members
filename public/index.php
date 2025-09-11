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
use App\Controllers\AdminAccessController;
use App\Controllers\ViewController;
use App\Controllers\AdminViewController;
use App\Controllers\AdminCronController;
use App\Controllers\AdminDashboardController;
 

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
$router->post('/api/auth/password/change', AuthMiddleware::requireUser([AuthController::class, 'changePassword']));

// Auth admin
$router->post('/api/admin/login', [AdminAuthController::class, 'login']);

// Admin - produtos
$router->get('/api/admin/products', AdminMiddleware::requireAdmin([AdminProductController::class, 'list']));
$router->post('/api/admin/products', AdminMiddleware::requireAdmin([AdminProductController::class, 'create']));
$router->put('/api/admin/products', AdminMiddleware::requireAdmin([AdminProductController::class, 'update'])) ;
$router->post('/api/admin/products/update', AdminMiddleware::requireAdmin([AdminProductController::class, 'update'])) ;
$router->post('/api/admin/products/deactivate', AdminMiddleware::requireAdmin([AdminProductController::class, 'deactivate'])) ;
$router->post('/api/admin/products/upload-cover', AdminMiddleware::requireAdmin([AdminProductController::class, 'uploadCover']));

// Admin - usuários
$router->get('/api/admin/users', AdminMiddleware::requireAdmin([AdminUserController::class, 'list']));
$router->post('/api/admin/users/block', AdminMiddleware::requireAdmin([AdminUserController::class, 'block']));
$router->post('/api/admin/users/unblock', AdminMiddleware::requireAdmin([AdminUserController::class, 'unblock']));
$router->post('/api/admin/users/reset-password', AdminMiddleware::requireAdmin([AdminUserController::class, 'resetPassword']));

// Admin - CRON (teste)
$router->post('/api/admin/cron/run-d7', AdminMiddleware::requireAdmin([AdminCronController::class, 'runD7']));

// Admin - dashboard
$router->get('/api/admin/dashboard/summary', AdminMiddleware::requireAdmin([AdminDashboardController::class, 'summary']));

// Admin - acessos
$router->post('/api/admin/accesses/assign', AdminMiddleware::requireAdmin([AdminAccessController::class, 'assign']));
// Admin - acessos (listar e bloquear)
$router->get('/api/admin/accesses/by-user', AdminMiddleware::requireAdmin([AdminAccessController::class, 'listByUser']));
$router->post('/api/admin/accesses/block', AdminMiddleware::requireAdmin([AdminAccessController::class, 'block']));
$router->post('/api/admin/accesses/update-status', AdminMiddleware::requireAdmin([AdminAccessController::class, 'updateStatus']));

// Produtos e acessos (usuário autenticado)
$router->get('/api/products', AuthMiddleware::requireUser([ProductController::class, 'list']));
$router->get('/api/accesses', AuthMiddleware::requireUser([AccessController::class, 'list']));
// Visualização (view-only)
$router->get('/api/view/pdf', AuthMiddleware::requireUser([ViewController::class, 'pdfPage']));
$router->get('/api/view/audio', AuthMiddleware::requireUser([ViewController::class, 'audio']));
$router->get('/api/view/pdf-file', AuthMiddleware::requireUser([ViewController::class, 'pdfFile']));
$router->get('/api/view/audio/playlist', AuthMiddleware::requireUser([ViewController::class, 'playlist']));
$router->get('/api/view/audio/track', AuthMiddleware::requireUser([ViewController::class, 'audioTrack']));

// Admin - pré-visualização (sem vínculo ao usuário, mas protegido por admin)
$router->get('/api/admin/view/pdf-file', AdminMiddleware::requireAdmin([AdminViewController::class, 'pdfFile']));
$router->get('/api/admin/view/audio/playlist', AdminMiddleware::requireAdmin([AdminViewController::class, 'playlist']));
$router->get('/api/admin/view/audio/track', AdminMiddleware::requireAdmin([AdminViewController::class, 'audioTrack']));
$router->get('/api/admin/view/audio/file', AdminMiddleware::requireAdmin([AdminViewController::class, 'audioFile']));

// Downloads protegidos
$router->post('/api/downloads/token', AuthMiddleware::requireUser([DownloadController::class, 'createToken']));
$router->get('/api/downloads/file', [DownloadController::class, 'streamByToken']);

// Webhook Hotmart
$router->post('/api/hotmart/webhook', [WebhookController::class, 'handle']);

 

$router->dispatch();
