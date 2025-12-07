import {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  type ReactNode,
} from "react";
import { authService } from "../services/authService";
import { tokenStorage } from "../services/tokenStorage";
import { setRefreshFailureHandler } from "@/api/v1/apiClient";
import type {
  AuthContextType,
  AuthState,
  RegisterRequest,
  RegisterResponse,
  RequestLoginCodeResponse,
} from "../types/auth.types";

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider = ({ children }: AuthProviderProps) => {
  const [authState, setAuthState] = useState<AuthState>({
    user: null,
    isAuthenticated: false,
    isLoading: true,
  });

  /**
   * Handle token refresh failure
   * Called by apiClient interceptor when refresh token is invalid/expired
   * Clears auth state without hard redirect
   */
  const handleTokenRefreshFailure = useCallback(() => {
    console.log("Token refresh failed - clearing auth state");
    authService.clearAuthData();
    setAuthState({
      user: null,
      isAuthenticated: false,
      isLoading: false,
    });
  }, []);

  // Register refresh failure handler with apiClient
  useEffect(() => {
    setRefreshFailureHandler(handleTokenRefreshFailure);
  }, [handleTokenRefreshFailure]);

  // Initialize auth state on mount
  useEffect(() => {
    const initializeAuth = async () => {
      try {
        // Try to get current user using cookies
        // If access_token cookie is valid, this will succeed
        // If access_token expired but refresh_token is valid, backend will auto-refresh
        try {
          const currentUser = await authService.getCurrentUser();

          // Store user in localStorage for quick check on next reload
          // tokenStorage.setUser(currentUser);

          setAuthState({
            user: currentUser,
            isAuthenticated: true,
            isLoading: false,
          });
        } catch (error) {
          // No valid cookies - user needs to login
          console.log("No valid session, user needs to login");
          authService.clearAuthData();
          setAuthState({
            user: null,
            isAuthenticated: false,
            isLoading: false,
          });
        }
      } catch (error) {
        console.error("Failed to initialize auth:", error);
        authService.clearAuthData();
        setAuthState({
          user: null,
          isAuthenticated: false,
          isLoading: false,
        });
      }
    };

    initializeAuth();
  }, []);

  const register = async (data: RegisterRequest): Promise<RegisterResponse> => {
    try {
      const response = await authService.register(data);
      return response;
    } catch (error) {
      console.error("Registration failed:", error);
      throw error;
    }
  };

  const verifyRegister = async (phone: string, code: string): Promise<void> => {
    try {
      await authService.verifyRegister({ phone, code });
      // Backend sets both access_token and refresh_token as httpOnly cookies
      // Now fetch current user data
      const currentUser = await authService.getCurrentUser();

      // Store user in localStorage for persistence across page reloads
      authService.storeAuthData("", currentUser); // No token needed - it's in cookies

      setAuthState({
        user: currentUser,
        isAuthenticated: true,
        isLoading: false,
      });
    } catch (error) {
      console.error("Code verification failed:", error);
      throw error;
    }
  };

  const requestLoginCode = async (
    phone: string
  ): Promise<RequestLoginCodeResponse> => {
    try {
      const response = await authService.requestLoginCode({ phone });
      return response;
    } catch (error) {
      console.error("Request login code failed:", error);
      throw error;
    }
  };

  const verifyLogin = async (phone: string, code: string): Promise<void> => {
    try {
      await authService.verifyLogin({ phone, code });
      // Backend sets both access_token and refresh_token as httpOnly cookies
      // Now fetch current user data
      const currentUser = await authService.getCurrentUser();

      // Store user in localStorage for persistence across page reloads
      authService.storeAuthData("", currentUser); // No token needed - it's in cookies

      setAuthState({
        user: currentUser,
        isAuthenticated: true,
        isLoading: false,
      });
    } catch (error) {
      console.error("Login verification failed:", error);
      throw error;
    }
  };

  const logout = async (): Promise<void> => {
    try {
      await authService.logout();
    } catch (error) {
      console.error("Logout failed:", error);
    } finally {
      authService.clearAuthData();
      setAuthState({
        user: null,
        isAuthenticated: false,
        isLoading: false,
      });
    }
  };

  const refreshAuth = async (): Promise<void> => {
    try {
      const currentUser = await authService.getCurrentUser();
      setAuthState((prev) => ({
        ...prev,
        user: currentUser,
      }));
    } catch (error) {
      console.error("Failed to refresh auth:", error);
      throw error;
    }
  };

  const value: AuthContextType = {
    user: authState.user,
    isAuthenticated: authState.isAuthenticated,
    isLoading: authState.isLoading,
    register,
    verifyRegister,
    requestLoginCode,
    verifyLogin,
    logout,
    refreshAuth,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
};
