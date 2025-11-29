import { apiClient } from './client';
import type { User } from '../types';

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface RegisterData {
  username: string;
  password: string;
  name?: string;
  surname?: string;
  birthdate?: string;
}

export interface LoginResponse {
  token: string;
  user?: User;
}

export interface RegisterResponse {
  message: string;
  user: string;
}

export interface MeResponse {
  id: number;
  username: string;
  name?: string;
  surname?: string;
  birthdate?: string;
  roles: string[];
}

export const authApi = {
  login: async (credentials: LoginCredentials): Promise<LoginResponse> => {
    const response = await apiClient.post<{ token: string }>('/login', credentials);
    return response.data;
  },

  register: async (data: RegisterData): Promise<RegisterResponse> => {
    const response = await apiClient.post<RegisterResponse>('/register', data);
    return response.data;
  },

  getMe: async (): Promise<User> => {
    const response = await apiClient.get<MeResponse>('/me');
    return {
      id: response.data.id,
      username: response.data.username,
      roles: response.data.roles,
    };
  },

  logout: () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },
};
