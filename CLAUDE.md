# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BuzzChat is a multi-service chat application consisting of three main components:

1. **backend-api** - Symfony 7.1 API backend with JWT authentication
2. **frontend** - React 19 + TypeScript + Vite SPA
3. **gogate** - Go WebSocket gateway (minimal structure, uses gorilla/websocket)

## Backend API (Symfony 7.1)

### Technology Stack
- PHP 8.2+
- Symfony 7.1 with API Platform
- Doctrine ORM with PostgreSQL
- Lexik JWT Authentication Bundle with refresh tokens (gesdinet/jwt-refresh-token-bundle)
- CORS configured via nelmio/cors-bundle
- PHPUnit for testing

### Architecture

The backend follows a feature-based modular structure organized by domain:

- **Auth** (`src/Auth/`) - Complete authentication & authorization module with SMS verification:
  - `Entity/` - User, RefreshToken, and PendingUser entities
  - `DTO/` - Request/Response DTOs (RegisterRequest, LoginRequest, VerifyCodeRequest, RefreshTokenRequest, UserResponse)
  - `Handler/` - One-action handlers (RegisterHandler, VerifyCodeHandler, LoginHandler, RefreshTokenHandler, CurrentUserHandler, LogoutHandler)
  - `Service/` - Business logic (AuthService with registration/verification flow, SMS senders)
  - `Repository/` - Data access (UserRepository, PendingUserRepository)
  - `Command/` - Console commands (CleanupExpiredTokensCommand, CleanupPendingUsersCommand)
  - `EventSubscriber/` - JWT event handling (JWTCreatedSubscriber, AuthenticationSuccessSubscriber)
  - `Contracts/` - Interfaces (SmsSenderInterface)

  **Authentication Flow:**
  1. POST `/api/v1/auth/register` - Creates pending user, sends SMS code (4 digits, 10min expiry)
  2. POST `/api/v1/auth/verify` - Verifies code, creates user, returns JWT + refresh token
  3. POST `/api/v1/auth/login` - Login by phone + password, returns JWT + refresh token
  4. POST `/api/v1/auth/refresh` - Refresh access token using refresh token
  5. GET `/api/v1/auth/current` - Get current authenticated user (requires JWT)
  6. POST `/api/v1/auth/logout` - Invalidate refresh token

  See `backend-api/AUTH_DOCUMENTATION.md` for detailed API documentation.

- **Chat** (`src/Chat/`) - Complete chat management module for group chats and direct messages:
  - `Entity/` - Chat and ChatMember entities
  - `DTO/` - Request/Response DTOs (CreateGroupChatRequest, CreateDirectChatRequest, UpdateChatRequest, ChatResponse, etc.)
  - `Handler/` - One-action handlers (CreateGroupChatHandler, CreateDirectChatHandler, GetUserChatsHandler, UpdateChatHandler, etc.)
  - `Service/` - Business logic (ChatService with chat creation, member management, permissions)
  - `Repository/` - Data access (ChatRepository, ChatMemberRepository)
  - `Exception/` - Chat-specific exceptions (ChatNotFoundException, ChatAccessDeniedException, etc.)

  **Chat Features:**
  1. POST `/api/v1/chats/group` - Create group chat with multiple members
  2. POST `/api/v1/chats/direct` - Create/get direct chat between two users
  3. GET `/api/v1/chats` - Get all user's chats (with member previews, optimized for large chats)
  4. GET `/api/v1/chats/{id}` - Get chat details (without members list)
  5. GET `/api/v1/chats/{id}/members` - Get chat members with pagination, search, and filters (NEW)
  6. PATCH `/api/v1/chats/{id}` - Update chat name, description, photo (admins only)
  7. POST `/api/v1/chats/{id}/members` - Add members to chat (admins only)
  8. DELETE `/api/v1/chats/{id}/members/{userId}` - Remove member (admins only)
  9. POST `/api/v1/chats/{id}/leave` - Leave chat
  10. PATCH `/api/v1/chats/{id}/members/role` - Update member role (owner only)
  11. DELETE `/api/v1/chats/{id}` - Delete chat (owner only)

  **Chat Types:**
  - `direct` - Personal 1-on-1 chat (cannot be modified or deleted)
  - `group` - Group chat/беседа with multiple participants

  **Member Roles:**
  - `owner` - Chat creator with full control
  - `admin` - Can manage chat settings and members
  - `member` - Regular participant

  **Performance & Scalability:**
  - Optimized for chats with 500+ members
  - Paginated member endpoint with search and filters
  - Database indexes: `(chat_id, left_at)`, `(chat_id, role, left_at)`, `(chat_id, joined_at)`
  - Chat list returns only member previews (first 5 members)
  - Full member list accessed via separate paginated endpoint

  See `backend-api/CHAT_API_DOCUMENTATION.md` for detailed API documentation.

