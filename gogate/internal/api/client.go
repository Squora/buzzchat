package api

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"

	"buzzchat-gogate/internal/models"
)

type Client struct {
	baseURL    string
	apiKey     string
	httpClient *http.Client
}

func NewClient(baseURL, apiKey string) *Client {
	return &Client{
		baseURL: baseURL,
		apiKey:  apiKey,
		httpClient: &http.Client{
			Timeout: 10 * time.Second,
		},
	}
}

// ValidateToken validates JWT token and returns user info
func (c *Client) ValidateToken(token string) (*models.User, error) {
	reqBody := map[string]string{"token": token}
	bodyBytes, err := json.Marshal(reqBody)
	if err != nil {
		return nil, fmt.Errorf("marshal request: %w", err)
	}

	req, err := http.NewRequest("POST", c.baseURL+"/api/internal/v1/auth/validate", bytes.NewReader(bodyBytes))
	if err != nil {
		return nil, fmt.Errorf("create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-Internal-API-Key", c.apiKey)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("do request: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("read response: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		var errResp struct {
			Error string `json:"error"`
		}
		if err := json.Unmarshal(body, &errResp); err == nil {
			return nil, fmt.Errorf("backend error: %s", errResp.Error)
		}
		return nil, fmt.Errorf("backend error: status %d", resp.StatusCode)
	}

	var result struct {
		Valid bool         `json:"valid"`
		User  models.User  `json:"user"`
	}

	if err := json.Unmarshal(body, &result); err != nil {
		return nil, fmt.Errorf("unmarshal response: %w", err)
	}

	if !result.Valid {
		return nil, fmt.Errorf("invalid token")
	}

	return &result.User, nil
}

// GetChatMembers returns list of chat members
func (c *Client) GetChatMembers(chatID int) ([]models.ChatMember, error) {
	req, err := http.NewRequest("GET", fmt.Sprintf("%s/api/internal/v1/chats/%d/members", c.baseURL, chatID), nil)
	if err != nil {
		return nil, fmt.Errorf("create request: %w", err)
	}

	req.Header.Set("X-Internal-API-Key", c.apiKey)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("do request: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("read response: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		var errResp struct {
			Error string `json:"error"`
		}
		if err := json.Unmarshal(body, &errResp); err == nil {
			return nil, fmt.Errorf("backend error: %s", errResp.Error)
		}
		return nil, fmt.Errorf("backend error: status %d", resp.StatusCode)
	}

	var result struct {
		ChatID  int                  `json:"chat_id"`
		Members []models.ChatMember  `json:"members"`
	}

	if err := json.Unmarshal(body, &result); err != nil {
		return nil, fmt.Errorf("unmarshal response: %w", err)
	}

	return result.Members, nil
}

// SendMessage forwards message to backend API
func (c *Client) SendMessage(token string, data models.SendMessageData) (json.RawMessage, error) {
	bodyBytes, err := json.Marshal(map[string]interface{}{
		"chatId":        data.ChatID,
		"text":          data.Text,
		"replyToId":     data.ReplyToID,
		"attachmentIds": data.AttachmentIDs,
	})
	if err != nil {
		return nil, fmt.Errorf("marshal request: %w", err)
	}

	req, err := http.NewRequest("POST", c.baseURL+"/api/v1/messages", bytes.NewReader(bodyBytes))
	if err != nil {
		return nil, fmt.Errorf("create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("do request: %w", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("read response: %w", err)
	}

	if resp.StatusCode != http.StatusCreated && resp.StatusCode != http.StatusOK {
		var errResp struct {
			Error string `json:"error"`
		}
		if err := json.Unmarshal(body, &errResp); err == nil {
			return nil, fmt.Errorf("backend error: %s", errResp.Error)
		}
		return nil, fmt.Errorf("backend error: status %d", resp.StatusCode)
	}

	return json.RawMessage(body), nil
}

// AddReaction forwards reaction to backend API
func (c *Client) AddReaction(token string, data models.AddReactionData) error {
	bodyBytes, err := json.Marshal(map[string]interface{}{
		"emoji": data.Emoji,
	})
	if err != nil {
		return fmt.Errorf("marshal request: %w", err)
	}

	req, err := http.NewRequest("POST", fmt.Sprintf("%s/api/v1/messages/%d/reactions", c.baseURL, data.MessageID), bytes.NewReader(bodyBytes))
	if err != nil {
		return fmt.Errorf("create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return fmt.Errorf("do request: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusCreated {
		body, _ := io.ReadAll(resp.Body)
		var errResp struct {
			Error string `json:"error"`
		}
		if err := json.Unmarshal(body, &errResp); err == nil {
			return fmt.Errorf("backend error: %s", errResp.Error)
		}
		return fmt.Errorf("backend error: status %d", resp.StatusCode)
	}

	return nil
}

// MarkAsRead forwards read receipts to backend API
func (c *Client) MarkAsRead(token string, data models.MarkReadData) error {
	bodyBytes, err := json.Marshal(map[string]interface{}{
		"messageIds": data.MessageIDs,
	})
	if err != nil {
		return fmt.Errorf("marshal request: %w", err)
	}

	req, err := http.NewRequest("POST", c.baseURL+"/api/v1/messages/read", bytes.NewReader(bodyBytes))
	if err != nil {
		return fmt.Errorf("create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return fmt.Errorf("do request: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		var errResp struct {
			Error string `json:"error"`
		}
		if err := json.Unmarshal(body, &errResp); err == nil {
			return fmt.Errorf("backend error: %s", errResp.Error)
		}
		return fmt.Errorf("backend error: status %d", resp.StatusCode)
	}

	return nil
}
