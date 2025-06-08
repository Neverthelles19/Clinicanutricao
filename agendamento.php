<?php
// agendamento.php
// Inclui o arquivo de conexão com o banco de dados
include 'conexao.php'; // Certifique-se de que este arquivo existe e está configurado corretamente.

// Função para mapear nomes de dias da semana para seus números (0 para Domingo, 1 para Segunda, etc.)
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
        $trimmedDayName = trim($dayName); // Remove espaços em branco
        if (isset($dayMap[$trimmedDayName])) {
            $dayNumbers[] = $dayMap[$trimmedDayName];
        }
    }
    return implode(',', $dayNumbers); // Retorna uma string de números separados por vírgula (ex: "1,3,6")
}

// Pega os serviços do banco de dados
$servicos = mysqli_query($conexao, "SELECT * FROM servicos");
if (!$servicos) {
    die("Erro ao buscar serviços: " . mysqli_error($conexao));
}

// Pega os profissionais do banco de dados, apenas IDs a partir de 2, ordenados por nome
$profissionais = mysqli_query($conexao, "SELECT * FROM profissionais WHERE id >= 2 ORDER BY nome");
if (!$profissionais) {
    die("Erro ao buscar profissionais: " . mysqli_error($conexao));
}

// --- Início do Processamento do Agendamento (quando o formulário é submetido via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e sanitiza os dados do formulário
    $email = mysqli_real_escape_string($conexao, $_POST['email_cliente']);
    $senha = $_POST['senha']; // A senha NÃO deve ser sanitizada com mysqli_real_escape_string antes de password_verify/hash
    $servico_id = (int)$_POST['servico_id'];
    $profissional_id = (int)$_POST['profissional_id'];
    $data = mysqli_real_escape_string($conexao, $_POST['data']);
    $hora = mysqli_real_escape_string($conexao, $_POST['hora']);

    $nome_cliente = ''; // Inicializa as variáveis para nome e telefone
    $telefone_cliente = '';

    // 1. Verifica se o cliente já existe no banco de dados pelo e-mail
    $query_cliente = mysqli_query($conexao, "SELECT * FROM clientes WHERE email = '$email' LIMIT 1");
    if (!$query_cliente) {
        echo "<script>alert('Erro na consulta do cliente: " . mysqli_error($conexao) . "'); history.back();</script>";
        exit;
    }
    $cliente = mysqli_fetch_assoc($query_cliente);

    if ($cliente) {
        // Cliente existe, tenta fazer login com a senha fornecida
        if (!password_verify($senha, $cliente['senha'])) {
            echo "<script>alert('Senha incorreta para o e-mail " . htmlspecialchars($email) . "!'); history.back();</script>";
            exit;
        }
        $nome_cliente = $cliente['nome'];
        $telefone_cliente = $cliente['telefone'];
        $cliente_id = $cliente['id']; // Pega o ID do cliente existente
    } else {
        // Cliente não existe, processa o CADASTRO
        // Verifica se os campos de nome e telefone foram enviados, pois são obrigatórios para um novo cadastro
        if (empty($_POST['nome_cliente']) || empty($_POST['telefone_cliente'])) {
            echo "<script>alert('Por favor, preencha seu nome e telefone para criar sua conta e agendar.'); history.back();</script>";
            exit;
        }

        $nome_cliente = mysqli_real_escape_string($conexao, $_POST['nome_cliente']);
        $telefone_cliente = mysqli_real_escape_string($conexao, $_POST['telefone_cliente']);
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT); // Gera o hash da senha

        $insert_cliente = mysqli_query($conexao, "INSERT INTO clientes (nome, email, telefone, senha) VALUES ('$nome_cliente', '$email', '$telefone_cliente', '$senha_hash')");

        if (!$insert_cliente) {
            echo "<script>alert('Erro ao cadastrar cliente: " . mysqli_error($conexao) . "'); history.back();</script>";
            exit;
        }
        $cliente_id = mysqli_insert_id($conexao); // Pega o ID do cliente recém-cadastrado
    }

    // 2. Verifica se o horário já está agendado para o profissional na data e hora escolhidas
    $existe_agendamento = mysqli_query($conexao, "SELECT * FROM agendamentos WHERE profissional_id = $profissional_id AND data = '$data' AND hora = '$hora'");
    if (!$existe_agendamento) {
        echo "<script>alert('Erro na consulta de agendamento existente: " . mysqli_error($conexao) . "'); history.back();</script>";
        exit;
    }

    if (mysqli_num_rows($existe_agendamento) > 0) {
        echo "<script>alert('Este horário já está agendado para este profissional! Por favor, escolha outro horário.'); history.back();</script>";
        exit;
    }

    // NOVO: Verifica se o profissional existe e tem id >= 2
    $verifica_profissional = mysqli_query($conexao, "SELECT id FROM profissionais WHERE id = $profissional_id AND id >= 2 LIMIT 1");
    if (!$verifica_profissional || mysqli_num_rows($verifica_profissional) == 0) {
        echo "<script>alert('Profissional selecionado não existe!'); history.back();</script>";
        exit;
    }

    // 3. Insere o novo agendamento na tabela `agendamentos`
    $insert_agendamento = mysqli_query($conexao, "INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, nome_cliente, email_cliente, telefone_cliente, data, hora)
        VALUES ($cliente_id, $profissional_id, $servico_id, '$nome_cliente', '$email', '$telefone_cliente', '$data', '$hora')");

    if ($insert_agendamento) {
        echo "<script>alert('Agendamento realizado com sucesso! Em breve, você receberá um e-mail de confirmação.'); window.location.href='agendamento.php';</script>";
    } else {
        echo "<script>alert('Erro ao realizar o agendamento: " . mysqli_error($conexao) . "'); history.back();</script>";
    }
    exit;
}
// --- Fim do Processamento do Agendamento ---
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
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

