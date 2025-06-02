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
<style>
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
</style>
</head>
<body class="bg-light">
<div class="container py-5">
<h2 class="mb-4 text-center">Agendamento de Serviços de Nutrição</h2>
<p class="text-center lead">Selecione o serviço desejado para iniciar seu agendamento.</p>
<div class="row justify-content-center">
    <?php
    // Verifica se há serviços para exibir
    if (mysqli_num_rows($servicos) > 0) {
        while($s = mysqli_fetch_assoc($servicos)): ?>
        <div class="col-md-4 col-sm-6 mb-4">
          <div class="card shadow h-100">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title text-primary text-center"><?= htmlspecialchars($s['servico']) ?></h5>
              <p class="card-text flex-grow-1 text-center">
                Duração: **<?= intval($s['duracao']) ?> min**<br>
                Valor: **R$ <?= number_format($s['valor'], 2, ',', '.') ?>**
              </p>
              <button class="btn btn-primary mt-auto agendarBtn w-100"
                      data-id="<?= $s['id'] ?>"
                      data-servico="<?= htmlspecialchars($s['servico']) ?>">
                Agendar Agora
              </button>
            </div>
          </div>
        </div>
        <?php endwhile;
    } else {
        echo "<p class='text-center'>Nenhum serviço disponível no momento.</p>";
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
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirme seu Agendamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p>Serviço: <strong id="confirmServico" class="text-primary"></strong></p>
        <p>Profissional: <strong id="confirmProfissional" class="text-success"></strong></p>
        <p>Data: <strong id="confirmData" class="text-info"></strong></p>

        <div class="mb-3">
          <label for="selectHora" class="form-label fw-bold">Escolha o horário:</label>
          <select id="selectHora" class="form-select" style="max-width: 200px;">
            <option value="">Selecione um horário</option>
          </select>
        </div>

        <form method="POST" id="formAgendamento" style="display:none;" class="mt-4 p-3 border rounded bg-light">
            <input type="hidden" name="servico_id" id="inputServicoId" />
            <input type="hidden" name="profissional_id" id="inputProfissionalId" />
            <input type="hidden" name="data" id="inputData" />
            <input type="hidden" name="hora" id="inputHora" />

            <div id="formCadastro">
                <h6 class="mb-3 text-center">Crie sua conta para agendar:</h6>
                <div class="mb-3">
                    <label for="nome_cliente_cadastro" class="form-label">Seu Nome Completo:</label>
                    <input type="text" class="form-control" name="nome_cliente" id="nome_cliente_cadastro" placeholder="Nome Sobrenome" />
                </div>
                <div class="mb-3">
                    <label for="email_cliente_cadastro" class="form-label">E-mail:</label>
                    <input type="email" class="form-control" name="email_cliente" id="email_cliente_cadastro" placeholder="seu.email@exemplo.com" />
                </div>
                <div class="mb-3">
                    <label for="telefone_cliente_cadastro" class="form-label">Telefone (com DDD):</label>
                    <input type="tel" class="form-control" name="telefone_cliente" id="telefone_cliente_cadastro" placeholder="(XX) XXXXX-XXXX" pattern="\(\d{2}\) \d{4,5}-\d{4}" title="Formato: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX" />
                </div>
                <div class="mb-3">
                    <label for="senha_cadastro" class="form-label">Crie sua Senha:</label>
                    <input type="password" class="form-control" name="senha" id="senha_cadastro" placeholder="Mínimo 6 caracteres" />
                </div>
                <p class="text-center">
                    Já tem cadastro? <a href="#" id="linkFazerLogin">Faça Login</a>
                </p>
            </div>

            <div id="formLogin" style="display:none;">
                <h6 class="mb-3 text-center">Acesse sua conta:</h6>
                <div class="mb-3">
                    <label for="email_cliente_login" class="form-label">E-mail:</label>
                    <input type="email" class="form-control" name="email_cliente" id="email_cliente_login" placeholder="seu.email@exemplo.com" />
                </div>
                <div class="mb-3">
                    <label for="senha_login" class="form-label">Senha:</label>
                    <input type="password" class="form-control" name="senha" id="senha_login" placeholder="Sua senha" />
                </div>
                <p class="text-center">
                    Não tem cadastro? <a href="#" id="linkFazerCadastro">Crie sua conta</a>
                </p>
            </div>

            <button type="submit" class="btn btn-success w-100 mt-3" id="btnConfirmarAgendamento">Confirmar Agendamento</button>
        </form>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Variáveis globais
let mesAtualOffset = 0; // 0 é o mês atual, 1 é o próximo, etc.
let diasAtendimentoProfissional = [];
let horaInicio = "09:00";
let horaFim = "17:00";
let servicoSelecionadoId = null;
let profissionalSelecionadoId = null; // Inicialmente nulo, pois nenhum profissional está selecionado
let nomeServico = '';
let nomeProfissional = '';
let dataSelecionadaGlobal = null;

// Instâncias dos modais do Bootstrap
let modalAgendamentoInstancia;
let modalConfirmacaoInstancia;


document.addEventListener('DOMContentLoaded', function() {
    // Inicializa as instâncias dos modais quando o DOM estiver carregado
    modalAgendamentoInstancia = new bootstrap.Modal(document.getElementById('modalAgendamento'));
    modalConfirmacaoInstancia = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
});


// Função para renderizar o calendário
function criarCalendario(mesOffset) {
    const hoje = new Date();
    // Cria uma data para o 1º dia do mês que queremos exibir
    const dataExibicao = new Date(hoje.getFullYear(), hoje.getMonth() + mesOffset, 1);

    const ano = dataExibicao.getFullYear();
    const mes = dataExibicao.getMonth(); // Mês indexado em 0 (0=Janeiro, 11=Dezembro)

    const primeiroDiaMes = new Date(ano, mes, 1);
    const ultimoDiaMes = new Date(ano, mes + 1, 0).getDate(); // Último dia do mês (ex: 31 para janeiro)
    const diaSemanaInicio = primeiroDiaMes.getDay(); // Dia da semana do 1º dia do mês (0 para domingo, 6 para sábado)

    let html = '<table class="calendario-table"><thead><tr>';
    html += '<th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th>';
    html += '</tr></thead><tbody><tr>';

    // Preenche as células vazias antes do 1º dia do mês
    for (let i = 0; i < diaSemanaInicio; i++) {
        html += '<td class="calendario-dia inativo"></td>';
    }

    // Preenche os dias do mês
    for (let i = 1; i <= ultimoDiaMes; i++) {
        let dia = new Date(ano, mes, i);
        // Formato AAAA-MM-DD para o data-dia (necessário para o backend)
        const diaStr = `${dia.getFullYear()}-${String(dia.getMonth() + 1).padStart(2, '0')}-${String(dia.getDate()).padStart(2, '0')}`;

        // Verifica se o dia é no passado em relação à data atual (não pode ser agendado)
        const dataCompletaDoDia = new Date(dia.getFullYear(), dia.getMonth(), dia.getDate());
        const hojeSemHora = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate()); // Compara apenas a data, sem hora

        // Um dia é "passado" se for ANTES do dia de hoje
        const isDiaPassado = dataCompletaDoDia < hojeSemHora;

        let classeHabilitado = 'inativo'; // Por padrão, o dia é inativo

        // Só habilita dias se um profissional estiver selecionado E o dia não for passado
        if (profissionalSelecionadoId) {
            // `dia.getDay()` retorna 0 para domingo, 1 para segunda, etc.
            // `diasAtendimentoProfissional` deve conter strings de números (ex: ["1", "3"])
            if (diasAtendimentoProfissional.includes(dia.getDay().toString()) && !isDiaPassado) {
                classeHabilitado = 'habilitado';
            }
        }
        // Se nenhum profissional está selecionado, todos os dias permanecem 'inativo' (que é o valor inicial de classeHabilitado)

        html += `<td class="calendario-dia ${classeHabilitado}" data-dia="${diaStr}">${i}</td>`;

        // Começa uma nova linha a cada 7 dias (para formar as semanas do calendário)
        if ((diaSemanaInicio + i) % 7 === 0) {
            html += '</tr><tr>';
        }
    }

    // Preenche as células vazias finais se a última linha não estiver completa
    const totalCells = diaSemanaInicio + ultimoDiaMes;
    const remainingCells = 7 - (totalCells % 7);
    if (remainingCells !== 7 && remainingCells > 0) { // Garante que não adicione uma linha vazia extra
        for (let i = 0; i < remainingCells; i++) {
            html += '<td class="calendario-dia inativo"></td>';
        }
    }

    html += '</tr></tbody></table>';
    document.getElementById('calendario').innerHTML = html;
    // Exibe o mês e ano atuais formatados para português
    document.getElementById('tituloMesAno').textContent = primeiroDiaMes.toLocaleDateString('pt-BR', { year: 'numeric', month: 'long' });

    // Anexa/reatacha os event listeners aos botões de navegação para evitar duplicação em cada recriação
    const btnPrevMes = document.getElementById('btnPrevMes');
    const btnNextMes = document.getElementById('btnNextMes');

    // Limpa event listeners antigos para evitar múltiplas chamadas
    btnPrevMes.onclick = null;
    btnNextMes.onclick = null;

    // --- Lógica para desabilitar/habilitar botões de navegação ---
    // Desabilita "Mês Anterior" se estiver no mês atual ou antes
    if (mesOffset <= 0) {
        btnPrevMes.disabled = true;
        btnPrevMes.classList.add('disabled'); // Adiciona classe visual de desabilitado do Bootstrap
    } else {
        btnPrevMes.disabled = false;
        btnPrevMes.classList.remove('disabled');
        btnPrevMes.onclick = () => {
            mesAtualOffset--;
            dataSelecionadaGlobal = null; // Limpa a data selecionada ao mudar de mês
            criarCalendario(mesAtualOffset); // Recria o calendário com o novo offset
        };
    }

    // Desabilita "Próximo Mês" se estiver 3 meses à frente (mês atual (0), próximo (1), +2 (2), +3 (3) = 4 meses no total)
    if (mesOffset >= 3) {
        btnNextMes.disabled = true;
        btnNextMes.classList.add('disabled');
    } else {
        btnNextMes.disabled = false;
        btnNextMes.classList.remove('disabled');
        btnNextMes.onclick = () => {
            mesAtualOffset++;
            dataSelecionadaGlobal = null; // Limpa a data selecionada ao mudar de mês
            criarCalendario(mesAtualOffset); // Recria o calendário com o novo offset
        };
    }
    // --- Fim da lógica de botões de navegação ---

    // Adiciona o event listener APENAS para os dias que estão habilitados (roxo)
    // Primeiro remove listeners antigos para evitar múltiplas chamadas em cada recriação do calendário
    document.querySelectorAll('.calendario-dia.habilitado').forEach(dayElement => {
        dayElement.removeEventListener('click', handleDayClick); // Remove qualquer listener anterior
        dayElement.addEventListener('click', handleDayClick);    // Adiciona o listener atual
    });
}

