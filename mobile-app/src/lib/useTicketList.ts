import { useFocusEffect } from 'expo-router';
import { useCallback, useRef, useState } from 'react';

import { api, ApiError } from './api';
import type { Paginated, StatusCounts, Ticket, TicketStatus } from './types';

/**
 * Paginated ticket list (my tickets or department tickets) with status filter,
 * pull-to-refresh, infinite scroll, and reload whenever the screen regains focus.
 */
export function useTicketList(endpoint: '/tickets' | '/admin/tickets', search = '') {
  const [status, setStatus] = useState<'all' | TicketStatus>('all');
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [counts, setCounts] = useState<StatusCounts>();
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  const requestId = useRef(0);

  const fetchPage = useCallback(
    async (nextPage: number, mode: 'replace' | 'append') => {
      const id = ++requestId.current;
      const params = new URLSearchParams({ page: String(nextPage) });
      if (status !== 'all') params.set('status', status);
      if (search.trim()) params.set('search', search.trim());

      try {
        const result = await api<Paginated<Ticket>>(`${endpoint}?${params.toString()}`);
        if (id !== requestId.current) return; // a newer request replaced this one
        setTickets((prev) => (mode === 'append' ? [...prev, ...result.data] : result.data));
        setCounts(result.counts);
        setPage(result.meta.current_page);
        setLastPage(result.meta.last_page);
        setError(null);
      } catch (e) {
        if (id === requestId.current) setError(e as ApiError);
      } finally {
        if (id === requestId.current) {
          setLoading(false);
          setRefreshing(false);
        }
      }
    },
    [endpoint, status, search],
  );

  useFocusEffect(
    useCallback(() => {
      void fetchPage(1, 'replace');
    }, [fetchPage]),
  );

  const refresh = useCallback(() => {
    setRefreshing(true);
    void fetchPage(1, 'replace');
  }, [fetchPage]);

  const loadMore = useCallback(() => {
    if (!loading && !refreshing && page < lastPage) void fetchPage(page + 1, 'append');
  }, [fetchPage, loading, refreshing, page, lastPage]);

  const changeStatus = useCallback((value: 'all' | TicketStatus) => {
    setLoading(true);
    setStatus(value);
  }, []);

  return { tickets, counts, status, setStatus: changeStatus, loading, refreshing, error, refresh, loadMore };
}
