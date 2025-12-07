# Message API Documentation

Base URL: `/api/v1/messages`

## Endpoints

- [Send Message](#send-message)
- [Get Messages](#get-messages)
- [Update Message](#update-message)
- [Delete Message](#delete-message)
- [Add Reaction](#add-reaction)
- [Mark as Read](#mark-as-read)

---

## Send Message

Send a new message to a chat.

**Endpoint:** `POST /api/v1/messages`

**Authentication:** Required

### Request Body

```json
{
  "chatId": 1,
  "text": "Hello @john! Check out this link",
  "replyToId": 42,
  "mentionedUserIds": [2]
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| chatId | integer | Yes | Chat ID to send message to |
| text | string | Yes | Message text content |
| replyToId | integer | No | Message ID to reply to |
| mentionedUserIds | array | No | Array of user IDs mentioned |

### Response

**Success (201 Created)**

```json
{
  "id": 100,
  "chatId": 1,
  "user": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "photoUrl": "https://example.com/avatar.jpg"
  },
  "text": "Hello @john! Check out this link",
  "type": "text",
  "replyTo": {
    "id": 42,
    "text": "Previous message",
    "user": {
      "id": 2,
      "firstName": "Jane"
    }
  },
  "mentions": [2],
  "reactions": [],
  "isEdited": false,
  "isDeleted": false,
  "createdAt": "2025-10-25T15:00:00+00:00",
  "updatedAt": "2025-10-25T15:00:00+00:00",
  "editedAt": null,
  "deletedAt": null
}
```

**Error (403 Forbidden)**

```json
{
  "error": "You are not a member of this chat"
}
```

---

## Get Messages

Get message history for a chat with pagination.

**Endpoint:** `GET /api/v1/messages`

**Authentication:** Required

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| chatId | integer | Yes | Chat ID |
| beforeId | integer | No | Get messages before this ID (pagination) |
| limit | integer | No | Number of messages (default: 50, max: 100) |

### Examples

```
GET /api/v1/messages?chatId=1
GET /api/v1/messages?chatId=1&limit=20
GET /api/v1/messages?chatId=1&beforeId=100&limit=50
```

### Response

**Success (200 OK)**

```json
{
  "messages": [
    {
      "id": 99,
      "chatId": 1,
      "user": {
        "id": 2,
        "firstName": "Jane",
        "lastName": "Smith",
        "photoUrl": "https://example.com/jane.jpg"
      },
      "text": "Thanks!",
      "type": "text",
      "replyTo": null,
      "mentions": [],
      "reactions": [
        {
          "emoji": "👍",
          "count": 2,
          "users": [
            {"id": 1, "firstName": "John"},
            {"id": 3, "firstName": "Bob"}
          ]
        }
      ],
      "isEdited": false,
      "isDeleted": false,
      "createdAt": "2025-10-25T14:59:00+00:00"
    },
    {
      "id": 98,
      "chatId": 1,
      "user": {
        "id": 1,
        "firstName": "John",
        "lastName": "Doe"
      },
      "text": "[deleted]",
      "type": "text",
      "isDeleted": true,
      "deletedAt": "2025-10-25T14:58:30+00:00"
    }
  ],
  "total": 2,
  "hasMore": true
}
```

---

## Update Message

Edit message text (author only).

**Endpoint:** `PATCH /api/v1/messages/{id}`

**Authentication:** Required

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Message ID |

### Request Body

```json
{
  "text": "Updated message text with @jane mention"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| text | string | Yes | New message text |

### Response

**Success (200 OK)**

```json
{
  "id": 100,
  "text": "Updated message text with @jane mention",
  "mentions": [3],
  "isEdited": true,
  "editedAt": "2025-10-25T15:05:00+00:00",
  "updatedAt": "2025-10-25T15:05:00+00:00"
}
```

**Error (403 Forbidden)**

```json
{
  "error": "You can only edit your own messages"
}
```

**Error (422 Unprocessable Entity)**

```json
{
  "error": "Cannot edit deleted message"
}
```

---

## Delete Message

Delete message (soft delete - author only).

**Endpoint:** `DELETE /api/v1/messages/{id}`

**Authentication:** Required

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Message ID |

### Response

**Success (200 OK)**

```json
{
  "id": 100,
  "isDeleted": true,
  "deletedAt": "2025-10-25T15:10:00+00:00",
  "message": "Message deleted successfully"
}
```

**Error (403 Forbidden)**

```json
{
  "error": "You can only delete your own messages"
}
```

---

## Add Reaction

Add emoji reaction to a message.

**Endpoint:** `POST /api/v1/messages/{id}/reactions`

**Authentication:** Required

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Message ID |

### Request Body

```json
{
  "emoji": "👍"
}
```

#### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| emoji | string | Yes | Emoji character |

### Response

**Success (200 OK)**

```json
{
  "success": true,
  "message": "Reaction added",
  "reaction": {
    "emoji": "👍",
    "userId": 1,
    "createdAt": "2025-10-25T15:15:00+00:00"
  }
}
```

**Notes:**
- If reaction already exists, returns success without creating duplicate
- One user can add multiple different emoji reactions
- Same emoji from same user = no duplicate

---

## Mark as Read

Mark message(s) as read.

**Endpoint:** `POST /api/v1/messages/{id}/read`

**Authentication:** Required

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Message ID |

### Response

**Success (200 OK)**

```json
{
  "success": true,
  "message": "Message marked as read",
  "readAt": "2025-10-25T15:20:00+00:00"
}
```

---

## Error Responses

```json
{
  "error": "Error message"
}
```

### Common Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Unprocessable Entity |

---

## Development Tips

```bash
# Send message
curl -X POST http://localhost:8000/api/v1/messages \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"chatId":1,"text":"Hello world!"}'

# Get messages
curl -X GET "http://localhost:8000/api/v1/messages?chatId=1&limit=20" \
  -H "Authorization: Bearer $TOKEN"

# Update message
curl -X PATCH http://localhost:8000/api/v1/messages/100 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"text":"Updated text"}'

# Delete message
curl -X DELETE http://localhost:8000/api/v1/messages/100 \
  -H "Authorization: Bearer $TOKEN"

# Add reaction
curl -X POST http://localhost:8000/api/v1/messages/100/reactions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"emoji":"👍"}'

# Mark as read
curl -X POST http://localhost:8000/api/v1/messages/100/read \
  -H "Authorization: Bearer $TOKEN"
```

---

## Best Practices

1. **Pagination**: Always use pagination for message history
2. **Mentions**: Extract @mentions on client side before sending
3. **Reactions**: Limit to common emoji set for UI consistency
4. **Editing**: Show "edited" indicator in UI
5. **Deletion**: Show "[deleted]" placeholder instead of removing from UI
6. **Read Receipts**: Batch mark as read for performance
7. **Real-time**: Use WebSocket for instant message delivery

## Support

For support, refer to the [module README](./README.md).
