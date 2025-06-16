<?php
session_start(); // Inicia a sessão no início do script

// Inclui o arquivo de conexão com o banco de dados.
// Certifique-se de que 'conexao.php' está no mesmo diretório ou ajuste o caminho.
include 'conexao.php';

// Verifica se a conexão com o banco de dados falhou
if (!$conexao) {
    // Se houver um erro de conexão, loga o erro e redireciona com uma mensagem genérica
    error_log("Erro de conexão com o banco de dados em processa_agendamento.php: " . mysqli_connect_error());
    redirecionarComMensagem("Erro interno do servidor. Tente novamente mais tarde.", "danger", null);
}

// =================================================================================
// 1. Função Auxiliar para Redirecionamento e Mensagens
// =================================================================================

/**
 * Redireciona o usuário para a página inicial (ou outra especificada)
 * com uma mensagem de feedback armazenada na sessão.
 *
 * @param string $msg A mensagem a ser exibida.
 * @param string $type O tipo de alerta Bootstrap (e.g., 'success', 'danger', 'warning', 'info').
 * @param mysqli|null $conexao A conexão mysqli a ser fechada antes do redirecionamento.
 */
function redirecionarComMensagem($msg, $type, $conexao) {
    $_SESSION['mensagem'] = $msg;
    $_SESSION['tipo_mensagem'] = $type;
    // Fecha a conexão com o banco de dados, se estiver aberta
    if ($conexao && is_object($conexao) && method_exists($conexao, 'close')) {
        $conexao->close();
    }
    header('Location: index.php'); // Redireciona para a página principal
    exit(); // Garante que o script pare de ser executado após o redirecionamento
}

// =================================================================================
// 2. Processamento da Requisição POST
// =================================================================================

