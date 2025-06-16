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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="stylecadastroUser.css" rel="stylesheet" />
  <link href="styleUser.css" rel="stylesheet" />
  <link href="styleForms.css" rel="stylesheet" />
  <style>
    .input-icon-wrapper {
  position: relative;
}

.input-icon-wrapper .input-icon {
  position: absolute;
  top: 50%;
  left: 15px; /* ou right: 15px, se o ícone estiver à direita */
  transform: translateY(-50%);
  color: #aaa;
  pointer-events: none;
  z-index: 2;
}

.input-icon-wrapper input.form-control {
  padding-left: 40px; /* aumente se o ícone for maior ou mais afastado */
}

  </style>
</head>
<body class="bg-light">

<div class="floating-shapes">
  <div class="shape"></div>
  <div class="shape"></div>
  <div class="shape"></div>
</div>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gradient-text" href="#">
      <i class="fas fa-heartbeat me-2"></i>
      <span class="d-none d-md-inline">Clínica Nutrição</span>
    </a>
    <div class="ms-auto d-flex flex-wrap gap-2">
      <a href="cadastro_servico.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-gradient-hover d-flex align-items-center">
        <i class="fas fa-plus-circle"></i>
        <span class="ms-1 d-none d-md-inline">Cadastro Serviço</span>
      </a>
      <a href="cdfuncionario.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-gradient-hover d-flex align-items-center">
        <i class="fas fa-user-tie"></i>
        <span class="ms-1 d-none d-md-inline">Cadastro Funcionário</span>
      </a>
      <a href="agenda_adimin.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-gradient-hover d-flex align-items-center">
        <i class="fas fa-calendar-alt"></i>
        <span class="ms-1 d-none d-md-inline">Agenda Admin</span>
      </a>
      <a href="logoutAdm.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center">
        <i class="fas fa-sign-out-alt"></i>
        <span class="ms-1 d-none d-md-inline">Sair</span>
      </a>
    </div>
  </div>
</nav>

<main class="container">
  <div class="card card-form">
    <div class="card-header text-center">
      <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Cadastrar Serviço</h2>
      <p class="text-white-50 mt-2 mb-0">Adicione um novo serviço para a Clinica</p>
    </div>
    <div class="card-body p-4">
      <?php if ($mensagem): ?>
        <div class="error-message alert alert-info text-center rounded-pill"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-4">
          <label class="form-label fw-semibold">Nome do Serviço</label>
          <div class="input-icon-wrapper">
            <input type="text" name="servico" class="form-control rounded-pill" required>
            <i class="fas fa-cogs input-icon"></i>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Duração (em minutos)</label>
          <div class="input-icon-wrapper">
            <input type="number" name="duracao" class="form-control rounded-pill" min="1" required>
            <i class="fas fa-clock input-icon"></i>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Valor (opcional)</label>
          <div class="input-icon-wrapper">
            <input type="number" step="0.01" name="valor" class="form-control rounded-pill">
            <i class="fas fa-dollar-sign input-icon"></i>
          </div>
        </div>

        <div class="text-center mt-5">
          <button type="submit" class="btn btn-primary btn-animated px-5 py-3">
            <i class="fas fa-check me-2"></i>Cadastrar
          </button>
        </div>
      </form>
    </div>
    </div>
  </div>
</main>

<!-- Footer completo - visível a partir de md -->
<footer class="mt-5 text-white footer-gradiente py-4 text-center d-none d-md-block">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-3 mb-md-0">
        <h5 class="fw-bold"><i class="fas fa-heartbeat me-2"></i>Clínica Nutrição</h5>
        <p class="mb-0">Cuidando da sua saúde com responsabilidade e equilíbrio.</p>
      </div>
      <div class="col-md-4 mb-3 mb-md-0">
        <h6 class="fw-bold">Contato</h6>
        <p class="mb-1"><i class="fas fa-phone-alt me-2"></i>(00) 1234-5678</p>
        <p class="mb-1"><i class="fas fa-envelope me-2"></i>contato@clinica.com</p>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold">Siga-nos</h6>
        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
        <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
      </div>
    </div>

    <hr class="my-3 border-white">
    <div>&copy; 2025 Clínica Nutrição. Todos os direitos reservados.</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
