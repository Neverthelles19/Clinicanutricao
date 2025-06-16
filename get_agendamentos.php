<?php
// get_agendamentos.php
include 'conexao.php'; // Inclua sua conexão com o banco de dados

// Garante que a resposta seja JSON
header('Content-Type: application/json');

$agendamentos = [];

// Valida e sanitiza os inputs do GET
$profissional_id = isset($_GET['profissional_id']) ? (int)$_GET['profissional_id'] : 0;
$data = isset($_GET['data']) ? filter_var($_GET['data'], FILTER_SANITIZE_STRING) : '';

if ($profissional_id > 0 && !empty($data)) {
    // Usa Prepared Statements para prevenir SQL Injection
    // A consulta busca os horários de início e a duração dos serviços agendados
    $stmt = $conexao->prepare("
        SELECT
            a.hora,
            s.duracao
        FROM
            agendamentos a
        JOIN
            servicos s ON a.servico_id = s.id
        WHERE
            a.profissional_id = ? AND a.data = ?
    ");

    if ($stmt) {
        // 'is' significa que o primeiro parâmetro é um inteiro (profissional_id) e o segundo é uma string (data)
        $stmt->bind_param("is", $profissional_id, $data);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Adiciona o horário de início e a duração de cada agendamento
                $agendamentos[] = [
                    'hora' => $row['hora'],
                    'duracao' => (int)$row['duracao'] // Garante que a duração é um inteiro
                ];
            }
        } else {
            // Em ambiente de produção, é importante logar este erro para investigação.
            error_log("Erro ao buscar agendamentos: " . $conexao->error);
            // Em vez de um array vazio, você pode enviar um erro JSON
            echo json_encode(['error' => 'Erro ao carregar agendamentos.']);
            mysqli_close($conexao);
            exit;
        }
        $stmt->close();
    } else {
        error_log("Erro ao preparar consulta em get_agendamentos.php: " . $conexao->error);
        echo json_encode(['error' => 'Erro interno do servidor ao buscar horários.']);
        mysqli_close($conexao);
        exit;
    }
} else {
    // Retorna um erro se os parâmetros não forem fornecidos ou forem inválidos
    echo json_encode(['error' => 'Parâmetros "profissional_id" e "data" são obrigatórios.']);
    mysqli_close($conexao);
    exit;
}

// Retorna a lista de agendamentos em formato JSON
echo json_encode($agendamentos);

mysqli_close($conexao); // Fecha a conexão com o banco de dados
?>