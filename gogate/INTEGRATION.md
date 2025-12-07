# GoGate Integration Guide

This guide explains how to integrate the GoGate WebSocket gateway with the BuzzChat Backend API and Frontend.

## Architecture Overview

```
┌─────────────┐         WebSocket          ┌─────────────┐
│             │◄──────────────────────────►│             │
│  Frontend   │                             │   GoGate    │
│   (React)   │                             │   (Go WS)   │
│             │                             │             │
└─────────────┘                             └──────┬──────┘
                                                   │
                                                   │ REST
       ┌────────────────────────────────────────────┼───────────┐
       │                                            │           │
       │ HTTP (JWT)                                 │ Internal  │
       │                                            │ API       │
       ▼                                            ▼           │
┌─────────────────────────────────────────────────────────────┐│
│                     Backend API (Symfony)                   ││
│  - Authentication (JWT)                                     ││
│  - Business Logic                                           ││
│  - Message Storage (PostgreSQL)                             ││
│  - User/Chat Management                                     ││
└─────────────────────────────────────────────────────────────┘│
                                                                │
                      PostgreSQL Database ◄────────────────────┘
```

## Setup Steps

### 1. Backend API Configuration

Ensure these environment variables are set in `backend-api/.env`:

```env
# Internal API key (must match GoGate)
INTERNAL_API_KEY=your_secure_random_key_here

# CORS configuration (allow GoGate origin if needed)
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

The Backend API should already have these internal endpoints:
- `POST /api/internal/v1/auth/validate` - Validate JWT tokens
- `GET /api/internal/v1/chats/{chatId}/members` - Get chat members for broadcasting

### 2. GoGate Configuration

Create `.env` file in `gogate/` directory:

```env
# GoGate server port
PORT=8080

# Backend API URL
BACKEND_API_URL=http://localhost:8000

# Internal API key (must match Backend API)
INTERNAL_API_KEY=your_secure_random_key_here
```

Start GoGate:
```bash
cd gogate
go run cmd/gogate/main.go
```

Or build and run:
```bash
go build -o bin/gogate cmd/gogate/main.go
./bin/gogate
```

### 3. Frontend Integration

#### Install WebSocket Library (optional)

For raw WebSocket:
```bash
# No library needed, use native WebSocket API
```

For better abstractions, consider:
```bash
npm install socket.io-client
# or
npm install ws
```

#### WebSocket Client Example

**Basic Connection:**

```typescript
// src/services/websocket.ts
class WebSocketService {
  private ws: WebSocket | null = null;
  private token: string = '';
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 3000;
  private messageHandlers = new Map<string, (data: any) => void>();

  connect(token: string) {
    this.token = token;
    this.ws = new WebSocket('ws://localhost:8080/ws');

    this.ws.onopen = () => {
      console.log('WebSocket connected');
      this.reconnectAttempts = 0;

      // Send authentication
      this.send('auth', { token: this.token });
    };

    this.ws.onmessage = (event) => {
      try {
        const message = JSON.parse(event.data);
        this.handleMessage(message);
      } catch (error) {
        console.error('Failed to parse message:', error);
      }
    };

    this.ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    this.ws.onclose = () => {
      console.log('WebSocket disconnected');
      this.handleReconnect();
    };
  }

  private handleMessage(message: { event: string; data: any }) {
    const handler = this.messageHandlers.get(message.event);
    if (handler) {
      handler(message.data);
    }
  }

  on(event: string, handler: (data: any) => void) {
    this.messageHandlers.set(event, handler);
  }

