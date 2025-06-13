// Variáveis globais
let mesAtualOffset = 0; // 0 é o mês atual, 1 é o próximo, etc.
let diasAtendimentoProfissional = [];
let horaInicio = "09:00"; // Hora de início padrão, será atualizada pelo profissional
let horaFim = "17:00"; // Hora de fim padrão, será atualizada pelo profissional
let servicoSelecionadoId = null;
let profissionalSelecionadoId = null; // Inicialmente nulo, pois nenhum profissional está selecionado
let nomeServico = '';
let nomeProfissional = '';
let dataSelecionadaGlobal = null;
let horaSelecionadaGlobal = null; // Variável para armazenar a hora selecionada

// Instâncias dos modais do Bootstrap
let modalAgendamentoInstancia; // O primeiro modal que escolhe profissional e data
let modalConfirmacaoInstancia; // O segundo modal que escolhe a hora e tem o formulário de login/cadastro

// Referências aos elementos do DOM
// `formAgendamento` é o container geral que pode conter `formCadastro` e `formLogin`
const formAgendamento = document.getElementById('formAgendamento');
const formCadastro = document.getElementById('formCadastro'); // O div com o formulário de cadastro/agendamento
const formLogin = document.getElementById('formLogin');     // O div com o formulário de login

// Elementos dentro do formCadastro (Cadastro/Agendamento)
const nomeClienteCadastro = document.getElementById('nome_cliente_cadastro');
const emailClienteCadastro = document.getElementById('email_cliente_cadastro');
const telefoneClienteCadastro = document.getElementById('telefone_cliente_cadastro');
const senhaCadastro = document.getElementById('senha_cadastro');
const inputServicoId = document.getElementById('inputServicoId');
const inputProfissionalId = document.getElementById('inputProfissionalId');
const inputData = document.getElementById('inputData');
const inputHora = document.getElementById('inputHora'); // O input hidden da hora

// Elementos dentro do formLogin
const emailClienteLogin = document.getElementById('email_cliente_login');
const senhaLogin = document.getElementById('senha_login');

const linkFazerLogin = document.getElementById('linkFazerLogin');
const linkFazerCadastro = document.getElementById('linkFazerCadastro');


// --- Funções de Inicialização e Lógica Principal ---

document.addEventListener('DOMContentLoaded', function() {
    // Inicializa as instâncias dos modais quando o DOM estiver carregado
    modalAgendamentoInstancia = new bootstrap.Modal(document.getElementById('modalAgendamento'));
    modalConfirmacaoInstancia = new bootstrap.Modal(document.getElementById('modalConfirmacao'));

    // Efeito nos campos de formulário
    document.querySelectorAll('.form-control').forEach(input => {
      input.addEventListener('focus', function() {
        if (this.parentElement && this.parentElement.classList.contains('input-icon-wrapper')) {
            this.parentElement.style.transform = 'scale(1.02)';
        } else {
            this.style.transform = 'scale(1.02)';
        }
      });

      input.addEventListener('blur', function() {
        if (this.parentElement && this.parentElement.classList.contains('input-icon-wrapper')) {
            this.parentElement.style.transform = 'scale(1)';
        } else {
            this.style.transform = 'scale(1)';
        }
      });
    });

    // Aplica a formatação de telefone aos campos de telefone
    formatarTelefoneInput(telefoneClienteCadastro);

    // Event Listeners para alternar entre login e cadastro dentro do MODAL DE CONFIRMAÇÃO
    linkFazerLogin.addEventListener('click', function(e) {
        e.preventDefault();
        mostrarFormLogin();
    });

    linkFazerCadastro.addEventListener('click', function(e) {
        e.preventDefault();
        mostrarFormCadastro();
    });

    // Inicializa o calendário com o offset 0 (mês atual)
    criarCalendario(mesAtualOffset);
});


// --- Funções Auxiliares do Calendário e Agendamento ---

