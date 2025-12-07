# Chat API Documentation

Base URL: `/api/v1/chats`

## Endpoints

- [Create Group Chat](#create-group-chat)
- [Create Direct Chat](#create-direct-chat)
- [Get User Chats](#get-user-chats)
- [Get Chat Details](#get-chat-details)
- [Update Chat](#update-chat)
- [Add Members](#add-members)
- [Remove Member](#remove-member)
- [Leave Chat](#leave-chat)
- [Update Member Role](#update-member-role)
- [Delete Chat](#delete-chat)

---

## Create Group Chat

Create a new group chat with multiple participants.

**Endpoint:** `POST /api/v1/chats/group`

**Authentication:** Required (Bearer token)

### Request Body

```json
{
  "name": "Engineering Team",
  "description": "Team discussions and updates",
  "memberIds": [2, 3, 4, 5]
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Chat name (1-255 chars) |
| description | string | No | Chat description (max 500 chars) |
| memberIds | array | Yes | Array of user IDs to add (1-100 members) |

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

### Response

**Success (201 Created)**

```json
{
  "id": 1,
  "type": "group",
  "name": "Engineering Team",
  "description": "Team discussions and updates",
  "photo_url": null,
  "members_count": 5,
  "created_at": "2025-10-25T12:00:00+00:00",
  "updated_at": null,
  "members": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "phone": "+79991234567",
        "email": "john@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "fullName": "John Doe",
        "photoUrl": "https://example.com/avatar1.jpg",
        "position": "Team Lead",
        "statusMessage": "Available",
        "onlineStatus": "available",
        "lastSeenAt": "2025-10-25T11:55:00+00:00",
        "isActive": true,
        "department": {
          "id": 1,
          "name": "Engineering"
        },
        "createdAt": "2025-10-20T10:00:00+00:00"
      },
      "role": "owner",
      "joined_at": "2025-10-25T12:00:00+00:00",
      "left_at": null
    },
    {
      "id": 2,
      "user": {
        "id": 2,
        "phone": "+79991234568",
        "email": "jane@example.com",
        "firstName": "Jane",
        "lastName": "Smith",
        "fullName": "Jane Smith",
        "photoUrl": "https://example.com/avatar2.jpg",
        "position": "Senior Developer",
        "statusMessage": null,
        "onlineStatus": "available",
        "lastSeenAt": "2025-10-25T11:58:00+00:00",
        "isActive": true,
        "department": {
          "id": 1,
          "name": "Engineering"
        },
        "createdAt": "2025-10-21T09:00:00+00:00"
      },
      "role": "member",
      "joined_at": "2025-10-25T12:00:00+00:00",
      "left_at": null
    }
  ]
}
```

**Error (400 Bad Request) - Validation Error**

```json
{
  "error": "Validation failed",
  "details": "name: This value should not be blank."
}
```

**Error (401 Unauthorized) - Not Authenticated**

```json
{
  "error": "Authentication required"
}
```

**Error (404 Not Found) - User Not Found**

```json
{
  "error": "Users not found: [99, 100]"
}
```

### Notes

- Creator automatically becomes chat owner
- Member IDs can include the creator (will be deduplicated)
- All specified user IDs must exist
- Maximum 100 members per chat

---

## Create Direct Chat

Create or retrieve a direct (1-on-1) chat with another user.

**Endpoint:** `POST /api/v1/chats/direct`

**Authentication:** Required (Bearer token)

### Request Body

```json
{
  "userId": 2
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| userId | integer | Yes | ID of user to chat with |

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

### Response

**Success (200 OK or 201 Created)**

```json
{
  "id": 5,
  "type": "direct",
  "name": null,
  "description": null,
  "photo_url": null,
  "members_count": 2,
  "created_at": "2025-10-25T12:00:00+00:00",
  "updated_at": null,
  "members": [
    {
      "id": 10,
      "user": {
        "id": 1,
        "phone": "+79991234567",
        "email": "john@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "fullName": "John Doe",
        "photoUrl": "https://example.com/avatar1.jpg",
        "position": "Team Lead",
        "statusMessage": "Available",
        "onlineStatus": "available",
        "lastSeenAt": "2025-10-25T11:55:00+00:00",
        "isActive": true,
        "department": {
          "id": 1,
          "name": "Engineering"
        },
        "createdAt": "2025-10-20T10:00:00+00:00"
      },
      "role": "member",
      "joined_at": "2025-10-25T12:00:00+00:00",
      "left_at": null
    },
    {
      "id": 11,
      "user": {
        "id": 2,
        "phone": "+79991234568",
        "email": "jane@example.com",
        "firstName": "Jane",
        "lastName": "Smith",
        "fullName": "Jane Smith",
        "photoUrl": "https://example.com/avatar2.jpg",
        "position": "Senior Developer",
        "statusMessage": null,
        "onlineStatus": "available",
        "lastSeenAt": "2025-10-25T11:58:00+00:00",
        "isActive": true,
        "department": {
          "id": 1,
          "name": "Engineering"
        },
        "createdAt": "2025-10-21T09:00:00+00:00"
      },
      "role": "member",
      "joined_at": "2025-10-25T12:00:00+00:00",
      "left_at": null
    }
  ]
}
```

**Error (400 Bad Request) - Cannot Chat with Self**

```json
{
  "error": "Cannot create direct chat with yourself"
}
```

**Error (404 Not Found) - User Not Found**

```json
{
  "error": "User with ID 999 not found"
}
```

### Notes

- If direct chat already exists, returns existing chat (200 OK)
- If new chat created, returns 201 Created
- Direct chats have no name, description, or photo
- Both users have 'member' role (no owner)
- Cannot modify or delete direct chats

---

## Get User Chats

Get all chats for the authenticated user.

**Endpoint:** `GET /api/v1/chats`

**Authentication:** Required (Bearer token)

### Request

No request body required.

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Response

**Success (200 OK)**

```json
[
  {
    "id": 1,
    "type": "group",
    "name": "Engineering Team",
    "description": "Team discussions",
    "photo_url": "https://example.com/chat1.jpg",
    "members_count": 5,
    "created_at": "2025-10-25T12:00:00+00:00",
    "updated_at": "2025-10-25T14:30:00+00:00"
  },
  {
    "id": 2,
    "type": "direct",
    "name": null,
    "description": null,
    "photo_url": null,
    "members_count": 2,
    "created_at": "2025-10-24T10:00:00+00:00",
    "updated_at": null
  },
  {
    "id": 3,
    "type": "group",
    "name": "Project Alpha",
    "description": "Project Alpha coordination",
    "photo_url": null,
    "members_count": 8,
    "created_at": "2025-10-23T09:00:00+00:00",
    "updated_at": "2025-10-25T11:00:00+00:00"
  }
]
```

**Error (401 Unauthorized)**

```json
{
  "error": "Authentication required"
}
```

### Notes

- Returns all active chats (where user hasn't left)
- Ordered by last activity (updated_at DESC, created_at DESC)
- Does not include member details (use Get Chat Details for that)
- Empty array if user has no chats

---

## Get Chat Details

Get detailed information about a specific chat including members.

**Endpoint:** `GET /api/v1/chats/{id}`

**Authentication:** Required (Bearer token)

### Request

No request body required.

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Chat ID |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "type": "group",
  "name": "Engineering Team",
  "description": "Team discussions and updates",
  "photo_url": "https://example.com/chat1.jpg",
  "members_count": 5,
  "created_at": "2025-10-25T12:00:00+00:00",
  "updated_at": "2025-10-25T14:30:00+00:00",
  "members": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "phone": "+79991234567",
        "email": "john@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "fullName": "John Doe",
        "photoUrl": "https://example.com/avatar1.jpg",
        "position": "Team Lead",
        "statusMessage": "Available",
        "onlineStatus": "available",
        "lastSeenAt": "2025-10-25T11:55:00+00:00",
        "isActive": true,
        "department": {
          "id": 1,
          "name": "Engineering"
        },
        "createdAt": "2025-10-20T10:00:00+00:00"
      },
      "role": "owner",
      "joined_at": "2025-10-25T12:00:00+00:00",
      "left_at": null
    },
    {
      "id": 2,
      "user": {
        "id": 3,
        "phone": "+79991234569",
        "email": "alice@example.com",
        "firstName": "Alice",
        "lastName": "Johnson",
        "fullName": "Alice Johnson",
        "photoUrl": null,
        "position": "Developer",
        "statusMessage": null,
        "onlineStatus": "offline",
        "lastSeenAt": "2025-10-25T10:00:00+00:00",
        "isActive": true,
        "department": {
          "id": 1,
          "name": "Engineering"
        },
        "createdAt": "2025-10-22T08:00:00+00:00"
      },
      "role": "admin",
      "joined_at": "2025-10-25T12:00:00+00:00",
      "left_at": null
    }
  ]
}
```

**Error (403 Forbidden) - Not a Member**

```json
{
  "error": "Access denied to this chat"
}
```

**Error (404 Not Found) - Chat Not Found**

```json
{
  "error": "Chat with ID 999 not found"
}
```

### Notes

- Only accessible to chat members
- Includes full member list with user details
- Member roles are included

---

## Update Chat

Update chat name, description, or photo. Admins and owners only.

**Endpoint:** `PATCH /api/v1/chats/{id}`

**Authentication:** Required (Bearer token)

### Request Body

```json
{
  "name": "Engineering Team - Updated",
  "description": "Updated description",
  "photoUrl": "https://example.com/new-photo.jpg"
}
```

#### Fields

All fields are optional. Only provided fields will be updated.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | No | New chat name (1-255 chars) |
| description | string | No | New chat description (max 500 chars) |
| photoUrl | string | No | New photo URL |

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Chat ID |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "type": "group",
  "name": "Engineering Team - Updated",
  "description": "Updated description",
  "photo_url": "https://example.com/new-photo.jpg",
  "members_count": 5,
  "created_at": "2025-10-25T12:00:00+00:00",
  "updated_at": "2025-10-25T15:00:00+00:00",
  "members": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "firstName": "John",
        "lastName": "Doe",
        "fullName": "John Doe"
      },
      "role": "owner",
      "joined_at": "2025-10-25T12:00:00+00:00"
    }
  ]
}
```

**Error (400 Bad Request) - Cannot Modify Direct Chat**

```json
{
  "error": "Cannot modify direct chat properties"
}
```

**Error (403 Forbidden) - Not Admin/Owner**

```json
{
  "error": "Access denied: update chat"
}
```

**Error (404 Not Found) - Chat Not Found**

```json
{
  "error": "Chat with ID 999 not found"
}
```

### Notes

- Only admins and owners can update chat settings
- Direct chats cannot be modified
- All fields are optional (partial update)
- Setting null values will clear the field
- `updated_at` timestamp is automatically updated

---

## Add Members

Add new members to a group chat. Admins and owners only.

**Endpoint:** `POST /api/v1/chats/{id}/members`

**Authentication:** Required (Bearer token)

### Request Body

```json
{
  "userIds": [6, 7, 8]
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| userIds | array | Yes | Array of user IDs to add |

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Chat ID |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "type": "group",
  "name": "Engineering Team",
  "description": "Team discussions",
  "photo_url": null,
  "members_count": 8,
  "created_at": "2025-10-25T12:00:00+00:00",
  "updated_at": "2025-10-25T12:00:00+00:00",
  "members": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "firstName": "John",
        "lastName": "Doe",
        "role": "owner"
      },
      "role": "owner",
      "joined_at": "2025-10-25T12:00:00+00:00"
    },
    {
      "id": 6,
      "user": {
        "id": 6,
        "firstName": "Bob",
        "lastName": "Wilson",
        "role": "member"
      },
      "role": "member",
      "joined_at": "2025-10-25T15:30:00+00:00"
    }
  ]
}
```

**Error (400 Bad Request) - Cannot Modify Direct Chat**

```json
{
  "error": "Cannot modify direct chat properties"
}
```

**Error (403 Forbidden) - Not Admin/Owner**

```json
{
  "error": "Access denied: add members"
}
```

**Error (404 Not Found) - User Not Found**

```json
{
  "error": "Users not found: [99, 100]"
}
```

### Notes

- Only admins and owners can add members
- New members are added with 'member' role
- If user already a member, they are skipped (no error)
- All specified user IDs must exist
- Direct chats cannot have members added
- Maximum 100 members total per chat

---

## Remove Member

Remove a member from a group chat. Admins and owners only.

**Endpoint:** `DELETE /api/v1/chats/{id}/members/{userId}`

**Authentication:** Required (Bearer token)

### Request

No request body required.

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Chat ID |
| userId | integer | User ID to remove |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "type": "group",
  "name": "Engineering Team",
  "description": "Team discussions",
  "photo_url": null,
  "members_count": 4,
  "created_at": "2025-10-25T12:00:00+00:00",
  "updated_at": "2025-10-25T12:00:00+00:00",
  "members": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "firstName": "John",
        "lastName": "Doe"
      },
      "role": "owner",
      "joined_at": "2025-10-25T12:00:00+00:00"
    }
  ]
}
```

**Error (400 Bad Request) - Cannot Remove Self**

```json
{
  "error": "Cannot remove yourself from the chat. Use leave endpoint instead."
}
```

**Error (400 Bad Request) - Cannot Remove Owner**

```json
{
  "error": "Cannot remove chat owner"
}
```

**Error (400 Bad Request) - User Not Member**

```json
{
  "error": "User 99 is not a member of this chat"
}
```

**Error (403 Forbidden) - Not Admin/Owner**

```json
{
  "error": "Access denied: remove members"
}
```

### Notes

- Only admins and owners can remove members
- Cannot remove yourself (use Leave Chat endpoint)
- Cannot remove the chat owner
- Cannot remove from direct chats
- Member is permanently removed (not marked as left)

---

## Leave Chat

Leave a group chat. Members and admins only (owner cannot leave).

**Endpoint:** `POST /api/v1/chats/{id}/leave`

**Authentication:** Required (Bearer token)

### Request

No request body required.

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Chat ID |

### Response

**Success (200 OK)**

```json
{
  "success": true,
  "message": "Successfully left the chat"
}
```

**Error (400 Bad Request) - Owner Cannot Leave**

```json
{
  "error": "Owner cannot leave the chat. Please transfer ownership first or delete the chat."
}
```

**Error (400 Bad Request) - Cannot Leave Direct Chat**

```json
{
  "error": "Cannot modify direct chat properties"
}
```

**Error (400 Bad Request) - Not a Member**

```json
{
  "error": "User 5 is not a member of this chat"
}
```

**Error (403 Forbidden) - Not a Member**

```json
{
  "error": "Access denied to this chat"
}
```

### Notes

- Members and admins can leave
- Owner cannot leave (must delete chat or transfer ownership first)
- Cannot leave direct chats
- Member is permanently removed from chat
- Chat continues to exist after member leaves

---

## Update Member Role

Update a member's role in the chat. Owner only.

**Endpoint:** `PATCH /api/v1/chats/{id}/members/role`

**Authentication:** Required (Bearer token)

### Request Body

```json
{
  "userId": 3,
  "role": "admin"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| userId | integer | Yes | User ID to update |
| role | string | Yes | New role: 'admin' or 'member' |

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Chat ID |

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "type": "group",
  "name": "Engineering Team",
  "description": "Team discussions",
  "photo_url": null,
  "members_count": 5,
  "created_at": "2025-10-25T12:00:00+00:00",
  "updated_at": "2025-10-25T12:00:00+00:00",
  "members": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "firstName": "John",
        "lastName": "Doe"
      },
      "role": "owner",
      "joined_at": "2025-10-25T12:00:00+00:00"
    },
    {
      "id": 2,
      "user": {
        "id": 3,
        "firstName": "Alice",
        "lastName": "Johnson"
      },
      "role": "admin",
      "joined_at": "2025-10-25T12:00:00+00:00"
    }
  ]
}
```

**Error (400 Bad Request) - Cannot Change Own Role**

```json
{
  "error": "Cannot change your own role"
}
```

**Error (400 Bad Request) - Cannot Change Owner Role**

```json
{
  "error": "Cannot change owner role"
}
```

**Error (400 Bad Request) - Invalid Role**

```json
{
  "error": "Validation failed",
  "details": "role: The value you selected is not a valid choice."
}
```

**Error (403 Forbidden) - Not Owner**

```json
{
  "error": "Access denied: change member roles"
}
```

### Notes

- Only chat owner can change roles
- Cannot change owner role
- Cannot change your own role
- Valid roles: 'admin', 'member' (cannot set 'owner')
- User must be a member of the chat
- Direct chats don't support role changes

---

## Delete Chat

Delete a group chat. Owner only.

**Endpoint:** `DELETE /api/v1/chats/{id}`

**Authentication:** Required (Bearer token)

### Request

No request body required.

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Chat ID |

### Response

**Success (200 OK)**

```json
{
  "success": true,
  "message": "Chat deleted successfully"
}
```

**Error (400 Bad Request) - Cannot Delete Direct Chat**

```json
{
  "error": "Cannot modify direct chat properties"
}
```

**Error (403 Forbidden) - Not Owner**

```json
{
  "error": "Access denied: delete chat"
}
```

**Error (404 Not Found) - Chat Not Found**

```json
{
  "error": "Chat with ID 999 not found"
}
```

### Notes

- Only chat owner can delete chat
- Cannot delete direct chats
- Deletion cascades to all members
- All chat messages should also be deleted (if Message module implemented)
- Operation is irreversible

---

## Authentication

All endpoints require JWT authentication. Include the access token in the Authorization header:

```
Authorization: Bearer <access_token>
```

### Token Usage

1. Obtain token via Auth module login/register
2. Include in all Chat API requests
3. Refresh token when expired

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
| 201 | Created | Chat created |
| 400 | Bad Request | Validation error, invalid operation |
| 401 | Unauthorized | Invalid or missing token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Chat or user not found |
| 409 | Conflict | Direct chat already exists |
| 500 | Internal Server Error | Server error |

---

## Common Error Scenarios

### Validation Errors (400)

```json
{
  "error": "Validation failed",
  "details": "name: This value should not be blank.\nmemberIds: This collection should contain at least 1 element."
}
```

### Permission Errors (403)

```json
{
  "error": "Access denied: update chat"
}
```

### Not Found Errors (404)

```json
{
  "error": "Chat with ID 123 not found"
}
```

```json
{
  "error": "Users not found: [99, 100]"
}
```

### Business Logic Errors (400)

```json
{
  "error": "Cannot modify direct chat properties"
}
```

```json
{
  "error": "Cannot remove chat owner"
}
```

---

## Response Field Descriptions

### ChatResponse

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| id | integer | No | Chat unique identifier |
| type | string | No | Chat type: 'direct' or 'group' |
| name | string | Yes | Chat name (null for direct chats) |
| description | string | Yes | Chat description |
| photo_url | string | Yes | Chat photo URL |
| members_count | integer | No | Total number of members |
| created_at | string | No | ISO 8601 datetime |
| updated_at | string | Yes | ISO 8601 datetime |
| members | array | Yes | Array of ChatMemberResponse (optional) |

### ChatMemberResponse

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| id | integer | No | Membership unique identifier |
| user | object | No | UserResponse object |
| role | string | No | Member role: 'owner', 'admin', or 'member' |
| joined_at | string | No | ISO 8601 datetime |
| left_at | string | Yes | ISO 8601 datetime (null if active) |

### UserResponse (nested in ChatMemberResponse)

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| id | integer | No | User unique identifier |
| phone | string | No | Phone number (E.164 format) |
| email | string | No | Email address |
| firstName | string | No | First name |
| lastName | string | No | Last name |
| fullName | string | No | Full name (computed) |
| photoUrl | string | Yes | Profile photo URL |
| position | string | Yes | Job title/position |
| statusMessage | string | Yes | Custom status message |
| onlineStatus | string | No | Online status: 'available', 'busy', 'away', 'offline' |
| lastSeenAt | string | Yes | Last seen timestamp (ISO 8601) |
| isActive | boolean | No | Account active status |
| department | object | Yes | Department object |
| createdAt | string | No | Account creation timestamp |

---

## Development Tips

### Testing with curl

```bash
# Get access token first
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

