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
$router->addRoute('ebook/(\d+)', 'ebook');
$router->addRoute('video/(\d+)', 'video');
$router->addRoute('audio/(\d+)', 'audio');
$router->addRoute('download/(\d+)', 'download');

// Processa a requisição
$url = $_GET['url'] ?? '';
$router->dispatch($url);
?>