// Handler para o clique em um dia do calendário
function handleDayClick(event) {
    // Remove a classe 'selecionado' de qualquer dia que estava selecionado anteriormente
    const previouslySelected = document.querySelector('.calendario-dia.selecionado');
    if (previouslySelected) {
        previouslySelected.classList.remove('selecionado');
    }

    // Adiciona a classe 'selecionado' ao dia que foi clicado
    this.classList.add('selecionado');

    dataSelecionadaGlobal = this.getAttribute('data-dia'); // Pega a data no formato AAAA-MM-DD
    document.getElementById('inputData').value = dataSelecionadaGlobal; // Preenche o input hidden para envio no formulário

    // Preenche os dados de confirmação no segundo modal
    document.getElementById('confirmServico').textContent = nomeServico;
    document.getElementById('confirmProfissional').textContent = nomeProfissional;
    // Formata a data para exibição no modal de confirmação (ex: "Quarta-feira, 15 de Maio de 2024")
    const dataExibicaoFormatada = new Date(dataSelecionadaGlobal + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('confirmData').textContent = dataExibicaoFormatada;

    // Carrega os horários disponíveis para a data e profissional selecionados
    carregarHorariosDisponiveis(dataSelecionadaGlobal);

    // Fecha o modal principal de agendamento e abre o modal de confirmação
    modalAgendamentoInstancia.hide();
    modalConfirmacaoInstancia.show();
}


// Função assíncrona para carregar horários disponíveis do servidor
async function carregarHorariosDisponiveis(dataSelecionada) {
    const selectHora = document.getElementById('selectHora');
    selectHora.innerHTML = '<option value="">Carregando horários...</option>';
    formAgendamento.style.display = 'none'; // Oculta o formulário de login/cadastro enquanto carrega

    const interval = 60; // Intervalo de 60 minutos (1 hora) entre os agendamentos
    let horaAtual = horaInicio; // Hora de início do expediente do profissional
    const horariosGerados = []; // Array para armazenar todos os horários possíveis

    // Gera todos os horários possíveis para o dia, dentro do intervalo de trabalho do profissional
    while (true) {
        const [hAtual, mAtual] = horaAtual.split(':').map(Number);
        const tempoAtual = new Date(); // Cria um objeto Date para manipular a hora
        tempoAtual.setHours(hAtual, mAtual, 0, 0); // Define a hora e minuto, zerando segundos e milissegundos

        const [hFim, mFim] = horaFim.split(':').map(Number);
        const tempoFim = new Date();
        tempoFim.setHours(hFim, mFim, 0, 0);

        // Se o tempo atual ultrapassar ou for igual ao tempo final do expediente, pare o loop
        if (tempoAtual.getTime() >= tempoFim.getTime()) {
            break;
        }

        horariosGerados.push(horaAtual); // Adiciona o horário gerado à lista

        // Adiciona o intervalo para o próximo horário
        tempoAtual.setMinutes(tempoAtual.getMinutes() + interval);
        horaAtual = `${String(tempoAtual.getHours()).padStart(2, '0')}:${String(tempoAtual.getMinutes()).padStart(2, '0')}`;
    }

    try {
        // Faz uma requisição ao servidor para obter os agendamentos já existentes para o profissional e data
        const response = await fetch(`get_agendamentos.php?profissional_id=${profissionalSelecionadoId}&data=${dataSelecionada}`);
        if (!response.ok) { // Verifica se a resposta HTTP foi bem-sucedida (status 200)
            throw new Error(`Erro HTTP ao buscar agendamentos: ${response.status}`);
        }
        const agendamentosExistentes = await response.json(); // Converte a resposta JSON para um objeto JavaScript

        selectHora.innerHTML = ''; // Limpa a mensagem de carregamento do select de horários

        // Se nenhum horário foi gerado (ex: hora de início = hora de fim)
        if (horariosGerados.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Nenhum horário gerado para este profissional.';
            selectHora.appendChild(option);
            return;
        }

        let horariosDisponiveisParaSelecao = [];

        // Filtra os horários gerados, removendo aqueles que já estão agendados
        horariosGerados.forEach(hora => {
            const agendado = agendamentosExistentes.some(agendamento => agendamento.hora.substring(0, 5) === hora);
            if (!agendado) { // Se o horário NÃO estiver agendado, adicione-o aos disponíveis
                horariosDisponiveisParaSelecao.push(hora);
            }
        });

        // Preenche o select de horários com as opções disponíveis
        if (horariosDisponiveisParaSelecao.length > 0) {
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Selecione um horário';
            selectHora.appendChild(defaultOption);

            horariosDisponiveisParaSelecao.forEach(hora => {
                const option = document.createElement('option');
                option.value = hora;
                option.textContent = hora;
                selectHora.appendChild(option);
            });
        } else {
            // Se não houver horários disponíveis após a filtragem
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Todos os horários estão ocupados para esta data.';
            selectHora.appendChild(option);
            formAgendamento.style.display = 'none'; // Oculta o formulário se não houver horários
        }

    } catch (error) {
        console.error('Erro ao buscar agendamentos existentes:', error);
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Erro ao carregar horários. Tente novamente.';
        selectHora.appendChild(option);
        formAgendamento.style.display = 'none';
    }
}


// Event listener para a seleção de profissional no PRIMEIRO MODAL
document.getElementById('selectProfissional').addEventListener('change', function() {
    const profissionalSelecionadoOption = this.options[this.selectedIndex];

    if (profissionalSelecionadoOption.value) { // Se um profissional válido foi selecionado
        diasAtendimentoProfissional = JSON.parse(profissionalSelecionadoOption.getAttribute('data-dias'));
        horaInicio = profissionalSelecionadoOption.getAttribute('data-hora-inicio').substring(0, 5); // Garante formato HH:MM
        horaFim = profissionalSelecionadoOption.getAttribute('data-hora-fim').substring(0, 5);     // Garante formato HH:MM
        profissionalSelecionadoId = profissionalSelecionadoOption.value;
        nomeProfissional = profissionalSelecionadoOption.textContent.split(' - ')[0]; // Pega apenas o nome do profissional

        mesAtualOffset = 0; // Reseta o calendário para o mês atual ao mudar de profissional
        criarCalendario(mesAtualOffset); // Recria o calendário com a disponibilidade do novo profissional

    } else { // Se a opção "-- Selecione um especialista --" (valor vazio) for escolhida
        diasAtendimentoProfissional = []; // Limpa os dias disponíveis
        profissionalSelecionadoId = null; // Nenhum profissional selecionado
        nomeProfissional = '';
        mesAtualOffset = 0; // Reseta o calendário para o mês atual
        criarCalendario(mesAtualOffset); // Recria o calendário, mostrando todos os dias como inativos
    }
    // Limpa a seleção visual de um dia no calendário quando o profissional é alterado
    const previouslySelected = document.querySelector('.calendario-dia.selecionado');
    if (previouslySelected) {
        previouslySelected.classList.remove('selecionado');
    }
    dataSelecionadaGlobal = null; // Zera a data selecionada para um novo agendamento
});


// Variáveis para os elementos do formulário de login/cadastro
const formAgendamento = document.getElementById('formAgendamento');
const formCadastro = document.getElementById('formCadastro');
const formLogin = document.getElementById('formLogin');

const nomeClienteCadastro = document.getElementById('nome_cliente_cadastro');
const emailClienteCadastro = document.getElementById('email_cliente_cadastro');
const telefoneClienteCadastro = document.getElementById('telefone_cliente_cadastro');
const senhaCadastro = document.getElementById('senha_cadastro');

const emailClienteLogin = document.getElementById('email_cliente_login');
const senhaLogin = document.getElementById('senha_login');

const linkFazerLogin = document.getElementById('linkFazerLogin');
const linkFazerCadastro = document.getElementById('linkFazerCadastro');

// Função para mostrar o formulário de cadastro e ocultar o de login
function mostrarFormCadastro() {
    formCadastro.style.display = 'block';
    formLogin.style.display = 'none';

    // Campos de cadastro são obrigatórios
    nomeClienteCadastro.setAttribute('required', 'required');
    emailClienteCadastro.setAttribute('required', 'required');
    telefoneClienteCadastro.setAttribute('required', 'required');
    senhaCadastro.setAttribute('required', 'required');

    // Campos de login não são obrigatórios
    emailClienteLogin.removeAttribute('required');
    senhaLogin.removeAttribute('required');

    // Limpa os campos do formulário oculto
    emailClienteLogin.value = '';
    senhaLogin.value = '';
}

// Função para mostrar o formulário de login e ocultar o de cadastro
function mostrarFormLogin() {
    formLogin.style.display = 'block';
    formCadastro.style.display = 'none';

    // Campos de login são obrigatórios
    emailClienteLogin.setAttribute('required', 'required');
    senhaLogin.setAttribute('required', 'required');

    // Campos de cadastro não são obrigatórios
    nomeClienteCadastro.removeAttribute('required');
    emailClienteCadastro.removeAttribute('required');
    telefoneClienteCadastro.removeAttribute('required');
    senhaCadastro.removeAttribute('required');

    // Limpa os campos do formulário oculto
    nomeClienteCadastro.value = '';
    emailClienteCadastro.value = '';
    telefoneClienteCadastro.value = '';
    senhaCadastro.value = '';
}

// Event Listeners para alternar entre login e cadastro
linkFazerLogin.addEventListener('click', function(e) {
    e.preventDefault(); // Impede o link de rolar para o topo da página
    mostrarFormLogin();
});

linkFazerCadastro.addEventListener('click', function(e) {
    e.preventDefault(); // Impede o link de rolar para o topo da página
    mostrarFormCadastro();
});

// Função para formatar o telefone enquanto o usuário digita (aplicada a qualquer input de telefone)
function formatarTelefoneInput(inputElement) {
    inputElement.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
        let formattedValue = '';

        if (value.length > 0) {
            formattedValue = '(' + value.substring(0, 2);
        }
        if (value.length > 2) {
            if (value.length >= 7 && value.charAt(2) === '9') { // Se for um número de 9 dígitos (celular, como 9XXXX-XXXX)
                 formattedValue += ') ' + value.substring(2, 7);
            } else { // Se for 8 dígitos (fixo, como XXXX-XXXX)
                formattedValue += ') ' + value.substring(2, 6);
            }
        }
        if (value.length > 6 && value.charAt(2) === '9') { // Continuação para 9 dígitos
            formattedValue += '-' + value.substring(7, 11);
        } else if (value.length > 6) { // Continuação para 8 dígitos
            formattedValue += '-' + value.substring(6, 10);
        }
        e.target.value = formattedValue;
    });
}