  send(event: string, data: any) {
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ event, data }));
    }
  }

  sendMessage(chatId: number, text: string, replyToId?: number) {
    this.send('send_message', {
      chat_id: chatId,
      text,
      reply_to_id: replyToId,
    });
  }

  sendTyping(chatId: number, isTyping: boolean) {
    this.send('typing', {
      chat_id: chatId,
      is_typing: isTyping,
    });
  }

  addReaction(messageId: number, emoji: string) {
    this.send('add_reaction', {
      message_id: messageId,
      emoji,
    });
  }

  markAsRead(messageIds: number[]) {
    this.send('mark_read', {
      message_ids: messageIds,
    });
  }

  private handleReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      console.log(`Reconnecting... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

      setTimeout(() => {
        this.connect(this.token);
      }, this.reconnectDelay);
    }
  }

  disconnect() {
    this.ws?.close();
    this.ws = null;
  }
}

export const wsService = new WebSocketService();
```

**React Integration:**

```typescript
// src/hooks/useWebSocket.ts
import { useEffect } from 'react';
import { wsService } from '../services/websocket';

export function useWebSocket(token: string) {
  useEffect(() => {
    if (token) {
      wsService.connect(token);
    }

    return () => {
      wsService.disconnect();
    };
  }, [token]);

  return wsService;
}
```

**Component Usage:**

```typescript
// src/components/ChatArea.tsx
import { useEffect } from 'react';
import { useWebSocket } from '../hooks/useWebSocket';

export function ChatArea({ currentChat, authToken }) {
  const ws = useWebSocket(authToken);

  useEffect(() => {
    // Listen for new messages
    ws.on('new_message', (data) => {
      console.log('New message received:', data);
      // Update UI with new message
    });

    // Listen for typing indicators
    ws.on('user_typing', (data) => {
      console.log('User typing:', data);
      // Show typing indicator
    });

    // Listen for reactions
    ws.on('new_reaction', (data) => {
      console.log('New reaction:', data);
      // Update message reactions
    });

    // Listen for read receipts
    ws.on('message_read', (data) => {
      console.log('Messages read:', data);
      // Update read count
    });

    // Listen for errors
    ws.on('error', (data) => {
      console.error('WebSocket error:', data.message);
    });

    // Listen for auth success
    ws.on('auth_success', (data) => {
      console.log('Authenticated as:', data.name);
    });
  }, [ws]);

  const handleSendMessage = (text: string) => {
    ws.sendMessage(currentChat.id, text);
  };

  const handleTyping = (isTyping: boolean) => {
    ws.sendTyping(currentChat.id, isTyping);
  };

  return (
    <div>
      {/* Your chat UI */}
    </div>
  );
}
```

## Message Flow Examples

### Sending a Message

1. **Frontend** sends via WebSocket:
   ```json
   {"event":"send_message","data":{"chat_id":1,"text":"Hello!"}}
   ```

2. **GoGate** receives, validates user is authenticated

3. **GoGate** forwards to Backend API:
   ```
   POST http://localhost:8000/api/v1/messages
   Authorization: Bearer <user_jwt>
   {"chatId":1,"text":"Hello!"}
   ```

4. **Backend API** validates, saves to database, returns MessageResponse

5. **GoGate** gets chat members:
   ```
   GET http://localhost:8000/api/internal/v1/chats/1/members
   X-Internal-API-Key: <key>
   ```

6. **GoGate** broadcasts to all online chat members:
   ```json
   {"event":"new_message","data":{...MessageResponse}}
   ```

### Typing Indicator

1. **Frontend** sends typing event:
   ```json
   {"event":"typing","data":{"chat_id":1,"is_typing":true}}
   ```

2. **GoGate** broadcasts to chat members (except sender):
   ```json
   {
     "event":"user_typing",
     "data":{
       "chat_id":1,
       "user_id":2,
       "name":"Jane",
       "is_typing":true
     }
   }
   ```

## Testing

### Test Authentication

```bash
# Install wscat
npm install -g wscat

# Connect
wscat -c ws://localhost:8080/ws

# Authenticate (replace with real JWT)
> {"event":"auth","data":{"token":"eyJ0eXAiOiJKV1QiLCJhbGc..."}}

# Expected response
< {"event":"auth_success","data":{"user_id":1,"name":"John Doe","phone":"+79991234567"}}
```

### Test Sending Message

```bash
# After authentication
> {"event":"send_message","data":{"chat_id":1,"text":"Test message"}}

# Expected response (to all chat members)
< {"event":"new_message","data":{...}}
```

## Deployment

### Development

```bash
# Terminal 1: Backend API
cd backend-api
symfony server:start

# Terminal 2: GoGate
cd gogate
go run cmd/gogate/main.go

# Terminal 3: Frontend
cd frontend
npm run dev
```

### Production

**Backend API:**
- Deploy to PHP-FPM + Nginx
- Configure PostgreSQL
- Set production environment variables

**GoGate:**
```bash
# Build binary
go build -o gogate cmd/gogate/main.go

# Run with systemd or supervisor
./gogate
```

**Frontend:**
```bash
# Build
npm run build

# Serve with nginx
```

### Docker Compose Example

```yaml
# docker-compose.yml
version: '3.8'

services:
  postgres:
    image: postgres:16
    environment:
      POSTGRES_DB: buzzchat
      POSTGRES_USER: app
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data

  backend:
    build: ./backend-api
    environment:
      DATABASE_URL: postgresql://app:secret@postgres:5432/buzzchat
      INTERNAL_API_KEY: ${INTERNAL_API_KEY}
      JWT_PASSPHRASE: ${JWT_PASSPHRASE}
    ports:
      - "8000:8000"
    depends_on:
      - postgres

  gogate:
    build: ./gogate
    environment:
      BACKEND_API_URL: http://backend:8000
      INTERNAL_API_KEY: ${INTERNAL_API_KEY}
      PORT: 8080
    ports:
      - "8080:8080"
    depends_on:
      - backend

  frontend:
    build: ./frontend
    ports:
      - "80:80"
    depends_on:
      - backend
      - gogate

volumes:
  postgres_data:
```

## Security Considerations

1. **JWT Validation**: All WebSocket connections must authenticate with valid JWT
2. **Internal API Key**: Keep `INTERNAL_API_KEY` secret and strong
3. **CORS**: Configure CORS properly in production
4. **Rate Limiting**: Add rate limiting to prevent abuse
5. **TLS/WSS**: Use WSS (WebSocket Secure) in production
6. **Origin Validation**: Configure allowed origins in `ws/handler.go`

## Troubleshooting

### Connection Refused

- Check GoGate is running: `curl http://localhost:8080/health`
- Check Backend API is accessible from GoGate
- Verify firewall rules

### Authentication Failed

- Verify JWT token is valid: Test with Backend API directly
- Check `INTERNAL_API_KEY` matches in both services
- Check Backend API logs

### Messages Not Broadcasting

- Verify chat members endpoint returns correct users
- Check GoGate logs for errors
- Ensure users are connected and authenticated

### High Memory Usage

- Limit max connections per user
- Reduce message buffer size in `ws/connection.go`
- Enable message size limits

## Monitoring

Monitor these metrics:
- Active WebSocket connections
- Message throughput
- Authentication failures
- Backend API response times
- Memory/CPU usage

Use health endpoint for monitoring:
```bash
curl http://localhost:8080/health
# {"status":"ok","service":"gogate"}
```

## Next Steps

- [ ] Add Redis pub/sub for horizontal scaling
- [ ] Add Prometheus metrics
- [ ] Add structured logging
- [ ] Add graceful shutdown
- [ ] Add connection limits
- [ ] Configure TLS/WSS
- [ ] Add rate limiting
