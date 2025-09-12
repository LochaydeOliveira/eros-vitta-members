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

    /**
     * Métricas avançadas de faturamento
     */
    public static function metrics(array $_body = [], array $_request = []): void
    {
        $pdo = Database::pdo();
        $period = $_request['period'] ?? '30d'; // 7d, 30d, 90d, 1y
        
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };
        
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        
        try {
            // Faturamento por período
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(data_confirmacao) as data,
                    COUNT(*) as vendas,
                    SUM(valor_pago) as faturamento
                FROM compras 
                WHERE status = 'aprovada' 
                AND data_confirmacao >= ?
                GROUP BY DATE(data_confirmacao)
                ORDER BY data DESC
            ");
            $stmt->execute([$dateFrom]);
            $faturamento = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Top produtos
            $stmt = $pdo->prepare("
                SELECT 
                    p.titulo,
                    p.tipo,
                    COUNT(c.id) as vendas,
                    SUM(c.valor_pago) as faturamento
                FROM compras c
                JOIN produtos p ON p.id = c.produto_id
                WHERE c.status = 'aprovada' 
                AND c.data_confirmacao >= ?
                GROUP BY p.id, p.titulo, p.tipo
                ORDER BY vendas DESC
                LIMIT 10
            ");
            $stmt->execute([$dateFrom]);
            $topProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Resumo do período
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_vendas,
                    SUM(valor_pago) as total_faturamento,
                    AVG(valor_pago) as ticket_medio
                FROM compras 
                WHERE status = 'aprovada' 
                AND data_confirmacao >= ?
            ");
            $stmt->execute([$dateFrom]);
            $resumo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            JsonResponse::ok([
                'period' => $period,
                'date_from' => $dateFrom,
                'faturamento' => $faturamento,
                'top_produtos' => $topProdutos,
                'resumo' => $resumo
            ]);
            
        } catch (\Throwable $e) {
            JsonResponse::error('Erro ao carregar métricas: ' . $e->getMessage(), 500);
        }
    }
}