// Verifica se a requisição HTTP é do tipo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Coleta e sanitiza os dados essenciais do agendamento.
    // FILTER_VALIDATE_INT garante que o valor é um número inteiro ou nulo/falso.
    // FILTER_SANITIZE_STRING remove tags HTML e caracteres indesejados.
    $servico_id = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
    $profissional_id = filter_input(INPUT_POST, 'profissional_id', FILTER_VALIDATE_INT);
    $data = filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING); // Formato YYYY-MM-DD
    $hora = filter_input(INPUT_POST, 'hora', FILTER_SANITIZE_STRING); // Formato HH:MM

    // Validação inicial dos dados básicos do agendamento.
    // Se algum dado essencial estiver faltando ou for inválido, redireciona com erro.
    if (!$servico_id || !$profissional_id || empty($data) || empty($hora)) {
        redirecionarComMensagem("Erro: Dados do serviço, profissional, data ou hora estão incompletos ou inválidos.", "danger", $conexao);
    }

    // =================================================================================
    // 3. Lógica de Autenticação/Cadastro do Cliente
    // =================================================================================

    $cliente_id = null; // Variável para armazenar o ID do cliente logado ou cadastrado

    // Verifica se o cliente já está logado (pela sessão)
    if (isset($_SESSION['cliente_id']) && $_SESSION['cliente_id'] > 0) {
        $cliente_id = $_SESSION['cliente_id']; // Usa o ID do cliente já logado
        // Mensagem informativa, mas não redireciona ainda, pois o agendamento precisa ser processado
    } else {
        // Se o cliente NÃO está logado, ele precisa se cadastrar ou fazer login

        $is_cadastro_form = filter_input(INPUT_POST, 'is_cadastro_form', FILTER_VALIDATE_INT);

        // Cenário 1: Tentativa de CADASTRO de novo cliente
        if ($is_cadastro_form === 1) { // '1' indica que o formulário de cadastro foi exibido e submetido
            $nome_cliente = filter_input(INPUT_POST, 'nome_cliente', FILTER_SANITIZE_STRING);
            $email_cliente = filter_input(INPUT_POST, 'email_cliente', FILTER_VALIDATE_EMAIL); // Valida o formato do e-mail
            $telefone_cliente = filter_input(INPUT_POST, 'telefone_cliente', FILTER_SANITIZE_STRING);
            $senha_cadastro = $_POST['senha'] ?? ''; // Pega a senha do campo 'senha' do formulário de cadastro

            // Validação dos campos de cadastro
            if (empty($nome_cliente) || !$email_cliente || empty($telefone_cliente) || empty($senha_cadastro)) {
                redirecionarComMensagem("Erro no cadastro: Por favor, preencha todos os campos corretamente para se cadastrar.", "danger", $conexao);
            }

            // Verifica se o e-mail já está em uso para evitar duplicidade
            $stmt_check_email = $conexao->prepare("SELECT id FROM clientes WHERE email = ?");
            if ($stmt_check_email) {
                $stmt_check_email->bind_param("s", $email_cliente);
                $stmt_check_email->execute();
                $stmt_check_email->store_result(); // Armazena o resultado para verificar o número de linhas
                if ($stmt_check_email->num_rows > 0) {
                    redirecionarComMensagem("Erro no cadastro: Este e-mail já está registrado. Por favor, faça login se você já tem uma conta.", "warning", $conexao);
                }
                $stmt_check_email->close();
            } else {
                error_log("Erro ao preparar consulta de verificação de e-mail: " . $conexao->error);
                redirecionarComMensagem("Erro interno. Tente novamente mais tarde.", "danger", $conexao);
            }

            // Gera o hash da senha de forma segura (NUNCA armazene senhas em texto puro!)
            $senha_hash = password_hash($senha_cadastro, PASSWORD_DEFAULT);

            // Insere o novo cliente no banco de dados usando prepared statement
            $stmt_insert_cliente = $conexao->prepare("INSERT INTO clientes (nome, email, telefone, senha) VALUES (?, ?, ?, ?)");
            if ($stmt_insert_cliente) {
                $stmt_insert_cliente->bind_param("ssss", $nome_cliente, $email_cliente, $telefone_cliente, $senha_hash);
                if ($stmt_insert_cliente->execute()) {
                    $cliente_id = $conexao->insert_id; // Obtém o ID do cliente recém-cadastrado
                    // Define as variáveis de sessão para manter o cliente logado após o cadastro
                    $_SESSION['cliente_id'] = $cliente_id;
                    $_SESSION['nome_cliente'] = $nome_cliente;
                    $_SESSION['email_cliente'] = $email_cliente;
                    $_SESSION['telefone_cliente'] = $telefone_cliente;
                    // Sucesso no cadastro/login, continua para o agendamento
                } else {
                    error_log("Erro ao executar inserção de cliente: " . $stmt_insert_cliente->error);
                    redirecionarComMensagem("Erro ao cadastrar cliente. Por favor, tente novamente.", "danger", $conexao);
                }
                $stmt_insert_cliente->close();
            } else {
                error_log("Erro ao preparar inserção de cliente: " . $conexao->error);
                redirecionarComMensagem("Erro interno. Tente novamente mais tarde.", "danger", $conexao);
            }

        } else if ($is_cadastro_form === 0) { // '0' indica que o formulário de LOGIN foi exibido e submetido
            $email_login = filter_input(INPUT_POST, 'email_login', FILTER_VALIDATE_EMAIL);
            $senha_login = $_POST['senha_login'] ?? ''; // Pega a senha do campo 'senha_login' do formulário de login

            // Validação dos campos de login
            if (!$email_login || empty($senha_login)) {
                redirecionarComMensagem("Erro no login: Por favor, preencha seu e-mail e senha.", "danger", $conexao);
            }

            // Busca o cliente pelo e-mail para verificar as credenciais
            $stmt_login = $conexao->prepare("SELECT id, nome, email, telefone, senha FROM clientes WHERE email = ?");
            if ($stmt_login) {
                $stmt_login->bind_param("s", $email_login);
                $stmt_login->execute();
                $result_login = $stmt_login->get_result();
                if ($result_login->num_rows > 0) {
                    $cliente = $result_login->fetch_assoc();
                    // Verifica a senha fornecida com o hash armazenado
                    if (password_verify($senha_login, $cliente['senha'])) {
                        $cliente_id = $cliente['id'];
                        // Define as variáveis de sessão para manter o cliente logado
                        $_SESSION['cliente_id'] = $cliente_id;
                        $_SESSION['nome_cliente'] = $cliente['nome'];
                        $_SESSION['email_cliente'] = $cliente['email'];
                        $_SESSION['telefone_cliente'] = $cliente['telefone'];
                        // Sucesso no login, continua para o agendamento
                    } else {
                        redirecionarComMensagem("Erro no login: E-mail ou senha incorretos.", "danger", $conexao);
                    }
                } else {
                    redirecionarComMensagem("Erro no login: E-mail ou senha incorretos.", "danger", $conexao);
                }
                $stmt_login->close();
            } else {
                error_log("Erro ao preparar consulta de login: " . $conexao->error);
                redirecionarComMensagem("Erro interno. Tente novamente mais tarde.", "danger", $conexao);
            }
        } else {
            // Caso $is_cadastro_form não seja '0' nem '1' e o cliente não esteja logado
            redirecionarComMensagem("Erro: A requisição de autenticação é inválida.", "danger", $conexao);
        }
    }

    // =================================================================================
    // 4. Lógica de Agendamento (Executa SOMENTE se $cliente_id for válido)
    // =================================================================================

    // Se um cliente_id foi obtido (seja por login, cadastro ou já estava logado),
    // prossegue com o registro do agendamento.
    if ($cliente_id) {

        // --- Verificação de Conflito de Horário (Backend Validation) ---
        // É crucial verificar a disponibilidade novamente no backend para evitar agendamentos duplos
        // se o usuário, por exemplo, abrir duas abas ou enviar a requisição várias vezes.

        // Primeiro, obtemos a duração do serviço para verificar o "slot" completo
        $duracao_servico = 0; // Inicializa com 0
        $stmt_duracao = $conexao->prepare("SELECT duracao FROM servicos WHERE id = ?");
        if ($stmt_duracao) {
            $stmt_duracao->bind_param("i", $servico_id);
            $stmt_duracao->execute();
            $result_duracao = $stmt_duracao->get_result();
            if ($result_duracao->num_rows > 0) {
                $servico_info = $result_duracao->fetch_assoc();
                $duracao_servico = (int)$servico_info['duracao'];
            }
            $stmt_duracao->close();
        }

        // Se a duração não foi encontrada ou é inválida, é um erro
        if ($duracao_servico <= 0) {
            redirecionarComMensagem("Erro: Duração do serviço não encontrada ou é inválida. Não foi possível agendar.", "danger", $conexao);
        }

        // Converter a hora selecionada e a duração para objetos DateTime para fácil comparação
        // Adiciona ':00' aos segundos para garantir formato completo TIME para MySQL
        $agendamento_inicio_str = $hora . ':00';
        // Calcula o timestamp de início
        $timestamp_inicio = strtotime($data . ' ' . $agendamento_inicio_str);
        // Calcula o timestamp de fim adicionando a duração em minutos (convertida para segundos)
        $timestamp_fim = $timestamp_inicio + ($duracao_servico * 60);
        // Formata o timestamp de fim para HH:MM:SS
        $agendamento_fim_str = date('H:i:s', $timestamp_fim);

        // Consulta para verificar agendamentos existentes que possam conflitar com o novo.
        // A lógica de sobreposição de intervalos:
        // (A.start <= B.end) AND (A.end >= B.start)
        // Onde A é o novo agendamento e B é um agendamento existente.
        $stmt_conflito = $conexao->prepare("
            SELECT
                a.id
            FROM
                agendamentos a
            JOIN
                servicos s ON a.servico_id = s.id
            WHERE
                a.profissional_id = ?
                AND a.data = ?
                AND (
                    -- Verifica se o novo agendamento se sobrepõe a um existente
                    (? < ADDTIME(a.hora, SEC_TO_TIME(s.duracao * 60))) AND (? > a.hora)
                )
        ");
        
        if ($stmt_conflito) {
            $stmt_conflito->bind_param("isss",
                $profissional_id,
                $data,
                $agendamento_inicio_str, // Início do novo agendamento (A.start)
                $agendamento_fim_str // Fim do novo agendamento (A.end)
            );
            
            $stmt_conflito->execute();
            $result_conflito = $stmt_conflito->get_result();

            if ($result_conflito->num_rows > 0) {
                // Conflito encontrado: o horário selecionado já está ocupado ou se sobrepõe
                redirecionarComMensagem("Erro: O horário selecionado já está ocupado ou se sobrepõe a outro agendamento. Por favor, escolha outro horário.", "warning", $conexao);
            }
            $stmt_conflito->close();
        } else {
            error_log("Erro ao preparar consulta de conflito: " . $conexao->error);
            redirecionarComMensagem("Erro interno ao verificar disponibilidade. Tente novamente mais tarde.", "danger", $conexao);
        }

        // Se não houver conflito, insere o novo agendamento
        $stmt_insert_agendamento = $conexao->prepare("INSERT INTO agendamentos (cliente_id, profissional_id, servico_id, data, hora, status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_insert_agendamento) {
            // "iiiss" -> inteiros para IDs, strings para data, hora e status
            $status_agendamento = 'confirmado'; // Status padrão
            $stmt_insert_agendamento->bind_param("iiisss", $cliente_id, $profissional_id, $servico_id, $data, $hora, $status_agendamento);

            if ($stmt_insert_agendamento->execute()) {
                redirecionarComMensagem("Agendamento confirmado com sucesso! Verifique seus agendamentos.", "success", $conexao);
            } else {
                error_log("Erro ao executar inserção de agendamento: " . $stmt_insert_agendamento->error);
                redirecionarComMensagem("Erro ao agendar o serviço. Por favor, tente novamente.", "danger", $conexao);
            }
            $stmt_insert_agendamento->close();
        } else {
            error_log("Erro ao preparar inserção de agendamento: " . $conexao->error);
            redirecionarComMensagem("Erro interno. Tente novamente mais tarde.", "danger", $conexao);
        }

    } else {
        // Este caso não deveria ser atingido se a lógica de autenticação acima funcionar.
        // É um fallback para garantir que nenhum agendamento seja feito sem cliente_id.
        redirecionarComMensagem("Erro: Não foi possível identificar o cliente para o agendamento. Por favor, tente novamente.", "danger", $conexao);
    }

} else {
    // Se a requisição não for POST (ex: acesso direto ao arquivo via URL), redireciona
    redirecionarComMensagem("Requisição inválida. Acesso não permitido.", "danger", $conexao);
}

// Em qualquer caso, se o script chegar até aqui sem um redirecionamento, significa um erro inesperado
// ou que a lógica não cobriu um cenário, então redireciona com uma mensagem de erro genérica.
redirecionarComMensagem("Ocorreu um erro inesperado. Por favor, tente novamente.", "danger", $conexao);

?>