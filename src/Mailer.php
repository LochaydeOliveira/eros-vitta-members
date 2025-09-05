<?php
// Define o caminho base do projeto
define('BASE_PATH', dirname(__DIR__));

// Carrega os arquivos principais do PHPMailer
require_once BASE_PATH . '/vendor/PHPMailer/Exception.php';
require_once BASE_PATH . '/vendor/PHPMailer/PHPMailer.php';
require_once BASE_PATH . '/vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../config/config.php';    // Configurações gerais (inclui LOG_FILE)
require_once __DIR__ . '/functions.php';          // Funções utilitárias
require_once __DIR__ . '/../config/email.php';    // Configurações do email

// Usa os namespaces corretos
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Classe que encapsula o envio de e-mails da aplicação
class Mailer {
    private $mailer; // Propriedade interna que armazenará o objeto PHPMailer

    // Construtor chamado automaticamente ao criar um Mailer
    public function __construct() {
        app_log("Iniciando configuração do PHPMailer"); // Grava log

        try {
            $this->mailer = new PHPMailer(true); // Instancia o PHPMailer com tratamento de erros

            // Configurações básicas de envio via SMTP
            $this->mailer->isSMTP();                      // Define o uso de SMTP
            $this->mailer->Host = SMTP_HOST;              // Servidor SMTP
            $this->mailer->SMTPAuth = true;               // Habilita autenticação
            $this->mailer->Username = SMTP_USER;          // Usuário (e-mail remetente)
            $this->mailer->Password = SMTP_PASS;          // Senha do e-mail
            $this->mailer->SMTPSecure = SMTP_SECURE;      // Tipo de segurança (TLS ou SSL)
            $this->mailer->Port = SMTP_PORT;              // Porta
            $this->mailer->CharSet = 'UTF-8';             // Codificação dos caracteres

            // Informações padrão do remetente
            $this->mailer->setFrom(SMTP_USER, 'Agência LED');
            $this->mailer->isHTML(true); // Os e-mails serão enviados em HTML

            // Ativa o modo debug (apenas erros)
            $this->mailer->SMTPDebug = 2; // Aumenta o nível de debug
            $this->mailer->Debugoutput = function($str, $level) {
                app_log("PHPMailer Debug [$level]: $str");
            };

            // Testa a conexão SMTP
            if (!$this->mailer->smtpConnect()) {
                throw new Exception("Falha ao conectar ao servidor SMTP");
            }

            app_log("Configurações SMTP: Host=" . SMTP_HOST . ", Port=" . SMTP_PORT . ", User=" . SMTP_USER);
            app_log("Conexão SMTP testada com sucesso");
        } catch (Exception $e) {
            app_log("Erro na configuração do PHPMailer: " . $e->getMessage());
            app_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    // Método para enviar email com tratamento de erro e fechamento da conexão
    private function sendEmail() {
        try {
            // Validações antes do envio
            if (empty($this->mailer->getToAddresses())) {
                throw new Exception("Nenhum destinatário definido");
            }

            if (empty($this->mailer->Subject)) {
                throw new Exception("Assunto do email não definido");
            }

            if (empty($this->mailer->Body)) {
                throw new Exception("Corpo do email não definido");
            }

            app_log("Tentando enviar email para: " . implode(", ", array_column($this->mailer->getToAddresses(), 0)));
            app_log("Assunto: " . $this->mailer->Subject);

            $result = $this->mailer->send();
            app_log("Email enviado com sucesso");
            
            $this->mailer->smtpClose(); // Fecha a conexão SMTP explicitamente
            return $result;
        } catch (Exception $e) {
            app_log("Erro ao enviar email: " . $e->getMessage());
            app_log("Stack trace: " . $e->getTraceAsString());
            $this->mailer->smtpClose(); // Fecha a conexão SMTP mesmo em caso de erro
            throw $e;
        }
    }

    // Envia o link de download após pagamento confirmado
    public function sendDownloadLink($email, $name, $orderNumber, $token) {
        try {
            app_log("Iniciando envio de email de download para $email");
            app_log("Detalhes do email: Nome=$name, Pedido=$orderNumber, Token=$token");

            $this->mailer->clearAddresses(); // Remove destinatários anteriores
            $this->mailer->addAddress($email, $name); // Define o novo destinatário
            $this->mailer->Subject = '✅ PAGAMENTO CONFIRMADO - Sua Lista Está Pronta!'; // Título do e-mail

            // Corpo em HTML
            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px;'>
                <h2 style='color: #28a745;'>Olá {$name},</h2>
                <p>Seu pagamento foi confirmado com sucesso!</p>
                <p><strong>Clique no botão abaixo para acessar sua lista de fornecedores agora mesmo.</strong></p>
                <div style='text-align: center; margin: 30px;'>
                    <a href='https://agencialed.com/download_page.php?token={$token}'
                       style='background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>
                       Acessar Lista
                    </a>
                </div>
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px;'>
                    <p><strong>⏰ ATENÇÃO:</strong> Este link expirará em 24 horas. Recomendamos que você faça o download o quanto antes.</p>
                </div>
                <p>📋 O que você vai encontrar:</p>
                <ul>
                    <li>Lista completa de fornecedores nacionais</li>
                    <li>Contatos e informações de cada fornecedor</li>
                    <li>Dicas de negociação e melhores práticas</li>
                </ul>
                <hr>
                <p style='font-size: 14px; color: #888;'>Em caso de dúvidas, entre em contato: <a href='mailto:contato@agencialed.com'>contato@agencialed.com</a></p>
            </div>";

            $this->mailer->Body = $html; // Define corpo HTML
            $this->mailer->AltBody = "Olá {$name},\n\nAcesse sua lista: https://agencialed.com/download_page.php?token={$token}\nEste link expira em 24 horas."; // Texto alternativo (caso HTML não carregue)

            app_log("Tentando enviar email de download para $email");
            $result = $this->sendEmail();
            app_log("Email de download enviado com sucesso para $email");
            return $result;
        } catch (Exception $e) {
            app_log("Erro ao enviar email de download para $email: " . $e->getMessage());
            app_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Envia um aviso de pedido aguardando pagamento
    public function sendOrderConfirmation($to, $name, $orderNumber, $value) {
        try {
            app_log("Iniciando envio de email de confirmação para $to");
            app_log("Detalhes do email: Nome=$name, Pedido=$orderNumber, Valor=$value");

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to, $name);
            $this->mailer->Subject = '🚨 PAGAMENTO PENDENTE - Pedido #' . $orderNumber;

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px;'>
                <h2 style='color: #e74c3c;'>Olá {$name},</h2>
                <p>Recebemos seu pedido #{$orderNumber} com sucesso!</p>
                <p><strong>Para garantir seu acesso à lista de fornecedores, realize o pagamento agora mesmo.</strong></p>
                <div style='background-color: #e1ffe2;padding: 20px 12px;border-radius:8px;text-align: center;font-size: 20px;color: #137817;border: 0.15rem solid #137817;border-style: dashed;'>
                    Valor a Pagar:<strong> R$ " . number_format($value, 2, ',', '.') . "</strong>
                </div>
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px;'>
                    <p><strong>⏰ ATENÇÃO:</strong> O pagamento via PIX expira em poucos minutos.</p>
                </div>
                <ol>
                    <li>Realize o pagamento via PIX agora mesmo</li>
                    <li>Aguarde a confirmação automática do pagamento</li>
                    <li>Receba o email com os dados de acesso a área de clientes</li>
                </ol>
                <hr>
                <p style='font-size: 14px; color: #888;'>Dúvidas? <a href='mailto:contato@agencialed.com'>contato@agencialed.com</a></p>
            </div>";

            $this->mailer->Body = $html;
            $this->mailer->AltBody = "Olá {$name}, seu pedido foi registrado. Valor: R$ " . number_format($value, 2, ',', '.') . ". Pague via PIX para garantir o acesso.";

            app_log("Tentando enviar email de confirmação para $to");
            $result = $this->sendEmail();
            app_log("Email de confirmação enviado com sucesso para $to");
            return $result;
        } catch (Exception $e) {
            app_log("Erro ao enviar confirmação para $to: " . $e->getMessage());
            app_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Envia os dados de login para a área de membros após pagamento confirmado
    public function sendMemberAccess($email, $name, $senha) {
        try {
            app_log("Iniciando envio de email de acesso para $email");
            app_log("Detalhes do email: Nome=$name, Senha=$senha");

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = '🔐 ACESSO LIBERADO! - Área dos Clientes | Agência LED';

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px;'>
                <h2 style='color: #28a745;font-size: 30px;'>Olá {$name},</h2>
                <p style='font-size: 17px;margin: 0 0 -18px 0;'>Seu pagamento foi confirmado e seu acesso à área de clientes foi liberado!</p>
                <p style='font-size: 15px;'><strong>Use os dados abaixo para acessar:</strong></p>
                <div style='background-color: #e6f9eae0;padding:15px;border-radius:8px;font-size: 15px;text-decoration: none!important;border-left: 6px solid #61d17b;'>
                    <p style='text-decoration: none !important;'><strong>Email:</strong> {$email}</p>
                    <p><strong>Senha:</strong> {$senha}</p>
                </div>
                <div style='text-align: center; margin: 30px;'>
                    <a href='https://agencialed.com/login.php'
                       style='max-width: 40%;margin: 0 auto;background: #28a745;color: white;padding: 15px 30px;text-decoration: none;border-radius: 8px;font-size: 16px;font-weight: bold;display: inline-block;width: 100%;'>
                       Ver Área de Clientes
                    </a>
                </div>
                <hr>
                <p style='font-size: 14px; color: #888;'>Dúvidas? <a href='mailto:contato@agencialed.com'>contato@agencialed.com</a></p>
            </div>";

            $this->mailer->Body = $html;
            $this->mailer->AltBody = "Olá {$name},\n\nSeu acesso à área dos clientes foi liberado.\nEmail: {$email}\nSenha: {$senha}\nAcesse: https://agencialed.com/login.php";

            app_log("Tentando enviar email de acesso para $email");
            $result = $this->sendEmail();
            app_log("Email de acesso enviado com sucesso para $email");
            return $result;
        } catch (Exception $e) {
            app_log("Erro ao enviar dados de acesso para $email: " . $e->getMessage());
            app_log("Stack trace: " . $e->getTraceAsString());
            throw $e; // Propaga o erro para ser tratado no nível superior
        }
    }

    public function sendPasswordReset($email, $nome, $link) {
        try {
            $this->mailer->clearAddresses(); // Limpa endereços anteriores
            $this->mailer->addAddress($email, $nome);
            $this->mailer->Subject = 'Recuperação de Senha - Área de Clientes';
            
            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #333;'>Olá {$nome},</h2>
                <p>Recebemos uma solicitação para redefinir sua senha na Área de Clientes.</p>
                <p>Para redefinir sua senha, clique no botão abaixo:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$link}' style='background-color: #0d6efd; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Redefinir Senha</a>
                </div>
                <p>Se você não solicitou a redefinição de senha, por favor ignore este email.</p>
                <p>Este link é válido por 1 hora.</p>
                <hr style='border: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>Este é um email automático, por favor não responda.</p>
            </div>";
            
            $this->mailer->Body = $html;
            $this->mailer->AltBody = "Olá {$nome},\n\nRecebemos uma solicitação para redefinir sua senha na Área de Clientes.\n\nPara redefinir sua senha, acesse o link: {$link}\n\nSe você não solicitou a redefinição de senha, por favor ignore este email.\n\nEste link é válido por 1 hora.";
            
            return $this->sendEmail();
        } catch (Exception $e) {
            app_log("Erro ao enviar email de recuperação de senha para {$email}: " . $e->getMessage(), 'error');
            throw $e; // Propaga o erro para ser tratado no nível superior
        }
    }

    public function sendCustomEmail($to, $subject, $body, $altBody = '') {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($to);
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;
        $this->mailer->AltBody = $altBody ?: strip_tags(str_replace('<br>', "\n", $body));
        return $this->sendEmail();
    }
}
