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
  <style>
    .custom-green { background-color: #2B7540; }
    .form-container { max-width: 500px; margin: auto; padding-top: 100px; }
  </style>
</head>
<body>

<header class="fixed-top custom-green text-white p-3 shadow">
  <div class="container d-flex justify-content-between align-items-center">
    <h1 class="h4 m-0">Nutrição</h1>
  </div>
</header>

<div class="container form-container">
  <h2 class="text-center mb-4">Login - Bem-vindo, Colaborador!</h2>

  <?php if (!empty($mensagemErro)): ?>
    <div class="mb-4">
      <div class="text-danger border border-danger rounded p-2 text-center bg-light fw-semibold">
        <?= htmlspecialchars($mensagemErro) ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="email" class="form-label">Email:</label>
      <input type="email" name="email" class="form-control" id="email" required>
    </div>

    <div class="mb-3">
      <label for="senha" class="form-label">Senha:</label>
      <input type="password" name="senha" class="form-control" id="senha" required>
    </div>

    <div class="text-center">
      <button type="submit" class="btn btn-success px-5">Login</button>
      <p class="mt-3">Não tem cadastro? <a href="cadastro.php">Cadastre-se</a></p>
    </div>
  </form>
</div>

<footer class="mt-5 text-white custom-green py-4 text-center">
  <div>&copy; 2025. Aims.</div>
</footer>

</body>
</html>
