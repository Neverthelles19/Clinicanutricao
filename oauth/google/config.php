<?php
// Configurações do Google OAuth
$googleClientID = '1234567890-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com'; // Substitua pelo seu Client ID real
$googleClientSecret = 'GOCSPX-abcdefghijklmnopqrstuvwxyz'; // Substitua pelo seu Client Secret real
$googleRedirectURL = 'http://localhost/Clinicanutricao/oauth/google/callback.php';

// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/../../conexao.php';