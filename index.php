<?php
session_start();
require_once 'app/config.php';
require_once 'app/routes.php';

// Inicializa o roteador
$router = new Router();

// Define as rotas
$router->addRoute('', 'dashboard');
$router->addRoute('login', 'login');
$router->addRoute('logout', 'logout');
$router->addRoute('dashboard', 'dashboard');
$router->addRoute('libido-renovada', 'landing');
$router->addRoute('upsell', 'upsell');
$router->addRoute('libido-renovada-up', 'upsell');
$router->addRoute('downsell', 'downsell');
$router->addRoute('libido-renovada-down', 'downsell');
$router->addRoute('obrigado', 'obrigado');
$router->addRoute('libido-renovada-obrigado', 'obrigado');
$router->addRoute('debug-dashboard', 'debug-dashboard');
$router->addRoute('teste-dashboard', 'teste-dashboard');
$router->addRoute('dashboard-funcionando', 'dashboard-funcionando');
$router->addRoute('dashboard-simples', 'dashboard-simples');
$router->addRoute('verificar-arquivos', 'verificar-arquivos');
$router->addRoute('debug-dashboard-completo', 'debug-dashboard-completo');
$router->addRoute('ebook/(\d+)', 'ebook');
$router->addRoute('video/(\d+)', 'video');
$router->addRoute('audio/(\d+)', 'audio');
$router->addRoute('download/(\d+)', 'download');

// Processa a requisição
$url = $_GET['url'] ?? '';
$router->dispatch($url);
?>
