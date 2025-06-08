<?php
session_start();
include("conexao.php");

$mensagemErro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = strtolower(trim($_POST['email']));
    $senha = $_POST['senha'];

    $stmt = $conexao->prepare("SELECT id, senha FROM adm WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $colaborador = $resultado->fetch_assoc();
        if (password_verify($senha, $colaborador['senha'])) {
            $_SESSION['colaborador'] = [
                'id' => $colaborador['id'],
                'email' => $email
            ];
            header("Location: cdfuncionario.php");
            exit();
        }
    }

    // Se chegou aqui, login falhou
    $mensagemErro = "E-mail ou senha incorretos. Verifique seus dados e tente novamente.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Ordem de Serviço</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="styleUser.css" rel="stylesheet" />
  <link href="stylecadastroUser.css" rel="stylesheet" />

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


  </style>

  </head>

  <div class="floating-shapes">
  <div class="shape"></div>
  <div class="shape"></div>
  <div class="shape"></div>
</div>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gradient-text" href="#">
      <i class="fas fa-heartbeat me-2"></i>
      <span>Clínica Nutrição</span>
    </a>
  </div>
</nav>


<div class="login-container">
  <div class="login-card">
    <div class="login-header">
      <h2 class="mb-0"><i class="fas fa-lock me-2"></i>Login - Bem-vindo, Colaborador!</h2>
      <p class="text-white-50 mt-2 mb-0">Acesse com seu email corporativo</p>
    </div>
    
    <div class="login-body">
      <?php if (!empty($mensagemErro)): ?>
        <div class="error-message mb-4">
          <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle text-danger me-3"></i>
            <p class="mb-0"><?= htmlspecialchars($mensagemErro) ?></p>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-4">
          <label for="email" class="form-label">Email</label>
          <div class="input-icon-wrapper">
            <input type="email" name="email" class="form-control" id="email" required>
            <i class="fas fa-envelope input-icon"></i>
          </div>
        </div>

        <div class="mb-4">
          <label for="senha" class="form-label">Senha</label>
          <div class="input-icon-wrapper">
            <input type="password" name="senha" class="form-control" id="senha" required>
            <i class="fas fa-key input-icon"></i>
            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility()"></i>
          </div>
          <div class="text-end mt-2">
            <a href="recuperar_senha.php" class="small text-muted">Esqueceu sua senha?</a>
          </div>
        </div>

        <div class="text-center mt-5">
          <button type="submit" class="btn btn-signup btn-animated px-5 py-2">
            <i class="fas fa-sign-in-alt me-2"></i>Entrar
      </button>

          <p class="mt-4">Não tem cadastro? <a href="cadastro.php" class="signup-link">Cadastre-se</a></p>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
