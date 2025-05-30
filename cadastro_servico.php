<?php
session_start();
if (!isset($_SESSION['colaborador'])) {
    header("Location: login.php");
    exit();
}

include("conexao.php");

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servico = trim($_POST['servico']);
    $duracao = intval($_POST['duracao']);
    $valor = !empty($_POST['valor']) ? floatval($_POST['valor']) : null;

    $stmt = $conexao->prepare("INSERT INTO servicos (servico, duracao, valor) VALUES (?, ?, ?)");
    $stmt->bind_param("sid", $servico, $duracao, $valor);

    if ($stmt->execute()) {
        $mensagem = "Serviço cadastrado com sucesso!";
    } else {
        $mensagem = "Erro ao cadastrar serviço.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cadastrar Serviço</title>
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
  <h1 class="h4 m-0">Painel Administrativo - Serviços</h1>
</header>

<main class="container">
  <div class="card card-form mt-4 shadow-sm">
    <div class="card-body">
      <h2 class="card-title text-center mb-4">Cadastrar Serviço</h2>

      <?php if ($mensagem): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nome do Serviço</label>
          <input type="text" name="servico" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Duração (em minutos)</label>
          <input type="number" name="duracao" class="form-control" min="1" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Valor (opcional)</label>
          <input type="number" step="0.01" name="valor" class="form-control">
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
