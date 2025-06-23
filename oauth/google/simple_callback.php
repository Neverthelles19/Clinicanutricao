<?php
require_once 'config.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Trocar código por token
    $token_data = array(
        'client_id' => $googleClientID,
        'client_secret' => $googleClientSecret,
        'redirect_uri' => $googleRedirectURL,
        'grant_type' => 'authorization_code',
        'code' => $code
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_info = json_decode($response, true);
    
    if (isset($token_info['access_token'])) {
        // Obter informações do usuário
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_info['access_token']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $user_response = curl_exec($ch);
        curl_close($ch);
        
        $user_info = json_decode($user_response, true);
        
        if (isset($user_info['email'])) {
            $email = $user_info['email'];
            $name = $user_info['name'];
            
            // Verificar se o usuário já existe
            $stmt = $conexao->prepare("SELECT id, nome, email, telefone FROM clientes WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Usuário já existe, fazer login
                $cliente = $result->fetch_assoc();
                $_SESSION['cliente_id'] = $cliente['id'];
                $_SESSION['nome_cliente'] = $cliente['nome'];
                $_SESSION['email_cliente'] = $cliente['email'];
                $_SESSION['telefone_cliente'] = $cliente['telefone'];
                
                $_SESSION['mensagem'] = 'Login realizado com sucesso!';
                $_SESSION['tipo_mensagem'] = 'success';
            } else {
                // Usuário não existe, criar novo
                $senha_aleatoria = bin2hex(random_bytes(8));
                $senha_hash = password_hash($senha_aleatoria, PASSWORD_DEFAULT);
                $telefone = '';
                
                $stmt = $conexao->prepare("INSERT INTO clientes (nome, email, telefone, senha) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $telefone, $senha_hash);
                $stmt->execute();
                
                $_SESSION['cliente_id'] = $conexao->insert_id;
                $_SESSION['nome_cliente'] = $name;
                $_SESSION['email_cliente'] = $email;
                $_SESSION['telefone_cliente'] = $telefone;
                
                $_SESSION['mensagem'] = 'Conta criada com sucesso!';
                $_SESSION['tipo_mensagem'] = 'success';
            }
            
            header('Location: ../../index.php');
            exit;
        }
    }
}

$_SESSION['mensagem'] = 'Erro ao fazer login com Google.';
$_SESSION['tipo_mensagem'] = 'danger';
header('Location: ../../index.php');
exit;
?>