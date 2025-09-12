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
            $assinaturaAtiva = (bool)(($data['subscription']['status'] ?? '') === 'ACTIVE');
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
                $loginUrl = rtrim(Config::appUrl(), '/') . '/members';
                $html = self::getWelcomeEmailTemplate($nome ?: 'Cliente', $email, $senhaPlain, $loginUrl);
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

            // Upsert acesso - SEMPRE libera para compras aprovadas
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

    private static function getWelcomeEmailTemplate(string $nome, string $email, string $senha, string $loginUrl): string
    {
        return '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bem-vindo ao Eros Vitta</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: #000; color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: bold; }
                .header p { margin: 10px 0 0 0; font-size: 16px; opacity: 0.9; }
                .content { padding: 40px 30px; }
                .welcome-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0; }
                .credentials { background: #fff; border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .credential-item { margin: 10px 0; }
                .credential-label { font-weight: bold; color: #495057; }
                .credential-value { color:rgb(255, 255, 255); font-family: monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; display: inline-block; }
                .cta-button { display: inline-block; background: #000; color: white; text-decoration: none; padding: 15px 30px; border-radius: 25px; font-weight: bold; font-size: 16px; margin: 20px 0; transition: transform 0.2s; }
                .cta-button:hover { transform: translateY(-2px); }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                .security-note { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0; }
                .icon { font-size: 24px; margin-right: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Bem-vindo ao Eros Vitta!</h1>
                    <p>Sua jornada para uma vida íntima plena começa agora</p>
                </div>
                
                <div class="content">
                    <div class="welcome-box">
                        <h2>Olá, ' . htmlspecialchars($nome) . '!</h2>
                        <p>Sua compra foi confirmada com sucesso e sua conta na área de membros foi criada automaticamente. Lá dentro você tem acesso completo a todo o conteúdo que você comprou do Eros Vitta.</p>
                    </div>

                    <div class="credentials">
                        <h3>🔐 Suas Credenciais de Acesso</h3>
                        <div class="credential-item">
                            <span class="credential-label">📧 Email:</span>
                            <span class="credential-value">' . htmlspecialchars($email) . '</span>
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">🔑 Senha Provisória:</span>
                            <span class="credential-value">' . htmlspecialchars($senha) . '</span>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <a href="' . htmlspecialchars($loginUrl) . '" class="cta-button">
                            🚀 Acessar Área de Membros
                        </a>
                    </div>

                    <div class="security-note">
                        <strong>🔒 Importante:</strong> Esta é uma senha provisória. Recomendamos que você altere sua senha após o primeiro login por questões de segurança.
                    </div>

                    <h3>✨ O que você encontrará na área de membros:</h3>
                    <ul>
                        <li>📚 E-books exclusivos sobre relacionamentos íntimos</li>
                        <li>🎧 Áudios guiados para exercícios práticos</li>
                        <li>💡 Dicas e técnicas comprovadas</li>
                        <li>🎯 Conteúdo atualizado regularmente</li>
                        <li>🔍 Obs: Somente os produtos que você comprou estarão LIBERADOS na área de membros.</li>
                    </ul>

                    <p>Se você tiver alguma dúvida ou precisar de suporte, nossa equipe está sempre disponível para ajudar.</p>
                </div>

                <div class="footer">
                    <p><strong>Eros Vitta</strong> - Transformando relacionamentos, uma conexão por vez</p>
                    <p>Este é um email automático, por favor não responda diretamente.</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
