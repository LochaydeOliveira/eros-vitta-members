<?php
/**
 * Sistema de Email - ValidaPro
 * Gerencia envio de emails usando PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Verificar se PHPMailer está disponível
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Tentar carregar via Composer
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        // Fallback para carregamento manual
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    }
}

/**
 * Classe para gerenciar emails
 */
class EmailManager {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->config = [
            'host' => SMTP_HOST,
            'port' => SMTP_PORT,
            'username' => SMTP_USERNAME,
            'password' => SMTP_PASSWORD,
            'from_email' => FROM_EMAIL,
            'from_name' => FROM_NAME
        ];
        
        $this->setupMailer();
    }
    
    /**
     * Configura o PHPMailer
     */
    private function setupMailer() {
        try {
            // Configurações do servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['port'];
            
            // Configurações de charset
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            
            // Configurações de debug (apenas em modo debug)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            } else {
                $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
            }
            
            // Remetente padrão
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            
        } catch (Exception $e) {
            error_log("Erro na configuração do email: " . $e->getMessage());
            throw new Exception("Erro na configuração do email");
        }
    }
    
    /**
     * Envia email de recuperação de senha
     */
    public function sendPasswordReset($email, $name, $reset_link) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Recuperação de Senha - ValidaPro';
            
            $html_body = $this->getPasswordResetTemplate($name, $reset_link);
            $text_body = $this->getPasswordResetTextTemplate($name, $reset_link);
            
            $this->mailer->Body = $html_body;
            $this->mailer->AltBody = $text_body;
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email de recuperação enviado para: $email");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email de recuperação para $email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia um e-mail genérico HTML/texto
     */
    public function sendGeneric($to, $subject, $htmlBody, $textBody = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody ?: strip_tags(str_replace('<br>', "\n", $htmlBody));
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar email genérico para {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia email de boas-vindas para novos usuários
     */
    public function sendWelcomeEmail($email, $name, $username, $password) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            // Removido: anexo da logo, pois agora é carregada por URL externa
            // $logo_path = __DIR__ . '/../assets/img/logo-validapro-fundo-branco.jpeg';
            // if (file_exists($logo_path)) {
            //     $this->mailer->addEmbeddedImage($logo_path, 'logo', 'logo-validapro.jpg');
            // }
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Bem-vindo ao ValidaPro - Suas Credenciais de Acesso';
            
            $html_body = $this->getWelcomeTemplate($name, $username, $password);
            $text_body = $this->getWelcomeTextTemplate($name, $username, $password);
            
            $this->mailer->Body = $html_body;
            $this->mailer->AltBody = $text_body;
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email de boas-vindas enviado para: $email");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email de boas-vindas para $email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email de notificação de nova análise
     */
    public function sendAnalysisNotification($email, $name, $analysis_id, $score) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Nova Análise Concluída - ValidaPro';
            
            $html_body = $this->getAnalysisNotificationTemplate($name, $analysis_id, $score);
            $text_body = $this->getAnalysisNotificationTextTemplate($name, $analysis_id, $score);
            
            $this->mailer->Body = $html_body;
            $this->mailer->AltBody = $text_body;
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email de notificação de análise enviado para: $email");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email de notificação para $email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Template HTML para recuperação de senha
     */
    private function getPasswordResetTemplate($name, $reset_link) {
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperação de Senha - ValidaPro</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #f86f21 0%, #ee4d8d 100%); padding: 32px 0 18px 0; text-align: center; }
                .logo { max-width: 180px; height: auto; margin-bottom: 10px; margin-top: 10px; }
                .header p { color: #fff; margin: 10px 0 0 0; font-size: 18px; }
                .content { padding: 40px 30px; }
                .reset-title { color: #222; font-size: 26px; margin: 0 0 18px 0; font-weight: 600;text-align: left; }
                .reset-text { color: #444; font-size: 16px; margin-bottom: 25px;text-align: left; }
                .cta-button {display: block; background: #27ae33; color: #fff!important; padding: 14px 0; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 17px; margin: 32px auto 0 auto; width: 80%; text-align: center;}
                .cta-button:hover { background: #219a2b; }
                .footer { background: #222b3a; color: white; text-align: center; padding: 28px 20px; font-size: 14px; border-radius: 0 0 8px 8px; }
                .footer a { color: #fff; text-decoration: underline; }
                .footer p { margin: 6px 0; }
                @media (max-width: 600px) {
                    .header { padding: 24px 0 10px 0; }
                    .content { padding: 20px 8px; }
                    .cta-button { width: 100%; font-size: 16px; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://agencialed.com/validapro/assets/img/logo-validapro-fundo-branco.jpeg' alt='ValidaPro Logo' class='logo'>
                    <p>Recuperação de Senha</p>
                </div>
                <div class='content'>
                    <h2 class='reset-title'>Olá, {$name}!</h2>
                    <p class='reset-text'>Recebemos uma solicitação para redefinir sua senha no ValidaPro.</p>
                    <p class='reset-text'>Se você não fez essa solicitação, pode ignorar este email com segurança.</p>
                    <a href='{$reset_link}' class='cta-button'>Redefinir Senha</a>
                    <p style='font-size: 14px; color: #666; margin-top: 24px;'>Este link expira em 1 hora por motivos de segurança.</p>
                    <p style='font-size: 14px; color: #666;'>Se o botão não funcionar, copie e cole este link no seu navegador:</p>
                    <p style='font-size: 12px; color: #999; word-break: break-all;'>{$reset_link}</p>
                </div>
                <div class='footer'>
                    <p>© 2025 ValidaPro - Agência LED. Todos os direitos reservados.</p>
                    <p>Este email foi enviado para você</p>
                    <p><a href='https://agencialed.com'>agencialed.com</a> | <a href='mailto:contato@agencialed.com'>contato@agencialed.com</a></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template texto para recuperação de senha
     */
    private function getPasswordResetTextTemplate($name, $reset_link) {
        return "
        Recuperação de Senha - ValidaPro
        
        Olá, {$name}!
        
        Recebemos uma solicitação para redefinir sua senha no ValidaPro.
        
        Se você não fez essa solicitação, pode ignorar este email com segurança.
        
        Para redefinir sua senha, acesse este link:
        {$reset_link}
        
        Este link expira em 1 hora por motivos de segurança.
        
        © 2025 ValidaPro - Agência LED. Todos os direitos reservados.";
    }
    
    /**
     * Template HTML para boas-vindas
     */
    private function getWelcomeTemplate($name, $username, $password) {
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bem-vindo ao ValidaPro</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #f86f21 0%, #ee4d8d 100%); padding: 32px 0 18px 0; text-align: center; }
                .logo { max-width: 180px; height: auto; margin-bottom: 10px; margin-top: 10px; }
                .header p { color: #fff; margin: 10px 0 0 0; font-size: 18px; }
                .content { padding: 40px 30px; }
                .welcome-title { color: #222; font-size: 26px; margin: 0 0 18px 0; font-weight: 600;text-align: left; }
                .welcome-text { color: #444; font-size: 16px; margin-bottom: 25px;text-align: left; }
                .credentials-box { background: #eaffea; border: 1px solid #b2e5b2; border-radius: 8px; padding: 22px 24px; margin: 30px 0; }
                .credentials-title { color: #167b37; font-size: 18px; margin: 0 0 18px 0; font-weight: 600; display: flex; align-items: center; }
                .credentials-title i { margin-right: 8px; font-size: 20px; }
                .credential-item { margin-bottom: 12px; font-size: 15px; }
                .credential-label { color: #4a5568; font-weight: 600; font-size: 14px; display: inline-block; min-width: 60px; }
                .credential-value { color: #2d3748; font-weight: 700; font-size: 16px; }
                .credential-link { color: #167b37; text-decoration: underline; }
                .divider { border-bottom: 1px solid #d1e7dd; margin: 10px 0; }
                .cta-button {display: block; background: #27ae33; color: #fff!important; padding: 14px 0; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 17px; margin: 32px auto 0 auto; width: 80%; text-align: center;}
                .cta-button:hover { background: #219a2b; }
                .security-note { background: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px; padding: 15px; margin: 28px 0 18px 0; display: flex; align-items: center; }
                .security-note i { color: #e53e3e; margin-right: 8px; font-size: 18px; }
                .security-note p { color: #c53030; margin: 0; font-size: 15px; }
                .footer { background: #222b3a; color: white; text-align: center; padding: 28px 20px; font-size: 14px; border-radius: 0 0 8px 8px; }
                .footer a { color: #fff; text-decoration: underline; }
                .footer p { margin: 6px 0; }
                @media (max-width: 600px) {
                    .header { padding: 24px 0 10px 0; }
                    .content { padding: 20px 8px; }
                    .cta-button { width: 100%; font-size: 16px; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://agencialed.com/validapro/assets/img/logo-validapro-fundo-branco.jpeg' alt='ValidaPro Logo' class='logo'>
                    <p>Sua assinatura foi aprovada!</p>
                </div>
                <div class='content'>
                    <h2 class='welcome-title'>🎉 Parabéns, {$name}!</h2>
                    <p class='welcome-text'>Sua assinatura do ValidaPro foi aprovada com sucesso! Agora você tem acesso completo à nossa plataforma de análise e validação de dados.</p>
                    <div class='credentials-box'>
                        <div class='credentials-title'><span style='font-size:20px;margin-right:8px;'>🔐</span> Suas Credenciais de Acesso</div>
                        <div class='credential-item'><span class='credential-label'>Email:</span> <span class='credential-value credential-link'>{$username}</span></div>
                        <div class='divider'></div>
                        <div class='credential-item'><span class='credential-label'>Senha:</span> <span class='credential-value'>{$password}</span></div>
                    </div>
                    <a href='https://agencialed.com/validapro' class='cta-button'>Acessar ValidaPro</a>
                    <div class='security-note'><span style='font-size:18px;margin-right:8px;'>⚠️</span><p><strong>Importante:</strong> Por segurança, recomendamos que você altere sua senha no primeiro acesso.</p></div>
                    <p style='color: #4a5568; font-size: 15px; margin-top: 18px;'>Se você tiver alguma dúvida ou precisar de suporte, não hesite em nos contatar através do nosso canal de atendimento.</p>
                </div>
                <div class='footer'>
                    <p>© 2025 ValidaPro - Agência LED. Todos os direitos reservados.</p>
                    <p>Este email foi enviado para {$username}</p>
                    <p><a href='https://agencialed.com'>agencialed.com</a> | <a href='mailto:contato@agencialed.com'>contato@agencialed.com</a></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template texto para boas-vindas
     */
    private function getWelcomeTextTemplate($name, $username, $password) {
        return "
        Bem-vindo ao ValidaPro!
        
        Olá, {$name}!
        
        Parabéns! Sua assinatura do ValidaPro foi aprovada com sucesso.
        
        Suas Credenciais de Acesso:
        Email: {$username}
        Senha: {$password}
        
        Para acessar o sistema, visite: https://agencialed.com/validapro
        
        Importante: Guarde essas credenciais em um local seguro. Recomendamos alterar sua senha no primeiro acesso.
        
        © 2025 ValidaPro - Agência LED. Todos os direitos reservados.";
    }
    
    /**
     * Template HTML para notificação de análise
     */
    private function getAnalysisNotificationTemplate($name, $analysis_id, $score) {
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Nova Análise Concluída - ValidaPro</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #f86f21 0%, #ee4d8d 100%); padding: 32px 0 18px 0; text-align: center; }
                .logo { max-width: 180px; height: auto; margin-bottom: 10px; margin-top: 10px; }
                .header p { color: #fff; margin: 10px 0 0 0; font-size: 18px; }
                .content { padding: 40px 30px; }
                .analysis-title { color: #222; font-size: 26px; margin: 0 0 18px 0; font-weight: 600;text-align: left; }
                .analysis-text { color: #444; font-size: 16px; margin-bottom: 25px;text-align: left; }
                .result-box { background: #f8f9fa; border: 1px solid #b2e5b2; border-radius: 8px; padding: 22px 24px; margin: 30px 0; }
                .result-title { color: #167b37; font-size: 18px; margin: 0 0 18px 0; font-weight: 600; }
                .result-item { margin-bottom: 12px; font-size: 15px; }
                .cta-button {display: block; background: #27ae33; color: #fff!important; padding: 14px 0; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 17px; margin: 32px auto 0 auto; width: 80%; text-align: center;}
                .cta-button:hover { background: #219a2b; }
                .footer { background: #222b3a; color: white; text-align: center; padding: 28px 20px; font-size: 14px; border-radius: 0 0 8px 8px; }
                .footer a { color: #fff; text-decoration: underline; }
                .footer p { margin: 6px 0; }
                @media (max-width: 600px) {
                    .header { padding: 24px 0 10px 0; }
                    .content { padding: 20px 8px; }
                    .cta-button { width: 100%; font-size: 16px; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://agencialed.com/validapro/assets/img/logo-validapro-fundo-branco.jpeg' alt='ValidaPro Logo' class='logo'>
                    <p>Nova Análise Concluída</p>
                </div>
                <div class='content'>
                    <h2 class='analysis-title'>Olá, {$name}!</h2>
                    <p class='analysis-text'>Sua análise foi concluída com sucesso!</p>
                    <div class='result-box'>
                        <div class='result-title'>Resultados:</div>
                        <div class='result-item'><strong>ID da Análise:</strong> #{$analysis_id}</div>
                        <div class='result-item'><strong>Pontuação:</strong> {$score}/10</div>
                    </div>
                    <a href='" . APP_URL . "resultado.php?id={$analysis_id}' class='cta-button'>Ver Análise Completa</a>
                </div>
                <div class='footer'>
                    <p>© 2025 ValidaPro - Agência LED. Todos os direitos reservados.</p>
                    <p>Este email foi enviado para você</p>
                    <p><a href='https://agencialed.com'>agencialed.com</a> | <a href='mailto:contato@agencialed.com'>contato@agencialed.com</a></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template texto para notificação de análise
     */
    private function getAnalysisNotificationTextTemplate($name, $analysis_id, $score) {
        return "
        Nova Análise Concluída - ValidaPro
        
        Olá, {$name}!
        
        Sua análise foi concluída com sucesso!
        
        Resultados:
        ID da Análise: #{$analysis_id}
        Pontuação: {$score}/10
        
        Para ver a análise completa, acesse: " . APP_URL . "resultado.php?id={$analysis_id}
        
        © 2025 ValidaPro - Agência LED. Todos os direitos reservados.";
    }
}

/**
 * Função helper para enviar email de recuperação
 */
function sendPasswordResetEmail($email, $name, $reset_link) {
    try {
        $emailManager = new EmailManager();
        return $emailManager->sendPasswordReset($email, $name, $reset_link);
    } catch (Exception $e) {
        error_log("Erro ao enviar email de recuperação: " . $e->getMessage());
        return false;
    }
}

/**
 * Função helper para enviar email de boas-vindas
 */
function sendWelcomeEmail($email, $name, $username, $password) {
    try {
        $emailManager = new EmailManager();
        return $emailManager->sendWelcomeEmail($email, $name, $username, $password);
    } catch (Exception $e) {
        error_log("Erro ao enviar email de boas-vindas: " . $e->getMessage());
        return false;
    }
}

/**
 * Função helper para enviar notificação de análise
 */
function sendAnalysisNotification($email, $name, $analysis_id, $score) {
    try {
        $emailManager = new EmailManager();
        return $emailManager->sendAnalysisNotification($email, $name, $analysis_id, $score);
    } catch (Exception $e) {
        error_log("Erro ao enviar notificação de análise: " . $e->getMessage());
        return false;
    }
} 