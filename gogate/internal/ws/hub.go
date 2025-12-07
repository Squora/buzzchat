package ws

import (
	"encoding/json"
	"log"
	"sync"

	"buzzchat-gogate/internal/api"
	"buzzchat-gogate/internal/models"
)

// Hub maintains the set of active connections and broadcasts messages
type Hub struct {
	// Registered connections (userID -> set of connections)
	connections map[int]map[*Connection]bool

	// Register requests from connections
	register chan *Connection

	// Unregister requests from connections
	unregister chan *Connection

	// Backend API client
	apiClient *api.Client

	// Mutex for thread-safe operations
	mu sync.RWMutex
}

// NewHub creates a new Hub
func NewHub(apiClient *api.Client) *Hub {
	return &Hub{
		connections: make(map[int]map[*Connection]bool),
		register:    make(chan *Connection),
		unregister:  make(chan *Connection),
		apiClient:   apiClient,
	}
}

// Run starts the hub's main loop
func (h *Hub) Run() {
	for {
		select {
		case conn := <-h.register:
			h.registerConnection(conn)

		case conn := <-h.unregister:
			h.unregisterConnection(conn)
		}
	}
}

// registerConnection registers a connection for a user
func (h *Hub) registerConnection(conn *Connection) {
	if !conn.IsAuthenticated() {
		return
	}

	user := conn.GetUser()
	h.mu.Lock()
	defer h.mu.Unlock()

	if h.connections[user.ID] == nil {
		h.connections[user.ID] = make(map[*Connection]bool)
	}
	h.connections[user.ID][conn] = true

	log.Printf("User %d (%s) connected. Total connections: %d", user.ID, user.Name, len(h.connections[user.ID]))
}

// unregisterConnection unregisters a connection
func (h *Hub) unregisterConnection(conn *Connection) {
	user := conn.GetUser()
	if user == nil {
		conn.Close()
		return
	}

	h.mu.Lock()
	defer h.mu.Unlock()

	if connections, ok := h.connections[user.ID]; ok {
		if _, exists := connections[conn]; exists {
			delete(connections, conn)
			conn.Close()

			if len(connections) == 0 {
				delete(h.connections, user.ID)
			}

			log.Printf("User %d (%s) disconnected. Remaining connections: %d", user.ID, user.Name, len(connections))
		}
	}
}

// BroadcastToChatMembers sends a message to all online chat members
func (h *Hub) BroadcastToChatMembers(chatID int, event string, data interface{}, excludeUserID *int) {
	// Get chat members from backend
	members, err := h.apiClient.GetChatMembers(chatID)
	if err != nil {
		log.Printf("Error getting chat members: %v", err)
		return
	}

	// Prepare message
	msg := models.WebSocketMessage{Event: event}
	if data != nil {
		dataBytes, err := json.Marshal(data)
		if err != nil {
			log.Printf("Error marshaling broadcast data: %v", err)
			return
		}
		msg.Data = dataBytes
	}

	msgBytes, err := json.Marshal(msg)
	if err != nil {
		log.Printf("Error marshaling message: %v", err)
		return
	}

	// Send to all online members
	h.mu.RLock()
	defer h.mu.RUnlock()

	for _, member := range members {
		// Skip excluded user if specified
		if excludeUserID != nil && member.UserID == *excludeUserID {
			continue
		}

		// Send to all connections of this user
		if connections, ok := h.connections[member.UserID]; ok {
			for conn := range connections {
				select {
				case conn.send <- msgBytes:
				default:
					// Buffer full, skip
				}
			}
		}
	}
}

// SendToUser sends a message to a specific user (all their connections)
func (h *Hub) SendToUser(userID int, event string, data interface{}) {
	msg := models.WebSocketMessage{Event: event}
	if data != nil {
		dataBytes, err := json.Marshal(data)
		if err != nil {
			log.Printf("Error marshaling user message data: %v", err)
			return
		}
		msg.Data = dataBytes
	}

	msgBytes, err := json.Marshal(msg)
	if err != nil {
		log.Printf("Error marshaling user message: %v", err)
		return
	}

	h.mu.RLock()
	defer h.mu.RUnlock()

	if connections, ok := h.connections[userID]; ok {
		for conn := range connections {
			select {
			case conn.send <- msgBytes:
			default:
				// Buffer full, skip
			}
		}
	}
}

// handleMessage handles incoming WebSocket messages
func (h *Hub) handleMessage(conn *Connection, msg *models.WebSocketMessage) {
	// Handle authentication first
	if msg.Event == models.EventAuth {
		h.handleAuth(conn, msg)
		return
	}

	// All other events require authentication
	if !conn.IsAuthenticated() {
		conn.SendError("Authentication required")
		return
	}

	// Route to appropriate handler
	switch msg.Event {
	case models.EventSendMessage:
		h.handleSendMessage(conn, msg)
	case models.EventTyping:
		h.handleTyping(conn, msg)
	case models.EventAddReaction:
		h.handleAddReaction(conn, msg)
	case models.EventMarkRead:
		h.handleMarkRead(conn, msg)
	default:
		conn.SendError("Unknown event type")
	}
}