// Função para renderizar o calendário
function criarCalendario(mesOffset) {
    const hoje = new Date();
    const dataExibicao = new Date(hoje.getFullYear(), hoje.getMonth() + mesOffset, 1);

    const ano = dataExibicao.getFullYear();
    const mes = dataExibicao.getMonth();

    const primeiroDiaMes = new Date(ano, mes, 1);
    const ultimoDiaMes = new Date(ano, mes + 1, 0).getDate();
    const diaSemanaInicio = primeiroDiaMes.getDay();

    let html = '<table class="calendario-table"><thead><tr>';
    html += '<th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th>';
    html += '</tr></thead><tbody><tr>';

    for (let i = 0; i < diaSemanaInicio; i++) {
        html += '<td class="calendario-dia inativo"></td>';
    }

    for (let i = 1; i <= ultimoDiaMes; i++) {
        let dia = new Date(ano, mes, i);
        const diaStr = `${dia.getFullYear()}-${String(dia.getMonth() + 1).padStart(2, '0')}-${String(dia.getDate()).padStart(2, '0')}`;

        const dataCompletaDoDia = new Date(dia.getFullYear(), dia.getMonth(), dia.getDate());
        const hojeSemHora = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate());
        const isDiaPassado = dataCompletaDoDia < hojeSemHora;

        let classeHabilitado = 'inativo';

        if (profissionalSelecionadoId) {
            if (diasAtendimentoProfissional.includes(dia.getDay().toString()) && !isDiaPassado) {
                classeHabilitado = 'habilitado';
            }
        }

        let classeSelecionado = (dataSelecionadaGlobal === diaStr) ? 'selecionado' : '';

        html += `<td class="calendario-dia ${classeHabilitado} ${classeSelecionado}" data-dia="${diaStr}">${i}</td>`;

        if ((diaSemanaInicio + i) % 7 === 0) {
            html += '</tr><tr>';
        }
    }

    const totalCells = diaSemanaInicio + ultimoDiaMes;
    const remainingCells = 7 - (totalCells % 7);
    if (remainingCells !== 7 && remainingCells > 0) {
        for (let i = 0; i < remainingCells; i++) {
            html += '<td class="calendario-dia inativo"></td>';
        }
    }

    html += '</tr></tbody></table>';
    document.getElementById('calendario').innerHTML = html;
    document.getElementById('tituloMesAno').textContent = primeiroDiaMes.toLocaleDateString('pt-BR', { year: 'numeric', month: 'long' });

    const btnPrevMes = document.getElementById('btnPrevMes');
    const btnNextMes = document.getElementById('btnNextMes');

    btnPrevMes.onclick = null;
    btnNextMes.onclick = null;

    if (mesOffset <= 0) {
        btnPrevMes.disabled = true;
        btnPrevMes.classList.add('disabled');
    } else {
        btnPrevMes.disabled = false;
        btnPrevMes.classList.remove('disabled');
        btnPrevMes.onclick = () => {
            mesAtualOffset--;
            dataSelecionadaGlobal = null;
            criarCalendario(mesAtualOffset);
            document.getElementById('selectHora').innerHTML = '<option value="">Selecione um horário</option>';
            inputHora.value = '';
            formAgendamento.style.display = 'none';
        };
    }

    if (mesOffset >= 3) {
        btnNextMes.disabled = true;
        btnNextMes.classList.add('disabled');
    } else {
        btnNextMes.disabled = false;
        btnNextMes.classList.remove('disabled');
        btnNextMes.onclick = () => {
            mesAtualOffset++;
            dataSelecionadaGlobal = null;
            criarCalendario(mesAtualOffset);
            document.getElementById('selectHora').innerHTML = '<option value="">Selecione um horário</option>';
            inputHora.value = '';
            formAgendamento.style.display = 'none';
        };
    }

    document.querySelectorAll('.calendario-dia.habilitado').forEach(dayElement => {
        dayElement.removeEventListener('click', handleDayClick);
        dayElement.addEventListener('click', handleDayClick);
    });
}

