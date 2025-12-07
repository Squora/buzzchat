package models

import "encoding/json"

// Event types
const (
	// Client -> Server
	EventAuth         = "auth"
	EventSendMessage  = "send_message"
	EventTyping       = "typing"
	EventAddReaction  = "add_reaction"
	EventMarkRead     = "mark_read"

	// Server -> Client
	EventAuthSuccess  = "auth_success"
	EventNewMessage   = "new_message"
	EventUserTyping   = "user_typing"
	EventNewReaction  = "new_reaction"
	EventMessageRead  = "message_read"
	EventError        = "error"
)

// WebSocketMessage is the base message structure
type WebSocketMessage struct {
	Event string          `json:"event"`
	Data  json.RawMessage `json:"data,omitempty"`
}

// Auth event data
type AuthData struct {
	Token string `json:"token"`
}

type AuthSuccessData struct {
	UserID int    `json:"user_id"`
	Name   string `json:"name"`
	Phone  string `json:"phone"`
}

// Send message event data
type SendMessageData struct {
	ChatID        int   `json:"chat_id"`
	Text          string `json:"text,omitempty"`
	ReplyToID     *int  `json:"reply_to_id,omitempty"`
	AttachmentIDs []int `json:"attachment_ids,omitempty"`
}

// Typing event data
type TypingData struct {
	ChatID    int  `json:"chat_id"`
	IsTyping  bool `json:"is_typing"`
}

type UserTypingData struct {
	ChatID   int    `json:"chat_id"`
	UserID   int    `json:"user_id"`
	Name     string `json:"name"`
	IsTyping bool   `json:"is_typing"`
}

// Reaction event data
type AddReactionData struct {
	MessageID int    `json:"message_id"`
	Emoji     string `json:"emoji"`
}

type NewReactionData struct {
	MessageID int    `json:"message_id"`
	UserID    int    `json:"user_id"`
	Name      string `json:"name"`
	Emoji     string `json:"emoji"`
}

// Mark read event data
type MarkReadData struct {
	MessageIDs []int `json:"message_ids"`
}

type MessageReadData struct {
	MessageIDs []int `json:"message_ids"`
	UserID     int   `json:"user_id"`
	Name       string `json:"name"`
}

// Error event data
type ErrorData struct {
	Message string `json:"message"`
	Code    string `json:"code,omitempty"`
}

// User represents authenticated user
type User struct {
	ID       int    `json:"id"`
	Name     string `json:"name"`
	Phone    string `json:"phone"`
	Active   bool   `json:"active"`
}

// ChatMember represents a member of a chat
type ChatMember struct {
	UserID int    `json:"user_id"`
	Name   string `json:"name"`
}
