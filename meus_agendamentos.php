<?php
session_start();
include 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['cliente_id'])) {
    $_SESSION['mensagem'] = 'Você precisa fazer login para acessar seus agendamentos.';
    $_SESSION['tipo_mensagem'] = 'warning';
    header('Location: index.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// Processar cancelamento de agendamento
if (isset($_POST['cancelar_agendamento'])) {
    $agendamento_id = (int)$_POST['agendamento_id'];
    $motivo = mysqli_real_escape_string($conexao, $_POST['motivo_cancelamento']);
    
    // Buscar informações do agendamento antes de cancelar
    $sql = "SELECT a.*, p.nome as nome_profissional, s.servico 
            FROM agendamentos a 
            JOIN profissionais p ON a.profissional_id = p.id 
            JOIN servicos s ON a.servico_id = s.id 
            WHERE a.id = $agendamento_id AND a.cliente_id = $cliente_id";
    $result = mysqli_query($conexao, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $agendamento = mysqli_fetch_assoc($result);
        
        // Atualizar status para cancelado
        $sql_update = "UPDATE agendamentos SET status = 'cancelado', motivo_cancelamento = '$motivo' WHERE id = $agendamento_id AND cliente_id = $cliente_id";
        
        if (mysqli_query($conexao, $sql_update)) {
            // Enviar e-mail de notificação
            $to = "clinica@example.com"; // E-mail da clínica
            $subject = "Agendamento Cancelado - ID: $agendamento_id";
            $message = "Um agendamento foi cancelado:\n\n" .
                       "Cliente: {$_SESSION['nome_cliente']}\n" .
                       "E-mail: {$_SESSION['email_cliente']}\n" .
                       "Telefone: {$_SESSION['telefone_cliente']}\n" .
                       "Serviço: {$agendamento['servico']}\n" .
                       "Profissional: {$agendamento['nome_profissional']}\n" .
                       "Data: {$agendamento['data']}\n" .
                       "Hora: {$agendamento['hora']}\n" .
                       "Motivo do cancelamento: $motivo";
            
            $headers = "From: {$_SESSION['email_cliente']}\r\n";
            
            mail($to, $subject, $message, $headers);
            
            $_SESSION['mensagem'] = 'Agendamento cancelado com sucesso.';
            $_SESSION['tipo_mensagem'] = 'success';
        } else {
            $_SESSION['mensagem'] = 'Erro ao cancelar agendamento.';
            $_SESSION['tipo_mensagem'] = 'danger';
        }
    } else {
        $_SESSION['mensagem'] = 'Agendamento não encontrado ou não pertence a este cliente.';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
    
    header('Location: meus_agendamentos.php');
    exit;
}

// Buscar agendamentos do cliente
$sql = "SELECT a.*, p.nome as nome_profissional, s.servico 
        FROM agendamentos a 
        JOIN profissionais p ON a.profissional_id = p.id 
        JOIN servicos s ON a.servico_id = s.id 
        WHERE a.cliente_id = $cliente_id 
        ORDER BY a.data DESC, a.hora DESC";
$result = mysqli_query($conexao, $sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - Clínica Nutrição</title>
    
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
        <h2 class="mb-4">Meus Agendamentos</h2>
        
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Serviço</th>
                            <th>Profissional</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['servico']) ?></td>
                                <td><?= htmlspecialchars($row['nome_profissional']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                                <td><?= substr($row['hora'], 0, 5) ?></td>
                                <td>
                                    <?php if ($row['status'] == 'confirmado'): ?>
                                        <span class="badge bg-success">Confirmado</span>
                                    <?php elseif ($row['status'] == 'cancelado'): ?>
                                        <span class="badge bg-danger">Cancelado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    // Verificar se o agendamento é futuro e está confirmado
                                    $data_agendamento = strtotime($row['data'] . ' ' . $row['hora']);
                                    $agora = time();
                                    
                                    if ($data_agendamento > $agora && $row['status'] == 'confirmado'): 
                                    ?>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalAlterar<?= $row['id'] ?>">
                                            <i class="fas fa-edit"></i> Alterar
                                        </button>
                                        <button class="btn btn-sm btn-danger ms-1" data-bs-toggle="modal" data-bs-target="#modalCancelar<?= $row['id'] ?>">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                        
                                        <!-- Modal de Cancelamento -->
                                        <div class="modal fade" id="modalCancelar<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Cancelamento</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p>Tem certeza que deseja cancelar este agendamento?</p>
                                                            <p><strong>Serviço:</strong> <?= htmlspecialchars($row['servico']) ?></p>
                                                            <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($row['data'])) ?> às <?= substr($row['hora'], 0, 5) ?></p>
                                                            
                                                            <div class="mb-3">
                                                                <label for="motivo_cancelamento" class="form-label">Motivo do cancelamento:</label>
                                                                <textarea class="form-control" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
                                                            </div>
                                                            
                                                            <input type="hidden" name="agendamento_id" value="<?= $row['id'] ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                                                            <button type="submit" name="cancelar_agendamento" class="btn btn-danger">Confirmar Cancelamento</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal de Alteração -->
                                        <div class="modal fade" id="modalAlterar<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Alterar Agendamento</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Para alterar este agendamento, você será redirecionado para a página de edição.</p>
                                                        <p><strong>Serviço:</strong> <?= htmlspecialchars($row['servico']) ?></p>
                                                        <p><strong>Data atual:</strong> <?= date('d/m/Y', strtotime($row['data'])) ?> às <?= substr($row['hora'], 0, 5) ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                                                        <a href="editar_agendamento.php?id=<?= $row['id'] ?>" class="btn btn-primary">Continuar para Edição</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Você ainda não possui agendamentos.
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php#agendamento" class="btn btn-primary">
                <i class="fas fa-calendar-plus me-2"></i> Novo Agendamento
            </a>
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
        });
    </script>
</body>
</html>