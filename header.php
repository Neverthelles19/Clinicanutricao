<?php
// Arquivo header.php - Inclui o cabeçalho padrão do site
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gradient-text" href="index.php">
                <i class="fas fa-heartbeat me-2"></i>
                <span>Clínica Nutrição</span>
            </a>
            <div class="ms-auto d-flex">
                <a href="index.php" class="btn btn-sm rounded-pill px-3 me-2 btn-inicio">
                    <i class="fas fa-home me-1"></i> Início
                </a>
                
                <?php if (isset($_SESSION['cliente_id'])): ?>
                    <a href="meus_agendamentos.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">
                        <i class="fas fa-calendar-alt me-1"></i> Meus Agendamentos
                    </a>
                    <a href="logout.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-sign-out-alt me-1"></i> Sair
                    </a>
                <?php else: ?>
                    <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-sign-in-alt me-1"></i> Entrar
                    </a>
                    <a href="oauth/google/logingoogle.php" class="btn btn-sm btn-danger rounded-pill px-3">
                        <i class="fab fa-google me-1"></i> Entrar com Google
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>