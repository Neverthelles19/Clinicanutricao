<?php
// Configurações de exibição de erros (desative em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("conexao.php");

if (isset($conexao) && $conexao instanceof mysqli) {
    $conexao->set_charset("utf8");
} else {
    die("Erro: A variável de conexão não está definida ou não é um objeto mysqli válido.");
}

$mensagem_sucesso = '';
$mensagem_erro = '';

// --- Lógica de Exclusão (agora 'Cancelamento') ---
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['agendamento_id'])) {
    $agendamento_id_para_excluir = intval($_GET['agendamento_id']);

    $stmt_excluir = $conexao->prepare("DELETE FROM agendamentos WHERE id = ?");
    if ($stmt_excluir) {
        $stmt_excluir->bind_param("i", $agendamento_id_para_excluir);
        if ($stmt_excluir->execute()) {
            $mensagem_sucesso = "Agendamento cancelado com sucesso!"; // Mensagem atualizada
        } else {
            $mensagem_erro = "Erro ao cancelar agendamento: " . $stmt_excluir->error; // Mensagem atualizada
        }
        $stmt_excluir->close();
    } else {
        $mensagem_erro = "Erro na preparação do cancelamento: " . $conexao->error; // Mensagem atualizada
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?profissional_id=" . ($_GET['profissional_id'] ?? '') . "&data=" . ($_GET['data'] ?? date('Y-m-d')));
    exit();
}

// --- Lógica de Edição ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_agendamento') {
    $id_agendamento = intval($_POST['id_agendamento']);
    $nova_data = $_POST['edit_data'];
    $nova_hora = $_POST['edit_hora'];
    $novo_servico_id = intval($_POST['edit_servico_id']);
    $novo_cliente_nome = $_POST['edit_cliente_nome'];
    $novo_cliente_email = $_POST['edit_cliente_email'];
    $novo_cliente_telefone = $_POST['edit_cliente_telefone'];
    $cliente_id_oculto = intval($_POST['cliente_id_oculto']); // Novo campo oculto para o cliente_id

    // Inicia uma transação para garantir que ambas as atualizações ocorram ou nenhuma ocorra
    $conexao->begin_transaction();
    $sucesso_transacao = true;

    try {
        // 1. Atualiza os dados do cliente na tabela 'clientes'
        $stmt_update_cliente = $conexao->prepare("UPDATE clientes SET nome = ?, email = ?, telefone = ? WHERE id = ?");
        if ($stmt_update_cliente) {
            $stmt_update_cliente->bind_param("sssi", $novo_cliente_nome, $novo_cliente_email, $novo_cliente_telefone, $cliente_id_oculto);
            if (!$stmt_update_cliente->execute()) {
                throw new Exception("Erro ao atualizar dados do cliente: " . $stmt_update_cliente->error);
            }
            $stmt_update_cliente->close();
        } else {
            throw new Exception("Erro na preparação da atualização do cliente: " . $conexao->error);
        }

        // 2. Atualiza os dados do agendamento na tabela 'agendamentos'
        // NOTA: As colunas nome_cliente, email_cliente, telefone_cliente em 'agendamentos'
        // podem ser redundantes se você sempre busca de 'clientes'.
        // Se você decidiu mantê-las em 'agendamentos' para cache/desnormalização,
        // então elas também precisam ser atualizadas.
        // Se não, remova-as da query abaixo. Por segurança, vou incluí-las aqui
        // para corresponder ao seu schema existente, mas a atualização principal é no 'clientes'.
        $stmt_update_agendamento = $conexao->prepare("UPDATE agendamentos SET data = ?, hora = ?, servico_id = ?, nome_cliente = ?, email_cliente = ?, telefone_cliente = ? WHERE id = ?");
        if ($stmt_update_agendamento) {
            $stmt_update_agendamento->bind_param("ssisssi", $nova_data, $nova_hora, $novo_servico_id, $novo_cliente_nome, $novo_cliente_email, $novo_cliente_telefone, $id_agendamento);
            if (!$stmt_update_agendamento->execute()) {
                throw new Exception("Erro ao atualizar dados do agendamento: " . $stmt_update_agendamento->error);
            }
            $stmt_update_agendamento->close();
        } else {
            throw new Exception("Erro na preparação da atualização do agendamento: " . $conexao->error);
        }

        $conexao->commit();
        $mensagem_sucesso = "Agendamento e dados do cliente atualizados com sucesso!";

    } catch (Exception $e) {
        $conexao->rollback();
        $mensagem_erro = "Falha na edição: " . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?profissional_id=" . ($_POST['profissional_id_oculto'] ?? '') . "&data=" . ($_POST['data_oculta'] ?? date('Y-m-d')));
    exit();
}


$profissional_id = isset($_GET['profissional_id']) ? intval($_GET['profissional_id']) : null;
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Agenda do Profissional - Calendário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 50px;
        }
        .agenda-dia {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
        }
        .dia-header {
            background-color: #0d6efd;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .horario-slot {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 5px;
            background-color: #f0f8ff; /* Light blue background for time slots */
            border: 1px solid #d0e0ff;
        }
        .horario-slot strong {
            flex-shrink: 0; /* Prevent the time from shrinking */
            width: 70px; /* Fixed width for time */
            margin-right: 15px;
            text-align: right;
            font-size: 1.1em;
            color: #333;
        }
        .detalhes-consulta {
            flex-grow: 1;
            padding-left: 10px;
            border-left: 3px solid #0d6efd;
        }
        .detalhes-consulta p {
            margin-bottom: 3px;
        }
        .detalhes-consulta .btn-group {
            margin-top: 5px;
        }
        .navegacao-data {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .navegacao-data button {
            flex-grow: 1;
            margin: 0 5px;
        }
        .navegacao-data input[type="date"] {
            flex-grow: 2;
            text-align: center;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Agenda de Consultas do Profissional</h2>

    <?php if ($mensagem_sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $mensagem_sucesso ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $mensagem_erro ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="profissional" class="form-label">Profissional:</label>
                <select name="profissional_id" id="profissional" class="form-select" onchange="this.form.submit()" required>
                    <option value="">Selecione um profissional</option>
                    <?php
                    $profissionais = $conexao->query("SELECT id, nome FROM profissionais ORDER BY nome");
                    if ($profissionais) {
                        while ($prof = $profissionais->fetch_assoc()) {
                            $selected = ($prof['id'] == $profissional_id) ? 'selected' : '';
                            echo "<option value='{$prof['id']}' $selected>{$prof['nome']}</option>";
                        }
                        $profissionais->free();
                    } else {
                        echo "<option value=''>Erro ao carregar profissionais: " . $conexao->error . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="data" class="form-label">Visualizar Data:</label>
                <input type="date" name="data" id="data" class="form-control" value="<?= htmlspecialchars($data_selecionada) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary">Atualizar Agenda</button>
            </div>
        </div>
    </form>

    <?php if ($profissional_id): ?>
        <h4 class="mb-3 text-center">Agenda para <span class="text-primary"><?= date("d/m/Y", strtotime($data_selecionada)) ?></span></h4>

        <div class="navegacao-data mb-4">
            <?php
            $data_anterior = date('Y-m-d', strtotime($data_selecionada . ' -1 day'));
            $data_proxima = date('Y-m-d', strtotime($data_selecionada . ' +1 day'));
            ?>
            <a href="?profissional_id=<?= $profissional_id ?>&data=<?= $data_anterior ?>" class="btn btn-outline-secondary">&lt; Dia Anterior</a>
            <span class="flex-grow-2 text-center fs-5 text-muted"><?= date('l', strtotime($data_selecionada)) ?>, <?= date('d/m', strtotime($data_selecionada)) ?></span>
            <a href="?profissional_id=<?= $profissional_id ?>&data=<?= $data_proxima ?>" class="btn btn-outline-secondary">Próximo Dia &gt;</a>
        </div>

        <div class="agenda-dia">
            <div class="dia-header">
                <?= date("d/m/Y", strtotime($data_selecionada)) ?>
            </div>

            <?php
            // Use $conexao aqui para a query da agenda, filtrando pela data selecionada
            $sql = "
                SELECT a.id, a.data, a.hora, a.cliente_id, c.nome AS nome_cliente, c.email AS email_cliente, c.telefone AS telefone_cliente, s.id AS servico_id, s.servico
                FROM agendamentos a
                LEFT JOIN servicos s ON a.servico_id = s.id
                LEFT JOIN clientes c ON a.cliente_id = c.id
                WHERE a.profissional_id = ? AND a.data = ?
                ORDER BY a.hora
            ";

            $stmt = $conexao->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("is", $profissional_id, $data_selecionada);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                    while ($c = $result->fetch_assoc()): ?>
                        <div class="horario-slot">
                            <strong><?= date("H:i", strtotime($c['hora'])) ?></strong>
                            <div class="detalhes-consulta">
                                <p><strong>Serviço:</strong> <?= htmlspecialchars($c['servico']) ?></p>
                                <p><strong>Paciente:</strong> <?= htmlspecialchars($c['nome_cliente']) ?></p>
                                <p><strong>E-mail:</strong> <?= htmlspecialchars($c['email_cliente'] ?: 'Não informado') ?></p>
                                <p><strong>Telefone:</strong> <?= htmlspecialchars($c['telefone_cliente'] ?: 'Não informado') ?></p>

                                <div class="btn-group" role="group" aria-label="Ações do Agendamento">
                                    <button type="button" class="btn btn-sm btn-info"
                                            data-bs-toggle="modal" data-bs-target="#editarAgendamentoModal"
                                            data-id="<?= $c['id'] ?>"
                                            data-data="<?= $c['data'] ?>"
                                            data-hora="<?= $c['hora'] ?>"
                                            data-servico-id="<?= $c['servico_id'] ?>"
                                            data-cliente-id="<?= $c['cliente_id'] ?>" data-cliente-nome="<?= htmlspecialchars($c['nome_cliente']) ?>"
                                            data-cliente-email="<?= htmlspecialchars($c['email_cliente']) ?>"
                                            data-cliente-telefone="<?= htmlspecialchars($c['telefone_cliente']) ?>">
                                            Editar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal" data-bs-target="#confirmarCancelamentoModal" data-agendamento-id="<?= $c['id'] ?>"
                                            data-profissional-id="<?= $profissional_id ?>"
                                            data-data-selecionada="<?= $data_selecionada ?>">
                                            Cancelar </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                else:
                    echo "<div class='alert alert-info text-center mt-3'>Nenhuma consulta agendada para esta data.</div>";
                endif;
                $stmt->close();
            } else {
                echo "<div class='alert alert-danger text-center mt-3'>Erro na preparação da consulta: " . $conexao->error . "</div>";
            }
            ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">Por favor, selecione um profissional para visualizar a agenda.</div>
    <?php endif; ?>
</div>

<div class="modal fade" id="editarAgendamentoModal" tabindex="-1" aria-labelledby="editarAgendamentoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarAgendamentoModalLabel">Editar Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar_agendamento">
                    <input type="hidden" name="id_agendamento" id="modal_id_agendamento">
                    <input type="hidden" name="cliente_id_oculto" id="modal_cliente_id_oculto">
                    <input type="hidden" name="profissional_id_oculto" value="<?= htmlspecialchars($profissional_id) ?>">
                    <input type="hidden" name="data_oculta" value="<?= htmlspecialchars($data_selecionada) ?>">

                    <div class="mb-3">
                        <label for="edit_data" class="form-label">Data:</label>
                        <input type="date" class="form-control" id="edit_data" name="edit_data" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_hora" class="form-label">Hora:</label>
                        <input type="time" class="form-control" id="edit_hora" name="edit_hora" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_servico_id" class="form-label">Serviço:</label>
                        <select class="form-select" id="edit_servico_id" name="edit_servico_id" required>
                            <option value="">Selecione um serviço</option>
                            <?php
                            // Obter serviços para preencher o dropdown do modal
                            $servicos_modal = $conexao->query("SELECT id, servico FROM servicos ORDER BY servico");
                            if ($servicos_modal) {
                                while ($serv = $servicos_modal->fetch_assoc()) {
                                    echo "<option value='{$serv['id']}'>{$serv['servico']}</option>";
                                }
                                $servicos_modal->free();
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cliente_nome" class="form-label">Nome do Paciente:</label>
                        <input type="text" class="form-control" id="edit_cliente_nome" name="edit_cliente_nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cliente_email" class="form-label">E-mail do Paciente:</label>
                        <input type="email" class="form-control" id="edit_cliente_email" name="edit_cliente_email">
                    </div>
                    <div class="mb-3">
                        <label for="edit_cliente_telefone" class="form-label">Telefone do Paciente:</label>
                        <input type="text" class="form-control" id="edit_cliente_telefone" name="edit_cliente_telefone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmarCancelamentoModal" tabindex="-1" aria-labelledby="confirmarCancelamentoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmarCancelamentoModalLabel">Confirmar Cancelamento</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja **cancelar** este agendamento? Esta ação não pode ser desfeita.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não, Manter</button> <a href="#" id="linkCancelarAgendamento" class="btn btn-danger">Sim, Cancelar Agendamento</a> </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script para preencher o modal de edição (existente)
    var editarAgendamentoModal = document.getElementById('editarAgendamentoModal');
    editarAgendamentoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var data = button.getAttribute('data-data');
        var hora = button.getAttribute('data-hora');
        var servicoId = button.getAttribute('data-servico-id');
        var clienteId = button.getAttribute('data-cliente-id');
        var clienteNome = button.getAttribute('data-cliente-nome');
        var clienteEmail = button.getAttribute('data-cliente-email');
        var clienteTelefone = button.getAttribute('data-cliente-telefone');

        var modalIdAgendamento = editarAgendamentoModal.querySelector('#modal_id_agendamento');
        var modalClienteIdOculto = editarAgendamentoModal.querySelector('#modal_cliente_id_oculto');
        var modalData = editarAgendamentoModal.querySelector('#edit_data');
        var modalHora = editarAgendamentoModal.querySelector('#edit_hora');
        var modalServicoId = editarAgendamentoModal.querySelector('#edit_servico_id');
        var modalClienteNome = editarAgendamentoModal.querySelector('#edit_cliente_nome');
        var modalClienteEmail = editarAgendamentoModal.querySelector('#edit_cliente_email');
        var modalClienteTelefone = editarAgendamentoModal.querySelector('#edit_cliente_telefone');

        modalIdAgendamento.value = id;
        modalClienteIdOculto.value = clienteId;
        modalData.value = data;
        modalHora.value = hora;
        modalServicoId.value = servicoId;
        modalClienteNome.value = clienteNome;
        modalClienteEmail.value = clienteEmail;
        modalClienteTelefone.value = clienteTelefone;
    });

    // Script para preencher o modal de confirmação de cancelamento
    var confirmarCancelamentoModal = document.getElementById('confirmarCancelamentoModal'); // ID atualizado
    confirmarCancelamentoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var agendamentoId = button.getAttribute('data-agendamento-id');
        var profissionalId = button.getAttribute('data-profissional-id');
        var dataSelecionada = button.getAttribute('data-data-selecionada');

        var linkCancelar = confirmarCancelamentoModal.querySelector('#linkCancelarAgendamento'); // ID atualizado
        linkCancelar.href = `?acao=excluir&agendamento_id=${agendamentoId}&profissional_id=${profissionalId}&data=${dataSelecionada}`;
    });
</script>
</body>
</html>