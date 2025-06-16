# Clínica Nutrição - Sistema de Agendamento

## Configuração do Login com Google

Para configurar o login com Google, siga os passos abaixo:

1. Acesse o [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto ou selecione um existente
3. Vá para "APIs e Serviços" > "Credenciais"
4. Clique em "Criar Credenciais" > "ID do Cliente OAuth"
5. Configure a tela de consentimento OAuth
6. Crie um ID de cliente para aplicativo Web
7. Adicione a URL de redirecionamento: `http://localhost/Clinicanutricao/oauth/google/callback.php`
8. Copie o Client ID e Client Secret

Edite o arquivo `oauth/google/config.php` e substitua:
- `$googleClientID` com seu Client ID
- `$googleClientSecret` com seu Client Secret

## Instalação das Dependências

Execute o comando abaixo na pasta raiz do projeto:

```
composer install
```

Isso instalará a biblioteca do Google API Client necessária para o login com Google.