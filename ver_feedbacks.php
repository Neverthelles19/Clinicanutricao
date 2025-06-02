<?php
session_start();
include_once("conexao.php");

// Protege esta página: apenas usuários logados (colaboradores/admins) podem ver os feedbacks
if (!isset($_SESSION['colaborador']) || empty($_SESSION['colaborador'])) {
    header("Location: login.php");
    exit();
}

$lista_feedbacks = [];
$mensagem_erro = '';

// Consulta para buscar todos os feedbacks (sem id_usuario)
$sql = "SELECT id, email, assunto, mensagem, data_envio FROM feedbacks ORDER BY data_envio DESC";
$result = $conexao->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lista_feedbacks[] = $row;
    }
    $result->free();
} else {
    $mensagem_erro = "Erro ao carregar feedbacks: " . $conexao->error;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Feedbacks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 50px;
        }
        .feedback-item {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fefefe;
        }
        .feedback-item strong {
            color: #0d6efd;
        }
        .custom-green { background-color: #2B7540; }
    </style>
</head>
<body>

<header class="fixed-top custom-green text-white p-3 shadow">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="h4 m-0">Nutrição</h1>
        <div>
            <span class="me-3">Bem-vindo, <?= htmlspecialchars($_SESSION['colaborador']['email']) ?></span>
            <a href="cdfuncionario.php" class="btn btn-light btn-sm me-2">Voltar à Agenda</a>
            <a href="logout.php" class="btn btn-light btn-sm">Sair</a>
        </div>
    </div>
</header>

<div class="container mt-5 pt-5">
    <h2 class="mb-4 text-center">Feedbacks Recebidos</h2>

    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $mensagem_erro ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($lista_feedbacks)): ?>
        <div class="alert alert-info text-center">Nenhum feedback recebido ainda.</div>
    <?php else: ?>
        <?php foreach ($lista_feedbacks as $feedback): ?>
            <div class="feedback-item">
                <p><strong>Assunto:</strong> <?= htmlspecialchars($feedback['assunto']) ?></p>
                <p><strong>De:</strong> <?= htmlspecialchars($feedback['email']) ?></p>
                <p><strong>Mensagem:</strong><br><?= nl2br(htmlspecialchars($feedback['mensagem'])) ?></p>
                <small class="text-muted">Enviado em: <?= date('d/m/Y H:i', strtotime($feedback['data_envio'])) ?></small>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>