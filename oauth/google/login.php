<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

$client = new Google\Client();
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($googleRedirectURL);
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Obtém os dados do usuário
    $google_oauth = new Google\Service\Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = $google_account_info->email;
    $nome = $google_account_info->name;
    $google_id = $google_account_info->id;

    // Verifica se o usuário já existe no banco de dados
    $query = mysqli_query($conexao, "SELECT * FROM clientes WHERE email = '$email' LIMIT 1");
    
    if (mysqli_num_rows($query) > 0) {
        // Usuário existe - faz login
        $cliente = mysqli_fetch_assoc($query);
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_nome'] = $cliente['nome'];
        $_SESSION['cliente_email'] = $cliente['email'];
    } else {
        // Usuário não existe - cria novo registro
        $insert = mysqli_query($conexao, "INSERT INTO clientes (nome, email, google_id) VALUES ('$nome', '$email', '$google_id')");
        
        if ($insert) {
            $cliente_id = mysqli_insert_id($conexao);
            $_SESSION['cliente_id'] = $cliente_id;
            $_SESSION['cliente_nome'] = $nome;
            $_SESSION['cliente_email'] = $email;
        } else {
            die("Erro ao cadastrar usuário: " . mysqli_error($conexao));
        }
    }
    
    // Redireciona de volta para a página de agendamento
    header('Location: ../../../agendamento.php');
    exit();
} else {
    // Se não houver código de autenticação, redireciona para a página de login
    header('Location: ../../../agendamento.php');
    exit();
}