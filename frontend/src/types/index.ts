export interface User {
  id: number;
  username: string;
  email?: string;
  roles: string[];
}

export interface AuthResponse {
  token: string;
  refresh_token?: string;
  user: User;
}

export interface ChatChannel {
  id: number;
  name: string;
  visibility: 'public' | 'private';
  status: 'online' | 'offline';
  parentChannel?: ChatChannel;
  createdAt: string;
  updatedAt: string;
}

export interface ChatMessage {
  id: number;
  content: string;
  author: User;
  channel: ChatChannel;
  createdAt: string;
}

export interface JiraTicket {
  id: string;
  key: string;
  summary: string;
  description?: string;
  status: string;
  assignee?: string;
  created: string;
}

export type {};
