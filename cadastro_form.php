<?php
// Formulário de cadastro para ser incluído no modal de confirmação
?>
<div class="bg-white rounded p-4">
    <h4 class="mb-3 text-center">Criar Conta</h4>
    
    <div class="mb-3">
        <label for="nome_cliente_cadastro" class="form-label">Nome Completo</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" name="nome_cliente_cadastro" id="nome_cliente_cadastro" placeholder="Nome Sobrenome" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="telefone_cliente_cadastro" class="form-label">Telefone (com DDD)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-phone"></i></span>
            <input type="tel" class="form-control" name="telefone_cliente_cadastro" id="telefone_cliente_cadastro" placeholder="(XX) XXXXX-XXXX" pattern="\(\d{2}\) \d{4,5}-\d{4}" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="email_cliente_cadastro" class="form-label">E-mail</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input type="email" class="form-control" name="email_cliente_cadastro" id="email_cliente_cadastro" placeholder="seu.email@exemplo.com" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="senha_cadastro" class="form-label">Crie sua Senha</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" id="senha_cadastro" class="form-control" name="senha_cadastro" required minlength="6">
            <span class="input-group-text" style="cursor:pointer;" onclick="togglePasswordVisibility(this, 'senha_cadastro')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
    </div>

    <div class="d-grid gap-2 mt-4">
        <button type="submit" class="btn btn-primary" onclick="document.getElementById('formAgendamento').submit();">
            <i class="fas fa-user-plus me-2"></i>Cadastrar e Agendar
        </button>
        
        <div class="position-relative my-3">
            <hr>
            <span class="position-absolute top-0 start-50 translate-middle bg-white px-3 text-muted">ou</span>
        </div>
        
        <a href="oauth/google/logingoogle.php" class="btn btn-outline-danger d-flex align-items-center justify-content-center">
            <i class="fab fa-google me-2"></i> Cadastrar com Google
        </a>
    </div>
    
    <p class="mt-3 text-center">
        Já tem cadastro? <a href="#" id="linkFazerLogin" class="text-primary">Faça Login</a>
    </p>
</div>