import React, { useCallback, useEffect, useState } from 'react';
import api from '../services/api';
import type { User } from '../types';
import { AuthContext } from './useAuth';

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
    if (!token || user) {
      return;
    }

    let cancelled = false;

    api
      .get('/auth/me')
      .then((response) => {
        if (cancelled) return;
        setUser(response.data.data);
      })
      .catch(() => {
        if (cancelled) return;
        localStorage.removeItem('assetforge_token');
        setToken(null);
        setUser(null);
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [token, user]);

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
    setLoading(false);
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
      if (user.roles?.includes('Super User')) return true;
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
