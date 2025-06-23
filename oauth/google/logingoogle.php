<?php
session_start();
require_once 'config.php';

// Google OAuth URLs
$google_oauth_url = 'https://accounts.google.com/o/oauth2/auth';

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