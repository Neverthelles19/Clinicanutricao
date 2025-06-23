<?php
// Teste simples para verificar se o Google OAuth funciona
session_start();

echo "<h1>Teste Google OAuth</h1>";
echo "<p>Clique no link abaixo para testar:</p>";
echo "<a href='oauth/google/simple_google_auth.php'>Login com Google (Versão Simples)</a><br>";
echo "<a href='oauth/google/logingoogle.php'>Login com Google (Redirecionamento)</a><br>";

if (isset($_SESSION['mensagem'])) {
    echo "<div style='color: green; margin-top: 20px;'>" . $_SESSION['mensagem'] . "</div>";
    unset($_SESSION['mensagem']);
}
?>