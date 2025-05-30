<?php
session_start();
if (!isset($_SESSION['colaborador'])) {
    header("Location: login.php");
    exit();
}

include("conexao.php");

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $especialidade = trim($_POST['especialidade']);
    $dias = implode(',', $_POST['dias_disponiveis'] ?? []);
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];

    $stmt = $conexao->prepare("INSERT INTO profissionais (nome, especialidade, dias_disponiveis, hora_inicio, hora_fim) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nome, $especialidade, $dias, $hora_inicio, $hora_fim);

    if ($stmt->execute()) {
        $mensagem = "Profissional cadastrado com sucesso!";
    } else {
        $mensagem = "Erro ao cadastrar profissional.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cadastrar Profissional</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .card-form {
      max-width: 600px;
      margin: 0 auto;
      margin-top: 60px;
    }
  </style>
</head>
<body class="bg-light">

<header class="bg-success text-white text-center py-3 shadow-sm">
  <h1 class="h4 m-0">Painel Administrativo - Nutrição</h1>
</header>

<main class="container">
  <div class="card card-form mt-4 shadow-sm">
    <div class="card-body">
      <h2 class="card-title text-center mb-4">Cadastrar Profissional</h2>

      <?php if ($mensagem): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nome</label>
          <input type="text" name="nome" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Especialidade</label>
          <input type="text" name="especialidade" class="form-control">
        </div>

        <div class="mb-3">
          <label class="form-label">Dias disponíveis</label><br>
          <?php
          $dias = ["Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
          foreach ($dias as $dia) {
              echo "<div class='form-check form-check-inline'>
                      <input class='form-check-input' type='checkbox' name='dias_disponiveis[]' value='$dia' id='$dia'>
                      <label class='form-check-label' for='$dia'>$dia</label>
                    </div>";
          }
          ?>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Hora de Início</label>
            <input type="time" name="hora_inicio" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Hora de Fim</label>
            <input type="time" name="hora_fim" class="form-control" required>
          </div>
        </div>

        <div class="text-center">
          <button type="submit" class="btn btn-success px-4">Cadastrar</button>
        </div>
      </form>
    </div>
  </div>
</main>

<footer class="bg-success text-white text-center py-3 mt-5">
  &copy; 2025 - Sistema de Agendamentos
</footer>

</body>
</html>
