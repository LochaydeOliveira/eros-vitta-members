<?php
require_once 'config.php';

class Mailer {
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->smtpHost = SMTP_HOST;
        $this->smtpPort = SMTP_PORT;
        $this->smtpUser = SMTP_USER;
        $this->smtpPass = SMTP_PASS;
        $this->fromEmail = FROM_EMAIL;
        $this->fromName = FROM_NAME;
    }
    
    public function sendWelcomeEmail($email, $nome, $senha) {
        $template = file_get_contents(VIEWS_PATH . '/email_template.html');
        
        // Substitui as variáveis no template
        $template = str_replace('{{NOME_CLIENTE}}', htmlspecialchars($nome), $template);
        $template = str_replace('{{EMAIL_CLIENTE}}', htmlspecialchars($email), $template);
        $template = str_replace('{{SENHA_GERADA}}', htmlspecialchars($senha), $template);
        
        $subject = 'Bem-vindo à Área de Membros ErosVitta!';
        
        return $this->sendEmail($email, $nome, $subject, $template);
    }
    
    private function sendEmail($to, $toName, $subject, $body) {
        // Headers para email HTML
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $headersString = implode("\r\n", $headers);
        
        // Tenta enviar via SMTP se disponível, senão usa mail() nativo
        if (function_exists('mail')) {
            return mail($to, $subject, $body, $headersString);
        }
        
        // Fallback para SMTP manual (simplificado)
        return $this->sendSMTP($to, $toName, $subject, $body);
    }
    
    private function sendSMTP($to, $toName, $subject, $body) {
        // Implementação básica de SMTP
        // Em produção, use PHPMailer ou similar
        
        $smtp = fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
        
        if (!$smtp) {
            error_log("Erro SMTP: $errstr ($errno)");
            return false;
        }
        
        $commands = [
            "EHLO erosvitta.com.br\r\n",
            "AUTH LOGIN\r\n",
            base64_encode($this->smtpUser) . "\r\n",
            base64_encode($this->smtpPass) . "\r\n",
            "MAIL FROM: <{$this->fromEmail}>\r\n",
            "RCPT TO: <{$to}>\r\n",
            "DATA\r\n",
            "Subject: {$subject}\r\n",
            "To: {$toName} <{$to}>\r\n",
            "From: {$this->fromName} <{$this->fromEmail}>\r\n",
            "Content-Type: text/html; charset=UTF-8\r\n",
            "\r\n",
            $body . "\r\n",
            ".\r\n",
            "QUIT\r\n"
        ];
        
        foreach ($commands as $command) {
            fwrite($smtp, $command);
            $response = fgets($smtp, 512);
            
            if (strpos($response, '250') === false && strpos($response, '354') === false) {
                fclose($smtp);
                return false;
            }
        }
        
        fclose($smtp);
        return true;
    }
}
?>
