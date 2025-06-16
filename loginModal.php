<?php
// Modal de login para ser incluído em qualquer página
?>
<!-- Modal de Login -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Entrar no Sistema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" id="loginTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-tab-pane" type="button" role="tab" aria-controls="login-tab-pane" aria-selected="true">Login</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="cadastro-tab" data-bs-toggle="tab" data-bs-target="#cadastro-tab-pane" type="button" role="tab" aria-controls="cadastro-tab-pane" aria-selected="false">Cadastro</button>
          </li>
        </ul>
        <div class="tab-content pt-3" id="loginTabsContent">
          <!-- Aba de Login -->
          <div class="tab-pane fade show active" id="login-tab-pane" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
            <form method="POST" action="login.php">
              <div class="mb-3">
                <label for="login_email" class="form-label">E-mail ou Telefone</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                  <input type="text" class="form-control" id="login_email" name="email_cliente_login" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="login_senha" class="form-label">Senha</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-lock"></i></span>
                  <input type="password" class="form-control" id="login_senha" name="senha_login" required>
                  <span class="input-group-text" style="cursor:pointer;" onclick="togglePasswordVisibility(this, 'login_senha')">
                    <i class="fas fa-eye"></i>
                  </span>
                </div>
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Entrar</button>
                <a href="oauth/google/logingoogle.php" class="btn btn-outline-danger d-flex align-items-center justify-content-center">
                  <i class="fab fa-google me-2"></i> Entrar com Google
                </a>
              </div>
            </form>
          </div>
          
          <!-- Aba de Cadastro -->
          <div class="tab-pane fade" id="cadastro-tab-pane" role="tabpanel" aria-labelledby="cadastro-tab" tabindex="0">
            <form method="POST" action="cadastro.php">
              <div class="mb-3">
                <label for="cadastro_nome" class="form-label">Nome Completo</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                  <input type="text" class="form-control" id="cadastro_nome" name="nome_cliente_cadastro" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="cadastro_telefone" class="form-label">Telefone (com DDD)</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-phone"></i></span>
                  <input type="tel" class="form-control" id="cadastro_telefone" name="telefone_cliente_cadastro" placeholder="(XX) XXXXX-XXXX" pattern="\(\d{2}\) \d{4,5}-\d{4}" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="cadastro_email" class="form-label">E-mail</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                  <input type="email" class="form-control" id="cadastro_email" name="email_cliente_cadastro" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="cadastro_senha" class="form-label">Senha</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-lock"></i></span>
                  <input type="password" class="form-control" id="cadastro_senha" name="senha_cadastro" required minlength="6">
                  <span class="input-group-text" style="cursor:pointer;" onclick="togglePasswordVisibility(this, 'cadastro_senha')">
                    <i class="fas fa-eye"></i>
                  </span>
                </div>
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Cadastrar</button>
                <a href="oauth/google/logingoogle.php" class="btn btn-outline-danger d-flex align-items-center justify-content-center">
                  <i class="fab fa-google me-2"></i> Cadastrar com Google
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Função para alternar a visibilidade da senha
function togglePasswordVisibility(button, inputId) {
  const input = document.getElementById(inputId);
  const icon = button.querySelector('i');
  
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

// Formatar telefone automaticamente
document.addEventListener('DOMContentLoaded', function() {
  const telefoneInput = document.getElementById('cadastro_telefone');
  if (telefoneInput) {
    telefoneInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      let formattedValue = '';
      
      if (value.length > 0) {
        formattedValue = '(' + value.substring(0, 2);
      }
      if (value.length > 2) {
        formattedValue += ') ' + value.substring(2, value.length > 6 && value.charAt(2) === '9' ? 7 : 6);
      }
      if (value.length > 6) {
        if (value.charAt(2) === '9') {
          formattedValue += '-' + value.substring(7, 11);
        } else {
          formattedValue += '-' + value.substring(6, 10);
        }
      }
      
      e.target.value = formattedValue;
    });
  }
});
</script>