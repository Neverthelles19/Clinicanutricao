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
  <link href="stylecadastroUser.css" rel="stylesheet" />
  <link href="styleUser.css" rel="stylesheet" /> 
  <link href="styleForms.css" rel="stylesheet" />
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
        <br>
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