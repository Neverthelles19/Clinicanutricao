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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    :root {
      --primary-color: #6366f1;
      --primary-dark: #4f46e5;
      --primary-light: #a5b4fc;
      --secondary-color: #ec4899;
      --secondary-light: #f9a8d4;
      --dark-color: #1e293b;
      --light-color: #f8fafc;
      --gray-color: #64748b;
      --success-color: #10b981;
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
    
    .card-form {
      max-width: 800px;
      margin: 40px auto;
      border-radius: var(--border-radius);
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: var(--box-shadow);
      overflow: hidden;
      transform: translateY(0);
      transition: transform 0.5s ease;
    }
    
    .card-form:hover {
      transform: translateY(-5px);
    }
    
    .card {
      border-radius: var(--border-radius);
      transition: all 0.3s ease;
      opacity: 0.9;
      transform: translateY(20px);
      transition: opacity 0.5s ease, transform 0.5s ease, box-shadow 0.3s ease;
    }
    
    .card:hover {
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .card .section-title {
      margin-bottom: 15px;
    }
    
    .card-header {
      background: linear-gradient(135deg, #9600ff, #0f3fa8);
      color: white;
      padding: 25px;
      border-bottom: none;
      position: relative;
      overflow: hidden;
    }
    
    .card-header::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 60%);
      transform: rotate(30deg);
    }
    
    .card-body {
      padding: 30px;
      position: relative;
      z-index: 1;
    }
    
    .form-control {
      border-radius: var(--border-radius);
      padding: 12px 15px;
      border: 2px solid #e2e8f0;
      font-size: 1rem;
      transition: var(--transition);
      background-color: rgba(255, 255, 255, 0.8);
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
    
    .btn-primary {
      background: linear-gradient(135deg, #9600ff, #0f3fa8);
      border: none;
      border-radius: 50px;
      padding: 12px 30px;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: var(--transition);
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
      margin: 30px;
    }
    
    .btn-primary:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.6);
      background: linear-gradient(135deg,  #0f3fa8, #9600ff);
    }
    
    .form-check-input:checked {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .alert {
      border-radius: var(--border-radius);
      border: none;
      background: rgba(99, 102, 241, 0.1);
      border-left: 4px solid var(--primary-color);
      padding: 15px;
      position: relative;
      overflow: hidden;
    }
    
    .alert-icon-circle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
    }
    
    .alert-background-pattern {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      width: 30%;
      background-image: radial-gradient(circle, rgba(255,255,255,0.2) 1px, transparent 1px);
      background-size: 10px 10px;
      opacity: 0.3;
      z-index: -1;
    }
    
    .section-title {
      position: relative;
      display: inline-block;
      margin-bottom: 30px;
      font-weight: 600;
      color: var(--primary-color);
    }
    
    .section-title:after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 50px;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    .day-selector {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 15px;
}

.day-item {
  cursor: pointer;
  flex: 1;
  min-width: 100px;
  background-color: rgba(255, 255, 255, 0.8);
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px;
  text-align: center;
  transition: 0.3s;
  user-select: none;
}

.day-item:hover {
  background-color: rgba(99, 102, 241, 0.05);
  border-color: var(--primary-light);
}

.day-item.selected {
  background-color: var(--primary-light);
  color: white;
  font-weight: 500;
  border-color: var(--primary-color);
}

    
    .time-inputs {
      display: flex;
      gap: 20px;
    }
    
    .time-input {
      flex: 1;
    }
    
    footer {
      background: linear-gradient(135deg, #9600ff, #0f3fa8);
      color: white;
      padding: 20px 0;
      margin-top: 60px;
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
    
    .input-icon-wrapper {
      position: relative;
    }
    
    .input-icon {
      position: absolute;
      top: 50%;
      left: 15px;
      transform: translateY(-50%);
      color: var(--gray-color);
      font-size: 1.1rem;
      transition: var(--transition);
    }
    
    .icon-input {
      padding-left: 45px;
    }
    
    .icon-input:focus + .input-icon {
      color: var(--primary-color);
    }
    
    .floating-shapes {
      position: absolute;
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
      background: linear-gradient(45deg, var(--primary-light), var(--secondary-light));
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
    
    /* Animação para o botão */
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
      70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
      100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
    }
    
    .btn-animated {
      animation: pulse 2s infinite;
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


<main class="container">
  <div class="card card-form">
    <div class="card-header text-center">
      <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Cadastrar Profissional</h2>
      <p class="text-white-50 mt-2 mb-0">Adicione um novo profissional à equipe</p>
    </div>
    <div class="card-body p-4">
      <?php if ($mensagem): ?>
        <div class="alert alert-info alert-dismissible fade show position-relative overflow-hidden" role="alert">
          <div class="d-flex align-items-center">
            <div class="alert-icon-circle me-3">
              <i class="fas fa-info-circle"></i>
            </div>
            <div>
              <h6 class="alert-heading mb-1">Notificação</h6>
              <p class="mb-0"><?= htmlspecialchars($mensagem) ?></p>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          <div class="alert-background-pattern"></div>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="row mb-4">
          <div class="col-12">
            <div class="card bg-light border-0 shadow-sm p-3">
              <div class="card-body">
                <h5 class="section-title"><i class="fas fa-id-card me-2"></i>Informações Pessoais</h5>
                <div class="mb-4 mt-4">
                  <label class="form-label">Nome do Profissional</label>
                  <div class="input-icon-wrapper">
                    <input type="text" name="nome" class="form-control icon-input" required>
                    <i class="fas fa-user input-icon"></i>
                  </div>
                </div>

                <div class="mb-2">
                  <label class="form-label">Especialidade</label>
                  <div class="input-icon-wrapper">
                    <input type="text" name="especialidade" class="form-control icon-input">
                    <i class="fas fa-stethoscope input-icon"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-12">
            <div class="card bg-light border-0 shadow-sm p-3">
              <div class="card-body">
                <h5 class="section-title"><i class="fas fa-calendar-alt me-2"></i>Disponibilidade</h5>
                <p class="text-muted mb-4">Selecione os dias em que o profissional estará disponível</p>
                
                <div class="day-selector">
                  <?php
                  $dias = ["Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
                  foreach ($dias as $dia) {
                    $id = strtolower(str_replace(["ã", "ç", "á", "é", "í", "ó", "ú", " "], ["a", "c", "a", "e", "i", "o", "u", "-"], $dia));
                    echo "<div class='day-item' data-dia='$dia' id='dia-$id'>$dia
                            <input type='checkbox' name='dias_disponiveis[]' value='$dia' hidden>
                          </div>";
                  }
                  ?>
                </div>

                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-12">
            <div class="card bg-light border-0 shadow-sm p-3">
              <div class="card-body">
                <h5 class="section-title"><i class="fas fa-clock me-2"></i>Horário de Atendimento</h5>
                <p class="text-muted mb-4">Defina o período de atendimento do profissional</p>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Hora de Início</label>
                    <div class="input-icon-wrapper">
                      <input type="time" name="hora_inicio" class="form-control icon-input" required>
                      
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Hora de Fim</label>
                    <div class="input-icon-wrapper">
                      <input type="time" name="hora_fim" class="form-control icon-input" required>
                      
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        

        <div class="text-center mt-5">
          <button type="submit" class="btn btn-primary btn-animated px-5 py-3">
            <i class="fas fa-save me-2"></i>Cadastrar Profissional
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<footer class="mt-5 text-white footer-gradiente py-4 text-center">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-3 mb-md-0 text-center text-md-start">
        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-1">
          <i class="fas fa-heartbeat me-2"></i>
          <h5 class="fw-bold mb-0">Clínica Nutrição</h5>
        </div>
        <p class="mb-0">Cuidando da sua saúde com responsabilidade e equilíbrio.</p>
      </div>
      <div class="col-md-4 mb-3 mb-md-0">
        <h6 class="fw-bold">Contato</h6>
        <p class="mb-1"><i class="fas fa-phone-alt me-2"></i>(00) 1234-5678</p>
        <p class="mb-1"><i class="fas fa-envelope me-2"></i>contato@aims.com</p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Adiciona efeito de destaque nos campos ao focar
  document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('focus', function() {
      this.closest('.card').classList.add('shadow');
    });
    
    input.addEventListener('blur', function() {
      this.closest('.card').classList.remove('shadow');
    });
  });
  
  // Animação suave ao carregar a página
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.card').forEach((card, index) => {
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 100 * index);
    });
  });


  //logica de selecionar os botoes dos dias da semana
  document.querySelectorAll('.day-item').forEach(item => {
    item.addEventListener('click', () => {
      item.classList.toggle('selected');
      const checkbox = item.querySelector('input[type="checkbox"]');
      checkbox.checked = !checkbox.checked;
    });
  });
</script>
</body>
</html>