# Create group chat
curl -X POST http://localhost:8000/api/v1/chats/group \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Engineering Team",
    "description": "Team discussions",
    "memberIds": [2, 3, 4]
  }'

# Get user's chats
curl -X GET http://localhost:8000/api/v1/chats \
  -H "Authorization: Bearer $TOKEN"

# Get chat details
curl -X GET http://localhost:8000/api/v1/chats/1 \
  -H "Authorization: Bearer $TOKEN"

# Create direct chat
curl -X POST http://localhost:8000/api/v1/chats/direct \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"userId": 2}'

# Update chat
curl -X PATCH http://localhost:8000/api/v1/chats/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Engineering Team - Updated",
    "description": "New description"
  }'

# Add members
curl -X POST http://localhost:8000/api/v1/chats/1/members \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"userIds": [5, 6]}'

# Remove member
curl -X DELETE http://localhost:8000/api/v1/chats/1/members/5 \
  -H "Authorization: Bearer $TOKEN"

# Leave chat
curl -X POST http://localhost:8000/api/v1/chats/1/leave \
  -H "Authorization: Bearer $TOKEN"

# Update member role
curl -X PATCH http://localhost:8000/api/v1/chats/1/members/role \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": 3,
    "role": "admin"
  }'

# Delete chat
curl -X DELETE http://localhost:8000/api/v1/chats/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Postman Collection

