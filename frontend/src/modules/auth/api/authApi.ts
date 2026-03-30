import { api } from '@/core/api/client';

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface User {
  id: number;
  name: string;
  email: string;
  permissions: string[];
  roles: string[];
  company_id: number;
  company_name: string;
}

export const authApi = {
  login: (credentials: LoginCredentials) =>
    api.post<User>('/auth/login', credentials),

  logout: () =>
    api.post('/auth/logout'),

  me: () =>
    api.get<User>('/auth/me'),
};