- **User** (`src/User/`) - User profile management, settings, and departments:
  - `Entity/` - Department and UserSettings entities (User entity extended in Auth module)
  - `DTO/` - Request/Response DTOs (UpdateProfileRequest, UpdateSettingsRequest, UserProfileResponse, DepartmentResponse, etc.)
  - `Handler/` - One-action handlers (GetProfileHandler, UpdateProfileHandler, GetSettingsHandler, etc.)
  - `Service/` - Business logic (UserService with profile management, settings, departments)
  - `Repository/` - Data access (DepartmentRepository, UserSettingsRepository)
  - `Exception/` - User-specific exceptions (DepartmentNotFoundException, AccessDeniedException)

  **User Features:**
  1. GET `/api/v1/users/{id}` - Get user profile
  2. PATCH `/api/v1/users/{id}` - Update profile (own or admin)
  3. GET `/api/v1/users` - Get all users (with filters)
  4. PATCH `/api/v1/users/me/status` - Update online status
  5. GET `/api/v1/users/me/settings` - Get user settings
  6. PATCH `/api/v1/users/me/settings` - Update user settings
  7. POST `/api/v1/users/{id}/deactivate` - Deactivate user (admin only)
  8. POST `/api/v1/users/{id}/activate` - Activate user (admin only)
  9. POST `/api/v1/departments` - Create department (admin only)
  10. PATCH `/api/v1/departments/{id}` - Update department (admin only)
  11. GET `/api/v1/departments` - Get all departments

  **Online Status:**
  - `available` - Online and available
  - `busy` - Online but busy
  - `away` - Temporarily away
  - `offline` - Offline

  **User Profile Fields:**
  - Photo URL, position/job title, custom status message
  - Department assignment
  - Online status tracking with last seen timestamp
  - Active/inactive state (for deactivated employees)

  **User Settings:**
  - Theme: light/dark/auto
  - Notifications, sound, email preferences
  - Show online status visibility
  - Language preference (ru/en)

  See `backend-api/USER_API_DOCUMENTATION.md` for detailed API documentation.

- **Entity** (`src/Entity/`) - Domain entities with Doctrine ORM attributes
- **ApiResource** (`src/ApiResource/`) - API Platform resources
- **Controller** (`src/Controller/`) - HTTP controllers using PHP attributes for routing
- **Service** (`src/Service/`) - Shared application services
- **Repository** (`src/Repository/`) - Doctrine repositories

### Key Configuration Files

- `config/packages/security.yaml` - Security and firewall configuration
- `config/packages/lexik_jwt_authentication.yaml` - JWT settings (keys, TTL)
- `config/packages/nelmio_cors.yaml` - CORS configuration
- `config/packages/api_platform.yaml` - API Platform configuration
- `config/packages/doctrine.yaml` - Database and ORM settings
- `config/routes.yaml` - Routes are auto-discovered via attributes in Controller namespace

### Database

- PostgreSQL 16 (configured in compose.yaml)
- Doctrine migrations in `migrations/` directory
- Environment variables in `.env` file (DATABASE_URL, JWT keys, etc.)

### Common Commands

#### Development
```bash
cd backend-api

# Install dependencies
composer install

# Start PostgreSQL database
docker compose up -d

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Generate JWT keys (if needed)
php bin/console lexik:jwt:generate-keypair

# Start dev server
symfony server:start
# or
php -S localhost:8000 -t public/

# Clear cache
php bin/console cache:clear
```

#### Testing
```bash
cd backend-api

# Run all tests
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/Path/To/TestFile.php

# Run tests with coverage (if configured)
./vendor/bin/phpunit --coverage-html coverage/
```

