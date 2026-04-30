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
│   │   └── User.php
│   │
│   └── views/
│       ├── admin/
│       ├── auth/
│       ├── cards/
│       ├── dashboard/
│       ├── partials/
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

### Elevação de sessão administrativa

- Usuários com role admin precisam elevar a sessão antes de acessar funcionalidades administrativas.
- A elevação usa código temporário enviado pelo Telegram (produção) ou modo local em log.
- Tempo de elevação controlado por `ADMIN_ELEVATION_TTL` (padrão 900 segundos).

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

### Endpoints de exclusão

| Ação | Endpoint |
|---|---|
| Excluir histórico | `POST /privacy/delete-history` |
| Excluir projetos | `POST /privacy/delete-projects` |
| Excluir conta | `POST /privacy/delete-account` |

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
- No diretório do projeto, rode `composer install`.
- Crie o banco de dados e aplique o schema em `database/schema.sql`.

### 2. Criar arquivo `.env` com base no `.env.example`

- Copie `.env.example` para `.env`.
- Preencha os valores básicos de aplicação e banco:
	- `APP_ENV`, `APP_DEBUG`, `APP_URL`
	- `DB_HOST`, `DB_PORT`, `DB_NAME`
- Defina também:
	- `MASTER_KEY_FILE` com caminho absoluto do arquivo da master key
	- `SECRETS_FILE=config/secrets.enc`
	- `TELEGRAM_MODE=log` para desenvolvimento local (sem webhook real)
	- `ADMIN_ELEVATION_TTL` para tempo da elevação admin em segundos

Exemplo de caminho da master key no Windows:

```env
MASTER_KEY_FILE=C:\xampp\htdocs\cardleak.masterkey
```

### 3. Criar arquivo `config/secrets.json` com base no `config/secrets.json.example`

- Copie `config/secrets.json.example` para `config/secrets.json`.
- Alternativa rápida: copie `config/secrets.simple.example.json` para `config/secrets.json` (sem chaves `_comment_`).
- Preencha os campos reais de segredo:
	- `DB_USER`: usuário do banco
	- `DB_PASS`: senha do banco
	- `APP_KEY_TELEGRAM`: token do bot do Telegram
	- `TELEGRAM_WEBHOOK_SECRET`: token de segurança do webhook Telegram
	- `CSRF_SECRET`: segredo para proteção CSRF
	- `MAILTRAP_API_TOKEN`: token do Mailtrap API
	- `MAIL_USER`: usuário SMTP
	- `MAIL_PASS`: senha SMTP

Observação:

- As chaves iniciadas com `_comment_` no arquivo de exemplo são apenas explicativas. Você pode remover essas chaves no `config/secrets.json` final, se quiser manter somente os segredos.

### 4. Gerar arquivo criptografado de segredos

Com `.env` e `config/secrets.json` preenchidos, execute:

```bash
php generate-secrets.php
```

O comando vai:

- Usar `SECRET_MASTER_KEY` (se existir no ambiente), ou
- Ler a chave do arquivo apontado em `MASTER_KEY_FILE`.

Saída esperada:

- Geração de `config/secrets.enc`.

### 5. Pós-geração e execução

- Valide a aplicação com o `config/secrets.enc` gerado.
- Remova `config/secrets.json` do ambiente final (produção).
- Inicie Apache/PHP e acesse o `APP_URL` configurado.

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