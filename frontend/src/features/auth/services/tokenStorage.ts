/**
 * Secure token storage
 * - Access token: stored in memory only (cleared on page reload)
 * - Refresh token: stored in httpOnly cookies (managed by backend)
 * - User data: stored in localStorage for persistence
 */

class TokenStorage {
  private accessToken: string | null = null;

  /**
   * Set access token in memory
   */
  setAccessToken(token: string): void {
    this.accessToken = token;
  }

  /**
   * Get access token from memory
   */
  getAccessToken(): string | null {
    return this.accessToken;
  }

  /**
   * Clear access token from memory
   */
  clearAccessToken(): void {
    this.accessToken = null;
  }

  /**
   * Check if access token exists
   */
  hasAccessToken(): boolean {
    return this.accessToken !== null;
  }

  /**
   * Store user data in localStorage
   */
  setUser(user: any): void {
    try {
      localStorage.setItem('user', JSON.stringify(user));
    } catch (error) {
      console.error('Failed to store user data:', error);
    }
  }

  /**
   * Get user data from localStorage
   */
  getUser(): any | null {
    try {
      const userStr = localStorage.getItem('user');
      if (!userStr) return null;
      return JSON.parse(userStr);
    } catch (error) {
      console.error('Failed to retrieve user data:', error);
      return null;
    }
  }

  /**
   * Clear user data from localStorage
   */
  clearUser(): void {
    try {
      localStorage.removeItem('user');
    } catch (error) {
      console.error('Failed to clear user data:', error);
    }
  }

  /**
   * Clear all auth data
   */
  clearAll(): void {
    this.clearAccessToken();
    this.clearUser();
  }
}

export const tokenStorage = new TokenStorage();
