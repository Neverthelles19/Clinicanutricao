INSTRUÇÕES PARA CONFIGURAR O LOGIN COM GOOGLE

1. Acesse o Google Cloud Console: https://console.cloud.google.com/
2. Crie um novo projeto ou selecione um existente
3. Vá para "APIs e Serviços" > "Credenciais"
4. Clique em "Criar Credenciais" > "ID do Cliente OAuth"
5. Configure a tela de consentimento OAuth
6. Crie um ID de cliente para aplicativo Web
7. Adicione a URL de redirecionamento: http://localhost/Clinicanutricao/oauth/google/callback.php
8. Copie o Client ID e Client Secret

Edite o arquivo config.php e substitua:
- $googleClientID com seu Client ID
- $googleClientSecret com seu Client Secret

Depois, execute o comando "composer install" na pasta raiz do projeto para instalar as dependências necessárias.