#### Doctrine Commands
```bash
cd backend-api

# Create new migration
php bin/console doctrine:migrations:diff

# Execute migrations
php bin/console doctrine:migrations:migrate

# Create new entity
php bin/console make:entity

# Update database schema (dev only, use migrations in production)
php bin/console doctrine:schema:update --force
```

#### Auth-Specific Commands
```bash
cd backend-api

# Cleanup expired refresh tokens (recommended: run in cron)
php bin/console app:auth:cleanup-tokens

# Cleanup expired pending user registrations (recommended: run in cron)
# Removes pending users older than 10 minutes
php bin/console app:auth:cleanup-pending-users

# View SMS verification codes in dev logs
tail -f var/log/dev.log | grep "DEV SMS"
```

## Frontend (React + TypeScript + Vite)

### Technology Stack
- React 19.1
- TypeScript 5.9
- Vite 7 for build tooling
- Sass (sass-embedded) for styling
- Lucide React for icons
- ESLint for code quality

### Architecture

Component-based architecture with modular SCSS:

- **Components** (`src/components/`):
  - `Sidebar/` - Chat list and navigation
  - `ChatArea/` - Main chat interface
  - `UserPanel/` - User information panel
  - `NewGroupModal/` - Group chat creation
  - `NewDirectModal/` - Direct message creation
  - `ChatSettingsModal/` - Chat settings management

Each component has co-located `.tsx` and `.scss` files.

- **State Management** - Currently using React hooks (useState, useEffect) in App.tsx
- **Styling** - Global styles in `src/styles/`, component-specific styles co-located
- **Types** - Centralized in `src/types/index.ts`
- **Mock Data** - Development data in `src/data/mockData.ts`

### Common Commands

```bash
cd frontend

# Install dependencies
npm install

# Start dev server (usually http://localhost:5173)
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Run linter
npm run lint

# Type check (manually, since build includes it)
npx tsc --noEmit
```

### Development Notes

- Frontend currently uses mock data; not yet integrated with backend API
- No state management library (Redux/Zustand) - consider adding when integrating with backend
- SCSS modules are used for styling isolation
- API integration will require adding HTTP client (axios/fetch) and environment configuration

## GoGate (WebSocket Gateway)

### Technology Stack
- Go 1.24.5
- gorilla/websocket v1.5.3

### Status
Minimal structure with only go.mod/go.sum present. No source files yet implemented.

### Expected Commands (when implemented)
```bash
cd gogate

# Install dependencies
go mod download

# Run in development
go run main.go

# Build binary
go build -o bin/gogate

# Run tests
go test ./...
```

## Integration Architecture

The intended architecture connects:
1. **Frontend** → makes HTTP requests to **Backend API** (with JWT authentication)
2. **Frontend** → establishes WebSocket connection to **GoGate**
3. **GoGate** → likely communicates with Backend API for authentication/authorization
4. **Backend API** → serves as source of truth for data (PostgreSQL)

## Environment Setup

### Backend Environment Variables
Key variables in `backend-api/.env`:
- `APP_ENV` - Environment (dev/prod/test)
- `APP_SECRET` - Symfony secret
- `DATABASE_URL` - PostgreSQL connection string
- `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` - JWT configuration
- `JWT_ACCESS_TOKEN_TTL` - Token lifetime
- `CORS_ALLOW_ORIGIN` - Allowed CORS origins

### Docker Services
`backend-api/compose.yaml` defines:
- PostgreSQL 16 database service
- Health checks configured
- Volume for persistent data storage

## Code Style & Conventions

### Backend (PHP/Symfony)
- Strict types (`declare(strict_types=1);`) in all PHP files
- Typed properties and return types required
- Attribute-based configuration (annotations) for entities, routes, validation
- PSR-4 autoloading: `App\` namespace maps to `src/`
- Service autowiring enabled
- Repository pattern for data access
- DTO pattern for request/response objects

### Frontend (TypeScript/React)
- Functional components with TypeScript
- React.FC type for components
- Props interfaces defined inline or in types file
- SCSS modules for component styling
- Strict TypeScript configuration (check tsconfig.json)

### Testing
- Backend: PHPUnit tests in `tests/` directory mirroring `src/` structure
- Frontend: No test framework configured yet (consider adding Vitest)
