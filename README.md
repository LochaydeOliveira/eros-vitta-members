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

## Observações
- Token JWT: envie em `Authorization: Bearer <token>`.
- Download só libera quando `NOW() >= data_liberacao` do acesso.
- Webhook: registra evento, cria/atualiza usuário, compra e acesso; liberação = confirmação + 7 dias.
- Agende snapshots diários conforme script (EVENT ou CRON via PHP).
