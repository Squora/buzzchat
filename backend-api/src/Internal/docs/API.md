# Internal API Documentation

**⚠️ INTERNAL USE ONLY - Not for public clients**

Base URL: `/api/v1/internal`

## Security

These endpoints are for server-to-server communication only:
- Protect with firewall rules
- IP whitelist (WebSocket gateway only)
- Internal network/VPN
- DO NOT expose to internet

## Endpoints

- [Validate Token](#validate-token)
- [Get User](#get-user)
- [Get Chat Members](#get-chat-members)

---

## Validate Token

Validate JWT token for WebSocket connection.

**Endpoint:** `POST /api/v1/internal/validate-token`

**Authentication:** Internal only

### Request Body

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Response

**Success (200 OK)**

```json
{
  "valid": true,
  "userId": 1,
  "email": "user@example.com",
  "expiresAt": "2025-10-25T16:00:00+00:00"
}
```

**Error (401 Unauthorized)**

```json
{
  "valid": false,
  "error": "Invalid or expired token"
}
```

---

## Get User

Get user data by ID.

**Endpoint:** `GET /api/v1/internal/users/{id}`

**Authentication:** Internal only

### Response

**Success (200 OK)**

```json
{
  "id": 1,
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "isActive": true,
  "onlineStatus": "available"
}
```

---

## Get Chat Members

Get list of chat members (for message routing).

**Endpoint:** `GET /api/v1/internal/chats/{chatId}/members`

**Authentication:** Internal only

### Response

**Success (200 OK)**

```json
{
  "chatId": 1,
  "members": [
    {"userId": 1, "role": "owner"},
    {"userId": 2, "role": "admin"},
    {"userId": 3, "role": "member"}
  ]
}
```

---

## Implementation Example (GoGate)

```go
// Validate token when client connects
func validateToken(token string) (*User, error) {
    resp, err := http.Post(
        "http://backend-api:8000/api/v1/internal/validate-token",
        "application/json",
        bytes.NewBuffer([]byte(`{"token":"`+token+`"}`)),
    )
    // Handle response...
}
```
