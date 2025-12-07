# User API Documentation

Base URL: `/api/v1`

## Endpoints

### User Profile
- [Get User Profile](#get-user-profile)
- [Update User Profile](#update-user-profile)
- [Get All Users](#get-all-users)
- [Update Online Status](#update-online-status)

### User Settings
- [Get User Settings](#get-user-settings)
- [Update User Settings](#update-user-settings)

### User Administration
- [Deactivate User](#deactivate-user)
- [Activate User](#activate-user)

### Departments
- [Create Department](#create-department)
- [Update Department](#update-department)
- [Get All Departments](#get-all-departments)

---

## Get User Profile

Get user profile information by ID.

**Endpoint:** `GET /api/v1/users/{id}`

**Authentication:** Required

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | User ID |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "phone": "+79991234567",
  "email": "john.doe@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "fullName": "John Doe",
  "photoUrl": "https://example.com/avatars/john.jpg",
  "position": "Senior Software Engineer",
  "statusMessage": "Working on new features 🚀",
  "onlineStatus": "available",
  "lastSeenAt": "2025-10-25T14:30:00+00:00",
  "isActive": true,
  "department": {
    "id": 1,
    "name": "Engineering",
    "description": "Software development team"
  },
  "createdAt": "2025-10-20T10:00:00+00:00"
}
```

**Error (404 Not Found)**

```json
{
  "error": "User not found"
}
```

---

## Update User Profile

Update user profile information.

**Endpoint:** `PATCH /api/v1/users/{id}`

**Authentication:** Required

**Permissions:** Own profile or admin

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | User ID |

### Request Body

```json
{
  "photoUrl": "https://example.com/avatars/new-photo.jpg",
  "position": "Lead Software Engineer",
  "statusMessage": "On vacation until Nov 1",
  "departmentId": 2
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| photoUrl | string | No | Profile photo URL (max 500 chars) |
| position | string | No | Job position/title (2-100 chars) |
| statusMessage | string | No | Custom status message (max 200 chars) |
| departmentId | integer | No | Department ID (must exist) |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "phone": "+79991234567",
  "email": "john.doe@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "fullName": "John Doe",
  "photoUrl": "https://example.com/avatars/new-photo.jpg",
  "position": "Lead Software Engineer",
  "statusMessage": "On vacation until Nov 1",
  "onlineStatus": "away",
  "lastSeenAt": "2025-10-25T14:35:00+00:00",
  "isActive": true,
  "department": {
    "id": 2,
    "name": "Product",
    "description": "Product management team"
  },
  "createdAt": "2025-10-20T10:00:00+00:00"
}
```

**Error (403 Forbidden)**

```json
{
  "error": "You don't have permission to update this profile"
}
```

**Error (404 Not Found)**

```json
{
  "error": "Department not found"
}
```

---

## Get All Users

Get all users with optional filters.

**Endpoint:** `GET /api/v1/users`

**Authentication:** Required

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| departmentId | integer | No | Filter by department |
| isActive | boolean | No | Filter by active status |
| onlineStatus | string | No | Filter by online status |

### Examples

```
GET /api/v1/users
GET /api/v1/users?departmentId=1
GET /api/v1/users?isActive=true
GET /api/v1/users?onlineStatus=available
GET /api/v1/users?departmentId=1&isActive=true
```

### Response

**Success (200 OK)**

```json
{
  "users": [
    {
      "id": 1,
      "firstName": "John",
      "lastName": "Doe",
      "fullName": "John Doe",
      "photoUrl": "https://example.com/avatars/john.jpg",
      "position": "Senior Software Engineer",
      "onlineStatus": "available",
      "lastSeenAt": "2025-10-25T14:30:00+00:00",
      "department": {
        "id": 1,
        "name": "Engineering"
      }
    },
    {
      "id": 2,
      "firstName": "Jane",
      "lastName": "Smith",
      "fullName": "Jane Smith",
      "photoUrl": "https://example.com/avatars/jane.jpg",
      "position": "Product Manager",
      "onlineStatus": "busy",
      "lastSeenAt": "2025-10-25T14:28:00+00:00",
      "department": {
        "id": 2,
        "name": "Product"
      }
    }
  ],
  "total": 2
}
```

---

## Update Online Status

Update current user's online status.

**Endpoint:** `PATCH /api/v1/users/me/status`

**Authentication:** Required

### Request Body

```json
{
  "status": "available"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| status | string | Yes | One of: available, busy, away, offline |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "onlineStatus": "available",
  "lastSeenAt": "2025-10-25T14:40:00+00:00"
}
```

**Error (400 Bad Request)**

```json
{
  "error": "Validation failed",
  "details": "status: Invalid status value"
}
```

### Online Status Values

| Status | Description | Use Case |
|--------|-------------|----------|
| available | Online and available | Ready to chat |
| busy | Online but busy | In a meeting, focused work |
| away | Temporarily away | AFK, taking a break |
| offline | Not connected | Manually set offline |

---

## Get User Settings

Get current user's settings.

**Endpoint:** `GET /api/v1/users/me/settings`

**Authentication:** Required

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "userId": 1,
  "theme": "dark",
  "notifications": true,
  "sound": true,
  "emailNotifications": false,
  "showOnlineStatus": true,
  "language": "en",
  "createdAt": "2025-10-20T10:00:00+00:00",
  "updatedAt": "2025-10-25T14:40:00+00:00"
}
```

### Notes

- Settings are created automatically on first access with default values
- Returns default settings if none exist

---

## Update User Settings

Update current user's settings.

**Endpoint:** `PATCH /api/v1/users/me/settings`

**Authentication:** Required

### Request Body

```json
{
  "theme": "dark",
  "notifications": true,
  "sound": false,
  "emailNotifications": true,
  "showOnlineStatus": false,
  "language": "ru"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| theme | string | No | Theme: light, dark, auto |
| notifications | boolean | No | Enable push notifications |
| sound | boolean | No | Enable notification sounds |
| emailNotifications | boolean | No | Enable email notifications |
| showOnlineStatus | boolean | No | Show online status to others |
| language | string | No | Interface language: ru, en |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "userId": 1,
  "theme": "dark",
  "notifications": true,
  "sound": false,
  "emailNotifications": true,
  "showOnlineStatus": false,
  "language": "ru",
  "createdAt": "2025-10-20T10:00:00+00:00",
  "updatedAt": "2025-10-25T14:45:00+00:00"
}
```

**Error (400 Bad Request)**

```json
{
  "error": "Validation failed",
  "details": "theme: Invalid theme value"
}
```

---

## Deactivate User

Deactivate user account (admin only).

**Endpoint:** `POST /api/v1/users/{id}/deactivate`

**Authentication:** Required

**Permissions:** Admin only

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | User ID to deactivate |

### Response

**Success (200 OK)**

```json
{
  "id": 5,
  "isActive": false,
  "message": "User deactivated successfully"
}
```

**Error (403 Forbidden)**

```json
{
  "error": "Access denied. Admin permission required"
}
```

**Error (404 Not Found)**

```json
{
  "error": "User not found"
}
```

**Error (422 Unprocessable Entity)**

```json
{
  "error": "Cannot deactivate yourself"
}
```

### Notes

- Deactivated users cannot log in
- Deactivated users remain in the database
- Can be reactivated later
- Cannot deactivate the last admin

---

## Activate User

Activate previously deactivated user account (admin only).

**Endpoint:** `POST /api/v1/users/{id}/activate`

**Authentication:** Required

**Permissions:** Admin only

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | User ID to activate |

### Response

**Success (200 OK)**

```json
{
  "id": 5,
  "isActive": true,
  "message": "User activated successfully"
}
```

**Error (403 Forbidden)**

```json
{
  "error": "Access denied. Admin permission required"
}
```

**Error (404 Not Found)**

```json
{
  "error": "User not found"
}
```

---

## Create Department

Create new department (admin only).

**Endpoint:** `POST /api/v1/departments`

**Authentication:** Required

**Permissions:** Admin only

### Request Body

```json
{
  "name": "Engineering",
  "description": "Software development and QA team"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Department name (2-100 chars, unique) |
| description | string | No | Department description (max 500 chars) |

### Response

**Success (201 Created)**

```json
{
  "id": 1,
  "name": "Engineering",
  "description": "Software development and QA team",
  "createdAt": "2025-10-25T14:50:00+00:00"
}
```

**Error (400 Bad Request)**

```json
{
  "error": "Validation failed",
  "details": "name: This value is already used"
}
```

**Error (403 Forbidden)**

```json
{
  "error": "Access denied. Admin permission required"
}
```

---

## Update Department

Update department information (admin only).

**Endpoint:** `PATCH /api/v1/departments/{id}`

**Authentication:** Required

**Permissions:** Admin only

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Department ID |

### Request Body

```json
{
  "name": "Engineering & Development",
  "description": "Software development, QA, and DevOps teams"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | No | Department name (2-100 chars, unique) |
| description | string | No | Department description (max 500 chars) |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "name": "Engineering & Development",
  "description": "Software development, QA, and DevOps teams",
  "createdAt": "2025-10-20T10:00:00+00:00"
}
```

**Error (403 Forbidden)**

```json
{
  "error": "Access denied. Admin permission required"
}
```

**Error (404 Not Found)**

```json
{
  "error": "Department not found"
}
```

---

## Get All Departments

Get list of all departments.

**Endpoint:** `GET /api/v1/departments`

**Authentication:** Required

### Response

**Success (200 OK)**

```json
{
  "departments": [
    {
      "id": 1,
      "name": "Engineering",
      "description": "Software development team",
      "createdAt": "2025-10-20T10:00:00+00:00"
    },
    {
      "id": 2,
      "name": "Product",
      "description": "Product management team",
      "createdAt": "2025-10-20T10:00:00+00:00"
    },
    {
      "id": 3,
      "name": "Design",
      "description": "UX/UI design team",
      "createdAt": "2025-10-20T10:00:00+00:00"
    }
  ],
  "total": 3
}
```

---

## Authentication

All endpoints require JWT authentication. Include the access token in the Authorization header:

```
Authorization: Bearer <access_token>
```

---

## Error Responses

All errors follow this structure:

```json
{
  "error": "Error message",
  "details": "Optional detailed information"
}
```

### Common HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Success |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Validation error |
| 401 | Unauthorized | Not authenticated |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Business logic error |

---

## Development Tips

### Testing with curl

```bash
# Get user profile
curl -X GET http://localhost:8000/api/v1/users/1 \
  -H "Authorization: Bearer $TOKEN"

# Update profile
curl -X PATCH http://localhost:8000/api/v1/users/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"position":"Senior Developer","statusMessage":"Working from home"}'

# Update online status
curl -X PATCH http://localhost:8000/api/v1/users/me/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"available"}'

# Get all users
curl -X GET http://localhost:8000/api/v1/users?departmentId=1 \
  -H "Authorization: Bearer $TOKEN"

# Get settings
curl -X GET http://localhost:8000/api/v1/users/me/settings \
  -H "Authorization: Bearer $TOKEN"

# Update settings
curl -X PATCH http://localhost:8000/api/v1/users/me/settings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"theme":"dark","language":"ru"}'

# Create department (admin)
curl -X POST http://localhost:8000/api/v1/departments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Engineering","description":"Software development team"}'
```

---

## Best Practices

1. **Profile Photos**: Use CDN URLs for better performance
2. **Status Messages**: Keep under 200 characters for UI compatibility
3. **Online Status**: Update when user activity detected
4. **Settings**: Cache on client side, sync on change
5. **Department Assignment**: Validate department exists before assignment
6. **Privacy**: Respect `showOnlineStatus` setting when displaying user status
7. **Deactivation**: Check user status before allowing login/actions
8. **Admin Operations**: Log all admin actions for audit trail

---

## Support

For API support, refer to the [module README](./README.md) or contact the development team.
