<?php
// Script para atualizar o banco de dados com os novos campos
include 'conexao.php';

// Array com as consultas SQL a serem executadas
$queries = [
    "ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS lembrete_2h_enviado TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS motivo_cancelamento TEXT NULL",
    "ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS motivo_alteracao TEXT NULL",
    "ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'confirmado'",
    "CREATE INDEX IF NOT EXISTS idx_agendamentos_cliente ON agendamentos(cliente_id)",
    "CREATE INDEX IF NOT EXISTS idx_agendamentos_profissional ON agendamentos(profissional_id)",
    "CREATE INDEX IF NOT EXISTS idx_agendamentos_data ON agendamentos(data)",
    "CREATE INDEX IF NOT EXISTS idx_agendamentos_status ON agendamentos(status)"
];

// Executar cada consulta
$success = true;
$errors = [];

foreach ($queries as $query) {
    if (!mysqli_query($conexao, $query)) {
        $success = false;
        $errors[] = "Erro ao executar: $query - " . mysqli_error($conexao);
    }
}

// Exibir resultado
if ($success) {
    echo "<h2>Banco de dados atualizado com sucesso!</h2>";
    echo "<p>Todos os campos necessários foram adicionados.</p>";
} else {
    echo "<h2>Erro ao atualizar o banco de dados</h2>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
?>