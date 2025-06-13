<?php
// Carregar a biblioteca do Google API
require_once 'vendor/autoload.php';  // Certifique-se de que a Google API Client está instalada

// Conectar ao banco de dados
require_once 'conexao.php';

// Pegando o token enviado do front-end
$data = json_decode(file_get_contents('php://input'), true);
$idToken = $data['token'];  // O id_token enviado pelo Google

// Inicializando o client do Google
$client = new Google_Client(['client_id' => '523526330482-sp2asb3i98mp2auvq3iojp2isl23o7fl.apps.googleusercontent.com']); // Seu Client ID
$payload = $client->verifyIdToken($idToken);  // Verificando o token

if ($payload) {
    // Se o token for válido, obtenha as informações do usuário
    $google_id = $payload['sub'];  // ID do Google
    $nome = $payload['name'];      // Nome do usuário
    $email = $payload['email'];    // Email do usuário

    // Verifique se o usuário já existe no banco de dados
    $stmt = $conexao->prepare("SELECT * FROM clientes WHERE google_id = ? OR email = ?");
    $stmt->bind_param('ss', $google_id, $email); // Bind do google_id e email
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Se o usuário já existe, você pode atualizar as informações dele (caso necessário)
        // Exemplo: Atualizando o nome (se necessário)
        $updateStmt = $conexao->prepare("UPDATE clientes SET nome = ? WHERE id = ?");
        $updateStmt->bind_param('si', $nome, $user['id']);
        $updateStmt->execute();
    } else {
        // Caso o usuário não exista, insira no banco
        $insertStmt = $conexao->prepare("INSERT INTO clientes (nome, email, google_id) VALUES (?, ?, ?)");
        $insertStmt->bind_param('sss', $nome, $email, $google_id);
        $insertStmt->execute();
    }

    // Criar uma sessão para o usuário se desejar
    session_start();
    $_SESSION['user_id'] = $user ? $user['id'] : $conexao->insert_id;  // Usando o ID do usuário já existente ou o ID do novo usuário

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Token inválido']);
}
?>