/* Estilos do calendário */
.calendario-table {
  width: 100%;
  table-layout: fixed;
  user-select: none; /* Impede a seleção de texto no calendário */
  border-collapse: collapse; /* Para remover espaços entre as células da tabela */
}
.calendario-table th, .calendario-table td {
  text-align: center;
  padding: 0.5rem;
  border: 1px solid #e0e0e0; /* Borda leve para as células */
  transition: all 0.2s ease-in-out; /* Transição suave para hover/seleção */
}
.calendario-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}
.calendario-dia {
  cursor: pointer;
  border-radius: 6px; /* Borda arredondada para os dias */
  box-sizing: border-box; /* Garante que padding e border sejam incluídos na largura/altura */
}
.calendario-dia.habilitado {
  background-color: #783f8e; /* Cor para dias disponíveis para agendamento (roxo) */
  color: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Sombra suave */
}
.calendario-dia.habilitado:hover {
  background-color: #5f259f; /* Cor mais escura no hover */
  transform: translateY(-2px); /* Efeito de "levantar" no hover */
}
.calendario-dia.inativo {
  background-color: #e9ecef; /* Cor para dias indisponíveis (passados, fora do limite ou sem profissional) */
  color: #a0a0a0;
  cursor: not-allowed;
}
.calendario-dia.selecionado {
  border: 3px solid #3a0ca3; /* Borda forte para o dia selecionado (azul royal) */
  background-color: #5f259f; /* Mantém a cor de habilitado, mas com borda */
  transform: scale(1.05); /* Pequeno zoom no selecionado */
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
/* Estilo para botões desabilitados do Bootstrap */
.btn.disabled {
    opacity: 0.65;
    pointer-events: none; /* Impede cliques */
}
/* Espaçamento para o select de profissional e calendário no modal */
#modalAgendamento .modal-body > div:first-child {
    margin-bottom: 20px;
}

.card {
  border-left: 4px solid #0d6efd; /* Azul Bootstrap */
  border-radius: 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: scale(1.01);
  box-shadow: 0 0.8rem 1.5rem rgba(0, 0, 0, 0.1);
}

.card-body {
  padding: 1.5rem;
}

