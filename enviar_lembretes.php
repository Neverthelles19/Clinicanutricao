<?php
include 'conexao.php';

// Função para enviar email
function enviarEmailLembrete($email, $nome, $data, $hora, $tipo) {
    $to = $email;
    $subject = "Lembrete de Agendamento - Clínica de Nutrição";
    
    if ($tipo == "dia") {
        $message = "Olá, {$nome}!\n\n"
                 . "Lembramos que você tem um agendamento amanhã ({$data}) às {$hora}.\n\n"
                 . "Se precisar remarcar, entre em contato conosco.\n\n"
                 . "Atenciosamente,\nClínica de Nutrição";
    } else {
        $message = "Olá, {$nome}!\n\n"
                 . "Seu agendamento está próximo! Em apenas 2 horas você tem consulta marcada para hoje às {$hora}.\n\n"
                 . "Atenciosamente,\nClínica de Nutrição";
    }
    
    $headers = "From: contato@seudominio.com\r\n";

    // Envia o e-mail
    mail($to, $subject, $message, $headers);
}

// 1. Busca agendamentos para o dia seguinte
$amanha = date('Y-m-d', strtotime('+1 day'));
$sql = "SELECT a.*, c.email, c.nome as nome_cliente 
        FROM agendamentos a
        JOIN clientes c ON a.cliente_id = c.id
        WHERE a.data = '$amanha' AND a.status = 'confirmado'";
$result = mysqli_query($conexao, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    enviarEmailLembrete($row['email'], $row['nome_cliente'], $row['data'], $row['hora'], "dia");
}

// 2. Busca agendamentos para hoje com 2 horas de antecedência
$hoje = date('Y-m-d');
$horaAtual = date('H:i:s');
$duasHorasDepois = date('H:i:s', strtotime('+2 hours'));

$sql = "SELECT a.*, c.email, c.nome as nome_cliente 
        FROM agendamentos a
        JOIN clientes c ON a.cliente_id = c.id
        WHERE a.data = '$hoje' 
        AND a.hora BETWEEN '$horaAtual' AND '$duasHorasDepois'
        AND a.status = 'confirmado'
        AND a.lembrete_2h_enviado = 0";
$result = mysqli_query($conexao, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    enviarEmailLembrete($row['email'], $row['nome_cliente'], $row['data'], $row['hora'], "hora");
    
    // Marcar que o lembrete de 2 horas foi enviado
    $atualizarSql = "UPDATE agendamentos SET lembrete_2h_enviado = 1 WHERE id = " . $row['id'];
    mysqli_query($conexao, $atualizarSql);
}
?>