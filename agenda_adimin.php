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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="styleUser.css" rel="stylesheet" />
    <link href="stylecadastroUser.css" rel="stylesheet" />
    <link href="styleForms.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container1 {
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

        .select-wrapper {
  position: relative;
}

.select-wrapper select.form-control {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  padding-right: 2.5rem; /* espaço para a seta */
  background-color: #fff;
}

.select-wrapper::after {
  content: "\f078"; /* ícone da seta para baixo (Font Awesome) */
  font-family: "Font Awesome 6 Free";
  font-weight: 900;
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #6c757d;
}


       .btn-paleta {
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  color: #9600ff;        /* Roxo vibrante */
  border: 2px solid #9600ff;
  border-radius: 0.35rem;
  background-color: transparent;
  transition: background-color 0.25s ease, color 0.25s ease;
  text-decoration: none;
  box-shadow: none;
  cursor: pointer;
}

.btn-paleta:hover,
.btn-paleta:focus {
  background-color: #f7f7f7;  /* cinza muito clarinho e neutro */
  color: #0f3fa8;             /* azul da paleta, mais escuro */
  border-color: #0f3fa8;
  outline: none;
  box-shadow: none;
}

.horario-slot {
  background: #fff;
  border: 1px solid #e6e6e6;
  border-left: 5px solid #9600ff;
  padding: 16px 20px;
  border-radius: 12px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.04);
  margin-bottom: 16px;
  transition: box-shadow 0.2s ease;
}

.horario-slot:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.hora-box strong {
  font-size: 1.5rem;
  color: #9600ff;
}

.info-box p {
  margin: 2px 0;
  font-size: 0.95rem;
  color: #333;
}

/* Botões estilizados */
.btn-editar {
  color: #9600ff;
  border: 2px solid #9600ff;
  background-color: transparent;
  font-weight: 500;
  transition: all 0.2s ease;
  border-radius: 8px;
}

.btn-editar:hover {
  background-color: rgba(150, 0, 255, 0.1);
  color: #9600ff;
}

/* Botão Cancelar - Azul */
.btn-cancelar {
  color: #0f3fa8;
  border: 2px solid #0f3fa8;
  background-color: transparent;
  font-weight: 500;
  transition: all 0.2s ease;
  border-radius: 8px;
}

.btn-cancelar:hover {
  background-color: rgba(15, 63, 168, 0.1);
  color: #0f3fa8;
}


/* Responsivo */
@media (max-width: 768px) {
  .btn-box {
    width: 100%;
    flex-direction: row !important;
    justify-content: center;
  }

  .btn-box .btn {
    flex: 1;
  }

  .hora-box {
    text-align: left;
  }
}

