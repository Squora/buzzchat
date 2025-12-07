package ws

import (
	"encoding/json"
	"log"
	"sync"
	"time"

	"buzzchat-gogate/internal/models"

	"github.com/gorilla/websocket"
)

const (
	// Time allowed to write a message to the peer
	writeWait = 10 * time.Second

	// Time allowed to read the next pong message from the peer
	pongWait = 60 * time.Second

	// Send pings to peer with this period (must be less than pongWait)
	pingPeriod = 54 * time.Second

	// Maximum message size allowed from peer
	maxMessageSize = 512000 // 500KB
)

// Connection represents a WebSocket connection
type Connection struct {
	// The websocket connection
	ws *websocket.Conn

	// Buffered channel of outbound messages
	send chan []byte

	// Hub reference
	hub *Hub

	// Authenticated user (nil until auth succeeds)
	user *models.User

	// JWT token for backend API calls
	token string

	// Mutex for thread-safe user assignment
	mu sync.RWMutex

	// Connection closed flag
	closed bool
}

// NewConnection creates a new connection
func NewConnection(ws *websocket.Conn, hub *Hub) *Connection {
	return &Connection{
		ws:   ws,
		send: make(chan []byte, 256),
		hub:  hub,
	}
}

// SetUser sets the authenticated user
func (c *Connection) SetUser(user *models.User, token string) {
	c.mu.Lock()
	defer c.mu.Unlock()
	c.user = user
	c.token = token
}

// GetUser returns the authenticated user
func (c *Connection) GetUser() *models.User {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.user
}

// GetToken returns the JWT token
func (c *Connection) GetToken() string {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.token
}

// IsAuthenticated checks if user is authenticated
func (c *Connection) IsAuthenticated() bool {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.user != nil
}

// SendMessage sends a message to the WebSocket client
func (c *Connection) SendMessage(event string, data interface{}) error {
	msg := models.WebSocketMessage{
		Event: event,
	}

	if data != nil {
		dataBytes, err := json.Marshal(data)
		if err != nil {
			return err
		}
		msg.Data = dataBytes
	}

	msgBytes, err := json.Marshal(msg)
	if err != nil {
		return err
	}

	select {
	case c.send <- msgBytes:
		return nil
	default:
		return nil // Drop message if buffer is full
	}
}

// SendError sends an error message to the client
func (c *Connection) SendError(message string) {
	c.SendMessage(models.EventError, models.ErrorData{
		Message: message,
	})
}

// readPump pumps messages from the WebSocket connection to the hub
func (c *Connection) readPump() {
	defer func() {
		c.hub.unregister <- c
		c.ws.Close()
	}()

	c.ws.SetReadDeadline(time.Now().Add(pongWait))
	c.ws.SetPongHandler(func(string) error {
		c.ws.SetReadDeadline(time.Now().Add(pongWait))
		return nil
	})
	c.ws.SetReadLimit(maxMessageSize)

	for {
		_, message, err := c.ws.ReadMessage()
		if err != nil {
			if websocket.IsUnexpectedCloseError(err, websocket.CloseGoingAway, websocket.CloseAbnormalClosure) {
				log.Printf("WebSocket error: %v", err)
			}
			break
		}

		// Parse message
		var msg models.WebSocketMessage
		if err := json.Unmarshal(message, &msg); err != nil {
			log.Printf("Invalid message format: %v", err)
			c.SendError("Invalid message format")
			continue
		}

		// Handle message
		c.hub.handleMessage(c, &msg)
	}
}

// writePump pumps messages from the hub to the WebSocket connection
func (c *Connection) writePump() {
	ticker := time.NewTicker(pingPeriod)
	defer func() {
		ticker.Stop()
		c.ws.Close()
	}()

	for {
		select {
		case message, ok := <-c.send:
			c.ws.SetWriteDeadline(time.Now().Add(writeWait))
			if !ok {
				// The hub closed the channel
				c.ws.WriteMessage(websocket.CloseMessage, []byte{})
				return
			}

			w, err := c.ws.NextWriter(websocket.TextMessage)
			if err != nil {
				return
			}
			w.Write(message)

			// Add queued messages to the current WebSocket message
			n := len(c.send)
			for i := 0; i < n; i++ {
				w.Write([]byte{'\n'})
				w.Write(<-c.send)
			}

			if err := w.Close(); err != nil {
				return
			}

		case <-ticker.C:
			c.ws.SetWriteDeadline(time.Now().Add(writeWait))
			if err := c.ws.WriteMessage(websocket.PingMessage, nil); err != nil {
				return
			}
		}
	}
}

// Start starts the connection's read and write pumps
func (c *Connection) Start() {
	go c.writePump()
	go c.readPump()
}

// Close closes the connection
func (c *Connection) Close() {
	c.mu.Lock()
	defer c.mu.Unlock()

	if !c.closed {
		c.closed = true
		close(c.send)
	}
}
