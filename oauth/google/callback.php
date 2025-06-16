<?php
require_once 'config.php';

// Verificar se o autoload do Composer existe
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    $_SESSION['mensagem'] = 'Erro: Dependências não instaladas. Execute "composer install" na pasta raiz.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../index.php');
    exit;
}

try {
    // Criar cliente Google
    if (class_exists('Google_Client')) {
        $client = new Google_Client();
        $oauth_class = 'Google_Service_Oauth2';
    } elseif (class_exists('Google\\Client')) {
        $client = new Google\Client();
        $oauth_class = 'Google\\Service\\Oauth2';
    } else {
        throw new Exception('Biblioteca Google Client não encontrada');
    }
    
    $client->setClientId($googleClientID);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri($googleRedirectURL);

    // Verificar se há um código de autorização
    if (isset($_GET['code'])) {
        // Trocar o código por um token de acesso
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);
        
        // Obter informações do usuário
        $google_oauth = new $oauth_class($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        // Extrair dados do usuário
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $picture = $google_account_info->picture;
        
        // Verificar se o usuário já existe no banco de dados
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
            // Usuário não existe, criar novo usuário
            $senha_aleatoria = bin2hex(random_bytes(8)); // Gera senha aleatória
            $senha_hash = password_hash($senha_aleatoria, PASSWORD_DEFAULT);
            $telefone = ''; // Telefone vazio por padrão
            
            $stmt = $conexao->prepare("INSERT INTO clientes (nome, email, telefone, senha) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $telefone, $senha_hash);
            $stmt->execute();
            
            // Definir variáveis de sessão para o novo usuário
            $_SESSION['cliente_id'] = $conexao->insert_id;
            $_SESSION['nome_cliente'] = $name;
            $_SESSION['email_cliente'] = $email;
            $_SESSION['telefone_cliente'] = $telefone;
            
            $_SESSION['mensagem'] = 'Conta criada com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
        }
        
        // Redirecionar para a página principal
        header('Location: ../../index.php');
        exit;
    } else {
        // Se não houver código, redirecionar para a página inicial com mensagem de erro
        $_SESSION['mensagem'] = 'Falha na autenticação com o Google.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: ../../index.php');
        exit;
    }
} catch (Exception $e) {
    // Log do erro
    error_log('Erro no callback Google: ' . $e->getMessage());
    
    // Redirecionar para a página inicial com mensagem de erro
    $_SESSION['mensagem'] = 'Erro ao processar login com Google: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../index.php');
    exit;
}