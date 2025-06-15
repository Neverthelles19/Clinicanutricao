<?php
// session_start();

include 'conexao.php';

if (!$conexao) {
    die("Erro de conexão com o banco de dados: " . mysqli_connect_error());
}

function mapDaysToNumbers($dayNamesString) {
    $dayMap = [
        'Domingo' => '0',
        'Segunda' => '1',
        'Terça'   => '2',
        'Quarta'  => '3',
        'Quinta'  => '4',
        'Sexta'   => '5',
        'Sábado'  => '6'
    ];
    $dayNames = explode(',', $dayNamesString);
    $dayNumbers = [];
    foreach ($dayNames as $dayName) {
        $trimmedDayName = trim($dayName);
        if (isset($dayMap[$trimmedDayName])) {
            $dayNumbers[] = $dayMap[$trimmedDayName];
        }
    }
    return implode(',', $dayNumbers);
}

function buscarClientePorEmail($conexao, $email) {
    $stmt = $conexao->prepare("SELECT * FROM clientes WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta do cliente: ' . $conexao->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function cadastrarCliente($conexao, $nome, $email, $telefone, $senha) {
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conexao->prepare("INSERT INTO clientes (nome, email, telefone, senha) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Erro ao preparar cadastro de cliente: ' . $conexao->error);
    }
    $stmt->bind_param("ssss", $nome, $email, $telefone, $senha_hash);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao cadastrar cliente: ' . $stmt->error);
    }
    return $conexao->insert_id;
}

