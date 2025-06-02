<?php
// get_agendamentos.php
include 'conexao.php'; // Inclua sua conexão com o banco de dados

header('Content-Type: application/json'); // Garante que a resposta seja JSON

$profissional_id = isset($_GET['profissional_id']) ? (int)$_GET['profissional_id'] : 0;
$data = isset($_GET['data']) ? mysqli_real_escape_string($conexao, $_GET['data']) : '';

$agendamentos = [];

if ($profissional_id > 0 && !empty($data)) {
    // Consulta para buscar horários agendados para o profissional e data específicos
    $query = "SELECT hora FROM agendamentos WHERE profissional_id = $profissional_id AND data = '$data'";
    $result = mysqli_query($conexao, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $agendamentos[] = $row;
        }
    } else {
        // Em ambiente de produção, é importante logar este erro para investigação.
        // Para fins de desenvolvimento, pode-se descomentar a linha abaixo para ver o erro.
        // error_log("Erro ao buscar agendamentos: " . mysqli_error($conexao));
    }
}

echo json_encode($agendamentos); // Retorna a lista de agendamentos em formato JSON
mysqli_close($conexao); // Fecha a conexão com o banco de dados
?>