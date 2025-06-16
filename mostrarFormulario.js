function mostrarFormulario() {
    const selectHora = document.getElementById('selectHora');
    const formAgendamento = document.getElementById('formAgendamento');
    const formCadastro = document.getElementById('formCadastro');
    const formLogin = document.getElementById('formLogin');
    const horaSelecionada = selectHora.value;
    
    // Atualizar campo oculto
    document.getElementById('inputHora').value = horaSelecionada;
    
    if (horaSelecionada && horaSelecionada !== '') {
        // Mostrar o formulário
        formAgendamento.style.display = 'block';
        
        // Verificar se o usuário está logado (esta parte será substituída pelo PHP)
        const isLoggedIn = window.isUserLoggedIn || false;
        
        if (isLoggedIn) {
            // Se o usuário estiver logado, mostrar mensagem de confirmação
            const nomeCliente = window.nomeCliente || '';
            const emailCliente = window.emailCliente || '';
            
            // Ocultar formulários de login/cadastro
            if (formCadastro) formCadastro.style.display = 'none';
            if (formLogin) formLogin.style.display = 'none';
            
            // Ocultar botões de alternância
            document.querySelector('.btn-group').style.display = 'none';
            
            // Remover alertas existentes
            const alertasExistentes = formAgendamento.querySelectorAll('.alert');
            alertasExistentes.forEach(alerta => alerta.remove());
            
            // Adicionar mensagem de usuário logado
            const infoHtml = `
                <div class="alert alert-success mb-4">
                    <i class="fas fa-user-check me-2"></i>
                    <strong>Você está logado como:</strong> ${nomeCliente} (${emailCliente})
                </div>
                <div class="d-grid mb-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-calendar-check me-2"></i>Confirmar Agendamento
                    </button>
                </div>
            `;
            formAgendamento.insertAdjacentHTML('afterbegin', infoHtml);
        } else {
            // Se o usuário não estiver logado, mostrar formulário de cadastro por padrão
            if (formCadastro) formCadastro.style.display = 'block';
            if (formLogin) formLogin.style.display = 'none';
            
            // Atualizar botões
            document.getElementById('btnCadastro').classList.add('active');
            document.getElementById('btnLogin').classList.remove('active');
            
            // Definir como cadastro
            document.getElementById('isCadastroForm').value = '1';
            
            // Remover alertas existentes
            const alertasExistentes = formAgendamento.querySelectorAll('.alert-info');
            alertasExistentes.forEach(alerta => alerta.remove());
            
            // Adicionar mensagem informativa
            const alertHtml = `
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Atenção:</strong> Para agendar uma consulta, é necessário fazer login ou criar uma conta.
                </div>
            `;
            formAgendamento.insertAdjacentHTML('afterbegin', alertHtml);
        }
    } else {
        formAgendamento.style.display = 'none';
    }
}