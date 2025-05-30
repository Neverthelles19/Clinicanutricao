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
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .custom-green { background-color: #2B7540; }
    .input-menor { max-width: 400px; margin: auto; }
    .form-container { max-width: 500px; margin: auto; padding-top: 100px; }
  </style>
</head>
<body>

<header class="fixed-top custom-green text-white p-3 shadow">
  <div class="container d-flex justify-content-between align-items-center">
    <h1 class="h4 m-0">Nutrição</h1>
    <nav class="nav-cliente">
      <a href="cadastro.php" class="btn btn-light btn-sm" title="Área do ADM">👤 ADM</a>
    </nav>
  </div>
</header>

<div class="container form-container">
  <h2 class="text-center mb-4">Cadastro de Administrador</h2>

  <?= $mensagem ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="email" class="form-label">Email:</label>
      <input type="email" name="email" class="form-control input-menor" id="email" required>
    </div>

    <div class="mb-3">
      <label for="senha" class="form-label">Senha:</label>
      <input type="password" name="senha" class="form-control input-menor" id="senha" required>
    </div>

    <div class="text-center">
      <button type="submit" class="btn btn-success px-5">Cadastrar</button>
      <p class="mt-3">Já tem conta? <a href="login.php">Faça login</a></p>
    </div>
  </form>
</div>

<footer class="mt-5 text-white custom-green py-4 text-center">
  <div>Copyright © 2025. Aims.</div>
</footer>

</body>
</html>
