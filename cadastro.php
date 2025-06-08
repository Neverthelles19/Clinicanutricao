<?php
include("conexao.php");

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = strtolower(trim($_POST['email']));
    $senha = $_POST['senha'];
    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

    $verifica = $conexao->prepare("SELECT id FROM adm WHERE email = ?");
    $verifica->bind_param("s", $email);
    $verifica->execute();
    $verifica->store_result();

    if ($verifica->num_rows > 0) {
        $mensagem = '<div class="alert alert-danger text-center mt-3">E-mail já cadastrado.</div>';
    } else {
        $stmt = $conexao->prepare("INSERT INTO adm (email, senha) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $senhaCriptografada);

        if ($stmt->execute()) {
            $mensagem = '<div class="alert alert-success text-center mt-3">Cadastro realizado com sucesso! Redirecionando para o login...</div>';
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
        } else {
            $mensagem = '<div class="alert alert-danger text-center mt-3">Erro ao cadastrar. Tente novamente.</div>';
        }
        $stmt->close();
    }
    $verifica->close();
    $conexao->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro ADM - Nutrição</title>
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
<body>

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
      <h2 class="mb-0"><i class="fas fa-user-shield me-2"></i>Cadastro de Administrador</h2>
      <p class="text-white-50 mt-2 mb-0">Crie sua conta de administrador</p>
    </div>

    <div class="login-body">
      <?= $mensagem ?>

      <form method="POST" action="">
        <div class="mb-4">
          <label for="email" class="form-label">Email</label>
          <div class="input-icon-wrapper">
            <input type="email" name="email" class="form-control input-menor" id="email" required>
            <i class="fas fa-envelope input-icon"></i>
          </div>
        </div>

        <div class="mb-4">
          <label for="senha" class="form-label">Senha</label>
          <div class="input-icon-wrapper">
            <input type="password" name="senha" class="form-control input-menor" id="senha" required>
            <i class="fas fa-key input-icon"></i>
            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility()"></i>
          </div>
        </div>

        <div class="text-center mt-5">
          <button type="submit" class="btn btn-signup btn-animated px-5 py-2">
            <i class="fas fa-user-check me-2"></i>Cadastrar
          </button>
          <p class="mt-4">
            Já tem conta? <a href="login.php" class="signup-link">Faça login</a>
          </p>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
