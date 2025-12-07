package main

import (
	"log"
	"net/http"

	"buzzchat-gogate/internal/api"
	"buzzchat-gogate/internal/config"
	"buzzchat-gogate/internal/ws"

	"github.com/joho/godotenv"
)

func main() {
	// Load .env file (ignore error if not exists)
	_ = godotenv.Load()

	// Load configuration
	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("Failed to load config: %v", err)
	}

	log.Printf("Starting GoGate WebSocket Gateway...")
	log.Printf("Backend API URL: %s", cfg.BackendAPIURL)
	log.Printf("Server Port: %s", cfg.Port)

	// Create Backend API client
	apiClient := api.NewClient(cfg.BackendAPIURL, cfg.InternalAPIKey)

	// Create Hub
	hub := ws.NewHub(apiClient)
	go hub.Run()

	// Create WebSocket handler
	wsHandler := ws.NewHandler(hub)

	// Setup HTTP routes
	http.HandleFunc("/ws", wsHandler.ServeHTTP)
	http.HandleFunc("/health", healthHandler)
	http.HandleFunc("/", rootHandler)

	// Start HTTP server
	addr := ":" + cfg.Port
	log.Printf("GoGate is listening on %s", addr)
	log.Printf("WebSocket endpoint: ws://localhost%s/ws", addr)

	if err := http.ListenAndServe(addr, nil); err != nil {
		log.Fatalf("Server error: %v", err)
	}
}

// healthHandler returns health status
func healthHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	w.Write([]byte(`{"status":"ok","service":"gogate"}`))
}

// rootHandler returns basic info
func rootHandler(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path != "/" {
		http.NotFound(w, r)
		return
	}

	w.Header().Set("Content-Type", "text/plain")
	w.WriteHeader(http.StatusOK)
	w.Write([]byte("GoGate WebSocket Gateway\n\nWebSocket endpoint: /ws\nHealth check: /health\n"))
}
