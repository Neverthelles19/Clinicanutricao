<?php
session_start();
include 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['cliente_id'])) {
    $_SESSION['mensagem'] = 'Você precisa fazer login para editar agendamentos.';
    $_SESSION['tipo_mensagem'] = 'warning';
    header('Location: index.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$agendamento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verificar se o agendamento existe e pertence ao cliente
$sql = "SELECT a.*, p.nome as nome_profissional, p.id as profissional_id, s.servico, s.id as servico_id 
        FROM agendamentos a 
        JOIN profissionais p ON a.profissional_id = p.id 
        JOIN servicos s ON a.servico_id = s.id 
        WHERE a.id = $agendamento_id AND a.cliente_id = $cliente_id AND a.status = 'confirmado'";
$result = mysqli_query($conexao, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    $_SESSION['mensagem'] = 'Agendamento não encontrado ou não pode ser editado.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: meus_agendamentos.php');
    exit;
}

$agendamento = mysqli_fetch_assoc($result);

// Verificar se a data do agendamento já passou
$data_agendamento = strtotime($agendamento['data'] . ' ' . $agendamento['hora']);
if ($data_agendamento <= time()) {
    $_SESSION['mensagem'] = 'Não é possível editar agendamentos passados.';
    $_SESSION['tipo_mensagem'] = 'warning';
    header('Location: meus_agendamentos.php');
    exit;
}

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_data = mysqli_real_escape_string($conexao, $_POST['data']);
    $nova_hora = mysqli_real_escape_string($conexao, $_POST['hora']);
    $motivo = mysqli_real_escape_string($conexao, $_POST['motivo_alteracao']);
    
    // Verificar se o horário está disponível
    $sql_check = "SELECT id FROM agendamentos 
                 WHERE profissional_id = {$agendamento['profissional_id']} 
                 AND data = '$nova_data' 
                 AND hora = '$nova_hora' 
                 AND id != $agendamento_id 
                 AND status = 'confirmado'";
    $result_check = mysqli_query($conexao, $sql_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        $_SESSION['mensagem'] = 'Este horário já está ocupado. Por favor, escolha outro.';
        $_SESSION['tipo_mensagem'] = 'danger';
    } else {
        // Guardar os dados antigos para o e-mail
        $dados_antigos = "Data anterior: " . date('d/m/Y', strtotime($agendamento['data'])) . " às " . substr($agendamento['hora'], 0, 5);
        $dados_novos = "Nova data: " . date('d/m/Y', strtotime($nova_data)) . " às " . substr($nova_hora, 0, 5);
        
        // Atualizar o agendamento
        $sql_update = "UPDATE agendamentos 
                      SET data = '$nova_data', hora = '$nova_hora', motivo_alteracao = '$motivo' 
                      WHERE id = $agendamento_id AND cliente_id = $cliente_id";
        
        if (mysqli_query($conexao, $sql_update)) {
            // Enviar e-mail de notificação
            $to = "clinica@example.com"; // E-mail da clínica
            $subject = "Agendamento Alterado - ID: $agendamento_id";
            $message = "Um agendamento foi alterado:\n\n" .
                       "Cliente: {$_SESSION['nome_cliente']}\n" .
                       "E-mail: {$_SESSION['email_cliente']}\n" .
                       "Telefone: {$_SESSION['telefone_cliente']}\n" .
                       "Serviço: {$agendamento['servico']}\n" .
                       "Profissional: {$agendamento['nome_profissional']}\n" .
                       "$dados_antigos\n" .
                       "$dados_novos\n" .
                       "Motivo da alteração: $motivo";
            
            $headers = "From: {$_SESSION['email_cliente']}\r\n";
            
            mail($to, $subject, $message, $headers);
            
            $_SESSION['mensagem'] = 'Agendamento alterado com sucesso.';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: meus_agendamentos.php');
            exit;
        } else {
            $_SESSION['mensagem'] = 'Erro ao alterar agendamento.';
            $_SESSION['tipo_mensagem'] = 'danger';
        }
    }
}

// Buscar datas disponíveis para o profissional
$sql_dias = "SELECT dias_disponiveis FROM profissionais WHERE id = {$agendamento['profissional_id']}";
$result_dias = mysqli_query($conexao, $sql_dias);
$row_dias = mysqli_fetch_assoc($result_dias);
$dias_disponiveis = explode(',', $row_dias['dias_disponiveis']);