// handleAuth handles authentication
func (h *Hub) handleAuth(conn *Connection, msg *models.WebSocketMessage) {
	var authData models.AuthData
	if err := json.Unmarshal(msg.Data, &authData); err != nil {
		conn.SendError("Invalid auth data")
		return
	}

	if authData.Token == "" {
		conn.SendError("Token is required")
		return
	}

	// Validate token with backend
	user, err := h.apiClient.ValidateToken(authData.Token)
	if err != nil {
		log.Printf("Auth failed: %v", err)
		conn.SendError("Authentication failed: " + err.Error())
		return
	}

	// Set user and token
	conn.SetUser(user, authData.Token)

	// Register connection
	h.register <- conn

	// Send success response
	conn.SendMessage(models.EventAuthSuccess, models.AuthSuccessData{
		UserID: user.ID,
		Name:   user.Name,
		Phone:  user.Phone,
	})

	log.Printf("User authenticated: %d (%s)", user.ID, user.Name)
}

// handleSendMessage handles send_message event
func (h *Hub) handleSendMessage(conn *Connection, msg *models.WebSocketMessage) {
	var data models.SendMessageData
	if err := json.Unmarshal(msg.Data, &data); err != nil {
		conn.SendError("Invalid message data")
		return
	}

	if data.ChatID <= 0 {
		conn.SendError("Invalid chat_id")
		return
	}

	// Forward to backend API
	messageResponse, err := h.apiClient.SendMessage(conn.GetToken(), data)
	if err != nil {
		log.Printf("Error sending message to backend: %v", err)
		conn.SendError("Failed to send message: " + err.Error())
		return
	}

	// Broadcast to all chat members
	h.BroadcastToChatMembers(data.ChatID, models.EventNewMessage, json.RawMessage(messageResponse), nil)
}

// handleTyping handles typing indicator
func (h *Hub) handleTyping(conn *Connection, msg *models.WebSocketMessage) {
	var data models.TypingData
	if err := json.Unmarshal(msg.Data, &data); err != nil {
		conn.SendError("Invalid typing data")
		return
	}

	user := conn.GetUser()

	// Broadcast to chat members (excluding sender)
	typingData := models.UserTypingData{
		ChatID:   data.ChatID,
		UserID:   user.ID,
		Name:     user.Name,
		IsTyping: data.IsTyping,
	}

	h.BroadcastToChatMembers(data.ChatID, models.EventUserTyping, typingData, &user.ID)
}

// handleAddReaction handles add_reaction event
func (h *Hub) handleAddReaction(conn *Connection, msg *models.WebSocketMessage) {
	var data models.AddReactionData
	if err := json.Unmarshal(msg.Data, &data); err != nil {
		conn.SendError("Invalid reaction data")
		return
	}

	// Forward to backend API
	if err := h.apiClient.AddReaction(conn.GetToken(), data); err != nil {
		log.Printf("Error adding reaction: %v", err)
		conn.SendError("Failed to add reaction: " + err.Error())
		return
	}

	// Note: We need to get chat_id from backend or pass it in the event
	// For now, we'll send success to the sender
	user := conn.GetUser()
	reactionData := models.NewReactionData{
		MessageID: data.MessageID,
		UserID:    user.ID,
		Name:      user.Name,
		Emoji:     data.Emoji,
	}

	// TODO: Get chat_id from message and broadcast to chat members
	// For now, just confirm to sender
	conn.SendMessage(models.EventNewReaction, reactionData)
}

// handleMarkRead handles mark_read event
func (h *Hub) handleMarkRead(conn *Connection, msg *models.WebSocketMessage) {
	var data models.MarkReadData
	if err := json.Unmarshal(msg.Data, &data); err != nil {
		conn.SendError("Invalid read data")
		return
	}

	// Forward to backend API
	if err := h.apiClient.MarkAsRead(conn.GetToken(), data); err != nil {
		log.Printf("Error marking as read: %v", err)
		conn.SendError("Failed to mark as read: " + err.Error())
		return
	}

	user := conn.GetUser()
	readData := models.MessageReadData{
		MessageIDs: data.MessageIDs,
		UserID:     user.ID,
		Name:       user.Name,
	}

	// TODO: Get chat_id from messages and broadcast to chat members
	// For now, just confirm to sender
	conn.SendMessage(models.EventMessageRead, readData)
}
