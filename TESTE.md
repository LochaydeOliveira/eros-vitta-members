# 🧪 Guia de Testes - ErosVitta

## 📋 Pré-requisitos para Teste

### 1. Configuração do Banco de Dados
- ✅ Banco `paymen58_eros_vitta` criado
- ✅ Tabelas criadas (execute o `database.sql`)
- ✅ Configure as credenciais em `app/config.php`

### 2. Configuração do Servidor
- ✅ Domínio apontando para pasta `public/`
- ✅ Arquivos enviados para o servidor
- ✅ Permissões corretas nas pastas

## 🔧 Configuração Inicial

### 1. Editar Configurações
Abra `app/config.php` e configure:

```php
// Banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'paymen58_eros_vitta');
define('DB_USER', 'seu_usuario_mysql_hostgator');
define('DB_PASS', 'sua_senha_mysql_hostgator');

// Email SMTP
define('SMTP_HOST', 'smtp.hostgator.com.br');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contato@erosvitta.com.br');
define('SMTP_PASS', 'sua_senha_email');

// URLs
define('BASE_URL', 'https://erosvitta.com.br');
```

### 2. Adicionar Logo
- Coloque o arquivo `logo.png` em `public/assets/images/`
- Dimensões recomendadas: 150x60 pixels

## 🧪 Testes da Aplicação

### Teste 1: Acesso à Página de Login
**URL:** `https://erosvitta.com.br/login`

**O que verificar:**
- [ ] Página carrega sem erros
- [ ] Formulário de login aparece
- [ ] Design responsivo funciona
- [ ] Logo aparece (se adicionado)

### Teste 2: Login com Usuário de Teste
**Credenciais de teste:**
- **Email:** `teste@exemplo.com`
- **Senha:** `password` (senha padrão do hash no banco)

**O que verificar:**
- [ ] Login funciona com credenciais corretas
- [ ] Redirecionamento para dashboard
- [ ] Mensagem de erro com credenciais incorretas

### Teste 3: Dashboard
**URL:** `https://erosvitta.com.br/dashboard`

**O que verificar:**
- [ ] Header com nome do usuário
- [ ] Sidebar com materiais disponíveis
- [ ] Cards dos materiais aparecem
- [ ] Botão "Sair" funciona
- [ ] Design responsivo

### Teste 4: Visualização de Materiais

#### Ebook
**URL:** `https://erosvitta.com.br/ebook/1`

**O que verificar:**
- [ ] Ebook carrega em HTML
- [ ] Conteúdo do exemplo aparece
- [ ] Botão de download (após 7 dias)
- [ ] Breadcrumb funciona

#### Vídeo
**URL:** `https://erosvitta.com.br/video/2`

**O que verificar:**
- [ ] Player de vídeo aparece
- [ ] Controles funcionam
- [ ] Botão de download

#### Áudio
**URL:** `https://erosvitta.com.br/audio/3`

**O que verificar:**
- [ ] Player de áudio aparece
- [ ] Controles funcionam
- [ ] Botão de download

### Teste 5: Sistema de Download
**URL:** `https://erosvitta.com.br/download/1`

**O que verificar:**
- [ ] Download funciona (após 7 dias)
- [ ] Arquivo correto é baixado
- [ ] Nome do arquivo está correto

### Teste 6: Webhook da Hotmart
**URL:** `https://erosvitta.com.br/app/hotmartWebhook.php`

**Método:** POST

**Dados de teste:**
```json
{
  "event": "PURCHASE_APPROVED",
  "data": {
    "buyer": {
      "email": "cliente.teste@exemplo.com",
      "name": "Cliente Teste"
    },
    "product": {
      "id": "12345"
    }
  }
}
```

**O que verificar:**
- [ ] Webhook responde com status 200
- [ ] Usuário é criado no banco
- [ ] Materiais são liberados
- [ ] Email é enviado (verificar logs)

### Teste 7: URLs Amigáveis
**URLs para testar:**
- [ ] `https://erosvitta.com.br/` → Dashboard
- [ ] `https://erosvitta.com.br/login` → Login
- [ ] `https://erosvitta.com.br/logout` → Logout
- [ ] `https://erosvitta.com.br/ebook/1` → Ebook
- [ ] `https://erosvitta.com.br/pagina-inexistente` → 404

### Teste 8: Segurança
**O que verificar:**
- [ ] `https://erosvitta.com.br/storage/` → Acesso negado
- [ ] `https://erosvitta.com.br/dashboard` sem login → Redireciona para login
- [ ] Sessão expira após 1 hora
- [ ] Arquivos são servidos via PHP (não acesso direto)

## 🐛 Solução de Problemas Comuns

### Erro de Conexão com Banco
```
Erro na conexão com o banco: SQLSTATE[HY000] [1045] Access denied
```
**Solução:** Verificar credenciais em `app/config.php`

### Página em Branco
**Possíveis causas:**
- Erro de sintaxe PHP
- Problema de permissões
- Erro de configuração

**Solução:** Ativar exibição de erros no PHP

### URLs não Funcionam
**Possíveis causas:**
- `.htaccess` não está funcionando
- Mod_rewrite não ativado
- Configuração incorreta do servidor

**Solução:** Verificar configuração do Apache

### Email não Envia
**Possíveis causas:**
- Credenciais SMTP incorretas
- Porta bloqueada
- Configuração de email

**Solução:** Verificar logs de erro e configurações SMTP

## 📊 Checklist de Testes

- [ ] **Configuração inicial completa**
- [ ] **Banco de dados funcionando**
- [ ] **Login/logout funcionando**
- [ ] **Dashboard carregando**
- [ ] **Materiais visualizando**
- [ ] **Downloads funcionando**
- [ ] **Webhook respondendo**
- [ ] **URLs amigáveis funcionando**
- [ ] **Segurança implementada**
- [ ] **Design responsivo**

## 🚀 Próximos Passos Após Testes

1. **Adicionar materiais reais** nas pastas `storage/`
2. **Configurar webhook** no painel da Hotmart
3. **Personalizar design** se necessário
4. **Configurar backup** do banco de dados
5. **Monitorar logs** de erro

---

**Dica:** Use as ferramentas de desenvolvedor do navegador (F12) para verificar erros de JavaScript e requisições de rede.
