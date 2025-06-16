<?php
include 'conexao.php';

header('Content-Type: text/html; charset=utf-8');

$profId = intval($_GET['profId'] ?? 0);
$data = $_GET['data'] ?? '';
$servicoId = intval($_GET['servicoId'] ?? 0);

if (!$profId || !$data || !$servicoId) {
    echo "<option value=''>Selecione profissional, data e serviço</option>";
    exit;
}

// Pega o horário de atendimento do profissional
$res = mysqli_query($conexao, "SELECT hora_inicio, hora_fim FROM profissionais WHERE id = $profId LIMIT 1");
if (!$res) {
    echo "<option value=''>Erro ao buscar profissional</option>";
    exit;
}

$prof = mysqli_fetch_assoc($res);
if (!$prof) {
    echo "<option value=''>Profissional não encontrado</option>";
    exit;
}

$inicioAtendimento = $prof['hora_inicio']; // ex: 09:15:00
$fimAtendimento = $prof['hora_fim']; // ex: 16:00:00

// Pega duração do serviço em minutos
$res = mysqli_query($conexao, "SELECT duracao FROM servicos WHERE id = $servicoId LIMIT 1");
if (!$res) {
    echo "<option value=''>Erro ao buscar serviço</option>";
    exit;
}

$serv = mysqli_fetch_assoc($res);
if (!$serv) {
    echo "<option value=''>Serviço não encontrado</option>";
    exit;
}

$duracao = intval($serv['duracao']); // duração em minutos

// Converter para timestamps (usar data passada com horário)
$timestampInicio = strtotime("$data $inicioAtendimento");
$timestampFim = strtotime("$data $fimAtendimento");

// Calcular o último horário de início possível para caber o serviço inteiro
$timestampUltimoInicio = $timestampFim - ($duracao * 60);

$intervalo = 30 * 60; // intervalos de 30 minutos
$horariosDisponiveis = [];
for ($ts = $timestampInicio; $ts <= $timestampUltimoInicio; $ts += $intervalo) {
    $horariosDisponiveis[] = date('H:i', $ts);
}

// Verifica horários já agendados para evitar conflito
$res = mysqli_query($conexao, "SELECT hora FROM agendamentos WHERE profissional_id = $profId AND data = '$data'");
$horariosOcupados = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $horariosOcupados[] = substr($row['hora'], 0, 5); // Pega apenas HH:MM
    }
}

// Filtra horários disponíveis removendo os ocupados
$horariosLivres = array_values(array_filter($horariosDisponiveis, function($h) use ($horariosOcupados) {
    return !in_array($h, $horariosOcupados);
}));

// Adiciona opção padrão
echo "<option value=''>Selecione um horário</option>";

if (count($horariosLivres) === 0) {
    echo "<option value=''>Nenhum horário disponível</option>";
} else {
    foreach ($horariosLivres as $h) {
        echo "<option value='$h'>$h</option>";
    }
}
?>
