<?php
include 'conexao.php'; // Usa a conexão já existente

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'verificarHorario') {
    $profissional_id = (int)$_POST['profissional_id'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    // Consulta no banco de dados para verificar se o horário já está ocupado
    $query = $conexao->query("SELECT * FROM agendamentos WHERE profissional_id = $profissional_id AND data = '$data' AND hora = '$hora'");
    
    if ($query->num_rows > 0) {
        echo json_encode(['ocupado' => true]);
    } else {
        echo json_encode(['ocupado' => false]);
    }
    exit;
}
?>
