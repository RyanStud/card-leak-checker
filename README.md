# Card Leak Checker

> Sistema web seguro para verificação de possível vazamento de cartões, com autenticação forte, controle de acesso por projeto e conformidade com princípios da LGPD.

O sistema permite que usuários autenticados consultem se um cartão pode ter sido exposto em vazamentos conhecidos, mantendo histórico das consultas e isolamento de dados por projeto.

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-XAMPP-D22128?logo=apache&logoColor=white)
![License](https://img.shields.io/badge/License-Acadêmico-blue)

---

## Sumário

1. [Objetivo do Projeto](#1-objetivo-do-projeto)
2. [Tecnologias Utilizadas](#2-tecnologias-utilizadas)
3. [Bibliotecas e Recursos Utilizados](#3-bibliotecas-e-recursos-utilizados)
4. [Arquitetura do Sistema](#4-arquitetura-do-sistema)
5. [Estrutura do Projeto](#5-estrutura-do-projeto)
6. [Funcionalidades do Sistema](#6-funcionalidades-do-sistema)
7. [Medidas de Segurança Implementadas](#7-medidas-de-segurança-implementadas)
8. [LGPD e Privacidade](#8-lgpd-e-privacidade)
9. [Auditoria](#9-auditoria)
10. [Hospedagem e serviços](#10-hospedagem-e-serviços)
11. [Processo para subir em novo computador](#11-processo-para-subir-em-novo-computador)

---

## 1. Objetivo do Projeto

O objetivo do sistema é demonstrar a implementação de boas práticas de segurança em aplicações web, incluindo:

- Autenticação segura
- Proteção contra ataques comuns
- Gestão de dados conforme princípios da LGPD
- Auditoria e monitoramento de segurança
- Autenticação multifator (2FA)

> O projeto foi desenvolvido como prova de conceito acadêmica.

---

## 2. Tecnologias Utilizadas

| Camada | Tecnologia |
|---|---|
| **Backend** | PHP 8+, PDO, Arquitetura MVC |
| **Banco de Dados** | MySQL / MariaDB |
| **Frontend** | HTML5, CSS, PHP Templates |
| **Infraestrutura** | Apache (XAMPP), GitHub, GitHub Actions |

---

## 3. Bibliotecas e Recursos Utilizados

O projeto utiliza **funções nativas do PHP** para segurança.

### Hash de senha

```php
password_hash($password, PASSWORD_DEFAULT); // bcrypt
password_verify($input, $hash);
```

### Geração de tokens seguros

```php
random_bytes(32); // CSRF tokens, reset de senha, tokens internos
bin2hex($bytes);
```

### Comparação segura (evita timing attacks)

```php
hash_equals($expected, $provided);
```

### Sanitização e escape (evita XSS)

```php
htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
strip_tags($input);
preg_replace('/[^allowed]/', '', $input);
```

### Autenticação MFA

Implementação baseada em **TOTP** (Time-based One Time Password), compatível com:

- Google Authenticator
- Microsoft Authenticator

---

## 4. Arquitetura do Sistema

O projeto segue o padrão **MVC** (Model-View-Controller).

```
app/
 ├── controllers/   # Lógica de controle e rotas
 ├── models/        # Acesso a dados e regras de negócio
 ├── views/         # Templates HTML/PHP
 ├── helpers/       # Funções utilitárias (CSRF, segurança, OTP, logger...)
 ├── middleware/    # Autenticação, autorização e monitoramento de segurança
 └── core/          # Roteador, banco de dados, env/config e segredos
```

---

## 5. Estrutura do Projeto

```
card-leak-checker/
│
├── composer.json
├── generate-secrets.php
├── index.php
├── README.md
├── robots.txt
│
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ProjectController.php
│   │   ├── CardController.php
│   │   ├── PrivacyController.php
│   │   └── AdminController.php
│   │
│   ├── core/
│   │   ├── Config.php
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   ├── Env.php
│   │   ├── Router.php
│   │   └── SecretManager.php
│   │
│   ├── helpers/
│   │   ├── auth.php
│   │   ├── csrf.php
│   │   ├── env.php
│   │   ├── logger.php
│   │   ├── mailer.php
│   │   ├── otp.php
│   │   ├── security.php
│   │   ├── security_questions.php
│   │   ├── security_view.php
│   │   ├── url.php
│   │   └── view.php
│   │
│   ├── middleware/
│   │   ├── AdminMiddleware.php
│   │   ├── AuthMiddleware.php
│   │   └── SecurityMiddleware.php
│   │
│   ├── models/
│   │   ├── AdminDashboard.php
│   │   ├── AuditLog.php
│   │   ├── CardCheckRequest.php
│   │   ├── EmailVerification.php
│   │   ├── LeakedCardVault.php
│   │   ├── LoginAttempt.php
│   │   ├── PasswordReset.php
│   │   ├── Privacy.php
│   │   ├── Project.php
│   │   ├── SecurityMonitor.php
│   │   ├── SuspiciousEvent.php
│   │   ├── UserSecurityAnswer.php
│   │   └── User.php
│   │
│   └── views/
│       ├── admin/
│       ├── auth/
│       │   └── admin-passwordless-questions.php
│       ├── cards/
│       ├── dashboard/
│       ├── partials/
│       │   └── cookie-consent.php
│       ├── privacy/
│       └── projects/
│
├── config/
│   ├── database.php
│   ├── secrets.enc
│   ├── secrets.json
│   └── secrets.json.example
│
├── database/
│   ├── leaked_cards_vault.sql
│   ├── sample_leaked_cards.csv
│   ├── schema.sql
│   └── sync_schema.sql
│
├── public/
│   ├── assets/
│   │   ├── icons/
│   │   └── images/
│   └── js/
│       ├── banner-toggle.js
│       ├── cookie-consent.js
│       ├── admin-passwordless.js
│       ├── privacy-security-questions.js
│       └── reject-modal.js
│
├── storage/
│   └── logs/
```

---

## 6. Funcionalidades do Sistema

### Autenticação

- Cadastro de usuário
- Login / Logout
- Confirmação de senha forte
- Autenticação multifator (2FA) por TOTP no login (Authenticator)
- Login administrativo com Telegram como fluxo principal e perguntas de segurança como fallback após 1 minuto

### Perguntas de segurança

- O usuário cadastra 5 respostas dentre 10 perguntas pré-definidas na aba de privacidade.
- No login administrativo, o sistema sorteia 3 perguntas aleatórias entre as respostas já cadastradas.
- As respostas são armazenadas com hash, sem texto puro.

### Elevação de sessão administrativa

- Usuários com role admin precisam elevar a sessão antes de acessar funcionalidades administrativas.
- A elevação usa código temporário enviado pelo Telegram (produção) ou modo local em log.
- Tempo de elevação controlado por `ADMIN_ELEVATION_TTL` (padrão 900 segundos).
- Se o Telegram não for respondido em tempo hábil, o sistema libera o fallback por perguntas de segurança após 60 segundos, mantendo o Telegram como método principal.

### Recuperação de senha

Fluxo de reset seguro:

1. Usuário solicita reset
2. Sistema gera token seguro
3. Token enviado por e-mail
4. Usuário redefine a senha

### Gerenciamento de projetos

Usuários podem criar projetos com níveis de privacidade:

| Nível | Descrição |
|---|---|
| `private` | Acesso restrito ao dono do projeto |
| `restricted` | Acesso controlado por permissão |

> Os dados ficam isolados por projeto.

### Consulta de vazamento de cartão

Campos informados na consulta:

- Número do cartão
- Mês e ano de validade
- CVV

Dados registrados por consulta (sem armazenar PAN/CVV em claro):

- Fingerprint do cartão
- BIN mascarado
- Últimos 4 mascarados
- Data da consulta
- Status do resultado
- Projeto associado
- Origem da consulta

### Histórico de consultas

O usuário pode visualizar o histórico com: data, resultado e projeto.

### Painel administrativo

Métricas exibidas no painel de segurança:

- Número de usuários
- Projetos criados
- Consultas realizadas
- Tentativas de login
- Eventos suspeitos

Regras de acesso:

- Role `admin` obrigatória.
- Sessão administrativa elevada obrigatória.
 - Em caso de indisponibilidade do Telegram, o acesso pode seguir pelo fluxo alternativo de perguntas de segurança.

---

## 7. Medidas de Segurança Implementadas

### Hash seguro de senha

Senhas armazenadas com **bcrypt**:

```php
password_hash($password, PASSWORD_DEFAULT);
```

### Proteção contra SQL Injection

Todas as queries utilizam **prepared statements**:

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### Proteção contra XSS

Saída HTML sanitizada via `htmlspecialchars()`. Helper disponível: `e()`.

### Proteção contra CSRF

Token `_csrf` incluído em todos os formulários:

```php
// Geração
$token = bin2hex(random_bytes(32));

// Validação
hash_equals($storedToken, $submittedToken);
```

### Rate Limiting

Tentativas de login monitoradas na tabela `login_attempts`. Bloqueia ataques de força bruta.

### Proteção adicional no login admin

- Tentativas inválidas de código do Telegram e de perguntas de segurança são registradas como eventos suspeitos.
- O fallback para perguntas de segurança só é habilitado após 60 segundos da solicitação do código via Telegram.

### Monitoramento de eventos suspeitos

Tabela `suspicious_events` registra:

- IP de origem
- Tipo de evento
- Metadata adicional

Tipos de eventos atualmente detectados:

- `global_rate_limit_exceeded`
- `scanner_path_detected`
- `rate_limit_triggered`
- `credential_stuffing_suspected`
- `invalid_2fa_code`
- `session_hijack_suspected`
- `privilege_escalation_attempt`
- `admin_access_denied_repeated`
- `password_reset_abuse`
- `suspicious_method_abuse`
- `admin_session_not_elevated`
- `admin_elevation_code_invalid`

Evento auxiliar para análise de abuso de reset:

- `password_reset_request`

### Content Security Policy

Headers de segurança configurados:

```
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Referrer-Policy
```

### HTTPS obrigatório e HSTS

Em produção, a aplicação força redirecionamento para HTTPS e envia HSTS:

- Redirect `http -> https` com status `301`
- Header `Strict-Transport-Security: max-age=31536000; includeSubDomains`

Isso reduz risco de downgrade/ssl-stripping e garante transporte seguro para sessões e autenticação.

### Cadeia de IP com proxy confiável

O IP real do cliente é aceito de headers (`CF-Connecting-IP` e `X-Forwarded-For`) **apenas** quando o `REMOTE_ADDR` pertence a um proxy explicitamente confiável (`TRUSTED_PROXIES`).

Sem proxy confiável configurado, o sistema usa somente `REMOTE_ADDR`, evitando spoofing de IP por headers forjados.

### Criptografia de segredos

Credenciais sensíveis podem ser armazenadas em `config/secrets.enc`, gerado a partir de `config/secrets.json` com:

- `AES-256-GCM`
- IV aleatório
- tag de autenticação GCM
- chave derivada de `SECRET_MASTER_KEY` (`SHA-256` binário)

Arquivo utilizado no runtime:

- `app/core/SecretManager.php` (descriptografia e leitura)
- `generate-secrets.php` (criptografia e geração de `secrets.enc`)

Fluxo recomendado:

1. Criar/atualizar `config/secrets.json` localmente.
2. Definir `SECRET_MASTER_KEY` no ambiente do servidor.
3. Executar `php generate-secrets.php` para gerar `config/secrets.enc`.
4. Remover `config/secrets.json` do ambiente de produção.

### Criptografia de dados no banco (vault)

Os dados sensíveis do dataset de cartões vazados são armazenados no banco com criptografia em repouso na tabela `leaked_cards_vault`.

Como funciona:

- Busca por cartão: hash HMAC-SHA256 em `card_lookup_hash`.
- Material sensível (`card_number`, `expiry_month`, `expiry_year`, `cvv`): criptografado com `AES-256-GCM`.
- Integridade/autenticidade: tag GCM (`payload_tag`) e IV aleatório por registro (`payload_iv`).
- Payload criptografado: armazenado em `payload_ciphertext`.

Chaves e segredos usados:

- `CARD_LOOKUP_PEPPER`: pepper do hash de busca.
- `CARD_VAULT_KEY`: material para derivar chave de criptografia do vault.

Observação importante:

- A tabela de histórico `card_check_requests` armazena apenas metadados e máscaras (ex.: BIN/last4), sem PAN/CVV em texto puro.

Arquivos relacionados:

- `app/models/LeakedCardVault.php` (criptografia, descriptografia, lookup e importação)
- `scripts/import-leaked-cards.php` (importação via CLI)
- `database/sync_schema.sql` (estrutura completa incluindo `leaked_cards_vault`)

### Cookies seguros

Sessões configuradas com:

- `HttpOnly`
- `SameSite`
- `Secure` (quando HTTPS)

### Banner de consentimento de cookies

- O consentimento de cookies é exibido desde o primeiro acesso, em um banner fixo separado do rodapé do site.
- O banner permanece visível até o usuário escolher uma das opções de consentimento.
- A escolha é salva no cookie `lgpd_cookie_consent`.

### Controle de acesso

| Middleware | Responsabilidade |
|---|---|
| `AuthMiddleware` | Garante que páginas privadas exigem login |
| `AdminMiddleware` | Garante role admin e sessão elevada para rotas administrativas |

---

## 8. LGPD e Privacidade

O sistema implementa funcionalidades relacionadas à **Lei Geral de Proteção de Dados**.

### Direitos do usuário

O usuário pode, a qualquer momento:

- Excluir seu histórico de consultas
- Excluir seus projetos
- Excluir sua conta
- Registrar e alterar suas perguntas de segurança na aba de privacidade

### Consentimento de cookies

No primeiro acesso, o sistema exibe o banner de consentimento de cookies com opções para:

- Aceitar somente essenciais
- Aceitar todos

Esse banner fica separado do rodapé do site e aparece enquanto o usuário não escolher uma opção.

### Endpoints de exclusão

| Ação | Endpoint |
|---|---|
| Excluir histórico | `POST /privacy/delete-history` |
| Excluir projetos | `POST /privacy/delete-projects` |
| Excluir conta | `POST /privacy/delete-account` |
| Salvar perguntas de segurança | `POST /privacy/security-questions` |

### Acesso admin alternativo

| Ação | Endpoint |
|---|---|
| Solicitar código Telegram | `POST /admin/passwordless/request` |
| Ver perguntas de segurança | `GET /admin/passwordless/questions` |
| Validar perguntas de segurança | `POST /admin/passwordless/questions/verify` |

---

## 9. Auditoria

Logs registrados em dois locais:

- **Arquivo:** `storage/logs/app.log`
- **Banco de dados:** tabela `audit_logs`

Eventos registrados:

- Ações críticas
- Acessos administrativos
- Eventos relevantes de segurança

---

## 10. Hospedagem e serviços 

- Dominio: Hostinguer
- Servidor de email: mailtrap
- Hospedagem de aplicação: Hostinguer
- DB: Hostinguer/SQL

---

## 11. Processo para subir em novo computador

### 1. Preparar ambiente

- Instale PHP 8+, MySQL/MariaDB e Apache (ou use XAMPP).
- Instale o [Composer](https://getcomposer.org/).
- Crie o banco de dados e aplique o schema em `database/schema.sql`.

### 2. Criar `.env` com base no `.env.example`

- Copie `.env.example` para `.env`.
- Preencha os valores básicos:
	- `APP_ENV`, `APP_DEBUG`, `APP_URL`
	- `DB_HOST`, `DB_PORT`, `DB_NAME`
	- `TELEGRAM_MODE=log` para desenvolvimento local
	- `ADMIN_ELEVATION_TTL` em segundos
- Deixe `MASTER_KEY_FILE` vazio. A chave mestra fica em variável de ambiente do SO.

### 3. Criar `config/secrets.json` com base no exemplo

- Copie `config/secrets.json.example` para `config/secrets.json`.
- Preencha `DB_USER`, `DB_PASS`, `APP_KEY_TELEGRAM`, `TELEGRAM_WEBHOOK_SECRET`, `CSRF_SECRET`, `MAILTRAP_API_TOKEN`, `MAIL_USER`, `MAIL_PASS`, `CARD_LOOKUP_PEPPER`, `CARD_VAULT_KEY`.
- Pode remover as chaves `_comment_` do exemplo.

### 4. Rodar `composer install` (Linux ou Windows)

```bash
composer install
```

O `post-install-cmd` dispara automaticamente `composer setup`, que:

1. Pede a `SECRET_MASTER_KEY` uma única vez (input oculto no terminal).
2. Persiste a chave como variável de ambiente do SO:
	 - **Windows:** `setx SECRET_MASTER_KEY ...` no escopo do usuário (sem precisar de admin).
	 - **Linux/macOS:** linha `export SECRET_MASTER_KEY=...` em `~/.bashrc` ou `~/.zshrc`.
3. Gera `config/secrets.enc` criptografado com AES-256-GCM.

Para re-executar manualmente:

```bash
composer setup
```

Se a `SECRET_MASTER_KEY` já estiver presente no ambiente, o script pula a entrada interativa e só regenera o `config/secrets.enc` quando há `config/secrets.json` para criptografar.

### 5. Refletir a nova variável de ambiente

A `SECRET_MASTER_KEY` é gravada no sistema, mas processos já em execução não a enxergam automaticamente:

- **Windows (XAMPP/Apache):** feche o terminal atual e abra um novo. Reinicie o Apache (XAMPP Control Panel: Stop → Start) para que ele herde a variável.
- **Linux com Apache:** use `SetEnv SECRET_MASTER_KEY ...` no virtualhost ou `EnvironmentFile` no service do systemd.
- **Linux com nginx + php-fpm:** o `composer setup` já cuida disso. Ele grava `env[SECRET_MASTER_KEY] = "..."` em `/etc/php/X.Y/fpm/pool.d/www.conf` (permissões `640 root:root`) e roda `systemctl reload phpX.Y-fpm`. Se rodar como não-root, faça manualmente como root depois.

### 6. Pós-geração e execução

- Valide a aplicação carregando uma rota que dependa de segredos (ex.: login).
- Remova `config/secrets.json` do ambiente final (produção). A aplicação só precisa do `config/secrets.enc` + `SECRET_MASTER_KEY` na env do SO.
- O fluxo antigo via `MASTER_KEY_FILE` continua funcionando como fallback, caso prefira manter um arquivo de chave em produção.

### 7. Deploy em VM/container Ubuntu (lab da faculdade, nginx + php-fpm)

Cenário típico: container Ubuntu acessado via terminal, com nginx + php-fpm já instalados, mas sem composer. O diretório `/var/www/html` costuma ser um volume persistente (sobrevive entre sessões), enquanto `/etc/` pode estar no overlay (perde mudanças quando o container é recriado).

```bash
# 1. Instalar pré-requisitos (uma vez por sessão se o container reset)
apt update && apt install -y composer git

# 2. Clonar o projeto no volume persistente
cd /var/www/html
rm -f index.php   # remove o default
git clone <URL_DO_REPO> .

# 3. Preparar arquivos de configuração
cp .env.example .env
# editar .env (APP_ENV=production, DB_HOST, DB_NAME, etc.)

cp config/secrets.json.example config/secrets.json
# editar config/secrets.json com os segredos reais

# 4. Instalar dependências e configurar a chave mestra
composer install
# → vai pedir a SECRET_MASTER_KEY (input oculto)
# → grava em /root/.bashrc e /etc/php/8.3/fpm/pool.d/www.conf
# → roda systemctl reload php8.3-fpm
# → gera config/secrets.enc

# 5. Verificar se o vhost do nginx aponta para /var/www/html e passa PHP para php-fpm.
#    A maioria dos labs já vem com isso configurado.

# 6. Remover o secrets.json depois de validar
rm config/secrets.json
```

**Observações importantes nesse cenário:**

- Como `/etc/` é overlay e a config do php-fpm não persiste, **toda vez que o container for recriado** você roda `composer install` de novo. A chave é redigitada uma única vez por sessão e tudo é reconfigurado automaticamente.
- O `config/secrets.enc` fica em `/var/www/html/config/`, que persiste. Não precisa regerar entre sessões se os segredos não mudaram — basta redigitar a `SECRET_MASTER_KEY` para que o php-fpm consiga decifrar o arquivo já existente.
- Se quiser pular a geração e só reconfigurar a chave (quando `secrets.enc` já existe e `secrets.json` foi apagado), rode `composer setup` em vez de `composer install`.

### 5.1 Telegram webhook e modo local

- Defina no `.env`:
	- `APP_URL` com seu domínio real (ex.: `https://clonacartao.online`)
	- `TELEGRAM_BOT_USERNAME` com o username público do bot (sem `@`)
	- `TELEGRAM_MODE=api` para produção
- Gere/atualize o `config/secrets.enc` com `APP_KEY_TELEGRAM` e `TELEGRAM_WEBHOOK_SECRET` preenchidos.
- Configure o webhook no Telegram:

```bash
php scripts/configure-telegram-webhook.php
```

- Endpoint recebido pelo Telegram: `POST /webhook/telegram`.
- Segurança: o endpoint valida o header `X-Telegram-Bot-Api-Secret-Token`.

Desenvolvimento local (sem webhook público):

- Use `TELEGRAM_MODE=log` no `.env`.
- Nesse modo, o envio de código de elevação admin é registrado em `storage/logs/app.log`.
- Para elevar sessão admin localmente:
	- acesse `/admin/elevate`
	- clique em gerar código
	- copie o código do log e valide no formulário

#### Modo polling (ambiente fechado, ex.: lab atrás de VPN)

Quando o servidor não pode receber webhooks (não está exposto na internet pública), use `TELEGRAM_MODE=polling`. Um script CLI consulta `getUpdates` em loop e processa as mensagens com a mesma lógica do webhook.

Setup:

1. No `.env`:
	 - `TELEGRAM_MODE=polling`
	 - `TELEGRAM_BOT_USERNAME=<username_do_bot>` (sem `@`)
	 - `TELEGRAM_POLLING_INTERVAL=10` (opcional, default 10s)
2. Preencha `APP_KEY_TELEGRAM` no `config/secrets.json` e rode `composer setup` (ou `composer install`).

Pronto. Quando `TELEGRAM_MODE=polling`, o `composer setup` faz tudo:

1. Regera `config/secrets.enc`.
2. Verifica se já existe um polling em execução (via `storage/telegram-polling.pid`).
3. Se não, inicia `php scripts/telegram-polling.php` em background com `setsid`/`nohup`, gravando em `storage/logs/polling.log`.
4. Imprime o PID do processo iniciado.

Verificar status / parar manualmente:

```bash
cat storage/telegram-polling.pid       # PID atual
ps -p $(cat storage/telegram-polling.pid)   # confere se está vivo
tail -f storage/logs/polling.log       # acompanhar mensagens
kill $(cat storage/telegram-polling.pid)   # parar
```

Pra iniciar manualmente (fora do composer):

```bash
nohup php scripts/telegram-polling.php > storage/logs/polling.log 2>&1 &
```

Detalhes:

- O script remove qualquer webhook configurado ao iniciar (`deleteWebhook`).
- O offset (último `update_id` processado) fica persistido em `storage/telegram_offset.txt`, evitando reprocessar mensagens entre reinícios.
- O PID do processo fica em `storage/telegram-polling.pid` (auto-removido ao encerrar). O `composer setup` é idempotente: se já tem polling rodando, não inicia outro.
- Se o processo morrer (container reiniciar, sessão fechar), rode `composer setup` ou inicie manualmente — o polling retoma do último offset.
- O envio de mensagens (`telegram_send_message`) continua usando a API normalmente; só a *recepção* muda de webhook para polling.

### 5.2 Importar base de cartões vazados (vault)

Com o banco sincronizado via `database/sync_schema.sql`, execute a importação do CSV para popular `leaked_cards_vault`:

```bash
php scripts/import-leaked-cards.php
```

Comando com parâmetros opcionais:

```bash
php scripts/import-leaked-cards.php caminho/do/arquivo.csv nome-do-lote
```

Detalhes:

- Sem parâmetros, o script usa o CSV em `LEAKED_CARDS_SAMPLE_CSV` (padrão: `database/sample_leaked_cards.csv`).
- O script valida os cartões e grava apenas no vault criptografado (`leaked_cards_vault`).
- Também é possível importar pela interface de administração em `/admin`, no formulário "Importar cartões para o vault".

### 6. E-mail no primeiro deploy (sem Mailtrap)

Para facilitar o onboarding, o projeto pode rodar sem `MAILTRAP_API_TOKEN`.

- Use `MAIL_MODE=log` no `.env` para ambiente inicial.
- Nesse modo, os e-mails são registrados em log e não enviados externamente.
- Mesmo se `MAIL_MODE=mailtrap_api`, quando não houver token configurado o sistema faz fallback para log.

Quando quiser envio real por Mailtrap:

- Defina `MAIL_MODE=mailtrap_api`.
- Preencha `MAILTRAP_API_TOKEN` no `config/secrets.json` e gere novamente `config/secrets.enc`.