.btn-suave {
  transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-suave:hover {
  background-color: #f2f2f2;
  color: #333;
  border-color: #ccc;
}
   
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gradient-text" href="#">
      <i class="fas fa-heartbeat me-2"></i>
      <span class="d-none d-md-inline">Clínica Nutrição</span>
    </a>
    <div class="ms-auto d-flex flex-wrap gap-2">
      <a href="cadastro_servico.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-gradient-hover d-flex align-items-center">
        <i class="fas fa-plus-circle"></i>
        <span class="ms-1 d-none d-md-inline">Cadastro Serviço</span>
      </a>
      <a href="cdfuncionario.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-gradient-hover d-flex align-items-center">
        <i class="fas fa-user-tie"></i>
        <span class="ms-1 d-none d-md-inline">Cadastro Funcionário</span>
      </a>
      <a href="agenda_adimin.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-gradient-hover d-flex align-items-center">
        <i class="fas fa-calendar-alt"></i>
        <span class="ms-1 d-none d-md-inline">Agenda Admin</span>
      </a>
      <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center">
        <i class="fas fa-sign-out-alt"></i>
        <span class="ms-1 d-none d-md-inline">Sair</span>
      </a>
    </div>
  </div>
</nav>


<div class="container container1 py-5">
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
                <div class="select-wrapper">
                <select name="profissional_id" id="profissional" class="form-control" onchange="this.form.submit()" required>

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
            </div>
            <div class="col-md-4">
                <label for="data" class="form-label">Visualizar Data:</label>
                <input type="date" name="data" id="data" class="form-control" value="<?= htmlspecialchars($data_selecionada) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary w-100 btn-signup">Atualizar Agenda</button>
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
            <a href="?profissional_id=<?= $profissional_id ?>&data=<?= $data_anterior ?>" class="btn-paleta">&lt; Dia Anterior</a>
            <span class="flex-grow-2 text-center fs-5 text-muted"><?= date('l', strtotime($data_selecionada)) ?>, <?= date('d/m', strtotime($data_selecionada)) ?></span>
            <a href="?profissional_id=<?= $profissional_id ?>&data=<?= $data_proxima ?>" class="btn-paleta">Próximo Dia &gt;</a>



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
                        <div class="horario-slot d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
  <div class="hora-box text-md-center">
    <strong><?= date("H:i", strtotime($c['hora'])) ?></strong>
  </div>

  <div class="info-box flex-grow-1">
    <p><strong>Serviço:</strong> <?= htmlspecialchars($c['servico']) ?></p>
    <p><strong>Paciente:</strong> <?= htmlspecialchars($c['nome_cliente']) ?></p>
    <p><strong>E-mail:</strong> <?= htmlspecialchars($c['email_cliente'] ?: 'Não informado') ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($c['telefone_cliente'] ?: 'Não informado') ?></p>
  </div>

  <div class="btn-box d-flex flex-md-column gap-2">
    <button type="button" class="btn btn-sm btn-editar"
      data-bs-toggle="modal" data-bs-target="#editarAgendamentoModal"
      data-id="<?= $c['id'] ?>"
      data-data="<?= $c['data'] ?>"
      data-hora="<?= $c['hora'] ?>"
      data-servico-id="<?= $c['servico_id'] ?>"
      data-cliente-id="<?= $c['cliente_id'] ?>" 
      data-cliente-nome="<?= htmlspecialchars($c['nome_cliente']) ?>"
      data-cliente-email="<?= htmlspecialchars($c['email_cliente']) ?>"
      data-cliente-telefone="<?= htmlspecialchars($c['telefone_cliente']) ?>">
      Editar
    </button>

    <button type="button" class="btn btn-sm btn-cancelar"
      data-bs-toggle="modal" data-bs-target="#confirmarCancelamentoModal" 
      data-agendamento-id="<?= $c['id'] ?>"
      data-profissional-id="<?= $profissional_id ?>"
      data-data-selecionada="<?= $data_selecionada ?>">
      Cancelar
    </button>
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
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmarCancelamentoModal" tabindex="-1" aria-labelledby="confirmarCancelamentoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-white border border-light shadow-sm rounded-3">
      <div class="modal-header bg-white border-0">
        <h5 class="modal-title text-dark fw-bold" id="confirmarCancelamentoModalLabel">
          Cancelar Agendamento
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body text-center px-4 py-3">
        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
        <p class="fs-6">
          Você está prestes a <strong class="text-danger">cancelar</strong> este agendamento.
          <br><span class="text-muted small">Essa ação é irreversível.</span>
        </p>
      </div>
      <div class="modal-footer justify-content-center border-0 pb-4">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 btn-suave" data-bs-dismiss="modal">
          Manter Agendamento
        </button>
        <a href="#" id="linkCancelarAgendamento" class="btn btn-danger rounded-pill px-4">
          Cancelar Agora
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Footer completo - visível a partir de md -->
<footer class="mt-5 text-white footer-gradiente py-4 text-center d-none d-md-block" >
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-3 mb-md-0">
        <h5 class="fw-bold"><i class="fas fa-heartbeat me-2"></i>Clínica Nutrição</h5>
        <p class="mb-0">Cuidando da sua saúde com responsabilidade e equilíbrio.</p>
      </div>
      <div class="col-md-4 mb-3 mb-md-0">
        <h6 class="fw-bold">Contato</h6>
        <p class="mb-1"><i class="fas fa-phone-alt me-2"></i>(00) 1234-5678</p>
        <p class="mb-1"><i class="fas fa-envelope me-2"></i>contato@clinica.com</p>
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