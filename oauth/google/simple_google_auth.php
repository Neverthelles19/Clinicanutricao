<?php
// Autenticação Google simples sem Composer
require_once 'config.php';

// Configurações do Google OAuth
$google_oauth_url = 'https://accounts.google.com/o/oauth2/auth';
$google_token_url = 'https://oauth2.googleapis.com/token';
$google_userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';

// Parâmetros para autenticação
$params = array(
    'client_id' => $googleClientID,
    'redirect_uri' => $googleRedirectURL,
    'scope' => 'email profile',
    'response_type' => 'code',
    'access_type' => 'offline'
);

// Gerar URL de autenticação
$auth_url = $google_oauth_url . '?' . http_build_query($params);

// Redirecionar para o Google
header('Location: ' . $auth_url);
exit;
?>