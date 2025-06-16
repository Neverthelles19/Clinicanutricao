<?php
require_once 'config.php';

// Verificar se o autoload do Composer existe
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    $_SESSION['mensagem'] = 'Erro: Dependências não instaladas. Execute "composer install" na pasta raiz.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../index.php');
    exit;
}

try {
    // Criar cliente Google
    if (class_exists('Google_Client')) {
        $client = new Google_Client();
    } elseif (class_exists('Google\\Client')) {
        $client = new Google\Client();
    } else {
        throw new Exception('Biblioteca Google Client não encontrada');
    }
    
    $client->setClientId($googleClientID);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri($googleRedirectURL);
    $client->addScope("email");
    $client->addScope("profile");

    // Gerar URL de autenticação
    $authUrl = $client->createAuthUrl();

    // Redirecionar para a página de autenticação do Google
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;
} catch (Exception $e) {
    // Log do erro
    error_log('Erro no login Google: ' . $e->getMessage());
    
    // Redirecionar para a página inicial com mensagem de erro
    $_SESSION['mensagem'] = 'Erro ao conectar com o Google: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../index.php');
    exit;
}