// Aplica a formatação aos campos de telefone
formatarTelefoneInput(telefoneClienteCadastro);


// Event listener para a seleção de horário no SEGUNDO MODAL (de confirmação)
document.getElementById('selectHora').addEventListener('change', function() {
    document.getElementById('inputHora').value = this.value; // Atualiza o input hidden com o horário selecionado
    // Só mostra o formulário de login/cadastro se um horário válido for selecionado
    if (this.value) {
        formAgendamento.style.display = 'block';
        // Por padrão, mostra o formulário de cadastro primeiro ao escolher a hora
        mostrarFormCadastro();
    } else {
        formAgendamento.style.display = 'none';
    }
});


// Event listener para os botões "Agendar Agora" nos cards de serviço
document.querySelectorAll('.agendarBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        servicoSelecionadoId = this.getAttribute('data-id');
        nomeServico = this.getAttribute('data-servico');

        document.getElementById('nomeServicoModal').textContent = nomeServico;
        document.getElementById('inputServicoId').value = servicoSelecionadoId;

        // --- Resetar o estado do modal principal de agendamento (primeiro modal) ---
        document.getElementById('selectProfissional').value = ''; // Limpa a seleção do profissional
        profissionalSelecionadoId = null; // Zera o profissional selecionado
        nomeProfissional = ''; // Zera o nome do profissional

        // Reinicia o calendário para o mês atual (offset 0), mostrando todos os dias inativos
        mesAtualOffset = 0;
        criarCalendario(mesAtualOffset);

        // --- Resetar o estado do modal de confirmação (segundo modal) ---
        document.getElementById('selectHora').innerHTML = '<option value="">Selecione um horário</option>';
        // Limpa e reseta o formulário de login/cadastro para o estado inicial (cadastro)
        formAgendamento.style.display = 'none';
        nomeClienteCadastro.value = '';
        emailClienteCadastro.value = '';
        telefoneClienteCadastro.value = '';
        senhaCadastro.value = '';
        emailClienteLogin.value = '';
        senhaLogin.value = '';
        mostrarFormCadastro(); // Garante que o formulário de cadastro esteja visível por padrão ao abrir o modal de confirmação


        // Zera a data selecionada visualmente e logicamente para um novo agendamento
        dataSelecionadaGlobal = null;
        const previouslySelected = document.querySelector('.calendario-dia.selecionado');
        if (previouslySelected) {
            previouslySelected.classList.remove('selecionado');
        }

        // Abre o PRIMEIRO MODAL (Profissional e Calendário)
        modalAgendamentoInstancia.show();
    });
});
document.getElementById('selectProfissional').addEventListener('change', function() {
    document.getElementById('inputProfissionalId').value = this.value;
});

// Quando abrir o modal de confirmação, também garanta que o campo hidden está correto
function atualizarCampoProfissionalId() {
    document.getElementById('inputProfissionalId').value = profissionalSelecionadoId;
}

// Chame essa função sempre que for abrir o modal de confirmação ou antes de submeter o formulário
document.getElementById('selectHora').addEventListener('change', atualizarCampoProfissionalId);

// E também ao clicar em "Agendar Agora"
document.querySelectorAll('.agendarBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('inputProfissionalId').value = profissionalSelecionadoId;
    });
});
</script>
</body>
</html>