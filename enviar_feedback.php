<?php
session_start();
include_once("conexao.php"); // Inclui o arquivo de conexão com o banco de dados

$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicializa $email com o valor do POST (se existir)
    $email = $_POST['email'] ?? '';
    $assunto = $_POST['assunto'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    // Se o usuário estiver logado, pegamos o email da sessão
    // Isso garante que o email do admin logado seja usado se ele estiver enviando feedback.
    if (isset($_SESSION['colaborador']['email'])) {
        $email = $_SESSION['colaborador']['email'];
    }

    // Validação dos campos obrigatórios
    if (empty($email) || empty($assunto) || empty($mensagem)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios (E-mail, Assunto, Mensagem).";
    } else {
        // Prepara a instrução SQL para inserir o feedback (sem id_usuario)
        $stmt = $conexao->prepare("INSERT INTO feedbacks (email, assunto, mensagem) VALUES (?, ?, ?)");

        if ($stmt) {
            // "sss" significa: s (string para email), s (string para assunto), s (string para mensagem)
            $stmt->bind_param("sss", $email, $assunto, $mensagem);

            if ($stmt->execute()) {
                $mensagem_sucesso = "Seu feedback foi enviado com sucesso! Agradecemos sua contribuição.";
                // Limpa os campos do formulário após o envio bem-sucedido
                // O email só é limpo se não veio da sessão (ou seja, se o usuário digitou)
                if (!isset($_SESSION['colaborador']['email'])) {
                    $email = '';
                }
                $assunto = '';
                $mensagem = '';
            } else {
                $mensagem_erro = "Erro ao enviar feedback: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $mensagem_erro = "Erro na preparação da consulta de feedback: " . $conexao->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 50px;
            max-width: 700px;
        }
        .custom-green { background-color: #2B7540; }
    </style>
</head>
<body>

<header class="fixed-top custom-green text-white p-3 shadow">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="h4 m-0">Nutrição</h1>
        <div>
            <?php if (isset($_SESSION['colaborador']['email'])): ?>
                <span class="me-3">Bem-vindo, <?= htmlspecialchars($_SESSION['colaborador']['email']) ?></span>
                <a href="cdfuncionario.php" class="btn btn-light btn-sm me-2">Voltar</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-light btn-sm">Sair</a>
        </div>
    </div>
</header>

<div class="container mt-5 pt-5">
    <h2 class="mb-4 text-center">Envie seu Feedback</h2>

    <?php if ($mensagem_sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $mensagem_sucesso ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $mensagem_erro ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="enviar_feedback.php">
        <?php if (isset($_SESSION['colaborador']['email'])): ?>
            <div class="mb-3">
                <label for="email" class="form-label">Seu E-mail:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_SESSION['colaborador']['email']) ?>" readonly>
            </div>
        <?php else: ?>
            <div class="mb-3">
                <label for="email" class="form-label">Seu E-mail:</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="seu.email@exemplo.com" required>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="assunto" class="form-label">Assunto:</label>
            <input type="text" class="form-control" id="assunto" name="assunto" required>
        </div>

        <div class="mb-3">
            <label for="mensagem" class="form-label">Sua Mensagem:</label>
            <textarea class="form-control" id="mensagem" name="mensagem" rows="5" required></textarea>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Enviar Feedback</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>