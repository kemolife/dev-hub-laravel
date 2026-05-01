import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { api } from '../../lib/api';
import type { User } from '../../types';

const TOKEN_KEY = 'auth_token';

interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
}

export type LoginResult =
  | { twoFactor: false }
  | { twoFactor: true; challengeToken: string };

interface AuthContextValue extends AuthState {
  login(email: string, password: string, deviceName?: string): Promise<LoginResult>;
  register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ): Promise<void>;
  twoFactorChallenge(
    challengeToken: string,
    code: string,
    deviceName?: string,
  ): Promise<void>;
  logout(): Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

interface TokenResponse {
  token: string;
  token_type: string;
  user: User;
}

interface TwoFactorResponse {
  two_factor: true;
  challenge_token: string;
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [state, setState] = useState<AuthState>({
    user: null,
    token: localStorage.getItem(TOKEN_KEY),
    isLoading: true,
  });

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      setState({ user: null, token: null, isLoading: false });
      return;
    }
    api
      .get<User>('/me', token)
      .then((user) => setState({ user, token, isLoading: false }))
      .catch(() => {
        localStorage.removeItem(TOKEN_KEY);
        setState({ user: null, token: null, isLoading: false });
      });
  }, []);

  const setAuth = useCallback((token: string, user: User) => {
    localStorage.setItem(TOKEN_KEY, token);
    setState({ user, token, isLoading: false });
  }, []);

  const login = useCallback(
    async (email: string, password: string, deviceName = 'web'): Promise<LoginResult> => {
      const result = await api.post<TokenResponse | TwoFactorResponse>('/login', {
        email,
        password,
        device_name: deviceName,
      });

      if ('two_factor' in result) {
        return { twoFactor: true, challengeToken: result.challenge_token };
      }

      setAuth(result.token, result.user);
      return { twoFactor: false };
    },
    [setAuth],
  );

  const register = useCallback(
    async (name: string, email: string, password: string, passwordConfirmation: string) => {
      const data = await api.post<TokenResponse>('/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
        device_name: 'web',
      });
      setAuth(data.token, data.user);
    },
    [setAuth],
  );

  const twoFactorChallenge = useCallback(
    async (challengeToken: string, code: string, deviceName = 'web') => {
      const data = await api.post<TokenResponse>('/two-factor-challenge', {
        challenge_token: challengeToken,
        code,
        device_name: deviceName,
      });
      setAuth(data.token, data.user);
    },
    [setAuth],
  );

  const logout = useCallback(async () => {
    const { token } = state;
    localStorage.removeItem(TOKEN_KEY);
    setState({ user: null, token: null, isLoading: false });
    if (token) {
      await api.post('/logout', {}, token).catch(() => {});
    }
  }, [state]);

  return (
    <AuthContext.Provider
      value={{ ...state, login, register, twoFactorChallenge, logout }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
