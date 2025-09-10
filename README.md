# Eros Vitta Members - Backend PHP (HostGator)

Banco de dados: `paymen58_eros_vitta_members`

## Requisitos
- PHP 7.4+ (ou 8+), Apache com mod_rewrite
- MySQL/MariaDB

## Estrutura
- `public/` (document root)
- `src/` (código-fonte)

## Configuração
Defina variáveis de ambiente no cPanel (ou edite `src/Config.php`):
- `DB_HOST`, `DB_PORT`, `DB_NAME` (padrão: `paymen58_eros_vitta_members`)
- `DB_USER`, `DB_PASS`
- `APP_KEY` (chave JWT HS256)
- `APP_URL`
- `HOTMART_HOTTOK` (token de verificação enviado no header `X-HOTMART-HOTTOK`)

### SMTP (Zoho Mail)
- `SMTP_HOST` = `smtp.zoho.com`
- `SMTP_PORT` = `465` (SSL) ou `587` (TLS)
- `SMTP_SECURE` = `ssl` ou `tls`
- `SMTP_USER` = `contato@erosvitta.com.br`
- `SMTP_PASS` = `SUA_SENHA_ZOHO`
- `SMTP_FROM` = `contato@erosvitta.com.br`
- `SMTP_FROM_NAME` = `Eros Vitta`

Observações Zoho:
- Garanta SPF/DKIM configurados no DNS do domínio para melhor entregabilidade.
- Se usar porta 587, `SMTP_SECURE=tls`.

## Instalação
1. Crie o banco e rode os scripts SQL do schema e das views/snapshots enviados previamente.
2. Faça upload do conteúdo para o HostGator.
3. Aponte o Document Root do domínio/subdomínio para a pasta `public/`.
4. Garanta que os arquivos de mídia (PDF/áudio) fiquem fora de `public/` e configure os `storage_path_*` nas tabelas de `produtos`.

## Rotas
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/password/forgot`
- `POST /api/auth/password/reset`
- `GET /api/auth/me` (Bearer token)
- `POST /api/admin/login`
- `GET /api/products` (Bearer token)
- `GET /api/accesses` (Bearer token)
- `POST /api/downloads/token` (Bearer token)
- `GET /api/downloads/file?token=...`
- `POST /api/hotmart/webhook`

### Webhook Hotmart
- Validação: header `X-HOTMART-HOTTOK` deve igualar `HOTMART_HOTTOK` do ambiente.
- Fluxo quando `purchase.status` = approved:
  - Cria/atualiza usuário (gera senha provisória se novo) e envia e-mail de boas-vindas com login e senha
  - Registra/atualiza compra (`hotmart_transaction_id`, valores, datas)
  - Define `data_liberacao = data_confirmacao + 7 dias`
  - Cria/atualiza acesso do usuário ao produto

Exemplo de teste (substitua `HOTMART_HOTTOK`):
```bash
BODY='{"event":"approved","buyer":{"email":"usuario@exemplo.com","name":"Cliente","ucode":"U123"},"product":{"id":"SEU_PRODUCT_ID"},"purchase":{"transaction":"TX1","status":"approved","price":99.90,"currency":"BRL","approved_date":"2025-09-10T12:00:00-03:00"}}'
curl -sS -X POST 'https://SEU_DOMINIO/api/hotmart/webhook' \
  -H "Content-Type: application/json" \
  -H "X-HOTMART-HOTTOK: HOTMART_HOTTOK_AQUI" \
  -d "$BODY"
```

## Observações
- Token JWT: envie em `Authorization: Bearer <token>`.
- Download só libera quando `NOW() >= data_liberacao` do acesso.
- Webhook: registra evento, cria/atualiza usuário, compra e acesso; liberação = confirmação + 7 dias.
- Agende snapshots diários conforme script (EVENT ou CRON via PHP).
 - Armazenamento protegido: defina `storage_path_pdf`/`storage_path_audio` fora de `public/`; PDFs abrem inline; áudios baixam como attachment.
