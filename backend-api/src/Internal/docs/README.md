# Internal Module

**Internal APIs for WebSocket Gateway Integration**

## Overview

The Internal module provides internal-only APIs for the GoGate WebSocket gateway service. These endpoints are not exposed to public clients and are used for server-to-server communication.

## Responsibility

- JWT token validation for WebSocket connections
- User data retrieval for WebSocket context
- Chat membership verification
- Real-time connection authorization

## Security

**IMPORTANT:** These endpoints should be protected at infrastructure level:
- Only accessible from WebSocket gateway server
- Use internal network or VPN
- Add IP whitelist in nginx/load balancer
- Do NOT expose to public internet

## Components

### Handlers

- **ValidateTokenHandler** - Validate JWT token
- **GetUserHandler** - Get user data by ID
- **GetChatMembersHandler** - Get chat members

### Use Cases

1. **WebSocket Connection**: GoGate validates JWT before accepting connection
2. **Message Routing**: GoGate gets chat members to route messages
3. **User Context**: GoGate gets user data for connection context

## API Reference

See [API Documentation](./API.md)

## Integration with GoGate

```
┌─────────┐         ┌──────────┐         ┌─────────────┐
│ Client  │────────▶│  GoGate  │────────▶│ Backend API │
│         │ WebSocket│          │  HTTP   │  (Internal) │
└─────────┘         └──────────┘         └─────────────┘
    │                     │                      │
    │ 1. Connect +JWT     │                      │
    │────────────────────▶│                      │
    │                     │ 2. Validate Token    │
    │                     │─────────────────────▶│
    │                     │                      │
    │                     │◀─────────────────────│
    │                     │ 3. User Data         │
    │ 4. Connected        │                      │
    │◀────────────────────│                      │
```

## License

Proprietary
