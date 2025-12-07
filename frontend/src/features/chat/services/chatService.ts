import { apiClientV1 } from '@/api/v1/apiClient';
import type {
  Chat,
  ChatListResponse,
  ChatResponse,
  CreateGroupChatRequest,
  CreateDirectChatRequest,
  UpdateChatRequest,
  AddMembersRequest,
  UpdateMemberRoleRequest,
} from '../types/chat.types';

export const chatService = {
  /**
   * Get all user's chats
   * GET /api/v1/chats
   */
  getUserChats: async (): Promise<Chat[]> => {
    const response = await apiClientV1.get<ChatListResponse>('/chats');
    return response.data.chats;
  },

  /**
   * Get chat details with members
   * GET /api/v1/chats/{id}
   */
  getChat: async (id: number): Promise<Chat> => {
    const response = await apiClientV1.get<ChatResponse>(`/chats/${id}`);
    return response.data;
  },

  /**
   * Create a new group chat
   * POST /api/v1/chats/group
   */
  createGroupChat: async (data: CreateGroupChatRequest): Promise<Chat> => {
    const response = await apiClientV1.post<ChatResponse>('/chats/group', data);
    return response.data;
  },

  /**
   * Create or get existing direct chat
   * POST /api/v1/chats/direct
   */
  createDirectChat: async (data: CreateDirectChatRequest): Promise<Chat> => {
    const response = await apiClientV1.post<ChatResponse>('/chats/direct', data);
    return response.data;
  },

  /**
   * Update chat details (admins only)
   * PATCH /api/v1/chats/{id}
   */
  updateChat: async (id: number, data: UpdateChatRequest): Promise<Chat> => {
    const response = await apiClientV1.patch<ChatResponse>(`/chats/${id}`, data);
    return response.data;
  },

  /**
   * Add members to chat (admins only)
   * POST /api/v1/chats/{id}/members
   */
  addMembers: async (id: number, data: AddMembersRequest): Promise<Chat> => {
    const response = await apiClientV1.post<ChatResponse>(`/chats/${id}/members`, data);
    return response.data;
  },

  /**
   * Remove member from chat (admins only)
   * DELETE /api/v1/chats/{id}/members/{userId}
   */
  removeMember: async (id: number, userId: number): Promise<Chat> => {
    const response = await apiClientV1.delete<ChatResponse>(`/chats/${id}/members/${userId}`);
    return response.data;
  },

  /**
   * Update member role (owner only)
   * PATCH /api/v1/chats/{id}/members/role
   */
  updateMemberRole: async (id: number, data: UpdateMemberRoleRequest): Promise<Chat> => {
    const response = await apiClientV1.patch<ChatResponse>(`/chats/${id}/members/role`, data);
    return response.data;
  },

  /**
   * Leave chat
   * POST /api/v1/chats/{id}/leave
   */
  leaveChat: async (id: number): Promise<void> => {
    await apiClientV1.post(`/chats/${id}/leave`);
  },

  /**
   * Delete chat (owner only)
   * DELETE /api/v1/chats/{id}
   */
  deleteChat: async (id: number): Promise<void> => {
    await apiClientV1.delete(`/chats/${id}`);
  },
};
