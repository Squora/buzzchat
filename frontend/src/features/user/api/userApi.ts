import { apiClientV1 } from '@/api/v1/apiClient';
import type { User, UsersListResponse, UsersQueryParams } from '../types/user.types';

/**
 * Get paginated list of users
 */
export const getUsers = async (params?: UsersQueryParams): Promise<UsersListResponse> => {
  const response = await apiClientV1.get<UsersListResponse>('/users', { params });
  return response.data;
};

/**
 * Get user by ID
 */
export const getUserById = async (id: number): Promise<User> => {
  const response = await apiClientV1.get<User>(`/users/${id}`);
  return response.data;
};

/**
 * Search users with pagination
 */
export const searchUsers = async (
  search: string,
  page: number = 1,
  limit: number = 20
): Promise<UsersListResponse> => {
  return getUsers({ search, page, limit });
};