Import the Postman collection from `/docs/postman/BuzzChat.postman_collection.json` for easy testing.

Variables are automatically set after successful authentication.

---

## Rate Limiting

**Recommendation:** Implement rate limiting at infrastructure level (nginx, API gateway)

Suggested limits:
- Chat creation: 10 chats per user per hour
- Member operations: 30 requests per chat per minute
- Chat updates: 20 requests per chat per hour
- List chats: 100 requests per user per minute

---

## Pagination (Future Enhancement)

Currently, all endpoints return full results. For large chat lists, consider implementing pagination:

```
GET /api/v1/chats?page=1&limit=20&type=group
```

Response with pagination metadata:
```json
{
  "data": [...],
  "meta": {
    "total": 150,
    "page": 1,
    "limit": 20,
    "pages": 8
  }
}
```

---

## Filtering and Sorting (Future Enhancement)

### Filter by type
```
GET /api/v1/chats?type=group
GET /api/v1/chats?type=direct
```

### Sort by activity
```
GET /api/v1/chats?sort=activity  (default)
GET /api/v1/chats?sort=name
GET /api/v1/chats?sort=created
```

### Search by name
```
GET /api/v1/chats?search=engineering
```

---

## WebSocket Events (Future Integration)

When integrated with GoGate WebSocket gateway:

