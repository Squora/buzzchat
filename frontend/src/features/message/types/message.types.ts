// Message types matching backend API

export enum MessageStatus {
  SENT = 'sent',
  SENDING = 'sending',
  FAILED = 'failed',
}

export interface MessageAttachment {
  id: number;
  fileUrl: string;
  fileName: string;
  fileSize: number;
  fileType: string;
  thumbnailUrl?: string | null;
}

export interface Message {
  id: number;
  chatId: number;
  userId: number;
  userName: string;
  userAvatar?: string;
  content: string;
  createdAt: string;
  updatedAt: string | null;
  isEdited: boolean;
  attachments?: MessageAttachment[];
  // Local UI fields
  status?: MessageStatus;
  isOwn?: boolean;
  tempId?: string; // for optimistic UI
}

// Request types
export interface SendMessageRequest {
  chatId: number;
  content: string;
}

export interface UpdateMessageRequest {
  content: string;
}

export interface AddReactionRequest {
  emoji: string;
}

export interface MarkAsReadRequest {
  messageIds: number[];
}

// Response types
export interface MessageListResponse {
  messages: Message[];
  hasMore: boolean;
}

export interface MessageResponse extends Message {}
