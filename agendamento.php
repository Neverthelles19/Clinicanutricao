<?php
include 'conexao.php'; // Arquivo de conexão com o banco

// Pega os serviços
$servicos = mysqli_query($conexao, "SELECT * FROM servicos");

// Pega os profissionais
$profissionais = mysqli_query($conexao, "SELECT * FROM profissionais ORDER BY nome");

// Processamento do agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email_cliente'];
    $senha = $_POST['senha'];
    $servico_id = (int)$_POST['servico_id'];
    $profissional_id = (int)$_POST['profissional_id'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    // Verifica se o cliente já existe
    $query = mysqli_query($conexao, "SELECT * FROM clientes WHERE email = '$email' LIMIT 1");
    $cliente = mysqli_fetch_assoc($query);

    if ($cliente) {
        // Verifica a senha
        if (!password_verify($senha, $cliente['senha'])) {
            echo "<script>alert('Senha incorreta!'); history.back();</script>";
            exit;
        }
        $nome_cliente = $cliente['nome'];
        $telefone_cliente = $cliente['telefone'];
        $cliente_id = $cliente['id'];
    } else {
        // Cadastra cliente novo
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $nome_cliente = "Cliente"; // Nome do cliente - considere coletar isso no formulário
        $telefone_cliente = "";    // Telefone do cliente - considere coletar isso no formulário
        mysqli_query($conexao, "INSERT INTO clientes (nome, email, telefone, senha) VALUES ('$nome_cliente', '$email', '$telefone_cliente', '$senha_hash')");
        $cliente_id = mysqli_insert_id($conexao);
    }

    // Verifica se já existe agendamento no mesmo horário
    $existe = mysqli_query($conexao, "SELECT * FROM agendamentos WHERE profissional_id = $profissional_id AND data = '$data' AND hora = '$hora'");
    if (mysqli_num_rows($existe) > 0) {
        echo "<script>alert('Esse horário já está agendado!'); history.back();</script>";
        exit;
    }

    // Insere o agendamento
    mysqli_query($conexao, "INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, nome_cliente, email_cliente, telefone_cliente, data, hora)
        VALUES ($cliente_id, $profissional_id, $servico_id, '$nome_cliente', '$email', '$telefone_cliente', '$data', '$hora')");

    echo "<script>alert('Agendamento realizado com sucesso!'); window.location.href='agendamento.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<title>Agendamento de Serviços</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
/* Estilos do calendário */
.calendario-table {
  width: 100%;
  table-layout: fixed;
  user-select: none;
}
.calendario-table th, .calendario-table td {
  text-align: center;
  padding: 0.5rem;
  cursor: pointer;
  border: 1px solid #ddd;
}
.calendario-dia.habilitado {
  background-color: #783f8e;
  color: white;
  cursor: pointer;
  border-radius: 6px;
}
.calendario-dia.inativo {
  background-color: #ccc;
  color: #888;
  cursor: not-allowed;
}
.calendario-dia.selecionado {
  border: 3px solid #3a0ca3;
  background-color: #5f259f;
}
</style>
</head>
<body class="bg-light">
<div class="container py-5">
<h2 class="mb-4">Agendamento de Serviços de Nutrição</h2>
<div class="row">
    <?php while($s = mysqli_fetch_assoc($servicos)): ?>
    <div class="col-md-4 mb-3">
      <div class="card shadow">
        <div class="card-body">
          <h5 class="card-title"><?= htmlspecialchars($s['servico']) ?></h5>
          <p class="card-text">
            Duração: <?= intval($s['duracao']) ?> min<br>
            Valor: R$ <?= number_format($s['valor'], 2, ',', '.') ?>
          </p>
          <button class="btn btn-primary agendarBtn"
                  data-id="<?= $s['id'] ?>"
                  data-servico="<?= htmlspecialchars($s['servico']) ?>">
            Agendar
          </button>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
</div>
</div>

<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agendamento: <span id="nomeServicoModal"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">

        <div id="passo1">
          <label for="selectProfissional" class="form-label">Escolha o Profissional:</label>
          <select id="selectProfissional" class="form-select mb-3">
            <option value="">-- Selecione --</option>
            <?php
              mysqli_data_seek($profissionais, 0); // reseta o resultado
              while($p = mysqli_fetch_assoc($profissionais)):
            ?>
            <option value="<?= $p['id'] ?>"
              data-dias='<?= json_encode(explode(",", $p['dias_atendimento'])) ?>'
              data-hora-inicio="<?= $p['hora_inicio'] ?>"
              data-hora-fim="<?= $p['hora_fim'] ?>"
            >
              <?= htmlspecialchars($p['nome']) ?> - <?= htmlspecialchars($p['especialidade']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div id="passo2" style="display:none;">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <button id="btnPrevMes" class="btn btn-outline-secondary btn-sm">&lt; Mês Anterior</button>
            <h5 id="tituloMesAno"></h5>
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
        <p>Serviço: <strong id="confirmServico"></strong></p>
        <p>Profissional: <strong id="confirmProfissional"></strong></p>
        <p>Data: <strong id="confirmData"></strong></p>

        <div class="mb-3">
          <label for="selectHora" class="form-label">Escolha o horário:</label>
          <select id="selectHora" class="form-select" style="max-width: 200px;">
            <option value="">Selecione um horário</option>
          </select>
        </div>

        <form method="POST" id="formAgendamento" class="mt-4">
          <input type="hidden" name="servico_id" id="inputServicoId" />
          <input type="hidden" name="profissional_id" id="inputProfissionalId" />
          <input type="hidden" name="data" id="inputData" />
          <input type="hidden" name="hora" id="inputHora" />

          <div class="mb-3">
            <label for="email_cliente" class="form-label">E-mail:</label>
            <input type="email" class="form-control" name="email_cliente" id="email_cliente" required />
          </div>
          <div class="mb-3">
            <label for="senha" class="form-label">Senha (para login ou cadastro):</label>
            <input type="password" class="form-control" name="senha" id="senha" required />
          </div>
          <button type="submit" class="btn btn-success">Confirmar Agendamento</button>
        </form>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Variáveis globais
let mesAtualOffset = 0;
let diasAtendimentoProfissional = [];
let horaInicio = "09:00";
let horaFim = "17:00";
let servicoSelecionadoId = null;
let profissionalSelecionadoId = null;
let nomeServico = '';
let nomeProfissional = ''; // Adicionado para exibir no modal de confirmação
let dataSelecionadaGlobal = null; // Para armazenar a data selecionada

// Instâncias dos modais do Bootstrap
let modalAgendamentoInstancia;
let modalConfirmacaoInstancia;


document.addEventListener('DOMContentLoaded', function() {
    modalAgendamentoInstancia = new bootstrap.Modal(document.getElementById('modalAgendamento'));
    modalConfirmacaoInstancia = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
});


// Função para renderizar o calendário
function criarCalendario(mesOffset) {
    const hoje = new Date();
    // Ajusta 'hoje' para considerar o mesOffset sem alterar o dia atual
    const dataExibicao = new Date(hoje.getFullYear(), hoje.getMonth() + mesOffset, 1);

    const ano = dataExibicao.getFullYear();
    const mes = dataExibicao.getMonth(); // Mês indexado em 0

    const primeiroDiaMes = new Date(ano, mes, 1);
    const ultimoDiaMes = new Date(ano, mes + 1, 0).getDate();
    const diaSemanaInicio = primeiroDiaMes.getDay(); // 0 para domingo, 6 para sábado

    let html = '<table class="calendario-table"><thead><tr>';
    html += '<th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th>';
    html += '</tr></thead><tbody><tr>';

    // Preenche as células vazias iniciais para os dias antes do 1º do mês
    for (let i = 0; i < diaSemanaInicio; i++) {
        html += '<td class="calendario-dia inativo"></td>';
    }

    // Populate the days of the month
    for (let i = 1; i <= ultimoDiaMes; i++) {
        let dia = new Date(ano, mes, i);
        const diaStr = dia.toISOString().split('T')[0]; // Formato AAAA-MM-DD

        // Check if the day is in the past (only if mesOffset is 0, i.e., current month)
        // Or if the day is before today if we are in the current month
        const isDiaPassado = (mesOffset === 0 && dia < new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate()));

        // Check if the day of the week is in the professional's availability AND it's not a past day
        let habilitado = (diasAtendimentoProfissional.includes(dia.getDay()) && !isDiaPassado) ? 'habilitado' : 'inativo';
        html += `<td class="calendario-dia ${habilitado}" data-dia="${diaStr}">${i}</td>`;

        // Start a new row every 7 days
        if ((diaSemanaInicio + i) % 7 === 0) {
            html += '</tr><tr>';
        }
    }

    // Fill in trailing empty cells if the last row isn't full
    const totalCells = diaSemanaInicio + ultimoDiaMes;
    const remainingCells = 7 - (totalCells % 7);
    if (remainingCells !== 7 && remainingCells > 0) { // If it's not a full last row already and not 0 (full last row)
        for (let i = 0; i < remainingCells; i++) {
            html += '<td class="calendario-dia inativo"></td>';
        }
    }


    html += '</tr></tbody></table>';
    document.getElementById('calendario').innerHTML = html;
    document.getElementById('tituloMesAno').textContent = primeiroDiaMes.toLocaleDateString('pt-BR', { year: 'numeric', month: 'long' });

    // Anexa os *event listeners* aos botões de navegação
    document.getElementById('btnPrevMes').onclick = () => {
        mesAtualOffset--;
        criarCalendario(mesAtualOffset);
        // Clear selected date and time when month changes
        dataSelecionadaGlobal = null;
    };
    document.getElementById('btnNextMes').onclick = () => {
        mesAtualOffset++;
        criarCalendario(mesAtualOffset);
        // Clear selected date and time when month changes
        dataSelecionadaGlobal = null;
    };

    // Adiciona o *event listener* para cliques nos dias dentro do calendário
    document.querySelectorAll('.calendario-dia.habilitado').forEach(dayElement => {
        dayElement.addEventListener('click', function() {
            // Remove a classe 'selecionado' do dia selecionado anteriormente
            const previouslySelected = document.querySelector('.calendario-dia.selecionado');
            if (previouslySelected) {
                previouslySelected.classList.remove('selecionado');
            }

            // Adiciona a classe 'selecionado' ao dia clicado
            this.classList.add('selecionado');

            dataSelecionadaGlobal = this.getAttribute('data-dia');
            document.getElementById('inputData').value = dataSelecionadaGlobal;

            // Preenche os dados no modal de confirmação antes de abri-lo
            document.getElementById('confirmServico').textContent = nomeServico;
            document.getElementById('confirmProfissional').textContent = nomeProfissional;
            // Formata a data para exibição no modal
            const dataExibicaoFormatada = new Date(dataSelecionadaGlobal + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            document.getElementById('confirmData').textContent = dataExibicaoFormatada;

            // Carrega os horários para a data selecionada no novo modal
            carregarHorariosDisponiveis(dataSelecionadaGlobal);

            // Fecha o modal de agendamento e abre o modal de confirmação
            modalAgendamentoInstancia.hide();
            modalConfirmacaoInstancia.show();
        });
    });
}

// Carregar horários disponíveis
async function carregarHorariosDisponiveis(dataSelecionada) {
    const selectHora = document.getElementById('selectHora');
    selectHora.innerHTML = '<option value="">Carregando horários...</option>';

    const interval = 60; // Intervalo de 60 minutos (1 hora)
    let horaAtual = horaInicio;
    const horariosGerados = [];

    // Gera todos os horários possíveis para o dia
    while (true) {
        const [h, m] = horaAtual.split(':').map(Number);
        const currentMoment = new Date();
        currentMoment.setHours(h, m, 0, 0);

        if (horaAtual >= horaFim) {
            break;
        }

        horariosGerados.push(horaAtual);

        // Calcula o próximo horário
        currentMoment.setMinutes(currentMoment.getMinutes() + interval);
        horaAtual = currentMoment.toTimeString().substring(0, 5); // Formato para HH:MM
    }

    try {
        const response = await fetch(`get_agendamentos.php?profissional_id=${profissionalSelecionadoId}&data=${dataSelecionada}`);
        const agendamentosExistentes = await response.json();

        selectHora.innerHTML = ''; // Limpa a mensagem de carregamento

        if (horariosGerados.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Nenhum horário disponível para o profissional neste dia.';
            selectHora.appendChild(option);
            document.getElementById('formAgendamento').style.display = 'none'; // Oculta o formulário se não houver horários
            return;
        }

        let horariosDisponiveisParaSelecao = [];

        horariosGerados.forEach(hora => {
            const agendado = agendamentosExistentes.some(agendamento => agendamento.hora.substring(0, 5) === hora);
            if (!agendado) {
                horariosDisponiveisParaSelecao.push(hora);
            }
        });

        if (horariosDisponiveisParaSelecao.length > 0) {
            // Adiciona uma opção padrão para "Selecione"
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
            document.getElementById('formAgendamento').style.display = 'block'; // Mostra o formulário se houver horários disponíveis
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Todos os horários estão ocupados para esta data.';
            selectHora.appendChild(option);
            document.getElementById('formAgendamento').style.display = 'none'; // Oculta o formulário se não houver horários
        }

    } catch (error) {
        console.error('Erro ao buscar agendamentos existentes:', error);
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Erro ao carregar horários. Tente novamente.';
        selectHora.appendChild(option);
        document.getElementById('formAgendamento').style.display = 'none';
    }
}


// Quando seleciona um profissional
document.getElementById('selectProfissional').addEventListener('change', function() {
    const profissionalSelecionado = this.options[this.selectedIndex];

    if (profissionalSelecionado.value) {
        diasAtendimentoProfissional = JSON.parse(profissionalSelecionado.getAttribute('data-dias'));
        horaInicio = profissionalSelecionado.getAttribute('data-hora-inicio').substring(0, 5); // Garante HH:MM
        horaFim = profissionalSelecionado.getAttribute('data-hora-fim').substring(0, 5);     // Garante HH:MM
        profissionalSelecionadoId = profissionalSelecionado.value;
        nomeProfissional = profissionalSelecionado.textContent.split(' - ')[0]; // Pega apenas o nome

        // Redefine o deslocamento do mês e cria o calendário para o profissional selecionado
        mesAtualOffset = 0;
        criarCalendario(mesAtualOffset);
        document.getElementById('passo2').style.display = 'block';
    } else {
        // Se nenhum profissional for selecionado, oculta o calendário
        document.getElementById('passo2').style.display = 'none';
        diasAtendimentoProfissional = []; // Limpa os dias do profissional
        profissionalSelecionadoId = null;
        nomeProfissional = '';
    }
});

// Selecionou um horário no modal de confirmação
document.getElementById('selectHora').addEventListener('change', function() {
    document.getElementById('inputHora').value = this.value;
    // Só mostra o formulário de login/cadastro se um horário válido for selecionado
    if (this.value) {
        // O formulário já está visível dentro do modal de confirmação.
        // Nada precisa ser escondido/mostrado aqui, apenas o inputHora é atualizado.
    }
});

// Clicou em agendar (no card de serviço)
document.querySelectorAll('.agendarBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        servicoSelecionadoId = this.getAttribute('data-id');
        nomeServico = this.getAttribute('data-servico');

        document.getElementById('nomeServicoModal').textContent = nomeServico;
        document.getElementById('inputServicoId').value = servicoSelecionadoId; // Atualiza o ID do serviço para o formulário final

        // Reset etapas do modal principal
        document.getElementById('selectProfissional').value = ''; // Limpa a seleção do profissional
        document.getElementById('passo2').style.display = 'none'; // Oculta o calendário
        profissionalSelecionadoId = null;
        nomeProfissional = '';
        dataSelecionadaGlobal = null;

        // Reset etapas do modal de confirmação (caso esteja aberto)
        document.getElementById('selectHora').innerHTML = '<option value="">Selecione um horário</option>';
        document.getElementById('email_cliente').value = '';
        document.getElementById('senha').value = '';
        document.getElementById('formAgendamento').style.display = 'none';


        modalAgendamentoInstancia.show(); // Abre o modal principal de agendamento
    });
});
</script>
</body>
</html>