.agendarBtn {
  font-weight: 600;
  padding: 0.5rem 1rem;
  border: 2px solid transparent;
  border-radius: 0.375rem;
  background: linear-gradient(90deg, #9600ff, #0f3fa8);
  color: white;
  transition: all 0.3s ease-in-out;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  text-decoration: none;
}

.agendarBtn:hover {
  background: linear-gradient(90deg, #0f3fa8, #9600ff);
  box-shadow: 0 0.25rem 0.75rem rgba(15, 63, 168, 0.3);
  color: white;
}

/*Estilos dos pedidos*/

  h2 {
    font-weight: 600;
  }

  .card {
    border: none;
    border-left: 4px solid #0d6efd; /* Azul Bootstrap */
    border-radius: 8px;
    background-color: #ffffff;
    transition: box-shadow 0.2s ease;
  }

  .card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  .card-body {
    padding: 1.5rem;
  }

  .btn.agendarBtn {
    background-color: #0d6efd;
    color: white;
    border: none;
    font-weight: 500;
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    transition: background-color 0.3s ease, transform 0.2s ease;
  }

  .btn.agendarBtn:hover {
    background-color: #084298;
    transform: translateY(-2px);
  }

  p.text-muted {
    font-size: 0.95rem;
  }

  @media (max-width: 576px) {
    .card-body {
      padding: 1rem;
    }
    .btn.agendarBtn {
      width: 100%;
      text-align: center;
    }
  }


  body {
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

  .info-quadrado {
    flex: 0 0 600px;
    min-height: 520px;
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.07);
    padding: 2.5rem;
  }

  .info-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f3fa8;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.7rem;
  }

  .info-title i {
    font-size: 1.3rem;
    color: #0f3fa8;
  }

  .info-text {
    font-size: 1.1rem;
    line-height: 1.5;
    margin-bottom: 0.6rem;
    color: #333;
  }

  .btn-maps {
    font-weight: 600;
    padding: 0.4rem 1rem;
    border: 2px solid #0f3fa8;
    border-radius: 0.4rem;
    background: transparent;
    color: #0f3fa8;
    transition: all 0.3s ease-in-out;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
  }

  .btn-maps:hover {
    background: linear-gradient(90deg, #9600ff, #0f3fa8);
    color: #fff;
    border-color: transparent;
  }

  .social-link {
    font-size: 1.1rem;
    color: #0f3fa8;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
  }

  .social-link:hover {
    text-decoration: underline;
    color: #9600ff;
  }

  .info-block {
    margin-bottom: 2rem;
  }

  .comodidades-quadrados {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: center;
  }

  .comodidade-card {
    flex: 1 1 280px;
    background: #ffffff; /* Fundo branco */
    border: 1.5px solid #d1c4e9; /* borda lavanda clara */
    color: #222222;
    border-radius: 1rem;
    padding: 2rem 1.5rem;
    box-shadow: 0 4px 10px rgba(15, 63, 168, 0.07);
    text-align: center;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    cursor: default;
    min-width: 260px;
  }

  .comodidade-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(15, 63, 168, 0.15);
    border-color: #9600ff; /* roxo da paleta no hover */
  }

  .comodidade-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #9600ff; /* roxo da paleta */
  }

  .comodidade-titulo {
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
    color: #0f3fa8; /* azul da paleta */
  }

  .comodidade-texto {
    font-size: 1rem;
    color: #555555;
  }

  @media (max-width: 768px) {
    .comodidade-card {
      flex: 1 1 45%;
      min-width: auto;
    }
  }

  @media (max-width: 480px) {
  .comodidade-card {
    flex: 1 1 48%; /* duas caixas por linha */
    min-width: auto;
  }
}

.btn-outline-secondary:hover {
  background-color: #f0f0f0; /* cinza bem claro */
  border-color: #6c757d;
  color: #6c757d;
}

