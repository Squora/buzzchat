import { apiClientV1 } from '@/api/v1/apiClient';
import { tokenStorage } from './tokenStorage';
import type {
  RegisterRequest,
  RegisterResponse,
  VerifyRegisterRequest,
  VerifyRegisterResponse,
  RequestLoginCodeRequest,
  RequestLoginCodeResponse,
  VerifyLoginRequest,
  VerifyLoginResponse,
  CurrentUserResponse,
  LogoutResponse,
  User,
} from '../types/auth.types';

export const authService = {
  /**
   * Step 1: Register new user - sends SMS code
   * POST /api/v1/auth/register
   */
  register: async (data: RegisterRequest): Promise<RegisterResponse> => {
    const response = await apiClientV1.post<RegisterResponse>('/auth/register', data);
    return response.data;
  },

  /**
   * Step 2: Verify SMS code and complete registration
   * POST /api/v1/auth/register/verify
   * Backend sets refresh token as httpOnly cookie
   */
  verifyRegister: async (data: VerifyRegisterRequest): Promise<VerifyRegisterResponse> => {
    const response = await apiClientV1.post<VerifyRegisterResponse>('/auth/register/verify', data);
    return response.data;
  },

  /**
   * Step 1: Request SMS code for login
   * POST /api/v1/auth/login/request-code
   */
  requestLoginCode: async (data: RequestLoginCodeRequest): Promise<RequestLoginCodeResponse> => {
    const response = await apiClientV1.post<RequestLoginCodeResponse>(
      '/auth/login/request-code',
      data
    );
    return response.data;
  },

  /**
   * Step 2: Verify SMS code and login
   * POST /api/v1/auth/login/verify
   * Backend sets refresh token as httpOnly cookie
   */
  verifyLogin: async (data: VerifyLoginRequest): Promise<VerifyLoginResponse> => {
    const response = await apiClientV1.post<VerifyLoginResponse>('/auth/login/verify', data);
    return response.data;
  },

  /**
   * Get current authenticated user
   * GET /api/v1/auth/current
   */
  getCurrentUser: async (): Promise<User> => {
    const response = await apiClientV1.get<User>('/auth/current');
    return response.data;
  },

  /**
   * Refresh access token using refresh token from httpOnly cookie
   * POST /api/v1/auth/token/refresh
   */
  refreshTokens: async (): Promise<void> => {
    await apiClientV1.post<void>('/auth/token/refresh');
  },

  /**
   * Logout - invalidate refresh token cookie
   * POST /api/v1/auth/logout
   * Backend clears httpOnly cookie
   */
  logout: async (): Promise<void> => {
    // Refresh token is sent automatically via httpOnly cookie
    await apiClientV1.post<LogoutResponse>('/auth/logout', {});
  },

  /**
   * Store auth data securely
   * - Access token: in httpOnly cookie (managed by backend)
   * - User data: in localStorage
   * - Refresh token: httpOnly cookie (managed by backend)
   */
  storeAuthData: (_token: string, user: User): void => {
    // Token is now in cookies, no need to store in memory
    // Just store user data for UI purposes
    tokenStorage.setUser(user);
  },

  /**
   * Clear all auth data
   */
  clearAuthData: (): void => {
    tokenStorage.clearAll();
  },

  /**
   * Get stored user data from localStorage
   */
  getStoredUser: (): User | null => {
    return tokenStorage.getUser();
  },

  /**
   * Check if user is authenticated
   * Checks if user data exists in localStorage
   * Actual authentication is done via cookies on backend
   */
  isAuthenticated: (): boolean => {
    return tokenStorage.getUser() !== null;
  },
};