### Client subscribes to chat events
```json
{
  "type": "subscribe",
  "channel": "chat.1"
}
```

### Server sends chat updates
```json
{
  "type": "chat.updated",
  "chatId": 1,
  "data": {
    "name": "New Chat Name",
    "updatedBy": 1,
    "timestamp": "2025-10-25T15:00:00Z"
  }
}
```

### Member added event
```json
{
  "type": "chat.member.added",
  "chatId": 1,
  "data": {
    "userId": 5,
    "addedBy": 1,
    "role": "member",
    "timestamp": "2025-10-25T15:00:00Z"
  }
}
```

### Member removed event
```json
{
  "type": "chat.member.removed",
  "chatId": 1,
  "data": {
    "userId": 5,
    "removedBy": 1,
    "timestamp": "2025-10-25T15:00:00Z"
  }
}
```

---

## Security Considerations

1. **Always use HTTPS in production**
2. **Validate JWT tokens** on every request
3. **Implement rate limiting** to prevent abuse
4. **Sanitize all inputs** to prevent XSS
5. **Log security events** (failed access attempts)
6. **Validate file uploads** for photo URLs
7. **Implement CORS** properly for web clients
8. **Monitor suspicious activity** (rapid chat creation, mass member additions)
9. **Validate member limits** to prevent resource exhaustion
10. **Use prepared statements** (Doctrine ORM handles this)

---

## Best Practices

### Client Implementation

1. **Cache chat list** locally
2. **Update cache** on WebSocket events
3. **Refresh token** proactively before expiration
4. **Handle errors gracefully** with user-friendly messages
5. **Implement optimistic updates** for better UX
6. **Show loading states** during operations
7. **Validate inputs** client-side before sending
8. **Store minimal data** about chats (ID, name, last message)

### Error Handling

```javascript
// Example error handling in client
try {
  const response = await fetch('/api/v1/chats/group', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: 'Team Chat',
      memberIds: [2, 3, 4]
    })
  });

  if (!response.ok) {
    const error = await response.json();

    switch (response.status) {
      case 400:
        // Show validation errors
        showValidationErrors(error.details);
        break;
      case 403:
        // Show permission error
        showError('You do not have permission to perform this action');
        break;
      case 404:
        // Show not found error
        showError('One or more users not found');
        break;
      default:
        showError('An error occurred. Please try again.');
    }
    return;
  }

  const chat = await response.json();
  // Handle success
  navigateToChat(chat.id);
} catch (error) {
  // Network error
  showError('Network error. Please check your connection.');
}
```

---

## Support

For API support, contact the development team or refer to the [module README](./README.md).
