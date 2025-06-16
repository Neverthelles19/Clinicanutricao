<?php
// Script para testar o envio de e-mail com PHPMailer

// Incluir o arquivo de configuração do PHPMailer
require_once 'phpmailer_config.php';

// E-mail de teste
$to = "teste@example.com"; // Substitua pelo seu e-mail
$subject = "Teste de envio de e-mail com PHPMailer";
$message = "Este é um e-mail de teste para verificar se o envio com PHPMailer está funcionando.";

// Tenta enviar o e-mail usando PHPMailer
$result = enviarEmailPHPMailer($to, $subject, $message);

// Exibe o resultado
if ($result) {
    echo "E-mail enviado com sucesso usando PHPMailer!";
} else {
    echo "Falha ao enviar o e-mail com PHPMailer.";
    echo "<br><br>Verifique o arquivo email_log.txt para mais detalhes.";
}

// Exibe o conteúdo do log
if (file_exists('email_log.txt')) {
    echo "<br><br>Conteúdo do log:<br>";
    echo "<pre>" . htmlspecialchars(file_get_contents('email_log.txt')) . "</pre>";
}
?>