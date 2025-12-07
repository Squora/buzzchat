# Auth API Documentation

Base URL: `/api/v1/auth`

## Endpoints

- [Register - Start](#register---start)
- [Register - Verify Code](#register---verify-code)
- [Login - Request Code](#login---request-code)
- [Login - Verify Code](#login---verify-code)
- [Refresh Token](#refresh-token)
- [Get Current User](#get-current-user)
- [Logout](#logout)

---

## Register - Start

Start the registration process by creating a pending user and sending SMS verification code.

**Endpoint:** `POST /api/v1/auth/register`

**Authentication:** None

### Request Body

```json
{
  "phone": "+79991234567",
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| phone | string | Yes | Phone number in E.164 format |
| email | string | Yes | Valid email address |
| firstName | string | Yes | First name (2-50 chars) |
| lastName | string | Yes | Last name (2-50 chars) |

### Response

**Success (200 OK)**

```json
{
  "success": true,
  "message": "Verification code sent",
  "phone": "+79991234567"
}
```

**Error (400 Bad Request) - Validation Error**

```json
{
  "error": "Validation failed",
  "details": "phone: This value is not a valid phone number."
}
```

**Error (409 Conflict) - User Already Exists**

```json
{
  "error": "User with this phone already exists"
}
```

### Notes

- SMS code expires in 10 minutes
- Code format: 4-digit number (e.g., 1234)
- In development, code is logged to `var/log/dev.log`

---

## Register - Verify Code

Complete registration by verifying SMS code and creating user account.

**Endpoint:** `POST /api/v1/auth/verify`

**Authentication:** None

### Request Body

```json
{
  "phone": "+79991234567",
  "code": "1234"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| phone | string | Yes | Phone number used for registration |
| code | string | Yes | 4-digit verification code from SMS |

### Response

**Success (200 OK)**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "a3f7d9e8c2b1...",
  "user": {
    "id": 1,
    "phone": "+79991234567",
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "fullName": "John Doe",
    "photoUrl": null,
    "position": null,
    "statusMessage": null,
    "onlineStatus": "offline",
    "lastSeenAt": null,
    "isActive": true,
    "department": null,
    "createdAt": "2025-10-25T12:00:00+00:00"
  }
}
```

**Error (400 Bad Request) - Invalid Code**

```json
{
  "error": "Invalid verification code"
}
```

**Error (404 Not Found) - No Pending Verification**

```json
{
  "error": "No pending verification found for this phone number"
}
```

**Error (422 Unprocessable Entity) - Code Expired**

```json
{
  "error": "Verification code has expired"
}
```

### Notes

- After successful verification, pending user is deleted
- JWT access token is valid for 1 hour (configurable)
- Refresh token is long-lived and can be used to get new access tokens

---

## Login - Request Code

Request login code via SMS for existing user.

**Endpoint:** `POST /api/v1/auth/login/request-code`

**Authentication:** None

### Request Body

```json
{
  "phone": "+79991234567"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| phone | string | Yes | Phone number in E.164 format |

### Response

**Success (200 OK)**

```json
{
  "success": true,
  "message": "Verification code sent",
  "phone": "+79991234567"
}
```

**Error (401 Unauthorized) - User Not Found**

```json
{
  "error": "Invalid credentials"
}
```

**Error (403 Forbidden) - User Inactive**

```json
{
  "error": "User account is deactivated"
}
```

### Notes

- SMS code expires in 10 minutes
- If user doesn't exist, returns generic "Invalid credentials" for security

---

## Login - Verify Code

Authenticate user with SMS verification code.

**Endpoint:** `POST /api/v1/auth/login`

**Authentication:** None

### Request Body

```json
{
  "phone": "+79991234567",
  "code": "1234"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| phone | string | Yes | Phone number |
| code | string | Yes | 4-digit verification code from SMS |

### Response

**Success (200 OK)**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "a3f7d9e8c2b1...",
  "user": {
    "id": 1,
    "phone": "+79991234567",
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "fullName": "John Doe",
    "photoUrl": "https://example.com/avatar.jpg",
    "position": "Senior Developer",
    "statusMessage": "Working from home",
    "onlineStatus": "available",
    "lastSeenAt": "2025-10-25T11:55:00+00:00",
    "isActive": true,
    "department": {
      "id": 1,
      "name": "Engineering"
    },
    "createdAt": "2025-10-20T10:00:00+00:00"
  }
}
```

**Error (400 Bad Request) - Invalid Code**

```json
{
  "error": "Invalid verification code"
}
```

**Error (401 Unauthorized) - User Not Found**

```json
{
  "error": "Invalid credentials"
}
```

**Error (403 Forbidden) - User Inactive**

```json
{
  "error": "User account is deactivated"
}
```

---

## Refresh Token

Refresh JWT access token using refresh token.

**Endpoint:** `POST /api/v1/auth/refresh`

**Authentication:** None (uses refresh token)

### Request Body

```json
{
  "refresh_token": "a3f7d9e8c2b1..."
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| refresh_token | string | Yes | Valid refresh token |

### Response

**Success (200 OK)**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "b4g8e0f9d3c2..."
}
```

**Error (401 Unauthorized) - Invalid Refresh Token**

```json
{
  "error": "Invalid or expired refresh token"
}
```

### Notes

- Old refresh token is invalidated
- New refresh token is generated and returned
- Access token is refreshed with new expiration time

---

## Get Current User

Get currently authenticated user information.

**Endpoint:** `GET /api/v1/auth/current`

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
{
  "id": 1,
  "phone": "+79991234567",
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "fullName": "John Doe",
  "photoUrl": "https://example.com/avatar.jpg",
  "position": "Senior Developer",
  "statusMessage": "Working from home",
  "onlineStatus": "available",
  "lastSeenAt": "2025-10-25T11:55:00+00:00",
  "isActive": true,
  "department": {
    "id": 1,
    "name": "Engineering",
    "description": "Software development team"
  },
  "createdAt": "2025-10-20T10:00:00+00:00"
}
```

**Error (401 Unauthorized) - Invalid or Expired Token**

```json
{
  "error": "Invalid JWT Token"
}
```

---

## Logout

Logout user by invalidating refresh token.

**Endpoint:** `POST /api/v1/auth/logout`

**Authentication:** Required (Bearer token)

### Request Body

```json
{
  "refresh_token": "a3f7d9e8c2b1..."
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| refresh_token | string | Yes | Refresh token to invalidate |

#### Headers

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Response

**Success (200 OK)**

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Error (401 Unauthorized)**

```json
{
  "error": "Invalid JWT Token"
}
```

### Notes

- Access token will still be valid until it expires (max 1 hour)
- Client should delete access token immediately after logout
- Refresh token is permanently invalidated

---

## Authentication

Most endpoints require JWT authentication. Include the access token in the Authorization header:

```
Authorization: Bearer <access_token>
```

### Token Lifecycle

1. **Obtain tokens**: Login or register to get access + refresh tokens
2. **Use access token**: Include in Authorization header for API requests
3. **Token expires**: Access token expires after 1 hour
4. **Refresh token**: Use refresh token to get new access token
5. **Logout**: Invalidate refresh token when done

### Token Storage (Client-side)

**Recommended approach:**

- **Access Token**: Store in memory (JavaScript variable)
- **Refresh Token**: Store in HttpOnly secure cookie or encrypted localStorage
- Never expose tokens in URL or logs

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
| 400 | Bad Request | Validation error, invalid JSON |
| 401 | Unauthorized | Invalid or expired token, wrong credentials |
| 403 | Forbidden | User inactive |
| 404 | Not Found | Resource not found |
| 409 | Conflict | User already exists |
| 422 | Unprocessable Entity | Business logic error |
| 500 | Internal Server Error | Server error |

---

## Rate Limiting

**Recommendation:** Implement rate limiting at infrastructure level (nginx, API gateway)

Suggested limits:
- SMS sending: 3 requests per phone per 10 minutes
- Login attempts: 5 attempts per phone per 15 minutes
- Token refresh: 10 requests per refresh token per minute

---

## Development Tips

### Testing with curl

```bash
# Register
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"phone":"+79991234567","email":"test@example.com","firstName":"John","lastName":"Doe"}'

# Check logs for SMS code
tail -f var/log/dev.log | grep "DEV SMS"

# Verify
curl -X POST http://localhost:8000/api/v1/auth/verify \
  -H "Content-Type: application/json" \
  -d '{"phone":"+79991234567","code":"1234"}'

# Save token
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

# Get current user
curl -X GET http://localhost:8000/api/v1/auth/current \
  -H "Authorization: Bearer $TOKEN"
```

### Postman Collection

Import the Postman collection from `/docs/postman/BuzzChat.postman_collection.json` for easy testing.

Variables are automatically set after successful login/register.

---

## Security Considerations

1. **Always use HTTPS in production**
2. **Store JWT keys securely** (config/jwt/*.pem)
3. **Never expose private key**
4. **Implement rate limiting**
5. **Monitor failed login attempts**
6. **Use secure, random JWT passphrase**
7. **Regularly rotate JWT keys** (recommended: quarterly)
8. **Validate phone numbers** server-side
9. **Sanitize all inputs**
10. **Log security events**

---

## Support

For API support, contact the development team or refer to the [module README](./README.md).