// Mapear nomes dos dias para números
function mapDaysToNumbers($dayNames) {
    $dayMap = [
        'Domingo' => '0',
        'Segunda' => '1',
        'Terça'   => '2',
        'Quarta'  => '3',
        'Quinta'  => '4',
        'Sexta'   => '5',
        'Sábado'  => '6'
    ];
    
    $dayNumbers = [];
    foreach ($dayNames as $dayName) {
        $trimmedDayName = trim($dayName);
        if (isset($dayMap[$trimmedDayName])) {
            $dayNumbers[] = $dayMap[$trimmedDayName];
        }
    }
    return $dayNumbers;
}

$dias_numericos = mapDaysToNumbers($dias_disponiveis);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Agendamento - Clínica Nutrição</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="styleUser.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gradient-text" href="index.php">
                    <i class="fas fa-heartbeat me-2"></i>
                    <span>Clínica Nutrição</span>
                </a>
                <div class="ms-auto d-flex">
                    <a href="index.php" class="btn btn-sm rounded-pill px-3 me-2 btn-inicio">
                        <i class="fas fa-home me-1"></i> Início
                    </a>
                    <a href="meus_agendamentos.php" class="btn btn-sm rounded-pill px-3 me-2 btn-outline-primary">
                        <i class="fas fa-calendar me-1"></i> Meus Agendamentos
                    </a>
                    <a href="logout.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-sign-out-alt me-1"></i> Sair
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="toast show align-items-center text-white bg-<?= $_SESSION['tipo_mensagem'] ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= htmlspecialchars($_SESSION['mensagem']) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
                </div>
            </div>
            <?php 
            // Limpar mensagens após exibição
            unset($_SESSION['mensagem']);
            unset($_SESSION['tipo_mensagem']);
            ?>
        <?php endif; ?>
    </div>

    <main class="container py-5">
        <h2 class="mb-4">Editar Agendamento</h2>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Detalhes do Agendamento</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Serviço:</strong> <?= htmlspecialchars($agendamento['servico']) ?></p>
                        <p><strong>Profissional:</strong> <?= htmlspecialchars($agendamento['nome_profissional']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Data atual:</strong> <?= date('d/m/Y', strtotime($agendamento['data'])) ?></p>
                        <p><strong>Hora atual:</strong> <?= substr($agendamento['hora'], 0, 5) ?></p>
                    </div>
                </div>
                
                <form method="post" id="formEditar">
                    <div class="mb-3">
                        <label for="data" class="form-label">Nova Data</label>
                        <input type="date" class="form-control" id="data" name="data" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hora" class="form-label">Novo Horário</label>
                        <select class="form-select" id="hora" name="hora" required>
                            <option value="">Selecione uma data primeiro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo_alteracao" class="form-label">Motivo da Alteração</label>
                        <textarea class="form-control" id="motivo_alteracao" name="motivo_alteracao" rows="3" required></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="meus_agendamentos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0 text-muted">&copy; <?= date('Y'); ?> Clínica Nutrição. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializa os toasts do Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
            });
            toastList.forEach(toast => toast.show());
            
            // Dias disponíveis do profissional
            const diasDisponiveis = <?= json_encode($dias_numericos) ?>;
            const dataInput = document.getElementById('data');
            const horaSelect = document.getElementById('hora');
            
            // Quando a data for alterada
            dataInput.addEventListener('change', function() {
                const dataEscolhida = new Date(this.value);
                const diaSemana = dataEscolhida.getDay().toString();
                
                // Verificar se o dia escolhido está disponível
                if (!diasDisponiveis.includes(diaSemana)) {
                    alert('O profissional não atende neste dia da semana. Por favor, escolha outra data.');
                    this.value = '';
                    horaSelect.innerHTML = '<option value="">Selecione uma data primeiro</option>';
                    return;
                }
                
                // Buscar horários disponíveis
                fetch(`horarios_disponiveis.php?profId=<?= $agendamento['profissional_id'] ?>&data=${this.value}&servicoId=<?= $agendamento['servico_id'] ?>`)
                    .then(response => response.text())
                    .then(html => {
                        horaSelect.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Erro ao buscar horários:', error);
                        horaSelect.innerHTML = '<option value="">Erro ao carregar horários</option>';
                    });
            });
        });
    </script>
</body>
</html>