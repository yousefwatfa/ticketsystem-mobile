import { useSyncExternalStore } from 'react';

import { api } from './api';

/** Unread notification count shared by the bell icon and the notifications screen. */
let count = 0;
const listeners = new Set<() => void>();

function set(value: number) {
  count = value;
  listeners.forEach((l) => l());
}

export const unread = {
  set,
  async refresh() {
    try {
      const result = await api<{ unread: number }>('/notifications');
      set(result.unread);
    } catch {
      // offline: keep the last known value
    }
  },
};

export function useUnreadCount() {
  return useSyncExternalStore(
    (listener) => {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
    () => count,
    () => count,
  );
}
