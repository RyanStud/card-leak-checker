# Card Leak Checker - Branch de Testes Locais

> Branch: `test/setup-local-dev`

Esta branch existe para testar localmente, no XAMPP, os recursos novos da aplicacao antes de qualquer decisao sobre producao. O foco principal desta branch e validar:

- Integracao com Telegram em modo `log`
- Elevacao de sessao administrativa
- Historico de senhas antigas
- Sincronizacao do banco local
- Fluxos principais de autenticacao, LGPD, admin e consulta de cartoes

Esta branch nao deve ser usada como referencia final de producao sem revisao. Ela foi preparada para facilitar testes locais e evitar chamadas reais para servicos externos.

---

## Avisos importantes para quem for testar

- Use esta branch apenas para ambiente local/XAMPP.
- Confirme que voce esta na branch `test/setup-local-dev` antes de testar.
- Nao coloque tokens reais no Git.
- Nao suba o arquivo `.env` com dados sensiveis.
- O Telegram deve ficar em `TELEGRAM_MODE=log` para teste local.
- Em modo `log`, o sistema nao envia mensagem real para o Telegram.
- Os codigos de teste aparecem em `storage/logs/app.log`.
- Antes de testar o site, rode `database/sync_schema.sql` no phpMyAdmin.
- Se o phpMyAdmin parar em algum erro de SQL, copie exatamente a linha do erro antes de continuar.
- Esta branch pode conter ajustes especificos para teste, entao nao trate ela como branch final de deploy.

---

## Requisitos locais

- XAMPP com Apache e MySQL/MariaDB ligados
- PHP do XAMPP
- phpMyAdmin
- Navegador
- Repositorio em:

```text
C:\xampp\htdocs\card-leak-checker
```

URL esperada no navegador:

```text
http://localhost/card-leak-checker/
```

---

## Configuracao do .env local

O arquivo `.env` deve existir na raiz do projeto:

```text
C:\xampp\htdocs\card-leak-checker\.env
```

Para esta branch de teste, confirme principalmente:

```env
APP_URL=http://localhost/card-leak-checker/
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=u870812724_card_leak_chec
DB_USER=root
DB_PASS=
MAIL_MODE=log
TELEGRAM_MODE=log
ADMIN_ELEVATION_TTL=900
```

O ponto mais importante para testar Telegram localmente e:

```env
TELEGRAM_MODE=log
```

Com isso, o sistema grava o codigo no log em vez de chamar a API real do Telegram.

---

## Banco de dados

Crie ou selecione o banco no phpMyAdmin:

```sql
CREATE DATABASE IF NOT EXISTS u870812724_card_leak_chec
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Depois rode:

```text
database/sync_schema.sql
```

Esse arquivo sincroniza as tabelas necessarias para a branch, incluindo:

- `users`
- `projects`
- `project_memberships`
- `password_resets`
- `password_history`
- `telegram_accounts`
- `admin_role_change_requests`
- `leaked_cards_vault`
- `audit_logs`
- `request_logs`
- `suspicious_events`

### Aviso sobre MariaDB do XAMPP

No XAMPP com MariaDB 10.4, alguns comandos de indice podem ser sensiveis a sintaxe. Nesta branch foi removido o trecho duplicado que podia quebrar em:

```sql
ALTER TABLE password_history
    ADD INDEX IF NOT EXISTS idx_password_history_user_created (user_id, created_at);
```

O indice continua existindo dentro do proprio `CREATE TABLE password_history`:

```sql
INDEX idx_password_history_user_created (user_id, created_at)
```

Se outro erro parecido aparecer em um `ADD INDEX IF NOT EXISTS`, anote a linha exata e valide se o indice ja existe na criacao da tabela.

---

## Como criar um usuario admin local

Depois de criar um usuario pelo cadastro normal, voce pode transformar ele em admin pelo phpMyAdmin.

Troque o e-mail abaixo pelo seu:

```sql
USE u870812724_card_leak_chec;

