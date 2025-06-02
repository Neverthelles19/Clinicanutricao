<?php
include 'conexao.php'; // Arquivo de conexão com o banco

header('Content-Type: application/json'); // Define o cabeçalho para resposta JSON

if (isset($_GET['profissional_id']) && isset($_GET['data'])) {
    $profissional_id = (int)$_GET['profissional_id'];
    $data = $_GET['data'];

    // Consulta para obter agendamentos existentes para o profissional e data selecionados
    $query = mysqli_query($conexao, "SELECT hora FROM agendamentos WHERE profissional_id = $profissional_id AND data = '$data'");

    $agendamentos = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $agendamentos[] = $row;
    }

    echo json_encode($agendamentos);
} else {
    echo json_encode([]); // Retorna um array vazio se os parâmetros estiverem faltando
}

mysqli_close($conexao);
?>