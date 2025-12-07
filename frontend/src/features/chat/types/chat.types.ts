// Chat types matching backend API

export enum ChatType {
  GROUP = 'group',
  DIRECT = 'direct',
}

export enum MemberRole {
  OWNER = 'owner',
  ADMIN = 'admin',
  MEMBER = 'member',
}

export interface ChatMember {
  id: number;
  user: {
    id: number;
    email: string;
    phone: string;
    firstName: string;
    lastName: string;
    fullName: string;
    roles: string[];
    isActive: boolean;
  };
  role: MemberRole;
  joinedAt: string;
  leftAt?: string | null;
}

export interface Chat {
  id: number;
  type: ChatType;
  name: string | null;
  description: string | null;
  photoUrl: string | null;
  membersCount: number;
  createdAt: string;
  updatedAt: string | null;
  members?: ChatMember[];
  // Local UI fields
  unread?: boolean;
  lastMessage?: string;
  lastMessageTime?: string;
}

// Request types
export interface CreateGroupChatRequest {
  name: string;
  description?: string;
  photoUrl?: string;
  memberIds: number[];
}

export interface CreateDirectChatRequest {
  userId: number;
}

export interface UpdateChatRequest {
  name?: string;
  description?: string;
  photoUrl?: string;
}

export interface AddMembersRequest {
  userIds: number[];
}

export interface UpdateMemberRoleRequest {
  userId: number;
  role: MemberRole;
}

// Response types
export interface ChatListResponse {
  chats: Chat[];
}

export interface ChatResponse extends Chat {}