UPDATE users
SET role = 'admin'
WHERE email = 'seu-email-aqui@email.com';
```

Para conferir:

```sql
SELECT id, name, email, role
FROM users
WHERE email = 'seu-email-aqui@email.com';
```

Depois faca logout e login novamente.

---

## Como testar o Telegram em modo log

O Telegram local foi implementado para funcionar de dois modos:

- `TELEGRAM_MODE=api`: tenta enviar pela API real do Telegram.
- `TELEGRAM_MODE=log`: nao envia nada para fora; grava a mensagem no log local.

Para XAMPP, use:

```env
TELEGRAM_MODE=log
```

Fluxo de teste:

1. Entre no sistema com um usuario admin.
2. Acesse:

```text
http://localhost/card-leak-checker/admin/elevate
```

3. Clique para gerar/enviar o codigo de elevacao.
4. Abra o arquivo:

```text
C:\xampp\htdocs\card-leak-checker\storage\logs\app.log
```

5. Procure por:

```text
admin_elevation_log_mode
```

6. Copie o codigo exibido no log.
7. Cole o codigo na tela de elevacao admin.
8. Se estiver correto e dentro do prazo, a sessao admin sera elevada.

### Onde esta o codigo do Telegram

Helper que decide se envia pela API ou grava no log:

```text
app/helpers/telegram.php
```

Controller que gera e valida o codigo de elevacao:

```text
app/controllers/AdminController.php
```

Modelo que guarda dados de conta Telegram e codigos:

```text
app/models/TelegramAccount.php
```

Rotas:

```text
index.php
```

Rotas principais:

```text
GET  /admin/elevate
POST /admin/elevate/send-code
POST /admin/elevate/verify
POST /webhook/telegram
```

---

## Como testar historico de senhas antigas

Esta branch impede que o usuario reutilize a senha atual ou senhas antigas recentes.

Fluxo recomendado:

1. Crie um usuario novo.
2. Faca login.
3. Troque a senha no dashboard.
4. Tente trocar novamente para a senha antiga.
5. O sistema deve bloquear a reutilizacao.

Tabela usada:

```text
password_history
```

Arquivos principais:

```text
app/models/User.php
app/controllers/DashboardController.php
app/controllers/AuthController.php
database/sync_schema.sql
```

O sistema armazena hashes antigos, nao senhas em texto puro.

---

## Testes recomendados no XAMPP

### Autenticacao

- Criar usuario novo
- Fazer login
- Fazer logout
- Testar senha fraca no cadastro
- Testar senha forte no cadastro
- Testar recuperacao de senha em modo log
- Testar troca de senha no dashboard
- Testar bloqueio de senha antiga

### Admin

- Promover usuario para `admin` pelo phpMyAdmin
- Fazer login novamente
- Acessar `/admin`
- Testar `/admin/elevate`
- Gerar codigo em modo log
- Copiar codigo de `storage/logs/app.log`
- Validar elevacao admin

### Telegram

- Confirmar `TELEGRAM_MODE=log`
- Gerar codigo de elevacao
- Verificar se apareceu `admin_elevation_log_mode` no log
- Confirmar que nenhuma chamada real para API do Telegram foi necessaria

### Banco

- Rodar `database/sync_schema.sql`
- Conferir se `password_history` existe
- Conferir se `telegram_accounts` existe
- Conferir se `admin_role_change_requests` existe
- Conferir se `leaked_cards_vault` existe

### LGPD e privacidade

- Acessar area de privacidade do usuario
- Testar visualizacao de dados
- Testar solicitacao de exclusao/anonimizacao, se disponivel
- Conferir registros de auditoria quando aplicavel

### Cartoes e vault

- Testar consulta de cartao com dados ficticios
- Testar importacao de CSV apenas com base de exemplo
- Nunca usar cartoes reais em ambiente de teste

---

## O que nao da para testar totalmente no XAMPP

- Webhook real do Telegram, porque o Telegram precisa acessar uma URL publica HTTPS.
- Envio real de Telegram se `TELEGRAM_MODE=log`.
- Comportamento final de dominio, certificado SSL e headers de producao.
- Entrega real de e-mail se `MAIL_MODE=log`.

Para testar webhook real do Telegram, seria necessario publicar a aplicacao em uma URL HTTPS valida e configurar o webhook com:

```text
scripts/configure-telegram-webhook.php
```

---

## Checklist rapido antes de testar

- Apache ligado no XAMPP
- MySQL ligado no XAMPP
- Branch atual: `test/setup-local-dev`
- `.env` existe
- `TELEGRAM_MODE=log`
- `MAIL_MODE=log`
- Banco criado
- `database/sync_schema.sql` rodado
- Usuario criado
- Usuario promovido para `admin`, se for testar area admin
- Arquivo `storage/logs/app.log` verificado apos gerar codigo

---

## Cuidados para producao

Antes de levar qualquer coisa desta branch para producao, revisar:

- Trocar `TELEGRAM_MODE=log` para `TELEGRAM_MODE=api`
- Configurar token real do bot em segredo criptografado
- Configurar `TELEGRAM_WEBHOOK_SECRET`
- Usar HTTPS publico
- Rodar testes completos de banco
- Revisar permissoes de admin
- Revisar logs para nao expor codigos sensiveis em producao
- Garantir que `.env`, `secrets.json` e chaves locais nao sejam commitados
- Validar backup do banco antes de migracoes

---

## Resumo da branch

Esta branch serve para validar localmente os recursos de seguranca e Telegram sem depender de servicos externos. O ponto central e permitir que o professor ou avaliador teste a logica da aplicacao no XAMPP de forma controlada, com `TELEGRAM_MODE=log`, banco sincronizado e instrucoes claras de validacao.
