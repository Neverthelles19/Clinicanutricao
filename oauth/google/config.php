<?php
// Configurações do Google OAuth
$googleClientID = 'SEU_CLIENT_ID.apps.googleusercontent.com';
$googleClientSecret = 'SUA_CLIENT_SECRET';
$googleRedirectURL = 'http://seu-site.com/oauth/google/callback.php';

// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/../../../conexao.php';