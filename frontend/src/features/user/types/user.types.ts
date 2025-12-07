// User types based on backend API

export interface User {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  phone: string;
  photoUrl?: string | null;
  position?: string | null;
  statusMessage?: string | null;
  onlineStatus: 'available' | 'busy' | 'away' | 'offline';
  lastSeenAt?: string | null;
  department?: Department | null;
  isActive: boolean;
  createdAt: string;
}

export interface Department {
  id: number;
  name: string;
  description?: string | null;
}

export interface UsersListResponse {
  data: User[];
  meta: {
    total: number;
    page: number;
    limit: number;
    totalPages: number;
  };
}

export interface UsersQueryParams {
  page?: number;
  limit?: number;
  search?: string;
  department?: number;
  onlineStatus?: 'available' | 'busy' | 'away' | 'offline';
  isActive?: boolean;
}
