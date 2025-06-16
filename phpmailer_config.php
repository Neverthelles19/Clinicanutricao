<?php
// Primeiro, instale o PHPMailer via Composer:
// composer require phpmailer/phpmailer

// Verifique se o autoload do Composer existe
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("Erro: Dependências não instaladas. Execute 'composer require phpmailer/phpmailer' na pasta raiz.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Função para enviar e-mail usando PHPMailer
 * 
 * @param string $to Email do destinatário
 * @param string $subject Assunto do e-mail
 * @param string $message Corpo do e-mail
 * @param string $fromName Nome do remetente
 * @return bool Retorna true se o e-mail foi enviado com sucesso, false caso contrário
 */
function enviarEmailPHPMailer($to, $subject, $message, $fromName = 'Clínica Nutrição') {
    // Configurações do servidor SMTP
    $smtp_server = 'smtp.seudominio.com'; // Substitua pelo seu servidor SMTP
    $smtp_port = 587; // Porta do servidor SMTP (geralmente 25, 465 ou 587)
    $smtp_username = 'seu_email@seudominio.com'; // Seu e-mail
    $smtp_password = 'sua_senha'; // Sua senha
    $smtp_secure = 'tls'; // tls ou ssl
    
    // Criar uma nova instância do PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';
        
        // Remetente e destinatário
        $mail->setFrom($smtp_username, $fromName);
        $mail->addAddress($to);
        
        // Conteúdo do e-mail
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // Enviar o e-mail
        $mail->send();
        
        // Registrar o sucesso em log
        $log_message = date('Y-m-d H:i:s') . " - Envio para {$to}: Sucesso (PHPMailer)\n";
        file_put_contents('email_log.txt', $log_message, FILE_APPEND);
        
        return true;
    } catch (Exception $e) {
        // Registrar o erro em log
        $log_message = date('Y-m-d H:i:s') . " - Envio para {$to}: Falha (PHPMailer) - {$mail->ErrorInfo}\n";
        file_put_contents('email_log.txt', $log_message, FILE_APPEND);
        
        return false;
    }
}
?>