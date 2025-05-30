<?php
include("conexao.php");

$mensagem = "";

// Buscar profissionais
$profissionais = $conexao->query("SELECT id, nome FROM profissionais")->fetch_all(MYSQLI_ASSOC);

// Buscar serviços
$servicos = $conexao->query("SELECT id, servico FROM servicos")->fetch_all(MYSQLI_ASSOC);

// Agendamento
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_profissional = $_POST['profissional'];
    $id_servico = $_POST['servico'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $nome_cliente = trim($_POST['nome_cliente']);
    $email_cliente = trim($_POST['email_cliente']);
    $telefone_cliente = trim($_POST['telefone_cliente']);

    // Verifica se já existe agendamento no mesmo horário
    $stmt = $conexao->prepare("SELECT id FROM agendamentos WHERE id_profissional = ? AND data = ? AND hora = ?");
    $stmt->bind_param("iss", $id_profissional, $data, $hora);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensagem = "Este horário já está agendado. Por favor, escolha outro.";
    } else {
        // Inserir agendamento
        $stmt = $conexao->prepare("INSERT INTO agendamentos (id_profissional, id_servico, data, hora, nome_cliente, email_cliente, telefone_cliente) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $id_profissional, $id_servico, $data, $hora, $nome_cliente, $email_cliente, $telefone_cliente);

        if ($stmt->execute()) {
            $mensagem = "Agendamento confirmado com sucesso!";
        } else {
            $mensagem = "Erro ao agendar. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Agendamento de Serviço</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-form {
      max-width: 700px;
      margin: 60px auto;
    }
  </style>
</head>
<body class="bg-light">

<header class="bg-success text-white text-center py-3">
  <h1 class="h4 m-0">Agendamento - Avaliação Nutricional</h1>
</header>

<div class="container">
  <div class="card card-form shadow-sm">
    <div class="card-body">
      <h2 class="card-title text-center mb-4">Agende seu horário</h2>

      <?php if ($mensagem): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Profissional</label>
          <select name="profissional" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($profissionais as $prof): ?>
              <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Serviço</label>
          <select name="servico" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($servicos as $serv): ?>
              <option value="<?= $serv['id'] ?>"><?= htmlspecialchars($serv['servico']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Data</label>
            <input type="date" name="data" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Hora</label>
            <input type="time" name="hora" class="form-control" required>
          </div>
        </div>

        <hr>

        <div class="mb-3">
          <label class="form-label">Nome</label>
          <input type="text" name="nome_cliente" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email_cliente" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Telefone</label>
          <input type="tel" name="telefone_cliente" class="form-control" required>
        </div>

        <div class="text-center">
          <button type="submit" class="btn btn-success px-5">Confirmar Agendamento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="bg-success text-white text-center py-3 mt-5">
  &copy; 2025 - Nutrição | Sistema de Agendamentos
</footer>

</body>
</html>
