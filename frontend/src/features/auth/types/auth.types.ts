export interface User {
  id: number;
  email: string;
  phone: string;
  firstName: string | null;
  lastName: string | null;
  fullName: string;
  roles: string[];
  isActive: boolean;
}

// Register - Step 1: Send SMS code
export interface RegisterRequest {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
}

export interface RegisterResponse {
  success: boolean;
  message: string;
  phone: string;
}

// Register - Step 2: Verify code and complete registration
export interface VerifyRegisterRequest {
  phone: string;
  code: string;
}

export interface VerifyRegisterResponse {
  success: boolean;
  message: string;
  token: string;
  refreshToken: string;
  user: User;
}

// Login - Step 1: Request SMS code
export interface RequestLoginCodeRequest {
  phone: string;
}

export interface RequestLoginCodeResponse {
  success: boolean;
  message: string;
  phone: string;
}

// Login - Step 2: Verify code and login
export interface VerifyLoginRequest {
  phone: string;
  code: string;
}

export interface VerifyLoginResponse {
  success: boolean;
  token: string;
  refreshToken: string;
  user: User;
}

// Refresh token
export interface RefreshTokenRequest {
  refreshToken: string;
}

export interface RefreshTokenResponse {
  token: string;
  refreshToken: string;
}

// Logout
export interface LogoutRequest {
  refreshToken: string;
}

export interface LogoutResponse {
  message: string;
}

// Auth Context
export interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  register: (data: RegisterRequest) => Promise<RegisterResponse>;
  verifyRegister: (phone: string, code: string) => Promise<void>;
  requestLoginCode: (phone: string) => Promise<RequestLoginCodeResponse>;
  verifyLogin: (phone: string, code: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshAuth: () => Promise<void>;
}

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
}
