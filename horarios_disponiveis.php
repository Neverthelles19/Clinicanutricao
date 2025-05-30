<?php
include 'conexao.php';

$profId = $_GET['profId'];
$data = $_GET['data'];

$profQuery = mysqli_query($conexao, "SELECT * FROM profissionais WHERE id = $profId");
$prof = mysqli_fetch_assoc($profQuery);

if (!$prof) {
  echo "<option value=''>Profissional não encontrado</option>";
  exit;
}

$diasDisponiveis = explode(',', $prof['dias_disponiveis']);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$diaSemana = ucfirst(strftime('%A', strtotime($data)));

if (!in_array($diaSemana, $diasDisponiveis)) {
  echo "<option value=''>Não atende nesse dia</option>";
  exit;
}

$inicio = strtotime($prof['hora_inicio']);
$fim = strtotime($prof['hora_fim']);
$horariosDisponiveis = [];

while ($inicio < $fim) {
  $horaAtual = date('H:i:s', $inicio);
  $check = mysqli_query($conn, "SELECT * FROM agendamentos WHERE profissional_id = $profId AND data = '$data' AND hora = '$horaAtual'");
  if (mysqli_num_rows($check) == 0) {
    $horariosDisponiveis[] = $horaAtual;
  }
  $inicio += 30 * 60;
}

if (empty($horariosDisponiveis)) {
  echo "<option value=''>Sem horários disponíveis</option>";
} else {
  foreach ($horariosDisponiveis as $hora) {
    echo "<option value='$hora'>" . substr($hora, 0, 5) . "</option>";
  }
}
?>
