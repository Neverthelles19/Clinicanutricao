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

    function editarAgendamento($conexao, &$mensagem_sucesso, &$mensagem_erro) {
      $id_agendamento = intval($_POST['id_agendamento']);
      $nova_data = $_POST['edit_data'];
      $nova_hora = $_POST['edit_hora'];
      $novo_servico_id = intval($_POST['edit_servico_id']);
      $novo_cliente_nome = $_POST['edit_cliente_nome'];
      $novo_cliente_email = $_POST['edit_cliente_email'];
      $novo_cliente_telefone = $_POST['edit_cliente_telefone'];
      $cliente_id_oculto = intval($_POST['cliente_id_oculto']);

      $conexao->begin_transaction();
      try {
        // Atualiza cliente
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

        // Atualiza agendamento
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
    }

    function cancelarAgendamento($conexao, &$mensagem_sucesso, &$mensagem_erro) {
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


// --- Lógica de Exclusão (agora 'Cancelamento') ---
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['agendamento_id'])) {

    cancelarAgendamento($conexao, $mensagem_sucesso, $mensagem_erro);
}    

// --- Lógica de Edição ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_agendamento') {
    editarAgendamento($conexao, $mensagem_sucesso, $mensagem_erro);
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Custom Styles -->
    <link href="stylecadastroUser.css" rel="stylesheet" />
    <link href="styleUser.css" rel="stylesheet" />
    <link href="styleForms.css" rel="stylesheet" />
    <link rel="stylesheet" href="styleAgenda.css">
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
      <a href="logoutAdm.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center">
        <i class="fas fa-sign-out-alt"></i>
        <span class="ms-1 d-none d-md-inline">Sair</span>
      </a>
    </div>
  </div>
</nav>

<main  class="container">
  <div class="card card-form container1 py-5" style="min-height:560px;">
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
        onclick="abrirModalEditarAgendamento(
            <?= $c['id'] ?>,
            '<?= $c['data'] ?>',
            '<?= $c['hora'] ?>',
            <?= $c['servico_id'] ?>,
            <?= $c['cliente_id'] ?>,
            `<?= htmlspecialchars($c['nome_cliente'], ENT_QUOTES) ?>`,
            `<?= htmlspecialchars($c['email_cliente'], ENT_QUOTES) ?>`,
            `<?= htmlspecialchars($c['telefone_cliente'], ENT_QUOTES) ?>`
        )">
        Editar
      </button>

      <button type="button" class="btn btn-sm btn-cancelar"
        onclick="abrirModalCancelarAgendamento(
            <?= $c['id'] ?>,
            <?= $profissional_id ?>,
            '<?= $data_selecionada ?>'
        )">
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
</main>

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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Bootstrap JS (Popper + Bootstrap Bundle) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Função para abrir o modal de edição usando SweetAlert2
    function abrirModalEditarAgendamento(id, data, hora, servicoId, clienteId, clienteNome, clienteEmail, clienteTelefone) {
        // Monta o select de serviços via PHP embutido
        let servicosOptions = `
            <option value="">Selecione um serviço</option>
            <?php
            $servicos_modal = $conexao->query("SELECT id, servico FROM servicos ORDER BY servico");
            if ($servicos_modal) {
                while ($serv = $servicos_modal->fetch_assoc()) {
                    echo "<option value='{$serv['id']}'>{${'serv'}['servico']}</option>";
                }
                $servicos_modal->free();
            }
            ?>
        `;

        Swal.fire({
            title: 'Editar Agendamento',
            html:
                `<form id="formEditarAgendamento" action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                    <input type="hidden" name="acao" value="editar_agendamento">
                    <input type="hidden" name="id_agendamento" value="${id}">
                    <input type="hidden" name="cliente_id_oculto" value="${clienteId}">
                    <input type="hidden" name="profissional_id_oculto" value="<?= htmlspecialchars($profissional_id) ?>">
                    <input type="hidden" name="data_oculta" value="<?= htmlspecialchars($data_selecionada) ?>">

                    <div class="mb-3 text-start">
                        <label for="edit_data" class="form-label">Data:</label>
                        <input type="date" class="form-control" id="edit_data" name="edit_data" required value="${data}">
                    </div>
                    <div class="mb-3 text-start">
                        <label for="edit_hora" class="form-label">Hora:</label>
                        <input type="time" class="form-control" id="edit_hora" name="edit_hora" required value="${hora}">
                    </div>
                    <div class="mb-3 text-start">
                        <label for="edit_servico_id" class="form-label">Serviço:</label>
                        <select class="form-select" id="edit_servico_id" name="edit_servico_id" required>
                            ${servicosOptions}
                        </select>
                    </div>
                    <div class="mb-3 text-start">
                        <label for="edit_cliente_nome" class="form-label">Nome do Paciente:</label>
                        <input type="text" class="form-control" id="edit_cliente_nome" name="edit_cliente_nome" required value="${clienteNome}">
                    </div>
                    <div class="mb-3 text-start">
                        <label for="edit_cliente_email" class="form-label">E-mail do Paciente:</label>
                        <input type="email" class="form-control" id="edit_cliente_email" name="edit_cliente_email" value="${clienteEmail}">
                    </div>
                    <div class="mb-3 text-start">
                        <label for="edit_cliente_telefone" class="form-label">Telefone do Paciente:</label>
                        <input type="text" class="form-control" id="edit_cliente_telefone" name="edit_cliente_telefone" value="${clienteTelefone}">
                    </div>
                </form>`,
            showCancelButton: true,
            confirmButtonText: 'Salvar Alterações',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            didOpen: () => {
                // Seleciona o serviço correto
                document.getElementById('edit_servico_id').value = servicoId;
            },
            preConfirm: () => {
                document.getElementById('formEditarAgendamento').submit();
                return false;
            }
        });
    }

    // Função para abrir o modal de confirmação de cancelamento usando SweetAlert2
    function abrirModalCancelarAgendamento(agendamentoId, profissionalId, dataSelecionada) {
        Swal.fire({
            title: 'Cancelar Agendamento',
            html: `<p class="fs-6">
                Você está prestes a <strong class="text-danger">cancelar</strong> este agendamento.<br>
                <span class="text-muted small">Essa ação é irreversível.</span>
            </p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Cancelar Agora',
            cancelButtonText: 'Manter Agendamento',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `?acao=excluir&agendamento_id=${agendamentoId}&profissional_id=${profissionalId}&data=${dataSelecionada}`;
            }
        });
    }
</script>
</body>
</html>