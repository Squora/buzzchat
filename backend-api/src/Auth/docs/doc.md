# Authentication & Authorization Documentation

## Overview

Полная система аутентификации и авторизации для BuzzChat API с использованием JWT токенов и SMS-верификацией.

## Architecture

### Модульная структура (Auth Module)

```
src/Auth/
├── Command/              # Console commands
│   ├── CleanupExpiredTokensCommand.php
│   └── CleanupPendingUsersCommand.php
├── Contracts/           # Interfaces
│   └── SmsSenderInterface.php
├── DTO/                 # Data Transfer Objects
│   ├── LoginRequest.php
│   ├── RefreshTokenRequest.php
│   ├── RegisterRequest.php
│   ├── UserResponse.php
│   └── VerifyCodeRequest.php
├── Entity/              # Doctrine entities
│   ├── PendingUser.php
│   ├── RefreshToken.php
│   └── User.php
├── EventSubscriber/     # Event listeners
│   ├── AuthenticationSuccessSubscriber.php
│   └── JWTCreatedSubscriber.php
├── Handler/             # One-action request handlers
│   ├── CurrentUserHandler.php
│   ├── LoginHandler.php
│   ├── LogoutHandler.php
│   ├── RefreshTokenHandler.php
│   ├── RegisterHandler.php
│   └── VerifyCodeHandler.php
├── Repository/          # Data access layer
│   ├── PendingUserRepository.php
│   └── UserRepository.php
└── Service/             # Business logic
    ├── AuthService.php
    ├── SmsApiSender.php (для production)
    └── SmsLoggerSender.php (для dev/test)
```

## API Endpoints

### 1. Registration (Start) - `/api/v1/auth/register`

**Method:** POST
**Access:** Public

**Request:**
```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john.doe@example.com",
  "phone": "+79991234567"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Verification code sent via SMS",
  "phone": "+79991234567"
}
```

**Что происходит:**
1. Проверяется уникальность телефона и email
2. Создается запись в таблице `pending_users` с 4-значным кодом
3. SMS с кодом отправляется на указанный номер (в dev - логируется)
4. Код действителен 10 минут

---

### 2. Verify Code & Complete Registration - `/api/v1/auth/register/verify`

**Method:** POST
**Access:** Public

**Request:**
```json
{
  "phone": "+79991234567",
  "code": "1234"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration completed successfully",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502004c0e3a5f...",
  "user": {
    "id": 1,
    "email": "john.doe@example.com",
    "phone": "+79991234567",
    "first_name": "John",
    "last_name": "Doe",
    "full_name": "John Doe",
    "roles": ["ROLE_USER"],
    "is_active": true
  }
}
```

**Что происходит:**
1. Проверяется код верификации и срок его действия
2. Создается пользователь в таблице `users`
3. Пароль хешируется с использованием Symfony PasswordHasher
4. Генерируются JWT access token и refresh token
5. Удаляется запись из `pending_users`

---

### 3. Login - `/api/v1/auth/login`

**Method:** POST
**Access:** Public

**Request:**
```json
{
  "phone": "+79991234567"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502004c0e3a5f...",
  "user": {
    "id": 1,
    "email": "john.doe@example.com",
    "phone": "+79991234567",
    "first_name": "John",
    "last_name": "Doe",
    "full_name": "John Doe",
    "roles": ["ROLE_USER"],
    "is_active": true
  }
}
```

**Что происходит:**
1. Поиск пользователя по номеру телефона
2. Проверка пароля
3. Проверка активности аккаунта
4. Генерация access и refresh токенов

---

### 4. Refresh Token - `/api/v1/auth/refresh`

**Method:** POST
**Access:** Public

**Request:**
```json
{
  "refresh_token": "def502004c0e3a5f..."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def502004c0e3a5f..."
}
```

**Что происходит:**
1. Проверка валидности refresh token
2. Получение пользователя
3. Генерация нового access token

---

### 5. Current User - `/api/v1/auth/current`

