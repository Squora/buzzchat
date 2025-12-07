# GoGate - WebSocket Gateway for BuzzChat

GoGate is a high-performance WebSocket gateway service written in Go that provides real-time messaging transport for the BuzzChat corporate chat application.

## Architecture

GoGate acts as a WebSocket bridge between clients and the Backend API:

```
Client (WebSocket) <-> GoGate <-> Backend API (REST)
```

- **Clients** connect via WebSocket and authenticate using JWT tokens
- **GoGate** validates tokens, manages connections, and routes messages
- **Backend API** handles business logic, persistence, and authorization

## Features

- ✅ WebSocket connection management with automatic reconnection support
- ✅ JWT-based authentication via Backend API
- ✅ Real-time message delivery
- ✅ Typing indicators
- ✅ Message reactions
- ✅ Read receipts
- ✅ Multi-device support (user can have multiple connections)
- ✅ Efficient broadcasting to chat members
- ✅ Ping/Pong heartbeat for connection health

## Installation

### Prerequisites

- Go 1.24.5 or higher
- Access to Backend API (Symfony)

### Setup

1. Clone the repository and navigate to the gogate directory:
```bash
cd gogate
```

2. Install dependencies:
```bash
go mod download
```

3. Create `.env` file from example:
```bash
cp .env.example .env
```

4. Configure environment variables in `.env`:
```env
PORT=8080
BACKEND_API_URL=http://localhost:8000
INTERNAL_API_KEY=your_internal_api_key_here
```

## Running

### Development

```bash
go run cmd/gogate/main.go
```

### Production

Build the binary:
```bash
go build -o bin/gogate cmd/gogate/main.go
```

Run the binary:
```bash
./bin/gogate
```

### Docker (optional)

```bash
docker build -t buzzchat-gogate .
docker run -p 8080:8080 --env-file .env buzzchat-gogate
```

## WebSocket Protocol

### Connection

Connect to WebSocket endpoint:
```
ws://localhost:8080/ws
```

### Authentication

After connecting, send authentication message:

```json
{
  "event": "auth",
  "data": {
    "token": "your_jwt_access_token"
  }
}
```

**Success Response:**
```json
{
  "event": "auth_success",
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "phone": "+79991234567"
  }
}
```

**Error Response:**
```json
{
  "event": "error",
  "data": {
    "message": "Authentication failed: Invalid token"
  }
}
```

### Events

All events follow this structure:
```json
{
  "event": "event_name",
  "data": { /* event-specific data */ }
}
```

#### Client → Server Events

**Send Message:**
```json
{
  "event": "send_message",
  "data": {
    "chat_id": 1,
    "text": "Hello, world!",
    "reply_to_id": 123,
    "attachment_ids": [456, 789]
  }
}
```

**Typing Indicator:**
```json
{
  "event": "typing",
  "data": {
    "chat_id": 1,
    "is_typing": true
  }
}
```

**Add Reaction:**
```json
{
  "event": "add_reaction",
  "data": {
    "message_id": 123,
    "emoji": "👍"
  }
}
```

**Mark as Read:**
```json
{
  "event": "mark_read",
  "data": {
    "message_ids": [123, 124, 125]
  }
}
```

#### Server → Client Events

**New Message (broadcasted to chat members):**
```json
{
  "event": "new_message",
  "data": {
    "id": 124,
    "chat_id": 1,
    "user": {
      "id": 2,
      "name": "Jane Smith",
      "phone": "+79997654321"
    },
    "type": "text",
    "text": "Hello back!",
    "reply_to": null,
    "attachments": [],
    "reactions": [],
    "read_count": 0,
    "mentions": null,
    "created_at": "2025-10-25T12:34:56Z",
    "edited_at": null,
    "is_edited": false
  }
}
```

**User Typing (broadcasted to chat members except sender):**
```json
{
  "event": "user_typing",
  "data": {
    "chat_id": 1,
    "user_id": 2,
    "name": "Jane Smith",
    "is_typing": true
  }
}
```

**New Reaction:**
```json
{
  "event": "new_reaction",
  "data": {
    "message_id": 123,
    "user_id": 2,
    "name": "Jane Smith",
    "emoji": "❤️"
  }
}
```

**Message Read:**
```json
{
  "event": "message_read",
  "data": {
    "message_ids": [123, 124],
    "user_id": 2,
    "name": "Jane Smith"
  }
}
```

