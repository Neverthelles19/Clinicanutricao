<?php
// Este arquivo deve ser executado por um agendador de tarefas (cron job no Linux ou Task Scheduler no Windows)
// Exemplo de configuração no cron: 0 * * * * php /caminho/para/cron_lembretes.php

// Incluir o script de lembretes
include_once 'enviar_lembretes.php';

// Registrar execução em log
$log = date('Y-m-d H:i:s') . " - Verificação de lembretes executada\n";
file_put_contents('lembretes_log.txt', $log, FILE_APPEND);

// Exibir mensagem de confirmação quando executado manualmente
if (php_sapi_name() !== 'cli') {
    echo "<h2>Verificação de lembretes executada</h2>";
    echo "<p>Verifique os logs para mais detalhes:</p>";
    echo "<ul>";
    echo "<li>lembretes_log.txt - Log de execução do cron</li>";
    echo "<li>email_log.txt - Log de envio de e-mails</li>";
    echo "</ul>";
}
?>