/* Gradiente no texto do brand */
  .gradient-text {
    background: linear-gradient(90deg, #9600ff, #0f3fa8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
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

  /* Garantir navbar responsivo */
  @media (max-width: 991.98px) {
    .navbar .container {
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .ms-auto {
      width: 100%;
      justify-content: flex-end;
    }
  }

  .comodidade-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
  }

  @media (max-width: 991px) {
    .container > div.d-flex {
      flex-direction: column !important;
      align-items: flex-start !important;
    }
    .info-quadrado, .comodidades-quadrados {
      flex: 1 1 100% !important;
      max-width: 100% !important;
      min-height: auto !important;
      margin-bottom: 2rem;
    }
    .comodidades-quadrados {
      grid-template-columns: 1fr !important;
    }
    .comodidade-card {
      font-size: 1rem !important;
      width: 100% !important;
    }
  }

  .p-3 {
        padding: 80px !important;
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
        <h2 class="mb-3 text-primary">Sobre a Nossa Clínica</h2>
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


<!-- Caixa maior - conteúdo -->
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

<!-- Quadros de comodidades!-->
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


<div class="container py-5">
  <h2 class="mb-4 text-start">Agendamento de Serviços de Nutrição</h2>
  <p class="text-start lead">Selecione o serviço desejado para iniciar seu agendamento.</p>

  <div class="d-flex flex-column gap-4 align-items-start" style="max-width: 700px;">
    <?php
    if (mysqli_num_rows($servicos) > 0) {
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
                <button class="btn btn-outline-primary agendarBtn"
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
              // Reseta o ponteiro do resultado da consulta de profissionais para o início
              mysqli_data_seek($profissionais, 0);
              if (mysqli_num_rows($profissionais) > 0) {
                while($p = mysqli_fetch_assoc($profissionais)):
                    // ATENÇÃO: Se você mudou a coluna 'dias_disponiveis' para números no banco,
                    // REMOVA a chamada a mapDaysToNumbers() e use $p['dias_disponiveis'] diretamente.
                    // Caso contrário, MANTENHA a função mapDaysToNumbers() aqui.
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
                <button id="btnPrevMes" class="btn btn-outline-secondary btn-sm">&lt; Mês Anterior</button>
                <h5 id="tituloMesAno" class="mb-0 text-primary"></h5>
                <button id="btnNextMes" class="btn btn-outline-secondary btn-sm">Próximo Mês &gt;</button>
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

        <form method="POST" id="formAgendamento" style="display: none;">
          <input type="hidden" name="servico_id" id="inputServicoId" />
          <input type="hidden" name="profissional_id" id="inputProfissionalId" />
          <input type="hidden" name="data" id="inputData" />
          <input type="hidden" name="hora" id="inputHora" />
        </form>
      </div>

  <div id="formCadastro" class="signup-container">
  <div class="signup-card">
    <div class="signup-header">
      <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Cadastro</h2>
      <p class="text-white-50 mt-2 mb-0">Crie sua conta para agendar:</p>
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
            <input type="password" id="senha_cadastro" class="form-control" name="senha_cadastro">
            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility(this, 'senha_cadastro')"></i>
        </div>
        </div>

        <div class="text-center mt-5">
            <p class="mt-4">Já tem cadastro? <a href="#" id="linkFazerLogin" class="login-link">Faça Login</a></p>
          <button type="submit" class="btn btn-signup btn-animated px-5 py-2">
            <i class="fas fa-user-plus me-2"></i>Cadastrar
          </button>
          
        </div>
      </form>
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
                <?php if (!empty($mensagemErro)): ?>
                    <div class="error-message mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle text-danger me-3"></i>
                            <p class="mb-0"><?= htmlspecialchars($mensagemErro) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

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
                        <input type="password" id="senha_login" class="form-control" name="senha_login">
                        <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility(this, 'senha_login')"></i>
                    </div>
                    <div class="text-end mt-2">
                        <a href="recuperar_senha.php" class="small text-muted">Esqueceu sua senha?</a>
                    </div>
                </div>

                

                <div class="mt-3 text-center">
                    <div class="position-relative mb-4">
                        <hr>
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
                    <button type="submit" class="btn btn-signup btn-animated px-5 py-2">
                        <i class="fas fa-user-plus me-2"></i>Entrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!--<button type="submit" class="btn-login btn-signup btn-animated px-5 py-2" id="btnConfirmarAgendamento">Confirmar Agendamento</button>!-->
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

    <div>&copy; 2025 Clínica Nutrição. Todos os direitos reservados.</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>