<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use PDO;

final class WebhookController
{
    public static function handle(array $body): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $headersJson = json_encode(self::collectHeaders());
            $payloadJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmt = $pdo->prepare('INSERT INTO webhook_eventos (evento_tipo, assinatura, payload, headers, criado_em) VALUES (?, ?, ?, ?, NOW())');
            $eventoTipo = (string)($body['event'] ?? $body['event_type'] ?? 'hotmart');
            $assinatura = $_SERVER['HTTP_X_HOTMART_SIGNATURE'] ?? null;
            $stmt->execute([$eventoTipo, $assinatura, $payloadJson, $headersJson]);
            $webhookId = (int)$pdo->lastInsertId();

            // Validação de assinatura - implementar HMAC com segredo no futuro
            // if (!self::isValidSignature($payloadRaw, $assinatura)) { ... }

            $email = strtolower(trim((string)($body['buyer']['email'] ?? $body['email'] ?? '')));
            $nome = trim((string)($body['buyer']['name'] ?? $body['nome'] ?? 'Cliente'));
            $hotmartUserId = (string)($body['buyer']['ucode'] ?? $body['hotmart_user_id'] ?? null);
            $produtoHotmartId = (string)($body['product']['id'] ?? $body['product_id'] ?? '');
            $transactionId = (string)($body['purchase']['transaction'] ?? $body['transaction'] ?? null);
            $status = strtolower((string)($body['purchase']['status'] ?? $body['status'] ?? 'pendente'));
            $valor = (float)($body['purchase']['price'] ?? $body['price'] ?? 0);
            $moeda = (string)($body['purchase']['currency'] ?? $body['currency'] ?? 'BRL');
            $dataCompra = self::parseDate((string)($body['purchase']['approved_date'] ?? $body['approved_date'] ?? ''));
            $confirmada = in_array($status, ['approved','aprovada','completed','complete'], true);

            if ($email === '' || $produtoHotmartId === '') {
                throw new \RuntimeException('Payload inválido: falta email ou product_id');
            }

            // Upsert usuário
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $userId = (int)$user['id'];
                $pdo->prepare('UPDATE usuarios SET nome = COALESCE(NULLIF(?, ""), nome), hotmart_user_id = COALESCE(?, hotmart_user_id), atualizado_em = NOW() WHERE id = ?')
                    ->execute([$nome, $hotmartUserId ?: null, $userId]);
            } else {
                $pdo->prepare('INSERT INTO usuarios (nome, email, status, hotmart_user_id, criado_em, atualizado_em) VALUES (?, ?, "ativo", ?, NOW(), NOW())')
                    ->execute([$nome ?: 'Cliente', $email, $hotmartUserId ?: null]);
                $userId = (int)$pdo->lastInsertId();
            }

            // Buscar produto por hotmart_product_id
            $stmt = $pdo->prepare('SELECT id FROM produtos WHERE hotmart_product_id = ? LIMIT 1');
            $stmt->execute([$produtoHotmartId]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$prod) {
                throw new \RuntimeException('Produto não cadastrado para hotmart_product_id=' . $produtoHotmartId);
            }
            $produtoId = (int)$prod['id'];

            // Upsert compra por transactionId quando houver
            if ($transactionId) {
                $stmt = $pdo->prepare('SELECT id FROM compras WHERE hotmart_transaction_id = ? LIMIT 1');
                $stmt->execute([$transactionId]);
                $compra = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $compra = false;
            }

            $statusCompra = $confirmada ? 'aprovada' : ($status === 'cancelled' || $status === 'cancelada' ? 'cancelada' : 'pendente');
            $dataConfirmacao = $confirmada ? ($dataCompra ?: date('Y-m-d H:i:s')) : null;
            $dataLiberacao = $confirmada ? date('Y-m-d H:i:s', strtotime(($dataConfirmacao ?? date('Y-m-d H:i:s')) . ' +7 days')) : null;

            if ($compra) {
                $pdo->prepare('UPDATE compras SET usuario_id = ?, produto_id = ?, origem = "hotmart", status = ?, valor_pago = ?, moeda = ?, data_compra = COALESCE(?, data_compra), data_confirmacao = ?, data_liberacao = ?, atualizado_em = NOW() WHERE id = ?')
                    ->execute([$userId, $produtoId, $statusCompra, $valor, $moeda, $dataCompra, $dataConfirmacao, $dataLiberacao, (int)$compra['id']]);
                $compraId = (int)$compra['id'];
            } else {
                $pdo->prepare('INSERT INTO compras (usuario_id, produto_id, origem, status, hotmart_transaction_id, valor_pago, moeda, data_compra, data_confirmacao, data_liberacao, criado_em, atualizado_em) VALUES (?, ?, "hotmart", ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())')
                    ->execute([$userId, $produtoId, $statusCompra, $transactionId, $valor, $moeda, $dataCompra, $dataConfirmacao, $dataLiberacao]);
                $compraId = (int)$pdo->lastInsertId();
            }

            // Upsert acesso
            if ($confirmada) {
                $stmt = $pdo->prepare('SELECT id FROM acessos WHERE usuario_id = ? AND produto_id = ? LIMIT 1');
                $stmt->execute([$userId, $produtoId]);
                $ac = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ac) {
                    $pdo->prepare('UPDATE acessos SET status = "ativo", data_liberacao = ?, compra_id = ?, atualizado_em = NOW() WHERE id = ?')
                        ->execute([$dataLiberacao, $compraId, (int)$ac['id']]);
                } else {
                    $pdo->prepare('INSERT INTO acessos (usuario_id, produto_id, compra_id, origem, status, data_liberacao, criado_em, atualizado_em) VALUES (?, ?, ?, "hotmart", "ativo", ?, NOW(), NOW())')
                        ->execute([$userId, $produtoId, $compraId, $dataLiberacao]);
                }
            }

            // Marca processamento OK
            $pdo->prepare('UPDATE webhook_eventos SET processado_em = NOW(), resultado_status = "sucesso" WHERE id = ?')->execute([$webhookId]);
            $pdo->commit();
            JsonResponse::ok(['processed' => true]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $pdo->prepare('UPDATE webhook_eventos SET processado_em = NOW(), resultado_status = "falha", erro_mensagem = ? WHERE id = (SELECT MAX(id) FROM webhook_eventos)')
                ->execute([$e->getMessage()]);
            JsonResponse::error('Falha ao processar webhook', 400, ['detail' => $e->getMessage()]);
        }
    }

    /**
     * @return array<string,string>
     */
    private static function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string)$value;
            }
        }
        return $headers;
    }

    private static function parseDate(string $str): ?string
    {
        if ($str === '') return null;
        $ts = strtotime($str);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
