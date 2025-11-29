import { apiClient } from './client';
import type { ChatChannel } from '../types/index';

export const chatApi = {
  // Récupérer tous les channels
  getChannels: async (): Promise<ChatChannel[]> => {
    const response = await apiClient.get('v1/chat/channel');
    return response.data;
  },

  // Créer un nouveau channel
  createChannel: async (data: {
    name: string;
    visibility?: 'public' | 'private';
    parent?: number;
  }): Promise<ChatChannel> => {
    const response = await apiClient.post('v1/chat/channel/new', data);
    return response.data;
  },

  // Récupérer un channel spécifique
  getChannel: async (id: number): Promise<ChatChannel> => {
    const response = await apiClient.get(`v1/chat/channel/${id}`);
    return response.data;
  },
};
