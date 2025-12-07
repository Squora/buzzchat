import { useEffect, useRef, useCallback } from 'react';

interface UseInfiniteScrollOptions {
  loading: boolean;
  hasMore: boolean;
  onLoadMore: () => void;
  threshold?: number; // Distance from top in pixels to trigger load
}

/**
 * Hook for implementing infinite scroll upwards (for loading old messages)
 * Uses IntersectionObserver to detect when sentinel element is visible
 */
export const useInfiniteScroll = ({
  loading,
  hasMore,
  onLoadMore,
  threshold = 100,
}: UseInfiniteScrollOptions) => {
  const sentinelRef = useRef<HTMLDivElement | null>(null);
  const scrollContainerRef = useRef<HTMLDivElement | null>(null);
  const previousScrollHeight = useRef<number>(0);

  // Save scroll position before loading more messages
  const saveScrollPosition = useCallback(() => {
    if (scrollContainerRef.current) {
      previousScrollHeight.current = scrollContainerRef.current.scrollHeight;
    }
  }, []);

  // Restore scroll position after loading more messages
  const restoreScrollPosition = useCallback(() => {
    if (scrollContainerRef.current && previousScrollHeight.current) {
      const newScrollHeight = scrollContainerRef.current.scrollHeight;
      const scrollDiff = newScrollHeight - previousScrollHeight.current;
      scrollContainerRef.current.scrollTop += scrollDiff;
      previousScrollHeight.current = 0;
    }
  }, []);

  useEffect(() => {
    const sentinel = sentinelRef.current;
    if (!sentinel || loading || !hasMore) return;

    const observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0];
        if (entry.isIntersecting) {
          saveScrollPosition();
          onLoadMore();
        }
      },
      {
        root: scrollContainerRef.current,
        rootMargin: `${threshold}px 0px 0px 0px`,
        threshold: 0.1,
      }
    );

    observer.observe(sentinel);

    return () => {
      observer.disconnect();
    };
  }, [loading, hasMore, onLoadMore, threshold, saveScrollPosition]);

  return {
    sentinelRef,
    scrollContainerRef,
    restoreScrollPosition,
  };
};