function horarioOcupado($conexao, $profissional_id, $data, $hora) {
    $stmt = $conexao->prepare("SELECT id FROM agendamentos WHERE profissional_id = ? AND data = ? AND hora = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta de agendamento existente: ' . $conexao->error);
    }
    $stmt->bind_param("iss", $profissional_id, $data, $hora);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function profissionalValido($conexao, $profissional_id) {
    $stmt = $conexao->prepare("SELECT id FROM profissionais WHERE id = ? AND id >= 2 LIMIT 1");
    if (!$stmt) {
        error_log('Erro ao preparar consulta de profissional válido: ' . $conexao->error);
        return false;
    }
    $stmt->bind_param("i", $profissional_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function inserirAgendamento($conexao, $cliente_id, $profissional_id, $servico_id, $nome_cliente, $email, $telefone, $data, $hora) {
    $stmt = $conexao->prepare(
        "INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, nome_cliente, email_cliente, telefone_cliente, data, hora)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        throw new Exception('Erro ao preparar inserção de agendamento: ' . $conexao->error);
    }
    $stmt->bind_param("iiisssss", $cliente_id, $profissional_id, $servico_id, $nome_cliente, $email, $telefone, $data, $hora);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao realizar o agendamento: ' . $stmt->error);
    }
    return true;
}

$servicos = mysqli_query($conexao, "SELECT * FROM servicos");
if (!$servicos) {
    die("Erro ao buscar serviços: " . mysqli_error($conexao));
}

$profissionais = mysqli_query($conexao, "SELECT * FROM profissionais ORDER BY nome");
if (!$profissionais) {
    die("Erro ao buscar profissionais: " . mysqli_error($conexao));
}

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email_cliente']);
        $senha = $_POST['senha'];
        $servico_id = (int)$_POST['servico_id'];
        $profissional_id = (int)$_POST['profissional_id'];
        $data = trim($_POST['data']);
        $hora = trim($_POST['hora']);

        $nome_cliente = '';
        $telefone_cliente = '';
        $cliente_id = null;

        // Verifica se é tentativa de cadastro ou login
        $is_cadastro_attempt = !empty(trim($_POST['nome_cliente'] ?? '')) || !empty(trim($_POST['telefone_cliente'] ?? ''));

        // Depuração (remova após verificar valores)
        // var_dump($email, $senha); exit;

        $cliente = buscarClientePorEmail($conexao, $email);

        if ($cliente) {
            // Cliente existente: login
            if (!password_verify($senha, $cliente['senha'])) {
                echo "<script>alert('Senha incorreta para o e-mail " . htmlspecialchars($email) . "!'); history.back();</script>";
                exit;
            }
            $nome_cliente = $cliente['nome'];
            $telefone_cliente = $cliente['telefone'];
            $cliente_id = $cliente['id'];
        } else {
            // Novo cliente: cadastro
            if (!$is_cadastro_attempt) {
                // Não tentou criar conta
                echo "<script>alert('E-mail não cadastrado. Por favor, preencha nome e telefone para criar sua conta.'); history.back();</script>";
                exit;
            }
            // Validação obrigatória de senha no cadastro
            if (empty($senha)) {
                echo "<script>alert('Para se cadastrar, você deve criar uma senha.'); history.back();</script>";
                exit;
            }
            $nome_cliente = trim($_POST['nome_cliente']);
            $telefone_cliente = trim($_POST['telefone_cliente']);

            if (empty($nome_cliente) || empty($telefone_cliente)) {
                echo "<script>alert('Para se cadastrar, preencha seu nome e telefone.'); history.back();</script>";
                exit;
            }

            // Cadastra cliente
            $cliente_id = cadastrarCliente($conexao, $nome_cliente, $email, $telefone_cliente, $senha);
        }

        // Verificações de agendamento
        if (horarioOcupado($conexao, $profissional_id, $data, $hora)) {
            echo "<script>alert('Este horário já está agendado para este profissional! Por favor, escolha outro horário.'); history.back();</script>";
            exit;
        }

        if (!profissionalValido($conexao, $profissional_id)) {
            echo "<script>alert('Profissional selecionado não existe ou é inválido!'); history.back();</script>";
            exit;
        }

        if (is_null($cliente_id)) {
            echo "<script>alert('Erro interno: ID do cliente não definido.'); history.back();</script>";
            exit;
        }

        inserirAgendamento($conexao, $cliente_id, $profissional_id, $servico_id, $nome_cliente, $email, $telefone_cliente, $data, $hora);

        echo "<script>alert('Agendamento realizado com sucesso! Em breve, você receberá um e-mail de confirmação.'); window.location.href='agendamento.php';</script>";
        exit;

    } catch (Exception $e) {
        echo "<script>alert('Erro: " . addslashes($e->getMessage()) . "'); history.back();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<title>Agendamento de Serviços</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link href="styleUser.css" rel="stylesheet" />
<link href="stylecadastroUser.css" rel="stylesheet" />
<link rel="stylesheet" href="style.css">
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
    <div class="ms-auto d-flex">
      <a href="#" class="btn btn-sm rounded-pill px-3 me-2 btn-inicio">
        <i class="fas fa-home me-1"></i> Início
      </a>
      <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
        <i class="fas fa-sign-out-alt me-1"></i> Sair
      </a>
    </div>
  </div>
</nav>

    <section class="py-5">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-md-6">
        <img src="clinica.jpg" alt="Imagem da Clínica de Nutrição" class="img-fluid rounded shadow">
      </div>
      <div class="col-md-6">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="text-primary mb-0">Nossa Clínica</h2>
          <a href="#agendamento" class="btn agendarBtn ms-3">Agendar agora</a>
        </div>
        <div class="d-flex align-items-center mb-3">
          <span class="me-2 fs-5 fw-bold text-warning">★ 4.7</span>
          <small class="text-muted">Baseado em 321 avaliações</small>
        </div>
        <p class="lead">Cuidar da sua saúde é a nossa prioridade.</p>
        <p>
          Fundada com o compromisso de promover o bem-estar e a qualidade de vida, nossa clínica oferece serviços personalizados de nutrição para todas as idades. Contamos com profissionais qualificados, atendimento humanizado e estrutura moderna.
        </p>
        <p>
          Venha conhecer e descubra como podemos te ajudar a alcançar seus objetivos com saúde e equilíbrio!
        </p>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container" style="max-width: 1320px;">
    <div class="d-flex gap-4" style="justify-content: flex-start;">

<div class="info-quadrado">

  <h3 class="text-dark mb-4" style="font-weight: 700; font-size: 2.2rem; border-bottom: 3px solid #0f3fa8; padding-bottom: 0.3rem;">
    Informações da Clínica
  </h3>

  <div class="info-block">
    <div class="info-title">
      <i class="bi bi-geo-alt-fill"></i> Localização
    </div>
    <p class="info-text">
      Rua Nutrição, 123, Bairro Saúde, Cidade - Estado
    </p>
    <a href="https://www.google.com/maps" target="_blank" class="btn-maps">
      Ver no Google Maps <i class="bi bi-arrow-up-right"></i>
    </a>
  </div>

  <div class="info-block">
    <div class="info-title">
      <i class="bi bi-clock-fill"></i> Horários de Funcionamento
    </div>
    <ul class="list-unstyled info-text">
      <li>Segunda a Sexta: 08h00 - 18h00</li>
      <li>Sábado: 09h00 - 13h00</li>
      <li>Domingo: Fechado</li>
    </ul>
  </div>

  <div class="info-block">
    <div class="info-title">
      <i class="bi bi-credit-card-2-front-fill"></i> Formas de Pagamento
    </div>
    <p class="info-text">
      Aceitamos cartão de crédito, débito, Pix e dinheiro.
    </p>
  </div>

  <div class="info-block">
    <div class="info-title">
      <i class="bi bi-share-fill"></i> Redes Sociais
    </div>
    <div class="d-flex flex-column mt-2 gap-2">
      <a href="https://instagram.com/clinicanutricao" target="_blank" class="social-link">
        <i class="bi bi-instagram"></i> Instagram
      </a>
      <a href="https://facebook.com/clinicanutricao" target="_blank" class="social-link">
        <i class="bi bi-facebook"></i> Facebook
      </a>
      <a href="https://twitter.com/clinicanutricao" target="_blank" class="social-link">
        <i class="bi bi-twitter"></i> Twitter
      </a>
    </div>
  </div>

</div>

<div class="comodidades-quadrados">
  <div class="comodidade-card">
    <div class="comodidade-icon">🅿️</div>
    <div class="comodidade-titulo">Estacionamento</div>
    <div class="comodidade-texto">Gratuito no local</div>
  </div>

  <div class="comodidade-card">
    <div class="comodidade-icon">📶</div>
    <div class="comodidade-titulo">Wi-Fi</div>
    <div class="comodidade-texto">Internet rápida e gratuita</div>
  </div>

  <div class="comodidade-card">
    <div class="comodidade-icon">🧒</div>
    <div class="comodidade-titulo">Atendimento Infantil</div>
    <div class="comodidade-texto">Ambiente acolhedor para crianças</div>
  </div>

  <div class="comodidade-card">
    <div class="comodidade-icon">♿</div>
    <div class="comodidade-titulo">Acessibilidade</div>
    <div class="comodidade-texto">Estrutura adaptada para todos</div>
  </div>

  <div class="comodidade-card">
    <div class="comodidade-icon">💻</div>
    <div class="comodidade-titulo">Atendimento Online</div>
    <div class="comodidade-texto">Consultas via videochamada</div>
  </div>

  <div class="comodidade-card">
    <div class="comodidade-icon">📋</div>
    <div class="comodidade-titulo">Consultas Personalizadas</div>
    <div class="comodidade-texto">Planos alimentares exclusivos</div>
  </div>
</div>

</section>

<section id="agendamento" class="py-5">
  <div class="container py-5">
    <h2 class="mb-4 text-start">Agendamento de Serviços de Nutrição</h2>
    <p class="text-start lead">Selecione o serviço desejado para iniciar seu agendamento.</p>

    <div class="d-flex flex-column gap-4 align-items-start" style="max-width: 700px;">
      <?php
      if (mysqli_num_rows($servicos) > 0) {
          mysqli_data_seek($servicos, 0);
          while($s = mysqli_fetch_assoc($servicos)): ?>
            <div class="w-100">
              <div class="card shadow-sm border-start border-4 border-primary">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between text-start gap-3">
                  <div>
                    <h5 class="mb-2 text-primary"><?= htmlspecialchars($s['servico']) ?></h5>
                    <p class="mb-0 text-muted">
                      <strong>Duração:</strong> <?= intval($s['duracao']) ?> min<br>
                      <strong>Valor:</strong> R$ <?= number_format($s['valor'], 2, ',', '.') ?>
                    </p>
                  </div>
                  <div class="mt-3 mt-md-0">
                    <button type="button" class="btn btn-outline-primary agendarBtn"
                            data-id="<?= $s['id'] ?>"
                            data-servico="<?= htmlspecialchars($s['servico']) ?>">
                      Agendar Agora
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile;
      } else {
          echo "<p class='text-start'>Nenhum serviço disponível no momento.</p>";
      }
      ?>
    </div>
  </div>
</section>

<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agendamento: <span id="nomeServicoModal" class="text-info"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">

        <div>
          <label for="selectProfissional" class="form-label fw-bold">Escolha o Profissional:</label>
          <select id="selectProfissional" class="form-select mb-3">
            <option value="">-- Selecione um especialista --</option>
            <?php
              mysqli_data_seek($profissionais, 0);
              if (mysqli_num_rows($profissionais) > 0) {
                while($p = mysqli_fetch_assoc($profissionais)):
                    $dias_numericos = mapDaysToNumbers($p['dias_disponiveis']);
            ?>
            <option value="<?= $p['id'] ?>"
              data-dias='<?= json_encode(explode(",", $dias_numericos)) ?>'
              data-hora-inicio="<?= htmlspecialchars($p['hora_inicio']) ?>"
              data-hora-fim="<?= htmlspecialchars($p['hora_fim']) ?>"
            >
              <?= htmlspecialchars($p['nome']) ?> - **<?= htmlspecialchars($p['especialidade']) ?>**
            </option>
            <?php endwhile;
              } else {
                echo "<option value=''>Nenhum profissional disponível no momento.</option>";
              }
            ?>
          </select>
        </div>

        <div id="calendarioContainer">
            <h6 class="text-center mt-4 mb-2">Selecione uma data no calendário abaixo:</h6>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <button id="btnPrevMes" class="btn btn-outline-secondary btn-sm">< Mês Anterior</button>
                <h5 id="tituloMesAno" class="mb-0 text-primary"></h5>
                <button id="btnNextMes" class="btn btn-outline-secondary btn-sm">Próximo Mês ></button>
            </div>
            <div id="calendario"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm rounded-4">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Confirme seu Agendamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      
      <div class="modal-body pt-2">
        <ul class="list-unstyled mb-4">
          <li class="mb-3">
            <span class="text-muted small">Serviço</span><br>
            <span id="confirmServico" class="fw-semibold text-dark"></span>
          </li>
          <li class="mb-3">
            <span class="text-muted small">Profissional</span><br>
            <span id="confirmProfissional" class="fw-semibold text-dark"></span>
          </li>
          <li>
            <span class="text-muted small">Data</span><br>
            <span id="confirmData" class="fw-semibold text-dark"></span>
          </li>
        </ul>

        <div class="mb-3">
          <label for="selectHora" class="form-label fw-semibold">Escolha o horário:</label>
          <select id="selectHora" class="form-select" style="max-width: 200px;">
            <option value="">Selecione um horário</option>
          </select>
        </div>

        <form method="POST" id="formAgendamento">
          <input type="hidden" name="servico_id" id="inputServicoId" />
          <input type="hidden" name="profissional_id" id="inputProfissionalId" />
          <input type="hidden" name="data" id="inputData" />
          <input type="hidden" name="hora" id="inputHora" />

          <input type="hidden" name="is_cadastro_form" id="isCadastroForm" value="0">

          <div id="formCadastro" class="signup-container">
            <div class="signup-card">
              <div class="signup-header">
                <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Cadastro</h2>
                <p class="text-white-50 mt-2 mb-0">Crie sua conta para agendar:</p>
              </div>

              <div class="signup-body">
                <?php if (!empty($mensagem) && $tipoMensagem == 'info'): ?>
                  <div class="alert alert-info" role="alert">
                    <div class="d-flex align-items-center">
                      <i class="fas fa-info-circle text-info me-3"></i>
                      <p class="mb-0"><?= htmlspecialchars($mensagem) ?></p>
                    </div>
                  </div>
                <?php endif; ?>

                <div>
                  <div class="mb-4">
                    <label for="nome_cliente_cadastro" class="form-label">Seu Nome Completo</label>
                    <div class="input-icon-wrapper">
                      <input type="text" class="form-control" name="nome_cliente" id="nome_cliente_cadastro" placeholder="Nome Sobrenome" required>
                      <i class="fas fa-user input-icon"></i>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label for="telefone_cliente_cadastro" class="form-label">Telefone (com DDD)</label>
                    <div class="input-icon-wrapper">
                      <input type="tel" class="form-control" name="telefone_cliente" id="telefone_cliente_cadastro" placeholder="(XX) XXXXX-XXXX" pattern="\(\d{2}\) \d{4,5}-\d{4}" title="Formato: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX" required>
                      <i class="fas fa-phone input-icon"></i>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label for="email_cliente_cadastro" class="form-label">E-mail</label>
                    <div class="input-icon-wrapper">
                      <input type="email" class="form-control" name="email_cliente" id="email_cliente_cadastro" placeholder="seu.email@exemplo.com" required>
                      <i class="fas fa-envelope input-icon"></i>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label for="senha_cadastro" class="form-label">Crie sua Senha</label>
                    <div class="input-icon-wrapper">
                      <input type="password" id="senha_cadastro" class="form-control" name="senha" required>
                      <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility(this, 'senha_cadastro')"></i>
                    </div>
                  </div>

                  <div class="text-center mt-5">
                      <p class="mt-4">Já tem cadastro? <a href="#" id="linkFazerLogin" class="login-link">Faça Login</a></p>
                      <button type="submit" class="btn btn-signup btn-animated px-5 py-2">
                          <i class="fas fa-user-plus me-2"></i>Cadastrar e Agendar
                      </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div id="formLogin" style="display:none;" class="login-container">
            <div class="login-card">
              <div class="login-header">
                <h2 class="mb-0"><i class="fas fa-lock me-2"></i>Login</h2>
                <p class="text-white-50 mt-2 mb-0">Bem-vindo(a) ao sistema</p>
              </div>

              <div class="login-body">
                <div>
                  <div class="mb-4">
                    <label for="email_cliente_login" class="form-label">Email</label>
                    <div class="input-icon-wrapper">
                      <input type="email" name="email_cliente" class="form-control" id="email_cliente_login" required> 
                      <i class="fas fa-envelope input-icon"></i>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label for="senha_login" class="form-label">Senha</label>
                    <div class="input-icon-wrapper">
                      <input type="password" id="senha_login" class="form-control" name="senha" required>
                      <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility(this, 'senha_login')"></i>
                    </div>
                    <div class="text-end mt-2">
                      <a href="recuperar_senha.php" class="small text-muted">Esqueceu sua senha?</a>
                    </div>
                  </div>

                  <div class="mt-3 text-center">
                    <button type="submit" class="btn btn-signup btn-animated px-5 py-2">
                      <i class="fas fa-sign-in-alt me-2"></i>Login e Agendar
                    </button>

                    <div class="position-relative my-4"> <hr>
                      <span class="position-absolute top-0 start-50 translate-middle bg-white px-3 text-muted">ou</span>
                    </div>

                    <div class="d-grid gap-2">
                      <a href="#" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
                        <i class="fab fa-google me-2"></i> Entrar com Google
                      </a>
                      <a href="#" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
                        <i class="fab fa-facebook-f me-2"></i> Entrar com Facebook
                      </a>
                    </div>

                    <p class="mt-4">Não tem cadastro? <a href="#" id="linkFazerCadastro" class="signup-link">Cadastre-se</a></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

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

    <div>© 2025 Clínica Nutrição. Todos os direitos reservados.</div>
  </div>
  <script>
  function startApp() {
    gapi.load('auth2', function() {
      gapi.auth2.init({
        client_id: '523526330482-sp2asb3i98mp2auvq3iojp2isl23o7fl.apps.googleusercontent.com',
        cookiepolicy: 'single_host_origin'
      });
    });
  }
</script>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>