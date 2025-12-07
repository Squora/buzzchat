import axios from 'axios';
import { camelizeKeys, decamelizeKeys } from 'humps';
import { config } from '@/config/env';

/**
 * API Client for v1 endpoints
 * Uses cookie-based authentication:
 * - access_token in httpOnly cookie
 * - refresh_token in httpOnly cookie
 * - Tokens are automatically sent with each request
 * - Auto-refresh on 401 responses
 * - Automatic snake_case ↔ camelCase conversion
 */
export const apiClientV1 = axios.create({
  baseURL: `${config.backendUrl}/api/v1`,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // CRITICAL: send cookies with all requests
});

// Token refresh state
let isRefreshing = false;
let failedQueue: Array<{
  resolve: (value?: unknown) => void;
  reject: (reason?: unknown) => void;
}> = [];

// Callback for when refresh fails - set by AuthContext
let onRefreshFailure: (() => void) | null = null;

export const setRefreshFailureHandler = (handler: () => void) => {
  onRefreshFailure = handler;
};

/**
 * Process queued requests after successful refresh
 */
const processQueue = (error: unknown = null) => {
  failedQueue.forEach((promise) => {
    if (error) {
      promise.reject(error);
    } else {
      promise.resolve();
    }
  });
  failedQueue = [];
};

/**
 * Request interceptor for snake_case conversion
 * Converts outgoing request data and params from camelCase to snake_case
 */
apiClientV1.interceptors.request.use(
  (config) => {
    // Convert request body to snake_case
    if (config.data && typeof config.data === 'object') {
      config.data = decamelizeKeys(config.data);
    }

    // Convert query params to snake_case
    if (config.params && typeof config.params === 'object') {
      config.params = decamelizeKeys(config.params);
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

/**
 * Response interceptor for camelCase conversion and automatic token refresh on 401
 */
apiClientV1.interceptors.response.use(
  (response) => {
    // Convert response data from snake_case to camelCase
    if (response.data && typeof response.data === 'object') {
      response.data = camelizeKeys(response.data);
    }
    return response;
  },
  async (error) => {
    const originalRequest = error.config;

    // If error is not 401 or request already retried, reject immediately
    if (error.response?.status !== 401 || originalRequest._retry) {
      return Promise.reject(error);
    }

    // Don't retry refresh endpoint itself to avoid infinite loop
    if (originalRequest.url?.includes('/auth/token/refresh')) {
      return Promise.reject(error);
    }

    // If already refreshing, queue this request
    if (isRefreshing) {
      return new Promise((resolve, reject) => {
        failedQueue.push({ resolve, reject });
      })
        .then(() => apiClientV1(originalRequest))
        .catch((err) => Promise.reject(err));
    }

    // Mark this request as retried and start refresh process
    originalRequest._retry = true;
    isRefreshing = true;

    try {
      // Try to refresh the token
      await apiClientV1.post('/auth/token/refresh');

      // Refresh successful - process queued requests
      processQueue();
      isRefreshing = false;

      // Retry the original request
      return apiClientV1(originalRequest);
    } catch (refreshError) {
      // Refresh failed - clear queue and notify app
      processQueue(refreshError);
      isRefreshing = false;

      // Call the failure handler (clears auth state in AuthContext)
      if (onRefreshFailure) {
        onRefreshFailure();
      }

      return Promise.reject(refreshError);
    }
  }
);
