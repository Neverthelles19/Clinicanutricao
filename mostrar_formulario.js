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
        
        // Verificar se o usuário está logado
        if (typeof checkAndSetLoginStatus === 'function') {
            checkAndSetLoginStatus();
        } else {
            // Fallback se a função não estiver disponível
            if (formCadastro) formCadastro.style.display = 'block';
            if (formLogin) formLogin.style.display = 'none';
        }
    } else {
        formAgendamento.style.display = 'none';
    }
}