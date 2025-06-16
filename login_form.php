<?php
// Formulário de login para ser incluído no modal de confirmação
?>
<div class="bg-white rounded p-4">
    <h4 class="mb-3 text-center">Entrar no Sistema</h4>
    
    <div class="mb-3">
        <label for="email_cliente_login" class="form-label">E-mail ou Telefone</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" name="email_cliente_login" class="form-control" id="email_cliente_login" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="senha_login" class="form-label">Senha</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" id="senha_login" class="form-control" name="senha_login" required minlength="6">
            <span class="input-group-text" style="cursor:pointer;" onclick="togglePasswordVisibility(this, 'senha_login')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
        <div class="text-end mt-2">
            <a href="recuperar_senha.php" class="small text-muted">Esqueceu sua senha?</a>
        </div>
    </div>

    <div class="d-grid gap-2 mt-4">
        <button type="submit" class="btn btn-primary" onclick="document.getElementById('formAgendamento').submit();">
            <i class="fas fa-sign-in-alt me-2"></i>Entrar e Agendar
        </button>
        
        <div class="position-relative my-3">
            <hr>
            <span class="position-absolute top-0 start-50 translate-middle bg-white px-3 text-muted">ou</span>
        </div>
        
        <a href="oauth/google/logingoogle.php" class="btn btn-outline-danger d-flex align-items-center justify-content-center">
            <i class="fab fa-google me-2"></i> Entrar com Google
        </a>
    </div>
    
    <p class="mt-3 text-center">
        Não tem cadastro? <a href="#" id="linkFazerCadastro" class="text-primary">Cadastre-se</a>
    </p>
</div>