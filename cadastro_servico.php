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
  <style>

    .navbar {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid rgba(255, 255, 255, 0.3);
      padding: 15px 0;
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--primary-color) !important;
    }

    .btn-outline-secondary:hover {
  background-color: #f0f0f0; /* cinza bem claro */
  border-color: #6c757d;
  color: #6c757d;
}

 /* Botão Início com fundo roxo escuro e texto branco */
  .btn-inicio {
    background-color: #4b0082;
    color: #fff;
    border: none;
    transition: background-color 0.3s ease;
  }

  .btn-inicio:hover {
    background: linear-gradient(90deg, #9600ff, #0f3fa8);
    color: #fff;
  }

  .footer-gradiente {
  background: linear-gradient(135deg, #6b3fa8, #4a2d73);
  color: #ddd;
  padding: 12px 0;
  position: relative;
  margin-top: auto;
  text-align: center;
  font-weight: 400;
  font-size: 13px;
  overflow: hidden; /* garante que ::before não ultrapasse */
}

.footer-gradiente::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: repeating-linear-gradient(
    45deg,
    rgba(255, 255, 255, 0.05),
    rgba(255, 255, 255, 0.05) 10px,
    transparent 10px,
    transparent 20px
  );
  pointer-events: none;
  z-index: 0;
}

.footer-gradiente > * {
  position: relative;
  z-index: 1;
}

@media (max-width: 767.98px) {
  footer {
    animation: fadeOutFooter 0.5s forwards;
  }
}

@keyframes fadeOutFooter {
  from {
    opacity: 1;
    max-height: 200px; /* ajustar conforme seu footer */
    padding: 1rem 0;
  }
  to {
    opacity: 0;
    max-height: 0;
    padding: 0;
    overflow: hidden;
    pointer-events: none;
  }
}

.gradient-text {
    background: linear-gradient(90deg, #4e54c8, #8f94fb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .btn-gradient-hover:hover,
  .btn-gradient-hover:focus {
    color: white !important;
    background: linear-gradient(90deg, #4e54c8, #8f94fb);
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(78, 84, 200, 0.6);
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
      <a href="ver_feedbacks.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-gradient-hover d-flex align-items-center">
        <i class="fas fa-comments"></i>
        <span class="ms-1 d-none d-md-inline">Ver Feedbacks</span>
      </a>
      <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center">
        <i class="fas fa-sign-out-alt"></i>
        <span class="ms-1 d-none d-md-inline">Sair</span>
      </a>
    </div>
  </div>
</nav>

<main class="login-container my-5">
  <div class="login-card card shadow rounded-4 mx-auto" style="max-width: 600px;">
    
    <div class="login-header p-5 pt-4">
      <h2 class="card-title text-center mb-0 fw-bold">
        <i class="fas fa-plus-circle me-2"></i>Cadastrar Serviço
      </h2>
    </div>
    
    <div class="login-body card-body pt-4 pb-5 px-5">
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
          <button type="submit" class="btn btn-signup btn-animated px-5 py-2 rounded-pill shadow-sm">
            <i class="fas fa-check me-2"></i>Cadastrar
          </button>
        </div>
      </form>
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

<!-- Footer simples - visível apenas em telas pequenas
<footer class="footer-gradiente text-center py-2 d-block d-md-none bg-transparent text-muted small border-top">
  &copy; 2025 Clínica Nutrição. Todos os direitos reservados.
</footer>
-->




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
