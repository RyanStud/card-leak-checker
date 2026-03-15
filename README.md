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
10. [Variáveis de Ambiente](#10-variáveis-de-ambiente)

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
 ├── helpers/       # Funções utilitárias (CSRF, logger, mailer...)
 ├── middleware/    # Autenticação e autorização
 └── core/          # Roteador, banco de dados, base do framework
```

---

## 5. Estrutura do Projeto

```
card-leak-checker/
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
│   │   ├── Router.php
│   │   ├── Database.php
│   │   ├── Controller.php
│   │   └── Env.php
│   │
│   ├── helpers/
│   │   ├── auth.php
│   │   ├── csrf.php
│   │   ├── logger.php
│   │   ├── mailer.php
│   │   ├── otp.php
│   │   ├── security.php
│   │   ├── security_view.php
│   │   └── view.php
│   │
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   └── AdminMiddleware.php
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── CardCheckRequest.php
│   │   ├── AuditLog.php
│   │   ├── LoginAttempt.php
│   │   ├── SuspiciousEvent.php
│   │   ├── PasswordReset.php
│   │   ├── Privacy.php
│   │   └── AdminDashboard.php
│   │
│   └── views/
│
├── config/
│   └── database.php
│
├── database/
│   └── schema.sql
│
├── storage/
│   └── logs/
│
├── index.php
├── .env
└── .htaccess
```

---

## 6. Funcionalidades do Sistema

### Autenticação

- Cadastro de usuário
- Login / Logout
- Confirmação de senha forte
- Autenticação multifator (2FA)

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

Campos consultados:

- BIN do cartão
- Últimos 4 dígitos

Dados registrados por consulta:

- Data da consulta
- Status do resultado
- Projeto associado

### Histórico de consultas

O usuário pode visualizar o histórico com: data, resultado e projeto.

### Painel administrativo

Métricas exibidas no painel de segurança:

- Número de usuários
- Projetos criados
- Consultas realizadas
- Tentativas de login
- Eventos suspeitos

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

### Content Security Policy

Headers de segurança configurados:

```
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Referrer-Policy
```

### Cookies seguros

Sessões configuradas com:

- `HttpOnly`
- `SameSite`
- `Secure` (quando HTTPS)

### Controle de acesso

| Middleware | Responsabilidade |
|---|---|
| `AuthMiddleware` | Garante que páginas privadas exigem login |
| `AdminMiddleware` | Garante que o painel admin exige role adequada |

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

## 10. Variáveis de Ambiente

Configurações definidas no arquivo `.env`:

```env
APP_NAME=Card Leak Checker
APP_ENV=production
APP_DEBUG=false
APP_URL=https://clonacartao.online

DB_HOST=localhost
DB_PORT=3306
DB_NAME=card_leak_checker
DB_USER=root
DB_PASS=
```

## 11. Hospedagem e serviços 

Dominio: Hostinguer
Servidor de email: mailtrap
Hospedagem de aplicação: Hostinguer
DB: Hostinguer/SQL