<?php
// Script para testar o envio de e-mail

// Configurações do e-mail
$to = "teste@example.com"; // Substitua pelo seu e-mail
$subject = "Teste de envio de e-mail";
$message = "Este é um e-mail de teste para verificar se o envio está funcionando.";
$headers = "From: contato@seudominio.com\r\n";

// Tenta enviar o e-mail
$result = mail($to, $subject, $message, $headers);

// Exibe o resultado
if ($result) {
    echo "E-mail enviado com sucesso!";
} else {
    echo "Falha ao enviar o e-mail.";
    
    // Informações adicionais para debug
    echo "<br><br>Informações do PHP mail:<br>";
    echo "SMTP: " . ini_get('SMTP') . "<br>";
    echo "smtp_port: " . ini_get('smtp_port') . "<br>";
    echo "sendmail_from: " . ini_get('sendmail_from') . "<br>";
    
    // Verificar se o PHP está configurado para usar o SMTP
    if (empty(ini_get('SMTP'))) {
        echo "<br>ERRO: O servidor SMTP não está configurado no php.ini";
    }
    
    if (empty(ini_get('sendmail_from'))) {
        echo "<br>ERRO: O endereço de e-mail de origem (sendmail_from) não está configurado no php.ini";
    }
}
?>