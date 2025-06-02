<?php
include("conexao.php");

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $email = strtolower(trim($_POST['email']));
    $senha = $_POST['senha'];
    $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

    $verifica = $conexao->prepare("SELECT id FROM adm WHERE email = ?");
    $verifica->bind_param("s", $email);
    $verifica->execute();
    $verifica->store_result();

    if ($verifica->num_rows > 0) {
        $mensagem = "E-mail já cadastrado.";
        $tipoMensagem = "danger";
    } else {
        $stmt = $conexao->prepare("INSERT INTO adm (nome, telefone, email, senha) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $telefone, $email, $senhaCriptografada);

        if ($stmt->execute()) {
            $mensagem = "Cadastro realizado com sucesso! Redirecionando para o login...";
            $tipoMensagem = "success";
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
        } else {
            $mensagem = "Erro ao cadastrar. Tente novamente.";
            $tipoMensagem = "danger";
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    :root {
      --primary-color: #6366f1;
      --primary-dark: #4f46e5;
      --secondary-color: #ec4899;
      --dark-color: #1e293b;
      --light-color: #f8fafc;
      --border-radius: 12px;
      --box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    body {
      background: linear-gradient(135deg, #f6f7ff 0%, #eef1f5 100%);
      font-family: 'Poppins', sans-serif;
      color: var(--dark-color);
      line-height: 1.7;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
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
    
    .signup-container {
      max-width: 550px;
      margin: auto;
      padding: 40px 0;
      flex: 1;
      display: flex;
      align-items: center;
    }
    
    .signup-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: var(--border-radius);
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: var(--box-shadow);
      overflow: hidden;
      width: 100%;
      transform: translateY(0);
      transition: transform 0.5s ease;
    }
    
    .signup-card:hover {
      transform: translateY(-5px);
    }
    
    .signup-header {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 25px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .signup-header::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 60%);
      transform: rotate(30deg);
    }
    
    .signup-body {
      padding: 30px;
    }
    
    .form-control {
      border-radius: var(--border-radius);
      padding: 12px 15px;
      border: 2px solid #e2e8f0;
      font-size: 1rem;
      transition: var(--transition);
      background-color: rgba(255, 255, 255, 0.8);
      padding-left: 45px;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
      background-color: white;
    }
    
    .form-label {
      font-weight: 600;
      color: var(--dark-color);
      margin-bottom: 10px;
      font-size: 0.95rem;
      letter-spacing: 0.5px;
    }
    
    .btn-signup {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border: none;
      border-radius: 50px;
      padding: 12px 30px;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: var(--transition);
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
      color: white;
    }
    
    .btn-signup:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.6);
      background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    }
    
    .btn-outline-primary {
      color: var(--primary-color);
      border: 2px solid var(--primary-color);
      border-radius: 50px;
      padding: 10px;
      font-weight: 500;
      transition: var(--transition);
    }
    
    .btn-outline-primary:hover {
      background-color: rgba(99, 102, 241, 0.1);
      border-color: var(--primary-color);
      color: var(--primary-color);
      transform: translateY(-2px);
    }
    
    hr {
      opacity: 0.2;
    }
    
    .alert {
      border-radius: var(--border-radius);
      border: none;
      padding: 15px;
      position: relative;
      overflow: hidden;
      margin-bottom: 20px;
    }
    
    .alert-success {
      background-color: rgba(16, 185, 129, 0.1);
      border-left: 4px solid #10b981;
    }
    
    .alert-danger {
      background-color: rgba(239, 68, 68, 0.1);
      border-left: 4px solid #ef4444;
    }
    
    .input-icon-wrapper {
      position: relative;
    }
    
    .input-icon {
      position: absolute;
      top: 50%;
      left: 15px;
      transform: translateY(-50%);
      color: #a0aec0;
      transition: var(--transition);
    }
    
    .form-control:focus + .input-icon {
      color: var(--primary-color);
    }
    
    .toggle-password {
      position: absolute;
      top: 50%;
      right: 15px;
      transform: translateY(-50%);
      color: #a0aec0;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .toggle-password:hover {
      color: var(--primary-color);
    }
    
    footer {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 20px 0;
      position: relative;
      overflow: hidden;
      margin-top: auto;
    }
    
    footer::before {
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
    }
    
    .floating-shapes {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      overflow: hidden;
      z-index: -1;
      opacity: 0.4;
    }
    
    .shape {
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(45deg, rgba(99, 102, 241, 0.3), rgba(236, 72, 153, 0.3));
      animation: float 15s infinite ease-in-out;
    }
    
    .shape:nth-child(1) {
      width: 80px;
      height: 80px;
      top: 10%;
      left: 5%;
      animation-delay: 0s;
    }
    
    .shape:nth-child(2) {
      width: 120px;
      height: 120px;
      top: 60%;
      left: 80%;
      animation-delay: 2s;
    }
    
    .shape:nth-child(3) {
      width: 60px;
      height: 60px;
      top: 80%;
      left: 20%;
      animation-delay: 4s;
    }
    
    @keyframes float {
      0% { transform: translate(0, 0) rotate(0deg); }
      50% { transform: translate(20px, 20px) rotate(180deg); }
      100% { transform: translate(0, 0) rotate(360deg); }
    }
    
    .login-link {
      color: var(--primary-color);
      font-weight: 600;
      transition: var(--transition);
    }
    
    .login-link:hover {
      color: var(--secondary-color);
      text-decoration: none;
    }
    
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
      70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
      100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
    }
    
    .btn-animated {
      animation: pulse 2s infinite;
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
    <a class="navbar-brand d-flex align-items-center" href="#">
      <i class="fas fa-heartbeat me-2"></i>
      <span>Clínica Nutrição</span>
    </a>
  </div>
</nav>

<div class="signup-container">
  <div class="signup-card">
    <div class="signup-header">
      <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Cadastro de Administrador</h2>
      <p class="text-white-50 mt-2 mb-0">Crie sua conta para acessar o sistema</p>
    </div>
    
    <div class="signup-body">
      <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?= $tipoMensagem ?>" role="alert">
          <div class="d-flex align-items-center">
            <?php if ($tipoMensagem == "success"): ?>
              <i class="fas fa-check-circle text-success me-3"></i>
            <?php else: ?>
              <i class="fas fa-exclamation-circle text-danger me-3"></i>
            <?php endif; ?>
            <p class="mb-0"><?= htmlspecialchars($mensagem) ?></p>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-4">
          <label for="nome" class="form-label">Nome Completo</label>
          <div class="input-icon-wrapper">
            <input type="text" name="nome" class="form-control" id="nome" required>
            <i class="fas fa-user input-icon"></i>
          </div>
        </div>
        
        <div class="mb-4">
          <label for="telefone" class="form-label">Número de Telefone</label>
          <div class="input-icon-wrapper">
            <input type="tel" name="telefone" class="form-control" id="telefone" required>
            <i class="fas fa-phone input-icon"></i>
          </div>
        </div>

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
            <i class="fas fa-lock input-icon"></i>
            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility()"></i>
          </div>
        </div>

        <div class="text-center mt-5">
          <button type="submit" class="btn btn-signup btn-animated px-5 py-2">
            <i class="fas fa-user-plus me-2"></i>Cadastrar
          </button>
          
          <div class="mt-4 position-relative">
            <hr>
            <span class="position-absolute top-0 start-50 translate-middle bg-white px-3 text-muted">ou</span>
          </div>
          
          <div class="d-grid gap-2 mt-4">
            <a href="#" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
              <i class="fab fa-google me-2"></i> Entrar com Google
            </a>
            <a href="#" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
              <i class="fab fa-facebook-f me-2"></i> Entrar com Facebook
            </a>
          </div>
          
          <p class="mt-4">Já tem conta? <a href="login.php" class="login-link">Faça login</a></p>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="text-center py-3">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-4 text-md-start">
        <div class="d-flex align-items-center justify-content-center justify-content-md-start">
          <i class="fas fa-heartbeat fa-2x me-3"></i>
          <div>
            <h5 class="mb-0">Clínica Nutrição</h5>
            <small>Saúde e bem-estar</small>
          </div>
        </div>
      </div>
      <div class="col-md-4 my-3 my-md-0">
        <p class="m-0">&copy; 2025 - Sistema de Agendamentos</p>
      </div>
      <div class="col-md-4 text-md-end">
        <div class="d-flex justify-content-center justify-content-md-end">
          <a href="#" class="btn btn-sm btn-light rounded-circle mx-1"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="btn btn-sm btn-light rounded-circle mx-1"><i class="fab fa-instagram"></i></a>
          <a href="#" class="btn btn-sm btn-light rounded-circle mx-1"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>
    </div>
  </div>
</footer>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Efeito nos campos de formulário
    document.querySelectorAll('.form-control').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });
  });
  
  // Função para alternar a visibilidade da senha
  function togglePasswordVisibility() {
    const senhaInput = document.getElementById('senha');
    const toggleIcon = document.querySelector('.toggle-password');
    
    if (senhaInput.type === 'password') {
      senhaInput.type = 'text';
      toggleIcon.classList.remove('fa-eye');
      toggleIcon.classList.add('fa-eye-slash');
    } else {
      senhaInput.type = 'password';
      toggleIcon.classList.remove('fa-eye-slash');
      toggleIcon.classList.add('fa-eye');
    }
  }
</script>

</body>
</html>