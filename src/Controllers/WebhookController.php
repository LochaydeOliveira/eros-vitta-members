<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Database;
use App\Http\JsonResponse;
use App\Security\RateLimiter;
use App\Mail\Mailer;
use PDO;

final class WebhookController
{
    public static function handle(array $body): void
    {
        // Rate limiting global por IP para webhook
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $limiter = new RateLimiter('webhook_hotmart:' . $ip, 60, 60);
        if (!$limiter->allow()) {
            JsonResponse::error('Rate limit excedido', 429);
            return;
        }

        // Validação oficial Hotmart via Hottok
        $hottok = Config::hotmartHottok();
        $tokenHeader = $_SERVER['HTTP_X_HOTMART_HOTTOK']
            ?? ($_SERVER['HTTP_HOTTOK'] ?? ''); // fallback se a plataforma enviar somente Hottok
        if ($hottok !== '') {
            if ($tokenHeader === '' || !hash_equals($hottok, $tokenHeader)) {
                JsonResponse::error('Hottok inválido', 401);
                return;
            }
        }

        $pdo = Database::pdo();
        try {
            // Registra o evento ANTES da transação de negócio, para sempre ter trilha
            $headersJson = json_encode(self::collectHeaders());
            $payloadJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmt = $pdo->prepare('INSERT INTO webhook_eventos (evento_tipo, assinatura, payload, headers, criado_em) VALUES (?, ?, ?, ?, NOW())');
            $eventoTipo = (string)($body['event'] ?? $body['event_type'] ?? 'hotmart');
            // Armazena o Hottok recebido para auditoria
            $assinatura = $tokenHeader ?: null;
            $stmt->execute([$eventoTipo, $assinatura, $payloadJson, $headersJson]);
            $webhookId = (int)$pdo->lastInsertId();

            // Daqui em diante, faz a escrita de negócio dentro de transação
            $pdo->beginTransaction();

            // Extrair dados da estrutura real da Hotmart
            $data = $body['data'] ?? $body; // Fallback para estrutura antiga
            
            $email = strtolower(trim((string)($data['buyer']['email'] ?? $body['buyer']['email'] ?? $body['email'] ?? '')));
            $nome = trim((string)($data['buyer']['name'] ?? $body['buyer']['name'] ?? $body['nome'] ?? 'Cliente'));
            $hotmartUserId = (string)($data['buyer']['ucode'] ?? $body['buyer']['ucode'] ?? $body['hotmart_user_id'] ?? null);
            $produtoHotmartId = (string)($data['product']['id'] ?? $body['product']['id'] ?? $body['product_id'] ?? '');
            $transactionId = (string)($data['purchase']['transaction'] ?? $body['purchase']['transaction'] ?? $body['transaction'] ?? null);
            $status = strtolower((string)($data['purchase']['status'] ?? $body['purchase']['status'] ?? $body['status'] ?? 'pendente'));
            $valor = (float)($data['purchase']['price']['value'] ?? $body['purchase']['price'] ?? $body['price'] ?? 0);
            $moeda = (string)($data['purchase']['price']['currency_value'] ?? $body['purchase']['currency'] ?? $body['currency'] ?? 'BRL');
            $dataCompra = self::parseDate((string)($data['purchase']['approved_date'] ?? $body['purchase']['approved_date'] ?? $body['approved_date'] ?? ''));
            $confirmada = in_array($status, ['approved','aprovada','completed','complete'], true);
            $cancelada = in_array($status, ['refunded','chargeback','canceled','cancelled','cancelada','reembolsado'], true);

            // Extrair dados adicionais do payload
            $telefone = (string)($data['buyer']['checkout_phone'] ?? $body['buyer']['checkout_phone'] ?? '');
            $documento = (string)($data['buyer']['document'] ?? $body['buyer']['document'] ?? '');
            $tipoDocumento = (string)($data['buyer']['document_type'] ?? $body['buyer']['document_type'] ?? '');
            $cidade = (string)($data['buyer']['address']['city'] ?? $body['buyer']['address']['city'] ?? '');
            $estado = (string)($data['buyer']['address']['state'] ?? $body['buyer']['address']['state'] ?? '');
            $pais = (string)($data['buyer']['address']['country'] ?? $body['buyer']['address']['country'] ?? '');
            $cep = (string)($data['buyer']['address']['zipcode'] ?? $body['buyer']['address']['zipcode'] ?? '');
            $endereco = (string)($data['buyer']['address']['address'] ?? $body['buyer']['address']['address'] ?? '');
            $numero = (string)($data['buyer']['address']['number'] ?? $body['buyer']['address']['number'] ?? '');
            $complemento = (string)($data['buyer']['address']['complement'] ?? $body['buyer']['address']['complement'] ?? '');

            // Dados de afiliado
            $affiliateCode = '';
            $affiliateName = '';
            if (isset($data['affiliates']) && is_array($data['affiliates']) && count($data['affiliates']) > 0) {
                $affiliate = $data['affiliates'][0];
                $affiliateCode = (string)($affiliate['affiliate_code'] ?? '');
                $affiliateName = (string)($affiliate['name'] ?? '');
            }

            // Dados de pagamento
            $parcelas = (int)($data['purchase']['payment']['installments_number'] ?? $body['purchase']['payment']['installments_number'] ?? 1);
            $tipoPagamento = (string)($data['purchase']['payment']['type'] ?? $body['purchase']['payment']['type'] ?? '');
            $paisCheckout = (string)($data['purchase']['checkout_country']['iso'] ?? $body['purchase']['checkout_country']['iso'] ?? '');

            // Dados de oferta
            $codigoOferta = (string)($data['purchase']['offer']['code'] ?? $body['purchase']['offer']['code'] ?? '');
            $cupomDesconto = (string)($data['purchase']['offer']['coupon_code'] ?? $body['purchase']['offer']['coupon_code'] ?? '');
            $precoOriginal = (float)($data['purchase']['original_offer_price']['value'] ?? $body['purchase']['original_offer_price']['value'] ?? $valor);

            // Dados de assinatura
            $assinaturaAtiva = (bool)($data['subscription']['status'] === 'ACTIVE' ?? false);
            $planoId = (int)($data['subscription']['plan']['id'] ?? 0);
            $planoNome = (string)($data['subscription']['plan']['name'] ?? '');
            $codigoAssinante = (string)($data['subscription']['subscriber']['code'] ?? '');

            // Dados adicionais
            $isOrderBump = (bool)($data['purchase']['order_bump']['is_order_bump'] ?? false);
            $parentTransaction = (string)($data['purchase']['order_bump']['parent_purchase_transaction'] ?? '');
            $businessModel = (string)($data['purchase']['business_model'] ?? '');
            $isFunnel = (bool)($data['purchase']['is_funnel'] ?? false);

            if ($email === '' || $produtoHotmartId === '') {
                throw new \RuntimeException('Payload inválido: falta email ou product_id. Email: "' . $email . '", Product ID: "' . $produtoHotmartId . '"');
            }

            // Upsert usuário (gera senha se novo)
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $userId = (int)$user['id'];
                $pdo->prepare('UPDATE usuarios SET nome = COALESCE(NULLIF(?, ""), nome), hotmart_user_id = COALESCE(?, hotmart_user_id), telefone = COALESCE(NULLIF(?, ""), telefone), documento = COALESCE(NULLIF(?, ""), documento), tipo_documento = COALESCE(NULLIF(?, ""), tipo_documento), cidade = COALESCE(NULLIF(?, ""), cidade), estado = COALESCE(NULLIF(?, ""), estado), pais = COALESCE(NULLIF(?, ""), pais), cep = COALESCE(NULLIF(?, ""), cep), endereco = COALESCE(NULLIF(?, ""), endereco), numero = COALESCE(NULLIF(?, ""), numero), complemento = COALESCE(NULLIF(?, ""), complemento), atualizado_em = NOW() WHERE id = ?')
                    ->execute([$nome, $hotmartUserId ?: null, $telefone, $documento, $tipoDocumento, $cidade, $estado, $pais, $cep, $endereco, $numero, $complemento, $userId]);
            } else {
                $senhaPlain = bin2hex(random_bytes(4)); // 8 hex chars (~4 bytes)
                $senhaHash = password_hash($senhaPlain, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, status, hotmart_user_id, telefone, documento, tipo_documento, cidade, estado, pais, cep, endereco, numero, complemento, criado_em, atualizado_em) VALUES (?, ?, ?, "ativo", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())')
                    ->execute([$nome ?: 'Cliente', $email, $senhaHash, $hotmartUserId ?: null, $telefone, $documento, $tipoDocumento, $cidade, $estado, $pais, $cep, $endereco, $numero, $complemento]);
                $userId = (int)$pdo->lastInsertId();
                // Email de boas-vindas com credenciais
                $loginUrl = rtrim(Config::appUrl(), '/') . '/login';
                $html = '<p>Olá ' . htmlspecialchars($nome ?: 'Cliente') . ',</p>' .
                        '<p>Bem-vindo à área de membros Eros Vitta. Sua compra foi confirmada e sua conta foi criada automaticamente.</p>' .
                        '<p><strong>Login:</strong> ' . htmlspecialchars($email) . '<br><strong>Senha provisória:</strong> ' . htmlspecialchars($senhaPlain) . '</p>' .
                        '<p>Acesse: <a href="' . $loginUrl . '">' . $loginUrl . '</a></p>';
                Mailer::send($email, 'Bem-vindo | Eros Vitta Members', $html);
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

            $statusCompra = $confirmada ? 'aprovada' : ($cancelada ? 'cancelada' : 'pendente');
            $dataConfirmacao = $confirmada ? ($dataCompra ?: date('Y-m-d H:i:s')) : null;
            $dataLiberacao = $confirmada ? date('Y-m-d H:i:s', strtotime(($dataConfirmacao ?? date('Y-m-d H:i:s')) . ' +7 days')) : null;

            if ($compra) {
                $pdo->prepare('UPDATE compras SET usuario_id = ?, produto_id = ?, origem = "hotmart", status = ?, valor_pago = ?, moeda = ?, data_compra = COALESCE(?, data_compra), data_confirmacao = ?, data_liberacao = ?, affiliate_code = ?, affiliate_name = ?, parcelas = ?, tipo_pagamento = ?, pais_checkout = ?, codigo_oferta = ?, cupom_desconto = ?, preco_original = ?, assinatura_ativa = ?, plano_id = ?, plano_nome = ?, codigo_assinante = ?, is_order_bump = ?, parent_transaction = ?, business_model = ?, is_funnel = ?, atualizado_em = NOW() WHERE id = ?')
                    ->execute([$userId, $produtoId, $statusCompra, $valor, $moeda, $dataCompra, $dataConfirmacao, $dataLiberacao, $affiliateCode, $affiliateName, $parcelas, $tipoPagamento, $paisCheckout, $codigoOferta, $cupomDesconto, $precoOriginal, $assinaturaAtiva, $planoId, $planoNome, $codigoAssinante, $isOrderBump, $parentTransaction, $businessModel, $isFunnel, (int)$compra['id']]);
                $compraId = (int)$compra['id'];
            } else {
                $pdo->prepare('INSERT INTO compras (usuario_id, produto_id, origem, status, hotmart_transaction_id, valor_pago, moeda, data_compra, data_confirmacao, data_liberacao, affiliate_code, affiliate_name, parcelas, tipo_pagamento, pais_checkout, codigo_oferta, cupom_desconto, preco_original, assinatura_ativa, plano_id, plano_nome, codigo_assinante, is_order_bump, parent_transaction, business_model, is_funnel, criado_em, atualizado_em) VALUES (?, ?, "hotmart", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())')
                    ->execute([$userId, $produtoId, $statusCompra, $transactionId, $valor, $moeda, $dataCompra, $dataConfirmacao, $dataLiberacao, $affiliateCode, $affiliateName, $parcelas, $tipoPagamento, $paisCheckout, $codigoOferta, $cupomDesconto, $precoOriginal, $assinaturaAtiva, $planoId, $planoNome, $codigoAssinante, $isOrderBump, $parentTransaction, $businessModel, $isFunnel]);
                $compraId = (int)$pdo->lastInsertId();
            }

            // Upsert acesso
            if ($confirmada) {
                $stmt = $pdo->prepare('SELECT id FROM acessos WHERE usuario_id = ? AND produto_id = ? LIMIT 1');
                $stmt->execute([$userId, $produtoId]);
                $ac = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ac) {
                    $pdo->prepare('UPDATE acessos SET status = "ativo", data_liberacao = ?, compra_id = ?, data_bloqueio = NULL, motivo_bloqueio = NULL, atualizado_em = NOW() WHERE id = ?')
                        ->execute([$dataLiberacao, $compraId, (int)$ac['id']]);
                } else {
                    $pdo->prepare('INSERT INTO acessos (usuario_id, produto_id, compra_id, origem, status, data_liberacao, criado_em, atualizado_em) VALUES (?, ?, ?, "hotmart", "ativo", ?, NOW(), NOW())')
                        ->execute([$userId, $produtoId, $compraId, $dataLiberacao]);
                }
            } elseif ($cancelada) {
                // Bloqueia acesso imediatamente em caso de reembolso/cancelamento
                $stmt = $pdo->prepare('SELECT id FROM acessos WHERE usuario_id = ? AND produto_id = ? ORDER BY criado_em DESC LIMIT 1');
                $stmt->execute([$userId, $produtoId]);
                $ac = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ac) {
                    $pdo->prepare('UPDATE acessos SET status = "bloqueado", data_bloqueio = NOW(), motivo_bloqueio = "webhook_reembolso", atualizado_em = NOW() WHERE id = ?')
                        ->execute([(int)$ac['id']]);
                } else {
                    // Cria acesso bloqueado se não existir
                    $pdo->prepare('INSERT INTO acessos (usuario_id, produto_id, compra_id, origem, status, data_bloqueio, motivo_bloqueio, criado_em, atualizado_em) VALUES (?, ?, ?, "hotmart", "bloqueado", NOW(), "webhook_reembolso", NOW(), NOW())')
                        ->execute([$userId, $produtoId, $compraId]);
                }
            }

            // Marca processamento OK
            $pdo->prepare('UPDATE webhook_eventos SET processado_em = NOW(), resultado_status = "sucesso" WHERE id = ?')->execute([$webhookId]);
            $pdo->commit();
            JsonResponse::ok(['processed' => true]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Atualiza o evento registrado no início com a falha
            if (isset($webhookId) && $webhookId > 0) {
                $pdo->prepare('UPDATE webhook_eventos SET processado_em = NOW(), resultado_status = "falha", erro_mensagem = ? WHERE id = ?')
                    ->execute([$e->getMessage(), (int)$webhookId]);
            }
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

    private static function parseDate($str): ?string
    {
        if ($str === '' || $str === null) return null;
        
        // Se for timestamp (número), converter diretamente
        if (is_numeric($str)) {
            $ts = (int)$str;
            // Se for timestamp em milissegundos, converter para segundos
            if ($ts > 1000000000000) {
                $ts = $ts / 1000;
            }
            return date('Y-m-d H:i:s', $ts);
        }
        
        // Se for string, tentar parsear
        $ts = strtotime($str);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
