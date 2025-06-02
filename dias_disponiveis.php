<?php
include 'conexao.php';

$profId = intval($_GET['profId'] ?? 0);
if (!$profId) {
    echo json_encode([]);
    exit;
}

$res = mysqli_query($conn, "SELECT dias_semana FROM profissionais WHERE id = $profId LIMIT 1");
$prof = mysqli_fetch_assoc($res);

if (!$prof) {
    echo json_encode([]);
    exit;
}

// Converte string "Segunda,Quarta,Sábado" em array ["Segunda","Quarta","Sábado"]
$diasStr = $prof['dias_semana'];
$diasArray = array_map('trim', explode(',', $diasStr));

// Retorna JSON com os dias da semana que o profissional atende
// Você pode ajustar conforme a necessidade (exemplo: números do dia da semana)
echo json_encode($diasArray);
?>
