# User Module

**User Profile and Settings Management Module**

## Overview

The User module is responsible for user profile management, settings, online status tracking, department management, and user activation/deactivation. It provides comprehensive user management capabilities for the BuzzChat application.

## Responsibility

- User profile management (photo, position, status message)
- Online status tracking (available, busy, away, offline)
- User settings management (theme, notifications, language)
- Department management (create, update, list)
- User activation/deactivation (admin operations)
- Last seen timestamp tracking
- User filtering and search

## Architecture

### Clean Architecture Layers

```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│  (Handlers - HTTP Controllers)          │
│  - GetProfileHandler                    │
│  - UpdateProfileHandler                 │
│  - GetAllUsersHandler                   │
│  - UpdateOnlineStatusHandler            │
│  - GetSettingsHandler                   │
│  - UpdateSettingsHandler                │
│  - DeactivateUserHandler                │
│  - ActivateUserHandler                  │
│  - CreateDepartmentHandler              │
│  - UpdateDepartmentHandler              │
│  - GetAllDepartmentsHandler             │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│         Application Layer                │
│  (Services - Business Logic)            │
│  - UserService                          │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│         Domain Layer                     │
│  (Entities, Value Objects, Exceptions)  │
│  - User (from Auth module)              │
│  - Department                           │
│  - UserSettings                         │
│  - OnlineStatus (enum)                  │
│  - Theme (enum)                         │
│  - Language (enum)                      │
│  - Exceptions                           │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│         Infrastructure Layer             │
│  (Repositories, External Services)      │
│  - UserRepository (from Auth)           │
│  - DepartmentRepository                 │
│  - UserSettingsRepository               │
└─────────────────────────────────────────┘
```

### CQRS Pattern

**Commands** (Write Operations):
- `UpdateProfileHandler` - Update user profile
- `UpdateOnlineStatusHandler` - Update online status
- `UpdateSettingsHandler` - Update user settings
- `DeactivateUserHandler` - Deactivate user (admin)
- `ActivateUserHandler` - Activate user (admin)
- `CreateDepartmentHandler` - Create department (admin)
- `UpdateDepartmentHandler` - Update department (admin)

**Queries** (Read Operations):
- `GetProfileHandler` - Get user profile by ID
- `GetAllUsersHandler` - Get all users with filters
- `GetSettingsHandler` - Get current user settings
- `GetAllDepartmentsHandler` - Get all departments

## Components

### Handlers (Presentation Layer)

**Profile Management:**
- `GetProfileHandler` - Retrieve user profile
- `UpdateProfileHandler` - Update profile fields
- `GetAllUsersHandler` - List users with filters

**Online Status:**
- `UpdateOnlineStatusHandler` - Update user's online status

**Settings:**
- `GetSettingsHandler` - Retrieve user settings
- `UpdateSettingsHandler` - Update settings

**User Administration:**
- `DeactivateUserHandler` - Deactivate user account
- `ActivateUserHandler` - Reactivate user account

**Department Management:**
- `CreateDepartmentHandler` - Create new department
- `UpdateDepartmentHandler` - Update department info
- `GetAllDepartmentsHandler` - List all departments

### Services (Business Logic)

- **UserService** - Core user management logic
  - `getProfile()` - Get user profile with access check
  - `updateProfile()` - Update profile with validation
  - `getAllUsers()` - List users with filters
  - `updateOnlineStatus()` - Update status and timestamp
  - `getSettings()` - Get or create user settings
  - `updateSettings()` - Update settings
  - `deactivateUser()` - Deactivate with permission check
  - `activateUser()` - Activate with permission check
  - `createDepartment()` - Create department (admin)
  - `updateDepartment()` - Update department (admin)
  - `getAllDepartments()` - List all departments

### Entities (Domain Models)

- **User** - Main user entity (extended from Auth module)
  - Profile fields: photoUrl, position, statusMessage
  - Status tracking: onlineStatus, lastSeenAt
  - Department relationship
  - Active/inactive state

