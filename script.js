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


//Codigo JS do login Iago
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
  function togglePasswordVisibility(icon, inputId) {
  const senhaInput = document.getElementById(inputId);

  if (senhaInput.type === 'password') {
    senhaInput.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    senhaInput.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}