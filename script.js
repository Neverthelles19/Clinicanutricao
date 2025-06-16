// Variáveis globais
let mesAtualOffset = 0; // 0 é o mês atual, 1 é o próximo, etc.
let diasAtendimentoProfissional = [];
let horaInicio = "09:00"; // Hora de início padrão, será atualizada pelo profissional
let horaFim = "17:00"; // Hora de fim padrão, será atualizada pelo profissional
let servicoSelecionadoId = null;
let profissionalSelecionadoId = null; // Inicialmente nulo, pois nenhum profissional está selecionado
let nomeServico = '';
let nomeProfissional = '';
let valorServico = '';
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
    
    // Adiciona evento para fechar os modais quando o botão X é clicado
    document.querySelectorAll('.btn-close').forEach(function(button) {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    });

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
    if (telefoneClienteCadastro) {
        formatarTelefoneInput(telefoneClienteCadastro);
    }

    // Event Listeners para alternar entre login e cadastro dentro do MODAL DE CONFIRMAÇÃO
    if (linkFazerLogin) {
        linkFazerLogin.addEventListener('click', function(e) {
            e.preventDefault();
            mostrarFormLogin();
        });
    }

    if (linkFazerCadastro) {
        linkFazerCadastro.addEventListener('click', function(e) {
            e.preventDefault();
            mostrarFormCadastro();
        });
    }
    
    // Event listeners para os botões de alternância entre cadastro e login
    if (document.getElementById('btnCadastro')) {
        document.getElementById('btnCadastro').addEventListener('click', function() {
            mostrarFormCadastro();
        });
    }
    
    if (document.getElementById('btnLogin')) {
        document.getElementById('btnLogin').addEventListener('click', function() {
            mostrarFormLogin();
        });
    }

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
    // Limpar todos os campos do formulário
    if (document.getElementById('nome_cliente_cadastro')) document.getElementById('nome_cliente_cadastro').value = '';
    if (document.getElementById('telefone_cliente_cadastro')) document.getElementById('telefone_cliente_cadastro').value = '';
    if (document.getElementById('email_cliente_cadastro')) document.getElementById('email_cliente_cadastro').value = '';
    if (document.getElementById('senha_cadastro')) document.getElementById('senha_cadastro').value = '';
    if (document.getElementById('email_cliente_login')) document.getElementById('email_cliente_login').value = '';
    if (document.getElementById('senha_login')) document.getElementById('senha_login').value = '';
    
    // Verificar se o usuário está logado antes de prosseguir
    const previouslySelected = document.querySelector('.calendario-dia.selecionado');
    if (previouslySelected) {
        previouslySelected.classList.remove('selecionado');
    }

    this.classList.add('selecionado');

    dataSelecionadaGlobal = this.getAttribute('data-dia');
    inputData.value = dataSelecionadaGlobal;

    document.getElementById('confirmServico').textContent = nomeServico;
    document.getElementById('confirmProfissional').textContent = nomeProfissional;
    document.getElementById('valorServico').textContent = valorServico;
    const dataExibicaoFormatada = new Date(dataSelecionadaGlobal + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('confirmData').textContent = dataExibicaoFormatada;

    carregarHorariosDisponiveis(dataSelecionadaGlobal);

    modalAgendamentoInstancia.hide();
    modalConfirmacaoInstancia.show();
}