**Error:**
```json
{
  "event": "error",
  "data": {
    "message": "Invalid chat_id",
    "code": "INVALID_CHAT"
  }
}
```

## API Endpoints

### Health Check

```
GET /health
```

**Response:**
```json
{
  "status": "ok",
  "service": "gogate"
}
```

### Root

```
GET /
```

Returns service information in plain text.

## Configuration

Environment variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `PORT` | Server port | `8080` |
| `BACKEND_API_URL` | Backend API base URL | `http://localhost:8000` |
| `INTERNAL_API_KEY` | Internal API key for backend communication | **Required** |
| `MAX_MESSAGE_SIZE` | Maximum WebSocket message size (bytes) | `512000` |
| `READ_BUFFER_SIZE` | WebSocket read buffer size (bytes) | `1024` |
| `WRITE_BUFFER_SIZE` | WebSocket write buffer size (bytes) | `1024` |
| `PING_PERIOD` | Ping interval (seconds) | `54` |
| `PONG_WAIT` | Pong timeout (seconds) | `60` |
| `WRITE_WAIT` | Write timeout (seconds) | `10` |

## Project Structure

```
gogate/
├── cmd/
│   └── gogate/
│       └── main.go          # Application entry point
├── internal/
│   ├── api/
│   │   └── client.go        # Backend API HTTP client
│   ├── config/
│   │   └── config.go        # Configuration management
│   ├── models/
│   │   └── events.go        # WebSocket event types
│   └── ws/
│       ├── connection.go    # WebSocket connection wrapper
│       ├── handler.go       # HTTP WebSocket upgrade handler
│       └── hub.go           # Connection manager & message router
├── .env.example             # Environment variables example
├── go.mod                   # Go module definition
├── go.sum                   # Go module checksums
└── README.md                # This file
```

## Development

### Testing WebSocket Connection

You can test the WebSocket connection using `wscat`:

```bash
# Install wscat
npm install -g wscat

# Connect
wscat -c ws://localhost:8080/ws

# Send auth
{"event":"auth","data":{"token":"YOUR_JWT_TOKEN"}}

# Send message
{"event":"send_message","data":{"chat_id":1,"text":"Test message"}}
```

### Logging

GoGate logs important events:
- User connections/disconnections
- Authentication attempts
- Message routing
- Errors

Check logs for debugging:
```bash
go run cmd/gogate/main.go 2>&1 | tee gogate.log
```

## Integration with Backend API

GoGate communicates with Backend API via internal endpoints:

1. **POST /api/internal/v1/auth/validate** - Validate JWT token
   - Header: `X-Internal-API-Key: <key>`
   - Body: `{"token": "jwt_token"}`

2. **GET /api/internal/v1/chats/{chatId}/members** - Get chat members
   - Header: `X-Internal-API-Key: <key>`

3. **POST /api/v1/messages** - Send message
   - Header: `Authorization: Bearer <user_jwt>`

4. **POST /api/v1/messages/{id}/reactions** - Add reaction
   - Header: `Authorization: Bearer <user_jwt>`

5. **POST /api/v1/messages/read** - Mark messages as read
   - Header: `Authorization: Bearer <user_jwt>`

## Performance Considerations

- **Multiple connections per user**: Users can connect from multiple devices
- **Connection pooling**: Reuses HTTP connections to Backend API
- **Efficient broadcasting**: Only sends to online chat members
- **Buffer management**: 256-message buffer per connection
- **Ping/Pong heartbeat**: Detects and closes dead connections

## Security

- JWT token validation via Backend API
- Internal API key for backend communication
- CORS origin validation (configure in production)
- Connection limits (configure in production)

## TODO / Future Improvements

- [ ] Add CORS origin whitelist configuration
- [ ] Add rate limiting per user/connection
- [ ] Add Prometheus metrics
- [ ] Add structured logging (e.g., zerolog)
- [ ] Add graceful shutdown
- [ ] Add Redis pub/sub for horizontal scaling
- [ ] Broadcast reactions and read receipts to chat members (requires chat_id from backend)
- [ ] Add connection limit per user
- [ ] Add TLS/WSS support

## License

BuzzChat Internal Project

## Support

For issues and questions, contact the BuzzChat development team.
