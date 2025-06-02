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
  <title>Login - Clínica Nutrição</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet" />
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
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f6f7ff 0%, #eef1f5 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    .navbar {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--primary-color) !important;
    }
    
    .login-container {
      max-width: 500px;
      margin: auto;
      padding: 40px 0;
      flex: 1;
      display: flex;
      align-items: center;
    }
    
    .login-card {
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
    
    .login-card:hover {
      transform: translateY(-5px);
    }
    
    .login-header {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 25px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .login-header::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 60%);
      transform: rotate(30deg);
    }
    
    .login-body {
      padding: 30px;
    }
    
    .form-control {
      border-radius: var(--border-radius);
      padding: 12px 15px;
      border: 2px solid #e2e8f0;
      font-size: 1rem;
      transition: all 0.3s ease;
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
    
    .btn-login {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border: none;
      border-radius: 50px;
      padding: 12px 30px;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
      color: white;
    }
    
    .btn-login:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.6);
      background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    }
    
    .error-message {
      background-color: rgba(239, 68, 68, 0.1);
      border-left: 4px solid #ef4444;
      border-radius: var(--border-radius);
      padding: 15px;
      position: relative;
      overflow: hidden;
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
      transition: all 0.3s ease;
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
      transition: all 0.3s ease;
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
    
    .signup-link {
      color: var(--primary-color);
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .signup-link:hover {
      color: var(--secondary-color);
      text-decoration: none;
    }
    
    .btn-outline-primary {
      color: var(--primary-color);
      border: 2px solid var(--primary-color);
      border-radius: 50px;
      padding: 10px;
      font-weight: 500;
      transition: all 0.3s ease;
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

<div class="login-container">
  <div class="login-card">
    <div class="login-header">
      <h2 class="mb-0"><i class="fas fa-lock me-2"></i>Login</h2>
      <p class="text-white-50 mt-2 mb-0">Bem-vindo(a) ao sistema</p>
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
          <button type="submit" class="btn btn-login btn-animated px-5 py-2">
            <i class="fas fa-sign-in-alt me-2"></i>Entrar
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
          
          <p class="mt-4">Não tem cadastro? <a href="cadastro.php" class="signup-link">Cadastre-se</a></p>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="text-center py-3">
  <div class="container">
    <p class="m-0">&copy; 2025 - Clínica Nutrição</p>
  </div>
</footer>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Animação suave ao carregar a página
    setTimeout(() => {
      document.querySelector('.login-card').style.opacity = '1';
    }, 100);
    
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