**Method:** GET
**Access:** Authenticated (JWT required)

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "john.doe@example.com",
    "phone": "+79991234567",
    "first_name": "John",
    "last_name": "Doe",
    "full_name": "John Doe",
    "roles": ["ROLE_USER"],
    "is_active": true
  }
}
```

---

### 6. Logout - `/api/v1/auth/logout`

**Method:** POST
**Access:** Public

**Request:**
```json
{
  "refresh_token": "def502004c0e3a5f..."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Что происходит:**
1. Удаление refresh token из базы данных

---

## Database Schema

### users
```sql
- id (PK)
- email (unique)
- phone (unique)
- password (hashed)
- first_name
- last_name
- roles (json)
- is_active
- created_at
- updated_at
```

### pending_users
```sql
- id (PK)
- phone
- email
- first_name
- last_name
- verification_code
- created_at
- expires_at (created_at + 10 minutes)
```

### refresh_tokens (gesdinet bundle)
```sql
- id (PK)
- refresh_token (unique)
- username (email)
- valid (expiration datetime)
```

---

## Configuration

### Environment Variables (.env)

```bash
# Database
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

# JWT Configuration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
JWT_ACCESS_TOKEN_TTL=3600  # 1 hour

# CORS (for frontend)
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

# SMS API (for production only)
# SMS_API_TOKEN=your_api_token_here
```

### Security Configuration

- **Public endpoints:** `/api/v1/auth/(register|verify|login|refresh)` - без аутентификации
- **Protected endpoints:** `/api/**` - требуют JWT токен
- **User provider:** Entity-based на User::email
- **Password hasher:** Auto (bcrypt/argon2)

### JWT Tokens

- **Access Token TTL:** 3600 seconds (1 hour)
- **Refresh Token TTL:** 2592000 seconds (30 days)
- **Token update on refresh:** Enabled
- **Single use refresh tokens:** Disabled (можно переиспользовать)

---

## SMS Sending

### Development/Test Environment

В dev/test окружении используется `SmsLoggerSender`, который логирует SMS-коды в файл логов:

```
[DEV SMS] To +79991234567: Your verification code: 1234
```

Посмотреть логи: `tail -f var/log/dev.log`

### Production Environment

В production используется `SmsApiSender`. Нужно:
1. Установить переменную окружения `SMS_API_TOKEN`
2. Реализовать логику отправки в `src/Auth/Service/SmsApiSender.php`

---

## Console Commands

### Cleanup Expired Pending Users
```bash
php bin/console app:auth:cleanup-pending-users
```
Удаляет истекшие записи из таблицы `pending_users` (старше 10 минут).

### Cleanup Expired Refresh Tokens
```bash
php bin/console app:auth:cleanup-tokens
```
Удаляет истекшие refresh токены из базы данных.

**Рекомендация:** Добавить эти команды в cron для регулярного выполнения.

---

## Development Workflow

### 1. Start Development Environment

```bash
cd backend-api

# Start database
docker compose up -d

# Run migrations (first time)
php bin/console doctrine:migrations:migrate

# Start development server
symfony server:start
# or
php -S localhost:8000 -t public/
```

### 2. Testing Authentication Flow

```bash
# 1. Register new user
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "phone": "+79991234567"
  }'

# 2. Check logs for verification code
tail -f var/log/dev.log | grep "DEV SMS"

# 3. Verify with code
curl -X POST http://localhost:8000/api/v1/auth/verify \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+79991234567",
    "code": "1234",
    "password": "MyPassword123"
  }'

# 4. Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+79991234567",
    "password": "MyPassword123"
  }'

# 5. Access protected endpoint
curl -X GET http://localhost:8000/api/v1/auth/current \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## Error Handling

Все эндпоинты возвращают стандартизированные ошибки:

**400 Bad Request:**
```json
{
  "error": "Invalid JSON body"
}
```
или
```json
{
  "errors": "Validation errors string"
}
```

**401 Unauthorized:**
```json
{
  "error": "Invalid credentials"
}
```

**403 Forbidden:**
```json
{
  "error": "Account is inactive"
}
```

---

## Security Best Practices

1. **Пароли:** Хешируются с помощью Symfony PasswordHasher (bcrypt/argon2)
2. **JWT:** Подписываются RSA ключами (приватный/публичный)
3. **Refresh Tokens:** Хранятся в БД, могут быть отозваны
4. **CORS:** Настроен для разрешенных доменов
5. **Валидация:** Все входные данные валидируются через Symfony Validator
6. **Rate Limiting:** Рекомендуется добавить для /register и /login
7. **HTTPS:** Обязательно для production

---

## Testing

Рекомендуется создать функциональные тесты для:
- Регистрации с валидными/невалидными данными
- Верификации с правильным/неправильным кодом
- Логина с правильными/неправильными credentials
- Обновления токенов
- Доступа к защищенным эндпоинтам

Пример структуры теста:
```
tests/
└── Auth/
    ├── RegisterTest.php
    ├── VerifyCodeTest.php
    ├── LoginTest.php
    └── RefreshTokenTest.php
```

---

## Future Enhancements

- [ ] Email verification (альтернатива SMS)
- [ ] 2FA (Two-Factor Authentication)
- [ ] Password reset flow
- [ ] Rate limiting для защиты от брутфорса
- [ ] Account lockout после N неудачных попыток
- [ ] Session management (список активных сессий)
- [ ] OAuth2 integration (Google, Facebook, etc.)
- [ ] Phone number change flow
- [ ] Email change flow
