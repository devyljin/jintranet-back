import { apiClient } from './client';

export interface JiraTicket {
  key: string;
  id: string;
  summary: string;
  description: string;
  status: string;
  priority: string;
  issueType: string;
  assignee: string;
  reporter: string;
  created: string;
  updated: string;
}

export interface CreateTicketData {
  summary: string;
  description: string;
  projectKey?: string;
  issueType?: string;
  assigneeAccountId?: string;
  additionalFields?: Record<string, any>;
}

export interface CreateTicketResponse {
  success: boolean;
  ticket: {
    key: string;
    id: string;
    self: string;
  };
  message: string;
}

export interface GetTicketResponse {
  success: boolean;
  ticket: JiraTicket;
}

export interface TestConnectionResponse {
  success: boolean;
  message: string;
}

export const jiraApi = {
  createTicket: async (data: CreateTicketData): Promise<CreateTicketResponse> => {
    const response = await apiClient.post<CreateTicketResponse>('/jira/tickets', data);
    return response.data;
  },

  getTicket: async (ticketKey: string): Promise<JiraTicket> => {
    const response = await apiClient.get<GetTicketResponse>(`/jira/tickets/${ticketKey}`);
    return response.data.ticket;
  },

  testConnection: async (): Promise<boolean> => {
    const response = await apiClient.get<TestConnectionResponse>('/jira/connection');
    return response.data.success;
  },

  getMetadata: async (projectKey: string = 'WEB') => {
    const response = await apiClient.get('/jira/metadata', {
      params: { projectKey }
    });
    return response.data;
  },
};
