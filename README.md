# 🚀 ErosVitta - Área de Membros

Sistema de área de membros desenvolvido em PHP puro para a ErosVitta, integrado com a Hotmart e hospedado na HostGator.

## 📋 Funcionalidades

- ✅ **Sistema de Login/Autenticação** com sessões seguras
- ✅ **Webhook da Hotmart** para liberação automática de materiais
- ✅ **Envio de emails** automático com template HTML profissional
- ✅ **Dashboard responsivo** com listagem de materiais
- ✅ **Visualizadores** para ebooks (HTML), vídeos e áudios
- ✅ **Controle de entrega** - PDF liberado após 7 dias
- ✅ **URLs amigáveis** com sistema de roteamento
- ✅ **Segurança** - arquivos protegidos, pasta storage privada
- ✅ **Design moderno** e responsivo

## 🗂️ Estrutura do Projeto

```
/eros-vitta
│── public/                    # Pasta pública (apontar domínio aqui)
│   │── index.php              # Roteador principal
│   │── .htaccess              # Regras de URL amigável
│   │── serve-file.php         # Servir arquivos protegidos
│   │── assets/                # CSS, JS, imagens
│   │   └── css/
│   │       └── style.css      # Estilos principais
│
│── app/                       # Lógica da aplicação
│   │── config.php             # Configurações gerais
│   │── db.php                 # Conexão com MySQL
│   │── auth.php               # Sistema de autenticação
│   │── routes.php             # Roteamento de URLs
│   │── hotmartWebhook.php     # Endpoint webhook Hotmart
│   │── mailer.php             # Envio de emails
│
│── views/                     # Templates das páginas
│   │── header.php             # Cabeçalho comum
│   │── sidebar.php            # Menu lateral
│   │── footer.php             # Rodapé comum
│   │── login.php              # Página de login
│   │── dashboard.php          # Dashboard principal
│   │── ebook.php              # Visualizador de ebooks
│   │── video.php              # Visualizador de vídeos
│   │── audio.php              # Visualizador de áudios
│   │── 404.php                # Página de erro 404
│   │── email_template.html    # Template de email
│
│── storage/                   # Arquivos de mídia (PRIVADO)
│   │── ebooks/                # Ebooks em HTML
│   │── videos/                # Arquivos de vídeo
│   │── audios/                # Arquivos de áudio
│
│── database.sql               # Script de criação do banco
│── config-example.php         # Exemplo de configuração
│── README.md                  # Este arquivo
```

## ⚙️ Instalação

### 1. Configuração do Banco de Dados

1. Acesse o cPanel da HostGator
2. Abra o phpMyAdmin
3. Execute o script `database.sql` para criar o banco e tabelas
4. Anote as credenciais do banco de dados

### 2. Configuração da Aplicação

1. Copie `config-example.php` para `app/config.php`
2. Edite `app/config.php` com suas configurações:
   - Dados do banco MySQL
   - Configurações de email SMTP
   - URLs do seu domínio

### 3. Configuração do Domínio

1. No cPanel, configure o domínio `erosvitta.com.br` para apontar para a pasta `public/`
2. Certifique-se de que o `.htaccess` está funcionando

### 4. Upload dos Arquivos

1. Faça upload de todos os arquivos para o servidor
2. Crie a pasta `storage/` e subpastas (`ebooks/`, `videos/`, `audios/`)
3. Configure permissões adequadas (755 para pastas, 644 para arquivos)

### 5. Configuração do Webhook da Hotmart

1. No painel da Hotmart, configure o webhook para:
   - URL: `https://erosvitta.com.br/app/hotmartWebhook.php`
   - Evento: `PURCHASE_APPROVED`

## 🔧 Configurações Importantes

### Banco de Dados
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'erosvitta_db');
define('DB_USER', 'seu_usuario_mysql');
define('DB_PASS', 'sua_senha_mysql');
```

### Email SMTP (HostGator)
```php
define('SMTP_HOST', 'smtp.hostgator.com.br');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contato@erosvitta.com.br');
define('SMTP_PASS', 'sua_senha_email');
```

### URLs
```php
define('BASE_URL', 'https://erosvitta.com.br');
```

## 📧 Sistema de Emails

O sistema envia automaticamente emails de boas-vindas quando:
- Um cliente faz uma compra aprovada na Hotmart
- O webhook recebe o evento `PURCHASE_APPROVED`
- Um novo usuário é criado no sistema

O template de email está em `views/email_template.html` e pode ser personalizado.

## 🔒 Segurança

- **Pasta storage privada**: Arquivos não são acessíveis diretamente
- **Autenticação obrigatória**: Todos os materiais requerem login
- **Sessões seguras**: Timeout automático de 1 hora
- **Validação de dados**: Todas as entradas são validadas
- **Proteção contra SQL injection**: Uso de prepared statements

## 🎨 Personalização

### Logo e Imagens
- Substitua `public/assets/images/logo.png` pelo seu logo
- Ajuste as cores no arquivo `public/assets/css/style.css`

### Template de Email
- Edite `views/email_template.html` para personalizar o email
- Mantenha as variáveis `{{NOME_CLIENTE}}`, `{{EMAIL_CLIENTE}}`, `{{SENHA_GERADA}}`

### Estilos CSS
- O arquivo `style.css` contém todos os estilos
- Design responsivo para mobile e desktop
- Cores e fontes podem ser facilmente alteradas

## 🚀 URLs da Aplicação

- **Login**: `https://erosvitta.com.br/login`
- **Dashboard**: `https://erosvitta.com.br/dashboard`
- **Ebook**: `https://erosvitta.com.br/ebook/{id}`
- **Vídeo**: `https://erosvitta.com.br/video/{id}`
- **Áudio**: `https://erosvitta.com.br/audio/{id}`
- **Download**: `https://erosvitta.com.br/download/{id}`
- **Webhook**: `https://erosvitta.com.br/app/hotmartWebhook.php`

## 📱 Responsividade

A aplicação é totalmente responsiva e funciona em:
- ✅ Desktop
- ✅ Tablet
- ✅ Mobile

## 🔧 Requisitos do Servidor

- **PHP**: 7.4 ou superior
- **MySQL**: 5.7 ou superior
- **Extensões PHP**: PDO, OpenSSL, cURL
- **Hospedagem**: HostGator (recomendado)

## 📞 Suporte

Para dúvidas ou problemas:
- Email: contato@erosvitta.com.br
- Documentação: Este README

---

**Desenvolvido para ErosVitta** 🚀
