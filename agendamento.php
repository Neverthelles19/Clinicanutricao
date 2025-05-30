<?php
// conexao.php (adicione isso no topo ou use include se já tiver separado)
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'nutricao';
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
  die("Erro na conexão com o banco de dados.");
}

// PROCESSAMENTO DO AGENDAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email_cliente'];
  $senha = $_POST['senha'];
  $servico_id = $_POST['servico_id'];
  $profissional_id = $_POST['profissional_id'];
  $data = $_POST['data'];
  $hora = $_POST['hora'];

  $query = mysqli_query($conexao, "SELECT * FROM agendamentos WHERE email_cliente = '$email' LIMIT 1");
  $cliente = mysqli_fetch_assoc($query);

  if ($cliente) {
    if ($cliente['senha'] !== $senha) {
      echo "<script>alert('Senha incorreta!'); history.back();</script>";
      exit;
    }
    $nome = $cliente['nome_cliente'];
    $telefone = $cliente['telefone_cliente'];
  } else {
    $nome = "Cliente";
    $telefone = "";
  }

  $existe = mysqli_query($conexao, "SELECT * FROM agendamentos WHERE profissional_id = $profissional_id AND data = '$data' AND hora = '$hora'");
  if (mysqli_num_rows($existe) > 0) {
    echo "<script>alert('Esse horário já está agendado!'); history.back();</script>";
    exit;
  }

  mysqli_query($conexao, "INSERT INTO agendamentos (profissional_id, servico_id, nome_cliente, email_cliente, telefone_cliente, data, hora, senha)
  VALUES ($profissional_id, $servico_id, '$nome', '$email', '$telefone', '$data', '$hora', '$senha')");

  echo "<script>alert('Agendamento realizado com sucesso!'); window.location.href='agendamento.php';</script>";
  exit;
}

// Lista serviços
$servicos = mysqli_query($conn, "SELECT * FROM servicos");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Agendamento de Serviços</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h2 class="mb-4">Agendamento de Serviços de Nutrição</h2>
  <div class="row">
    <?php while($s = mysqli_fetch_assoc($servicos)): ?>
    <div class="col-md-4 mb-3">
      <div class="card shadow">
        <div class="card-body">
          <h5 class="card-title"><?= $s['servico'] ?></h5>
          <p class="card-text">Duração: <?= $s['duracao'] ?> min<br>Valor: R$ <?= number_format($s['valor'], 2, ',', '.') ?></p>
          <button class="btn btn-primary agendarBtn"
                  data-id="<?= $s['id'] ?>"
                  data-servico="<?= $s['servico'] ?>"
                  data-valor="<?= $s['valor'] ?>">
            Agendar
          </button>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- MODAL AGENDAR -->
<div class="modal fade" id="modalAgendar" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Agendar: <span id="servicoSelecionado"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Profissional -->
          <div class="mb-3">
            <label class="form-label">Profissional</label>
            <select class="form-select" name="profissional_id" id="profissional_id" required>
              <option value="">Selecione</option>
              <?php
              $profissionais = mysqli_query($conn, "SELECT * FROM profissionais");
              while($p = mysqli_fetch_assoc($profissionais)):
              ?>
              <option value="<?= $p['id'] ?>"><?= $p['nome'] ?> - <?= $p['especialidade'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Data -->
          <div class="mb-3">
            <label class="form-label">Data</label>
            <input type="date" name="data" id="data" class="form-control" required>
          </div>

          <!-- Horário -->
          <div class="mb-3">
            <label class="form-label">Horário</label>
            <select name="hora" id="hora" class="form-select" required>
              <option value="">Selecione a data e profissional</option>
            </select>
          </div>

          <!-- Login -->
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email_cliente" class="form-control" required>
            <label class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control" required>
            <small><a href="cadastro.php">Ainda não tem cadastro? Clique aqui.</a></small>
          </div>

          <input type="hidden" name="servico_id" id="servico_id">
        </div>
        <div class="modal-footer">
          <strong class="me-auto">Valor: <span id="valorServico"></span></strong>
          <button type="submit" class="btn btn-success">Confirmar Agendamento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.agendarBtn').forEach(button => {
  button.addEventListener('click', function () {
    const servicoId = this.dataset.id;
    const servicoNome = this.dataset.servico;
    const valor = parseFloat(this.dataset.valor).toFixed(2);

    document.getElementById('servicoSelecionado').textContent = servicoNome;
    document.getElementById('valorServico').textContent = 'R$ ' + valor;
    document.getElementById('servico_id').value = servicoId;

    const modal = new bootstrap.Modal(document.getElementById('modalAgendar'));
    modal.show();
  });
});

function atualizarHorarios() {
  const profId = document.getElementById('profissional_id').value;
  const data = document.getElementById('data').value;
  const horaSelect = document.getElementById('hora');

  if (profId && data) {
    horaSelect.innerHTML = '<option>Carregando...</option>';
    fetch(`horarios_disponiveis.php?profId=${profId}&data=${data}`)
      .then(r => r.text())
      .then(html => horaSelect.innerHTML = html);
  }
}

document.getElementById('profissional_id').addEventListener('change', atualizarHorarios);
document.getElementById('data').addEventListener('change', atualizarHorarios);
</script>
</body>
</html>
