# Clínica Nutrição - Sistema de Agendamentos

## Configuração do Envio de E-mails

Para que o sistema de envio de lembretes por e-mail funcione corretamente, siga estas instruções:

### Opção 1: Usando PHPMailer (Recomendado)

1. Instale o PHPMailer via Composer:
   ```
   composer require phpmailer/phpmailer
   ```

2. Edite o arquivo `phpmailer_config.php` e configure com seus dados de SMTP:
   ```php
   $smtp_server = 'smtp.seudominio.com'; // Seu servidor SMTP
   $smtp_port = 587; // Porta do servidor SMTP
   $smtp_username = 'seu_email@seudominio.com'; // Seu e-mail
   $smtp_password = 'sua_senha'; // Sua senha
   $smtp_secure = 'tls'; // tls ou ssl
   ```

3. Teste o envio de e-mails executando:
   ```
   php test_phpmailer.php
   ```

### Opção 2: Usando a função mail() nativa do PHP

1. Edite o arquivo `smtp_config.php` e configure com seus dados de SMTP:
   ```php
   ini_set('SMTP', 'smtp.seudominio.com');
   ini_set('smtp_port', 587);
   ini_set('sendmail_from', 'contato@seudominio.com');
   ```

2. Teste o envio de e-mails executando:
   ```
   php test_email.php
   ```

### Configuração do Agendador de Tarefas

Para enviar lembretes automaticamente:

#### No Windows (Task Scheduler):
1. Abra o Task Scheduler
2. Crie uma nova tarefa
3. Configure para executar: `C:\xampp\php\php.exe -f C:\xampp\htdocs\Clinicanutricao\cron_lembretes.php`
4. Defina para executar a cada hora

#### No Linux (Cron):
1. Edite o crontab: `crontab -e`
2. Adicione: `0 * * * * php /caminho/para/Clinicanutricao/cron_lembretes.php`

## Logs

O sistema mantém dois arquivos de log:
- `lembretes_log.txt`: Registra quando o script de lembretes foi executado
- `email_log.txt`: Registra o resultado de cada tentativa de envio de e-mail