// Handler para o clique em um dia do calendário
function handleDayClick(event) {
    const previouslySelected = document.querySelector('.calendario-dia.selecionado');
    if (previouslySelected) {
        previouslySelected.classList.remove('selecionado');
    }

    this.classList.add('selecionado');

    dataSelecionadaGlobal = this.getAttribute('data-dia');
    inputData.value = dataSelecionadaGlobal;

    document.getElementById('confirmServico').textContent = nomeServico;
    document.getElementById('confirmProfissional').textContent = nomeProfissional;
    const dataExibicaoFormatada = new Date(dataSelecionadaGlobal + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('confirmData').textContent = dataExibicaoFormatada;

    carregarHorariosDisponiveis(dataSelecionadaGlobal);

    modalAgendamentoInstancia.hide();
    modalConfirmacaoInstancia.show();
}

// Função assíncrona para carregar horários disponíveis do servidor
async function carregarHorariosDisponiveis(dataSelecionada) {
    const selectHora = document.getElementById('selectHora');
    selectHora.innerHTML = '<option value="">Carregando horários...</option>';
    formAgendamento.style.display = 'none';

    const interval = 60;
    let horaAtual = horaInicio;
    const horariosGerados = [];

    while (true) {
        const [hAtual, mAtual] = horaAtual.split(':').map(Number);
        const tempoAtual = new Date();
        tempoAtual.setHours(hAtual, mAtual, 0, 0);

        const [hFim, mFim] = horaFim.split(':').map(Number);
        const tempoFim = new Date();
        tempoFim.setHours(hFim, mFim, 0, 0);

        if (tempoAtual.getTime() >= tempoFim.getTime()) {
            break;
        }

        horariosGerados.push(horaAtual);

        tempoAtual.setMinutes(tempoAtual.getMinutes() + interval);
        horaAtual = `${String(tempoAtual.getHours()).padStart(2, '0')}:${String(tempoAtual.getMinutes()).padStart(2, '0')}`;
    }

    try {
        const response = await fetch(`get_agendamentos.php?profissional_id=${profissionalSelecionadoId}&data=${dataSelecionada}`);
        if (!response.ok) {
            throw new Error(`Erro HTTP ao buscar agendamentos: ${response.status}`);
        }
        const agendamentosExistentes = await response.json();

        selectHora.innerHTML = '';

        if (horariosGerados.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Nenhum horário gerado para este profissional.';
            selectHora.appendChild(option);
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
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Todos os horários estão ocupados para esta data.';
            selectHora.appendChild(option);
            formAgendamento.style.display = 'none';
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

    if (profissionalSelecionadoOption.value) {
        diasAtendimentoProfissional = JSON.parse(profissionalSelecionadoOption.getAttribute('data-dias'));
        horaInicio = profissionalSelecionadoOption.getAttribute('data-hora-inicio').substring(0, 5);
        horaFim = profissionalSelecionadoOption.getAttribute('data-hora-fim').substring(0, 5);
        profissionalSelecionadoId = this.value;
        nomeProfissional = profissionalSelecionadoOption.textContent.split(' - ')[0];

        inputProfissionalId.value = profissionalSelecionadoId;

        mesAtualOffset = 0;
        criarCalendario(mesAtualOffset);

    } else {
        diasAtendimentoProfissional = [];
        profissionalSelecionadoId = null;
        nomeProfissional = '';
        inputProfissionalId.value = '';

        mesAtualOffset = 0;
        criarCalendario(mesAtualOffset);
    }
    const previouslySelected = document.querySelector('.calendario-dia.selecionado');
    if (previouslySelected) {
        previouslySelected.classList.remove('selecionado');
    }
    dataSelecionadaGlobal = null;
    document.getElementById('selectHora').innerHTML = '<option value="">Selecione um horário</option>';
    inputHora.value = '';
    formAgendamento.style.display = 'none';
});


// --- Funções para Gerenciamento de Formulários de Login/Cadastro no Modal de Confirmação ---

// Função para mostrar o formulário de cadastro e ocultar o de login
function mostrarFormCadastro() {
    formCadastro.style.display = 'block';
    formLogin.style.display = 'none';

    nomeClienteCadastro.setAttribute('required', 'required');
    emailClienteCadastro.setAttribute('required', 'required');
    telefoneClienteCadastro.setAttribute('required', 'required');
    // A obrigatoriedade da senha será gerenciada pelo PHP/JS que verifica o login
    senhaCadastro.setAttribute('required', 'required');
    senhaCadastro.style.display = 'block';

    emailClienteLogin.removeAttribute('required');
    senhaLogin.removeAttribute('required');

    emailClienteLogin.value = '';
    senhaLogin.value = '';
}

// Função para mostrar o formulário de login e ocultar o de cadastro
function mostrarFormLogin() {
    formLogin.style.display = 'block';
    formCadastro.style.display = 'none';

    emailClienteLogin.setAttribute('required', 'required');
    senhaLogin.setAttribute('required', 'required');

    nomeClienteCadastro.removeAttribute('required');
    emailClienteCadastro.removeAttribute('required');
    telefoneClienteCadastro.removeAttribute('required');
    senhaCadastro.removeAttribute('required');

    nomeClienteCadastro.value = '';
    emailClienteCadastro.value = '';
    telefoneClienteCadastro.value = '';
    senhaCadastro.value = '';
}

// Função para pré-preencher dados do cliente logado e desabilitar campos
function preencherDadosClienteLogado(nome, email, telefone) {
    nomeClienteCadastro.value = nome;
    emailClienteCadastro.value = email;
    telefoneClienteCadastro.value = telefone;

    nomeClienteCadastro.setAttribute('readonly', 'readonly');
    emailClienteCadastro.setAttribute('readonly', 'readonly');
    telefoneClienteCadastro.setAttribute('readonly', 'readonly');
    senhaCadastro.removeAttribute('required');
    senhaCadastro.style.display = 'none';

    linkFazerLogin.style.display = 'none';
    linkFazerCadastro.style.display = 'none';
}


// Função para formatar o telefone enquanto o usuário digita (aplicada a qualquer input de telefone)
function formatarTelefoneInput(inputElement) {
    inputElement.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';

        if (value.length > 0) {
            formattedValue = '(' + value.substring(0, 2);
        }
        if (value.length > 2) {
            if (value.length >= 7 && value.charAt(2) === '9') {
                 formattedValue += ') ' + value.substring(2, 7);
            } else {
                formattedValue += ') ' + value.substring(2, 6);
            }
        }
        if (value.length > 6 && value.charAt(2) === '9') {
            formattedValue += '-' + value.substring(7, 11);
        } else if (value.length > 6) {
            formattedValue += '-' + value.substring(6, 10);
        }
        e.target.value = formattedValue;
    });
}

