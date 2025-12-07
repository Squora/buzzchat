package config

import (
	"fmt"
	"os"
	"strconv"
)

type Config struct {
	// Server settings
	Port string

	// Backend API settings
	BackendAPIURL    string
	InternalAPIKey   string

	// WebSocket settings
	MaxMessageSize   int64
	ReadBufferSize   int
	WriteBufferSize  int
	PingPeriod       int // seconds
	PongWait         int // seconds
	WriteWait        int // seconds
}

func Load() (*Config, error) {
	cfg := &Config{
		Port:             getEnv("PORT", "8080"),
		BackendAPIURL:    getEnv("BACKEND_API_URL", "http://localhost:8000"),
		InternalAPIKey:   getEnv("INTERNAL_API_KEY", ""),
		MaxMessageSize:   getEnvInt64("MAX_MESSAGE_SIZE", 512000), // 500KB
		ReadBufferSize:   getEnvInt("READ_BUFFER_SIZE", 1024),
		WriteBufferSize:  getEnvInt("WRITE_BUFFER_SIZE", 1024),
		PingPeriod:       getEnvInt("PING_PERIOD", 54),  // 54 seconds
		PongWait:         getEnvInt("PONG_WAIT", 60),    // 60 seconds
		WriteWait:        getEnvInt("WRITE_WAIT", 10),   // 10 seconds
	}

	// Validate required fields
	if cfg.InternalAPIKey == "" {
		return nil, fmt.Errorf("INTERNAL_API_KEY is required")
	}

	return cfg, nil
}

func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

func getEnvInt(key string, defaultValue int) int {
	if value := os.Getenv(key); value != "" {
		if intVal, err := strconv.Atoi(value); err == nil {
			return intVal
		}
	}
	return defaultValue
}

func getEnvInt64(key string, defaultValue int64) int64 {
	if value := os.Getenv(key); value != "" {
		if intVal, err := strconv.ParseInt(value, 10, 64); err == nil {
			return intVal
		}
	}
	return defaultValue
}