- **Department** - Department/team entity
  - Basic info: name, description
  - User collection relationship

- **UserSettings** - User preferences
  - Theme (light/dark/auto)
  - Notifications settings
  - Language preference
  - Privacy settings

### Repositories

- **UserRepository** - User data access (from Auth module)
- **DepartmentRepository** - Department data access
- **UserSettingsRepository** - Settings data access

### DTOs (Data Transfer Objects)

**Request DTOs:**
- `UpdateProfileRequest` - Profile update data
- `UpdateOnlineStatusRequest` - Status update
- `UpdateSettingsRequest` - Settings update
- `CreateDepartmentRequest` - Department creation
- `UpdateDepartmentRequest` - Department update

**Response DTOs:**
- `UserProfileResponse` - Complete user profile
- `UserBriefResponse` - Brief user info for lists
- `UserSettingsResponse` - User settings
- `DepartmentResponse` - Department info

### Value Objects

- **OnlineStatus** - Enum: available, busy, away, offline
- **Theme** - Enum: light, dark, auto
- **Language** - Enum: ru, en

### Exceptions

- **UserNotFoundException** - User not found
- **DepartmentNotFoundException** - Department not found
- **AccessDeniedException** - Insufficient permissions
- **InvalidSettingsException** - Invalid settings data

## User Features

### Profile Management

Users can customize their profiles:
- Upload profile photo
- Set job position/title
- Add custom status message
- Assign to department

### Online Status

Four status types:
- **available** - Online and available for chat
- **busy** - Online but busy (do not disturb)
- **away** - Temporarily away (idle)
- **offline** - Not connected

Status automatically updates lastSeenAt timestamp.

### User Settings

Customizable preferences:
- **Theme**: light, dark, or auto (system)
- **Notifications**: Enable/disable push notifications
- **Sound**: Enable/disable notification sounds
- **Email Notifications**: Receive email updates
- **Show Online Status**: Privacy - hide status from others
- **Language**: Interface language (ru/en)

Settings are created automatically on first access with defaults.

### Department Management

Admins can organize users into departments:
- Create departments with name and description
- Update department information
- Assign users to departments
- View department member lists

### User Administration

Admins can manage user accounts:
- **Deactivate**: Temporarily disable user account
  - User cannot login
  - Remains in database
  - Can be reactivated
- **Activate**: Re-enable deactivated account

## Validation Rules

### UpdateProfileRequest

- `photoUrl`: Optional, valid URL, max 500 chars
- `position`: Optional, string, 2-100 chars
- `statusMessage`: Optional, string, max 200 chars
- `departmentId`: Optional, must exist

### UpdateOnlineStatusRequest

- `status`: Required, enum (available, busy, away, offline)

### UpdateSettingsRequest

- `theme`: Optional, enum (light, dark, auto)
- `notifications`: Optional, boolean
- `sound`: Optional, boolean
- `emailNotifications`: Optional, boolean
- `showOnlineStatus`: Optional, boolean
- `language`: Optional, enum (ru, en)

### CreateDepartmentRequest

- `name`: Required, string, 2-100 chars, unique
- `description`: Optional, string, max 500 chars

## Error Handling

### Custom Exceptions

- **UserNotFoundException** - User ID not found
- **DepartmentNotFoundException** - Department ID not found
- **AccessDeniedException** - User lacks permission
- **InvalidSettingsException** - Invalid settings values

### HTTP Status Codes

- `200 OK` - Success
- `400 Bad Request` - Validation error
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - User/department not found
- `422 Unprocessable Entity` - Business logic error

## Security & Permissions

### Profile Access Rules

- **Own profile**: Full read/write access
- **Other profiles**: Read-only access
- **Admins**: Can update any profile

### Settings Access

- Users can only access/modify their own settings
- No admin override (privacy)

### Department Management

- Only admins can create/update departments
- All users can view departments

### User Activation/Deactivation

- Only admins can deactivate/activate users
- Users cannot deactivate themselves
- Cannot deactivate the last admin

## Design Patterns Used

