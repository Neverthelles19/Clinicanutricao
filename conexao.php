<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "nutricao";

// Cria a conexão
$conexao = new mysqli($servername, $username, $password, $database);

// Verifica a conexão
if ($conexao->connect_error) {
    die("Conexão falhou: " . $conexao->connect_error);
}
?>
