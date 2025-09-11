<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class AdminDashboardController
{
    /**
     * Sumário simples para o dashboard admin
     */
    public static function summary(array $_body = [], array $_request = []): void
    {
        $pdo = Database::pdo();

        $counts = [
            'usuarios_total' => 0,
            'usuarios_ativos' => 0,
            'produtos_total' => 0,
            'produtos_ativos' => 0,
            'acessos_ativos' => 0,
            'vendas_confirmadas' => 0,
        ];

        // Usuários
        $counts['usuarios_total'] = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $counts['usuarios_ativos'] = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE status = 'ativo'")->fetchColumn();

        // Produtos
        $counts['produtos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
        $counts['produtos_ativos'] = (int)$pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();

        // Acessos ativos
        $counts['acessos_ativos'] = (int)$pdo->query("SELECT COUNT(*) FROM acessos WHERE status = 'ativo'")->fetchColumn();

        // Vendas confirmadas (compras aprovadas)
        try {
            $counts['vendas_confirmadas'] = (int)$pdo->query("SELECT COUNT(*) FROM compras WHERE status = 'aprovada'")->fetchColumn();
        } catch (\Throwable $_) {
            // tabela pode não existir em alguns ambientes do cliente
            $counts['vendas_confirmadas'] = 0;
        }

        JsonResponse::ok(['summary' => $counts]);
    }
}


