import { apiClientV1 } from '@/api/v1/apiClient';
import type {
  Message,
  MessageAttachment,
  MessageListResponse,
  MessageResponse,
  SendMessageRequest,
  UpdateMessageRequest,
  AddReactionRequest,
  MarkAsReadRequest,
} from '../types/message.types';

// Backend response type (using 'text' instead of 'content')
interface BackendMessageResponse {
  id: number;
  chat_id: number;
  user: {
    id: number;
    name: string;
    avatar?: string;
  };
  text: string | null;
  created_at: string;
  updated_at: string | null;
  is_edited: boolean;
  attachments?: Array<{
    id: number;
    file_url: string;
    file_name: string;
    file_size: number;
    file_type: string;
    thumbnail_url?: string | null;
  }>;
}

interface BackendMessageListResponse {
  messages: BackendMessageResponse[];
  has_more: boolean;
}

// Helper to transform backend response to frontend format
const transformMessage = (backendMsg: BackendMessageResponse, currentUserId?: number): Message => {
  return {
    id: backendMsg.id,
    chat_id: backendMsg.chat_id,
    user_id: backendMsg.user.id,
    user_name: backendMsg.user.name,
    user_avatar: backendMsg.user.avatar,
    content: backendMsg.text || '', // Map 'text' to 'content'
    created_at: backendMsg.created_at,
    updated_at: backendMsg.updated_at,
    is_edited: backendMsg.is_edited,
    attachments: backendMsg.attachments || [],
    isOwn: currentUserId ? backendMsg.user.id === currentUserId : undefined,
  };
};

export const messageService = {
  /**
   * Get messages for a chat with pagination
   * GET /api/v1/chats/{chatId}/messages?before_id={beforeId}&limit={limit}
   */
  getMessages: async (
    chatId: number,
    beforeId?: number,
    limit: number = 50,
    currentUserId?: number
  ): Promise<MessageListResponse> => {
    const params: Record<string, string | number> = { limit };
    if (beforeId) {
      params.before_id = beforeId;
    }

    const response = await apiClientV1.get<BackendMessageListResponse>(`/chats/${chatId}/messages`, {
      params,
    });

    return {
      messages: response.data.messages.map(msg => transformMessage(msg, currentUserId)),
      has_more: response.data.has_more,
    };
  },

  /**
   * Send a new message to a chat
   * POST /api/v1/messages
   */
  sendMessage: async (data: SendMessageRequest, currentUserId?: number): Promise<Message> => {
    const response = await apiClientV1.post<BackendMessageResponse>('/messages', data);
    return transformMessage(response.data, currentUserId);
  },

  /**
   * Update message content (author only)
   * PATCH /api/v1/messages/{id}
   */
  updateMessage: async (id: number, data: UpdateMessageRequest, currentUserId?: number): Promise<Message> => {
    const response = await apiClientV1.patch<BackendMessageResponse>(`/messages/${id}`, data);
    return transformMessage(response.data, currentUserId);
  },

  /**
   * Delete message (author only)
   * DELETE /api/v1/messages/{id}
   */
  deleteMessage: async (id: number): Promise<void> => {
    await apiClientV1.delete(`/messages/${id}`);
  },

  /**
   * Add emoji reaction to message
   * POST /api/v1/messages/{id}/reactions
   */
  addReaction: async (id: number, data: AddReactionRequest): Promise<void> => {
    await apiClientV1.post(`/messages/${id}/reactions`, data);
  },

  /**
   * Mark multiple messages as read
   * POST /api/v1/messages/read
   */
  markAsRead: async (data: MarkAsReadRequest): Promise<void> => {
    await apiClientV1.post('/messages/read', data);
  },
};
