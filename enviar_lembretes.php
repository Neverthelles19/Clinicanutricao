<?php
<?php
include 'conexao.php';

// Busca agendamentos para o dia seguinte
$amanha = date('Y-m-d', strtotime('+1 day'));
$sql = "SELECT a.*, c.email 
        FROM agendamentos a
        JOIN clientes c ON a.cliente_id = c.id
        WHERE a.data = '$amanha' AND a.status = 'confirmado'";
$result = mysqli_query($conexao, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $to = $row['email'];
    $subject = "Lembrete de Agendamento - Clínica de Nutrição";
    $message = "Olá, {$row['nome_cliente']}!\n\n"
             . "Lembramos que você tem um agendamento amanhã ({$row['data']}) às {$row['hora']}.\n\n"
             . "Se precisar remarcar, entre em contato conosco.\n\n"
             . "Atenciosamente,\nClínica de Nutrição";
    $headers = "From: contato@seudominio.com\r\n";

    // Envia o e-mail
    mail($to, $subject, $message, $headers);
}
?>