### Repository Pattern

Data access abstracted through repositories:
```php
interface DepartmentRepository {
    public function find(int $id): ?Department;
    public function findAll(): array;
    public function save(Department $department): void;
}
```

### DTO Pattern

Request/response data transfer:
- Clear API contracts
- Validation separation
- Type safety

### Handler Pattern

One handler per action:
- Single Responsibility
- Easy to test
- Clear intent

### Value Object Pattern

Enums for constrained values:
- Type safety
- Valid values guaranteed
- Self-documenting

### Service Layer Pattern

Business logic encapsulation:
- Reusable across handlers
- Testable independently
- Clear boundaries

## Database Schema

### User Table Extensions

```sql
ALTER TABLE users ADD COLUMN photo_url VARCHAR(500);
ALTER TABLE users ADD COLUMN position VARCHAR(100);
ALTER TABLE users ADD COLUMN status_message VARCHAR(200);
ALTER TABLE users ADD COLUMN online_status VARCHAR(20) DEFAULT 'offline';
ALTER TABLE users ADD COLUMN last_seen_at TIMESTAMP;
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT true;
ALTER TABLE users ADD COLUMN department_id INTEGER REFERENCES departments(id);
```

### Department Table

```sql
CREATE TABLE departments (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### UserSettings Table

```sql
CREATE TABLE user_settings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE NOT NULL REFERENCES users(id),
    theme VARCHAR(20) DEFAULT 'auto',
    notifications BOOLEAN DEFAULT true,
    sound BOOLEAN DEFAULT true,
    email_notifications BOOLEAN DEFAULT true,
    show_online_status BOOLEAN DEFAULT true,
    language VARCHAR(2) DEFAULT 'en',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Testing

### Unit Tests

```bash
./vendor/bin/phpunit --testsuite=User
```

### Example Test

```php
public function testUpdateProfileSuccess(): void
{
    $user = $this->createUser();
    $dto = new UpdateProfileRequest(
        photoUrl: 'https://example.com/photo.jpg',
        position: 'Senior Developer'
    );

    $updatedUser = $this->userService->updateProfile(
        $user->getId(),
        $dto,
        $user
    );

    $this->assertEquals('Senior Developer', $updatedUser->getPosition());
}
```

## Future Enhancements

1. **User Roles & Permissions**
   - Role-based access control (RBAC)
   - Custom permissions per role
   - Admin, moderator, user roles

2. **Advanced Filtering**
   - Search users by name, email, position
   - Filter by multiple criteria
   - Pagination and sorting

3. **User Avatars**
   - Avatar upload endpoint
   - Image processing and resizing
   - S3/CDN storage

4. **Activity Tracking**
   - User activity log
   - Login history
   - Action audit trail

5. **Privacy Controls**
   - Block users
   - Hide profile from specific users
   - Privacy zones

6. **Department Hierarchy**
   - Parent/child departments
   - Department trees
   - Organizational charts

7. **User Badges**
   - Achievement badges
   - Role badges
   - Custom badges

8. **Presence System**
   - Real-time status updates
   - WebSocket integration
   - Typing indicators

9. **User Preferences**
   - More customization options
   - Per-chat settings
   - Keyboard shortcuts

10. **Bulk Operations**
    - Bulk user import
    - Bulk department assignment
    - Batch activation/deactivation

## API Reference

See [API Documentation](./API.md) for detailed endpoint documentation.

## Dependencies

- `symfony/security-bundle` - Security and permissions
- `symfony/validator` - DTO validation
- `symfony/serializer` - Response serialization
- `doctrine/orm` - Database ORM
- User entity from Auth module

## Integration Points

### With Auth Module
- Uses User entity from Auth
- Extends user with profile fields
- Checks user active status for login

### With Chat Module
- User profiles shown in chat member lists
- Online status displayed in chats
- Department filtering for chat creation

### With Message Module
- User info in message metadata
- @mentions use user profiles
- Read receipts link to users

## Maintainers

BuzzChat Development Team

## License

Proprietary
