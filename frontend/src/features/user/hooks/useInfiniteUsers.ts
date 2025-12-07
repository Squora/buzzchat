import { useState, useEffect, useCallback, useRef } from 'react';
import { getUsers } from '../api/userApi';
import type { User, UsersQueryParams } from '../types/user.types';

interface UseInfiniteUsersOptions {
  limit?: number;
  initialSearch?: string;
  filters?: Omit<UsersQueryParams, 'page' | 'limit' | 'search'>;
}

interface UseInfiniteUsersReturn {
  users: User[];
  loading: boolean;
  error: string | null;
  hasMore: boolean;
  loadMore: () => void;
  search: (query: string) => void;
  refresh: () => void;
  observerRef: (node: HTMLElement | null) => void;
}

/**
 * Hook for infinite scroll pagination of users
 */
export const useInfiniteUsers = (options: UseInfiniteUsersOptions = {}): UseInfiniteUsersReturn => {
  const {
    limit = 20,
    initialSearch = '',
    filters = {},
  } = options;

  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [searchQuery, setSearchQuery] = useState(initialSearch);

  // Refs to track state in callbacks
  const pageRef = useRef(page);
  const searchQueryRef = useRef(searchQuery);
  const loadingRef = useRef(loading);

  // Update refs when state changes
  useEffect(() => {
    pageRef.current = page;
    searchQueryRef.current = searchQuery;
    loadingRef.current = loading;
  }, [page, searchQuery, loading]);

  // Intersection observer for infinite scroll
  const observer = useRef<IntersectionObserver | null>(null);

  const observerRef = useCallback((node: HTMLElement | null) => {
    if (loading) return;
    if (observer.current) observer.current.disconnect();

    observer.current = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMore && !loadingRef.current) {
        setPage((prevPage) => prevPage + 1);
      }
    });

    if (node) observer.current.observe(node);
  }, [loading, hasMore]);

  // Fetch users
  const fetchUsers = useCallback(async (pageNum: number, searchTerm: string, append: boolean = true) => {
    if (loadingRef.current) return;

    setLoading(true);
    setError(null);

    try {
      const params: UsersQueryParams = {
        page: pageNum,
        limit,
        ...filters,
      };

      if (searchTerm) {
        params.search = searchTerm;
      }

      const response = await getUsers(params);

      setUsers((prevUsers) => {
        if (append) {
          return [...prevUsers, ...response.data];
        }
        return response.data;
      });

      setHasMore(pageNum < response.meta.totalPages);
    } catch (err) {
      console.error('Failed to fetch users:', err);
      setError('Не удалось загрузить пользователей');
    } finally {
      setLoading(false);
    }
  }, [limit, filters]);

  // Load more (when page changes)
  useEffect(() => {
    if (page > 1) {
      fetchUsers(page, searchQuery, true);
    }
  }, [page]);

  // Initial load
  useEffect(() => {
    fetchUsers(1, searchQuery, false);
  }, []);

  // Load more manually
  const loadMore = useCallback(() => {
    if (!loading && hasMore) {
      setPage((prev) => prev + 1);
    }
  }, [loading, hasMore]);

  // Search
  const search = useCallback((query: string) => {
    setSearchQuery(query);
    setPage(1);
    setUsers([]);
    setHasMore(true);
    fetchUsers(1, query, false);
  }, [fetchUsers]);

  // Refresh
  const refresh = useCallback(() => {
    setPage(1);
    setUsers([]);
    setHasMore(true);
    fetchUsers(1, searchQuery, false);
  }, [searchQuery, fetchUsers]);

  return {
    users,
    loading,
    error,
    hasMore,
    loadMore,
    search,
    refresh,
    observerRef,
  };
};
