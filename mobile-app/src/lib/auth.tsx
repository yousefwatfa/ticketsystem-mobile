import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { Platform } from 'react-native';

import { api, loadSession, setServerUrl, setToken, setUnauthorizedHandler } from './api';
import { registerForPush, unregisterPush, type PushStatus } from './push';
import type { User } from './types';

type AuthState = {
  user: User | null;
  /** false until the stored session has been checked */
  ready: boolean;
  signIn: (username: string, password: string, server: string) => Promise<void>;
  signOut: () => Promise<void>;
  refreshUser: () => Promise<void>;
  /** null until registration has been attempted */
  push: PushStatus | null;
  enablePush: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);
  const [push, setPush] = useState<PushStatus | null>(null);

  const enablePush = useCallback(async () => {
    setPush(await registerForPush());
  }, []);

  const clearSession = useCallback(async () => {
    await setToken(null);
    setUser(null);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      void clearSession();
    });

    (async () => {
      const { token } = await loadSession();
      if (token) {
        try {
          const me = await api<{ data: User }>('/me');
          setUser(me.data);
          void registerForPush().then(setPush);
        } catch (error) {
          // offline: keep the token and retry later; 401 is handled by the unauthorized handler
          if (!(error as { offline?: boolean }).offline) {
            await setToken(null);
          }
        }
      }
      setReady(true);
    })();
  }, [clearSession]);

  const signIn = useCallback(async (username: string, password: string, server: string) => {
    await setServerUrl(server);
    const result = await api<{ token: string; user: User }>('/auth/login', {
      method: 'POST',
      body: { username: username.trim(), password, device_name: `app-${Platform.OS}` },
    });
    await setToken(result.token);
    setUser(result.user);
    void registerForPush().then(setPush);
  }, []);

  const signOut = useCallback(async () => {
    await unregisterPush();
    setPush(null);
    try {
      await api('/auth/logout', { method: 'POST' });
    } catch {
      // still sign out locally when the server is unreachable
    }
    await clearSession();
  }, [clearSession]);

  const refreshUser = useCallback(async () => {
    const me = await api<{ data: User }>('/me');
    setUser(me.data);
  }, []);

  const value = useMemo(
    () => ({ user, ready, signIn, signOut, refreshUser, push, enablePush }),
    [user, ready, signIn, signOut, refreshUser, push, enablePush],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
