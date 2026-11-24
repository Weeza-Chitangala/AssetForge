import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import api from '../services/api';
import type { User } from '../types';

interface AuthContextValue {
  user: User | null;
  token: string | null;
  loading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  hasRole: (role: string) => boolean;
  hasPermission: (permission: string) => boolean;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [token, setToken] = useState<string | null>(() => localStorage.getItem('assetforge_token'));
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState<boolean>(() => Boolean(localStorage.getItem('assetforge_token')));

  const clearAuth = useCallback(() => {
    localStorage.removeItem('assetforge_token');
    setToken(null);
    setUser(null);
    setLoading(false);
  }, []);

  const refreshUser = useCallback(async () => {
    const activeToken = localStorage.getItem('assetforge_token');
    if (!activeToken) {
      clearAuth();
      return;
    }

    try {
      const response = await api.get('/auth/me');
      setUser(response.data.data);
      setToken(activeToken);
    } catch {
      clearAuth();
    } finally {
      setLoading(false);
    }
  }, [clearAuth]);

  useEffect(() => {
    if (token) {
      refreshUser();
    } else {
      setLoading(false);
    }
  }, [token, refreshUser]);

  useEffect(() => {
    const handleUnauthorized = () => clearAuth();
    window.addEventListener('assetforge:unauthorized', handleUnauthorized);
    return () => window.removeEventListener('assetforge:unauthorized', handleUnauthorized);
  }, [clearAuth]);

  const login = async (email: string, password: string) => {
    const response = await api.post('/auth/login', { email, password });
    const { token: newToken, user: loggedInUser } = response.data.data;
    localStorage.setItem('assetforge_token', newToken);
    setToken(newToken);
    setUser(loggedInUser);
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // Proceed with local token cleanup even if remote revoke fails
    } finally {
      clearAuth();
    }
  };

  const hasRole = useCallback(
    (role: string): boolean => {
      if (!user?.roles) return false;
      return user.roles.includes(role);
    },
    [user]
  );

  const hasPermission = useCallback(
    (permission: string): boolean => {
      if (!user) return false;
      if (user.roles?.includes('Super Administrator')) return true;
      return Boolean(user.permissions?.includes(permission));
    },
    [user]
  );

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        isAuthenticated: Boolean(token && user),
        login,
        logout,
        refreshUser,
        hasRole,
        hasPermission,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return ctx;
}
