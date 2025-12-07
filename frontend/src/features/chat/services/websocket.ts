/**
 * WebSocket service for real-time chat updates
 * This is a stub/placeholder for future gogate (WebSocket gateway) integration
 *
 * When gogate is ready, this service will:
 * - Connect to ws://localhost:8080 (or configured WebSocket URL)
 * - Subscribe to chat and message events
 * - Notify ChatContext about new messages, chat updates, etc.
 */

export interface WebSocketMessage {
  type: 'new_message' | 'message_updated' | 'message_deleted' | 'chat_updated' | 'user_typing';
  data: unknown;
}

export type WebSocketEventHandler = (message: WebSocketMessage) => void;

class WebSocketService {
  private ws: WebSocket | null = null;
  private handlers: WebSocketEventHandler[] = [];
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 1000;

  /**
   * Connect to WebSocket server
   * @param url WebSocket URL (e.g., ws://localhost:8080)
   */
  connect(url: string): void {
    console.log(`[WebSocket] Stub: connect() called with url: ${url}`);
    // TODO: Implement actual WebSocket connection when gogate is ready
    // this.ws = new WebSocket(url);
    // this.setupEventListeners();
  }

  /**
   * Disconnect from WebSocket server
   */
  disconnect(): void {
    console.log('[WebSocket] Stub: disconnect() called');
    // TODO: Implement actual disconnect
    // if (this.ws) {
    //   this.ws.close();
    //   this.ws = null;
    // }
  }

  /**
   * Subscribe to WebSocket events
   * @param handler Callback function to handle incoming messages
   * @returns Unsubscribe function
   */
  subscribe(handler: WebSocketEventHandler): () => void {
    console.log('[WebSocket] Stub: subscribe() called');
    this.handlers.push(handler);

    // Return unsubscribe function
    return () => {
      this.handlers = this.handlers.filter((h) => h !== handler);
    };
  }

  /**
   * Send message through WebSocket
   */
  send(message: unknown): void {
    console.log('[WebSocket] Stub: send() called with message:', message);
    // TODO: Implement actual send
    // if (this.ws && this.ws.readyState === WebSocket.OPEN) {
    //   this.ws.send(JSON.stringify(message));
    // }
  }

  /**
   * Check if WebSocket is connected
   */
  isConnected(): boolean {
    // TODO: Return actual connection status
    // return this.ws !== null && this.ws.readyState === WebSocket.OPEN;
    return false;
  }

  // TODO: Implement these methods when gogate is ready
  // private setupEventListeners(): void {}
  // private handleMessage(event: MessageEvent): void {}
  // private handleError(event: Event): void {}
  // private handleClose(event: CloseEvent): void {}
  // private reconnect(): void {}
}

export const websocketService = new WebSocketService();