// Event listener para a seleção de horário no SEGUNDO MODAL (de confirmação)
document.getElementById('selectHora').addEventListener('change', function() {
    horaSelecionadaGlobal = this.value;
    inputHora.value = horaSelecionadaGlobal;

    if (this.value) {
        formAgendamento.style.display = 'block';

        // Aqui, chamamos uma função auxiliar que será definida globalmente (ou por PHP)
        // para decidir qual formulário mostrar e se deve pré-preencher.
        // `handleLoginStatusAndFormDisplay` é uma função que você adicionará globalmente
        // OU, como discutimos, a lógica do PHP que chama `preencherDadosClienteLogado`
        // e `mostrarFormCadastro/Login` deve estar na sua página principal.
        // Para que o script seja totalmente separado, `handleLoginStatusAndFormDisplay`
        // precisa ser definida **fora** deste arquivo JS, na sua página PHP,
        // ou você fará uma chamada AJAX para verificar o status de login.
        // Vamos manter a abordagem do PHP que imprime o JS no HTML principal por simplicidade.
        // O código abaixo será a **única exceção** de JS que fica na página PHP.
        if (typeof checkAndSetLoginStatus === 'function') {
            checkAndSetLoginStatus(); // Esta função virá do PHP
        } else {
            mostrarFormCadastro(); // Fallback se a função do PHP não estiver disponível
        }

    } else {
        formAgendamento.style.display = 'none';
        formCadastro.style.display = 'none';
        formLogin.style.display = 'none';
    }
});


// Event listener para os botões "Agendar Agora" nos cards de serviço
document.querySelectorAll('.agendarBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        servicoSelecionadoId = this.getAttribute('data-id');
        nomeServico = this.getAttribute('data-servico');

        document.getElementById('nomeServicoModal').textContent = nomeServico;
        inputServicoId.value = servicoSelecionadoId;

        // --- Resetar o estado do modal principal de agendamento (primeiro modal) ---
        document.getElementById('selectProfissional').value = '';
        profissionalSelecionadoId = null;
        nomeProfissional = '';
        inputProfissionalId.value = '';

        mesAtualOffset = 0;
        criarCalendario(mesAtualOffset);

        // --- Resetar o estado do modal de confirmação (segundo modal) ---
        document.getElementById('selectHora').innerHTML = '<option value="">Selecione um horário</option>';
        inputHora.value = '';
        horaSelecionadaGlobal = null;

        nomeClienteCadastro.value = '';
        emailClienteCadastro.value = '';
        telefoneClienteCadastro.value = '';
        senhaCadastro.value = '';
        emailClienteLogin.value = '';
        senhaLogin.value = '';

        linkFazerLogin.style.display = 'inline';
        linkFazerCadastro.style.display = 'inline';

        nomeClienteCadastro.removeAttribute('readonly');
        emailClienteCadastro.removeAttribute('readonly');
        telefoneClienteCadastro.removeAttribute('readonly');
        senhaCadastro.setAttribute('required', 'required');
        senhaCadastro.style.display = 'block';

        formAgendamento.style.display = 'none';
        formCadastro.style.display = 'none';
        formLogin.style.display = 'none';

        modalAgendamentoInstancia.show();
    });
});

document.getElementById('selectProfissional').addEventListener('change', function() {
    inputProfissionalId.value = this.value;
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