// Função assíncrona para carregar horários disponíveis do servidor
async function carregarHorariosDisponiveis(dataSelecionada) {
    const selectHora = document.getElementById('selectHora');
    if (!selectHora) {
        console.error("Elemento selectHora não encontrado!");
        return;
    }
    
    selectHora.innerHTML = '<option value="">Carregando horários...</option>';
    
    if (formAgendamento) {
        formAgendamento.style.display = 'none';
    }

    try {
        // Usar o endpoint horarios_disponiveis.php para obter horários disponíveis
        const url = `horarios_disponiveis.php?profId=${profissionalSelecionadoId}&data=${dataSelecionada}&servicoId=${servicoSelecionadoId}`;
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Erro HTTP ao buscar horários: ${response.status}`);
        }
        
        // A resposta já vem formatada como HTML com as opções
        const htmlOptions = await response.text();
        
        selectHora.innerHTML = htmlOptions;
        
        // Verificar se há opções válidas
        const hasValidOptions = Array.from(selectHora.options).some(option => option.value !== '');
        
        // Garantir que o evento onchange esteja configurado
        if (typeof mostrarFormulario === 'function') {
            selectHora.onchange = mostrarFormulario;
        }
        
    } catch (error) {
        console.error('Erro ao buscar horários disponíveis:', error);
        selectHora.innerHTML = '<option value="">Erro ao carregar horários. Tente novamente.</option>';
        if (formAgendamento) {
            formAgendamento.style.display = 'none';
        }
    }
}

// Event listener para a seleção de profissional no PRIMEIRO MODAL
document.getElementById('selectProfissional').addEventListener('change', function() {
    const profissionalSelecionadoOption = this.options[this.selectedIndex];

    // Limpar todos os campos do formulário
    if (document.getElementById('nome_cliente_cadastro')) document.getElementById('nome_cliente_cadastro').value = '';
    if (document.getElementById('telefone_cliente_cadastro')) document.getElementById('telefone_cliente_cadastro').value = '';
    if (document.getElementById('email_cliente_cadastro')) document.getElementById('email_cliente_cadastro').value = '';
    if (document.getElementById('senha_cadastro')) document.getElementById('senha_cadastro').value = '';
    if (document.getElementById('email_cliente_login')) document.getElementById('email_cliente_login').value = '';
    if (document.getElementById('senha_login')) document.getElementById('senha_login').value = '';

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
    
    // Atualiza os botões de alternância
    document.getElementById('btnCadastro').classList.add('active');
    document.getElementById('btnLogin').classList.remove('active');

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
    
    // Define o valor do campo hidden para indicar que é um cadastro
    document.getElementById('isCadastroForm').value = '1';
}

// Função para mostrar o formulário de login e ocultar o de cadastro
function mostrarFormLogin() {
    formLogin.style.display = 'block';
    formCadastro.style.display = 'none';
    
    // Atualiza os botões de alternância
    document.getElementById('btnCadastro').classList.remove('active');
    document.getElementById('btnLogin').classList.add('active');

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
    
    // Define o valor do campo hidden para indicar que é um login
    document.getElementById('isCadastroForm').value = '0';
}

// Função para pré-preencher dados do cliente logado e desabilitar campos
function preencherDadosClienteLogado(nome, email, telefone) {
    // Limpar campos primeiro para garantir que não haja dados de sessões anteriores
    limparCamposFormulario();
    
    // Preencher com os dados do usuário logado
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
    if (!inputElement) return;
    
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

// Event listener para os botões "Agendar Agora" nos cards de serviço
document.querySelectorAll('.agendarBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        servicoSelecionadoId = this.getAttribute('data-id');
        nomeServico = this.getAttribute('data-servico');
        valorServico = this.getAttribute('data-valor');

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

        // Limpar todos os campos de formulário
        limparCamposFormulario();

        modalAgendamentoInstancia.show();
    });
});

// Função para limpar todos os campos de formulário
function limparCamposFormulario() {
    try {
        // Limpar campos de cadastro se existirem
        if (nomeClienteCadastro) nomeClienteCadastro.value = '';
        if (emailClienteCadastro) emailClienteCadastro.value = '';
        if (telefoneClienteCadastro) telefoneClienteCadastro.value = '';
        if (senhaCadastro) senhaCadastro.value = '';
        
        // Limpar campos de login se existirem
        if (emailClienteLogin) emailClienteLogin.value = '';
        if (senhaLogin) senhaLogin.value = '';

        // Resetar atributos se os elementos existirem
        if (nomeClienteCadastro) nomeClienteCadastro.removeAttribute('readonly');
        if (emailClienteCadastro) emailClienteCadastro.removeAttribute('readonly');
        if (telefoneClienteCadastro) telefoneClienteCadastro.removeAttribute('readonly');
        if (senhaCadastro) {
            senhaCadastro.setAttribute('required', 'required');
            senhaCadastro.style.display = 'block';
        }

        // Mostrar links de alternância se existirem
        if (linkFazerLogin) linkFazerLogin.style.display = 'inline';
        if (linkFazerCadastro) linkFazerCadastro.style.display = 'inline';

        // Ocultar formulários se existirem
        if (formAgendamento) formAgendamento.style.display = 'none';
        if (formCadastro) formCadastro.style.display = 'none';
        if (formLogin) formLogin.style.display = 'none';
        
        // Remover qualquer mensagem de usuário logado que possa existir
        if (formAgendamento) {
            const alertasExistentes = formAgendamento.querySelectorAll('.alert');
            alertasExistentes.forEach(alerta => alerta.remove());
        }
    } catch (error) {
        console.error("Erro ao limpar campos:", error);
    }
}

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