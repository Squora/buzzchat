# BuzzChat Backend API

Modern chat application backend built with **Symfony 7.1**, following **Clean Architecture** and **CQRS** principles.

## 📋 Table of Contents

- [Technology Stack](#technology-stack)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Development](#development)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [API Documentation](#api-documentation)
- [Project Structure](#project-structure)
- [Modules](#modules)
- [Contributing](#contributing)

## 🛠 Technology Stack

- **PHP**: 8.2+
- **Framework**: Symfony 7.1
- **API**: API Platform 4
- **Database**: PostgreSQL 16
- **ORM**: Doctrine ORM 3
- **Authentication**: JWT (Lexik JWT Bundle + Refresh Tokens)
- **CORS**: Nelmio CORS Bundle
- **Code Quality**: PHPStan (Level 8), PHP_CodeSniffer (PSR-12)
- **Testing**: PHPUnit 12

## 🏗 Architecture

This project follows **Clean Architecture** principles combined with **CQRS** (Command Query Responsibility Segregation) pattern:

### Design Principles

- **SOLID** - Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- **DRY** (Don't Repeat Yourself)
- **KISS** (Keep It Simple, Stupid)
- **Clean Code** - Readable, maintainable, testable
- **GoF Design Patterns** - Strategy, Factory, Repository, Observer, etc.

### Architectural Patterns

- **Feature-based modules** - Each feature is isolated in its own module
- **CQRS** - Separation of commands (write) and queries (read)
- **DTO** (Data Transfer Objects) - For API requests and responses
- **Value Objects** - For domain-specific values
- **Repository Pattern** - For data access abstraction
- **Service Layer** - For business logic
- **Handler Pattern** - One handler per action (command/query)

### Module Structure

Each module follows this structure:

```
src/{Module}/
├── docs/                    # Module-specific documentation
│   ├── README.md           # Module overview
│   └── API.md              # API endpoints documentation
├── Command/                # Console commands
├── Contracts/              # Interfaces
├── DTO/                    # Data Transfer Objects
├── Entity/                 # Domain entities
├── Exception/              # Domain exceptions
├── Handler/                # Command/Query handlers
├── Repository/             # Data access repositories
├── Service/                # Business logic services
└── ValueObject/            # Value objects
```

## 📦 Requirements

- PHP 8.2 or higher
- Composer 2.x
- PostgreSQL 16+
- Docker & Docker Compose (optional, for database)
- OpenSSL (for JWT keys generation)

### PHP Extensions

- ext-ctype
- ext-iconv
- ext-pdo
- ext-pdo_pgsql

## 🚀 Installation

### 1. Clone the repository

```bash
cd backend-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env .env.local
```

Edit `.env.local` with your configuration:

```env
APP_ENV=dev
APP_SECRET=your-app-secret-here

# Database
DATABASE_URL="postgresql://user:password@localhost:5432/buzzchat?serverVersion=16&charset=utf8"

# JWT Configuration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-jwt-passphrase
JWT_ACCESS_TOKEN_TTL=3600

# CORS
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

### 4. Start PostgreSQL

#### Using Docker Compose:

```bash
docker compose up -d
```

#### Or install PostgreSQL locally and create database:

```bash
createdb buzzchat
```

### 5. Generate JWT keys

```bash
php bin/console lexik:jwt:generate-keypair
```

This creates:
- `config/jwt/private.pem`
- `config/jwt/public.pem`

### 6. Create database schema

```bash
# Create database (if not exists)
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate
```

### 7. Start development server

```bash
# Using Symfony CLI (recommended)
symfony server:start

# Or using PHP built-in server
php -S localhost:8000 -t public/
```

The API is now available at: `http://localhost:8000`

## ⚙️ Configuration

### JWT Authentication

JWT tokens are used for API authentication:

- **Access Token**: Short-lived (default: 1 hour)
- **Refresh Token**: Long-lived (can be revoked)

### CORS Configuration

Edit `config/packages/nelmio_cors.yaml` to configure allowed origins for your frontend:

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
```

### Database Migrations

```bash
# Generate new migration after entity changes
php bin/console doctrine:migrations:diff

# Execute migrations
php bin/console doctrine:migrations:migrate

# Rollback migration
php bin/console doctrine:migrations:migrate prev
```

## 💻 Development

### Common Commands

```bash
# Clear cache
php bin/console cache:clear

# List all routes
php bin/console debug:router

# List all services
php bin/console debug:container

# Create new entity
php bin/console make:entity

# Cleanup expired tokens (recommended: setup cron)
php bin/console app:auth:cleanup-tokens
php bin/console app:auth:cleanup-pending-users
```

### Development Tools

```bash
# Run all quality checks
composer code-check

# Run PHPStan (static analysis)
composer phpstan

# Run PHP_CodeSniffer (code style)
composer phpcs

# Auto-fix code style issues
composer phpcbf

# Run tests
composer test
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=Auth
./vendor/bin/phpunit --testsuite=Chat
./vendor/bin/phpunit --testsuite=User

# Run with coverage
./vendor/bin/phpunit --coverage-html var/coverage
```

### Test Structure

```
tests/
├── Unit/              # Unit tests (isolated, no dependencies)
├── Integration/       # Integration tests (with database, services)
├── Auth/             # Auth module tests
├── Chat/             # Chat module tests
├── User/             # User module tests
└── Message/          # Message module tests
```

## 🔍 Code Quality

### PHPStan - Static Analysis

Configuration: `phpstan.neon`

```bash
# Analyze code
composer phpstan

# Level 8 (maximum strictness)
# Includes Symfony, Doctrine, PHPUnit extensions
```

### PHP_CodeSniffer - Code Style

Configuration: `phpcs.xml` (PSR-12 standard)

```bash
# Check code style
composer phpcs

# Auto-fix issues
composer phpcbf
```

### Code Quality Standards

- **PSR-12** - Extended coding style
- **Strict types** - All files use `declare(strict_types=1)`
- **Type hints** - All parameters and returns are typed
- **Final classes** - Services and handlers are final (immutable)
- **Readonly properties** - Where applicable
- **DocBlocks** - For complex logic and public APIs

## 📚 API Documentation

API documentation is available in module-specific docs:

- [Auth API](src/Auth/docs/API.md) - Authentication & Authorization
- [Chat API](src/Chat/docs/API.md) - Chat management
- [User API](src/User/docs/API.md) - User profiles & settings
- [Message API](src/Message/docs/API.md) - Messages & reactions

### Postman Collection

Import Postman collection from: [docs/postman/BuzzChat.postman_collection.json](docs/postman/BuzzChat.postman_collection.json)

### API Base URL

```
http://localhost:8000/api/v1
```

### Authentication

Most endpoints require JWT authentication:

```http
Authorization: Bearer <your-jwt-token>
```

## 📁 Project Structure

```
backend-api/
├── config/                  # Symfony configuration
│   ├── packages/           # Bundle configurations
│   ├── routes/             # Route configurations
│   └── jwt/                # JWT keys
├── docs/                    # Project documentation
│   ├── postman/            # Postman collections
│   └── architecture/       # Architecture diagrams
├── migrations/              # Database migrations
├── public/                  # Web server document root
├── src/                     # Application source code
│   ├── Auth/               # Authentication module
│   ├── Chat/               # Chat management module
│   ├── User/               # User management module
│   ├── Message/            # Messaging module
│   ├── Internal/           # Internal APIs (WebSocket gateway)
│   └── Kernel.php          # Symfony kernel
├── tests/                   # Automated tests
├── var/                     # Cache, logs, sessions
│   ├── cache/
│   ├── log/
│   └── coverage/           # Test coverage reports
├── vendor/                  # Composer dependencies
├── .env                     # Environment variables (template)
├── .env.local              # Local environment (not in VCS)
├── composer.json           # PHP dependencies
├── phpstan.neon            # PHPStan configuration
├── phpcs.xml               # PHP_CodeSniffer configuration
├── phpunit.xml.dist        # PHPUnit configuration
└── README.md               # This file
```

## 📦 Modules

### Auth Module

**Responsibility**: User authentication, registration, JWT token management

**Features**:
- SMS-based registration with verification codes
- Phone & email validation
- JWT access tokens + refresh tokens
- Token refresh and revocation
- Automatic cleanup of expired tokens

[📖 Auth Module Documentation](src/Auth/docs/README.md)

### Chat Module

**Responsibility**: Chat creation and management (group & direct)

**Features**:
- Group chat creation with multiple participants
- Direct (1-on-1) messaging
- Member management (add/remove)
- Role-based permissions (owner, admin, member)
- Chat settings (name, description, photo)

[📖 Chat Module Documentation](src/Chat/docs/README.md)

### User Module

**Responsibility**: User profiles, settings, departments

**Features**:
- User profile management
- Online status tracking
- User settings (theme, notifications, language)
- Department management
- User activation/deactivation

[📖 User Module Documentation](src/User/docs/README.md)

### Message Module

**Responsibility**: Message sending, editing, reactions, read receipts

**Features**:
- Send text messages with mentions
- File attachments
- Message editing and deletion
- Emoji reactions
- Read receipts
- Message history with pagination

[📖 Message Module Documentation](src/Message/docs/README.md)

### Internal Module

**Responsibility**: Internal APIs for WebSocket gateway integration

**Features**:
- Token validation for WebSocket connections
- User data retrieval
- Chat membership verification

## 🤝 Contributing

### Code Style

- Follow PSR-12 coding standard
- Use strict types (`declare(strict_types=1);`)
- Add type hints to all parameters and returns
- Write meaningful variable and method names
- Keep methods small and focused (Single Responsibility)
- Add PHPDoc blocks for complex logic

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(auth): add SMS verification for login
fix(chat): resolve member permission check
docs(readme): update installation instructions
refactor(user): extract status update to separate service
test(chat): add tests for chat deletion
```

### Pull Request Process

1. Create feature branch: `git checkout -b feature/your-feature`
2. Write code following code style guidelines
3. Add tests for new functionality
4. Run code quality checks: `composer code-check`
5. Commit changes with meaningful messages
6. Push to your fork and create Pull Request

## 📄 License

Proprietary - All rights reserved

## 👥 Authors

- BuzzChat Development Team

## 📞 Support

For support, please contact the development team or create an issue in the repository.

---

**Built with ❤️ using Symfony 7.1**
