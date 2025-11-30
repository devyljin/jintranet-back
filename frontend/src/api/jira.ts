import { apiClient } from './client';

export interface JiraAttachment {
  id: string;
  filename: string;
  size: number;
  mimeType: string;
  created: string;
  author: string;
  content: string; // URL de téléchargement
  thumbnail?: string; // URL de la miniature
}

export interface JiraComment {
  id: string;
  author: string;
  authorEmail?: string;
  body: string;
  created: string;
  updated: string;
}

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
  url?: string;
  attachments?: JiraAttachment[];
  comments?: JiraComment[];
  attachmentsCount?: number;
  commentsCount?: number;
}

export interface CreateTicketData {
  summary: string;
  description: string;
  projectKey?: string;
  issueType?: string;
  assigneeAccountId?: string;
  additionalFields?: Record<string, any>;
  attachments?: File[];
}

export interface CreateTicketResponse {
  success: boolean;
  ticket: {
    key: string;
    id: string;
    url: string;
    attachments?: number;
    attachments_count?: number;
    attachments_failed?: number;
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
    // Créer un FormData pour supporter l'upload de fichiers
    const formData = new FormData();

    formData.append('summary', data.summary);
    formData.append('description', data.description);

    if (data.projectKey) {
      formData.append('project_key', data.projectKey);
    }

    if (data.issueType) {
      formData.append('issue_type', data.issueType);
    }

    // Ajouter les fichiers s'il y en a
    if (data.attachments && data.attachments.length > 0) {
      data.attachments.forEach((file) => {
        formData.append('attachments[]', file);
      });
    }

    const response = await apiClient.post<CreateTicketResponse>('/v1/jira/tickets', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  getTicket: async (ticketKey: string): Promise<JiraTicket> => {
    const response = await apiClient.get<GetTicketResponse>(`/v1/jira/tickets/${ticketKey}`);
    return response.data.ticket;
  },

  testConnection: async (): Promise<boolean> => {
    const response = await apiClient.get<TestConnectionResponse>('/v1/jira/connection');
    return response.data.success;
  },

  getMetadata: async (projectKey: string = 'WEB') => {
    const response = await apiClient.get('/v1/jira/metadata', {
      params: { projectKey }
    });
    return response.data;
  },

  getMyTickets: async (): Promise<{ success: boolean; data: { tickets: JiraTicket[]; total: number; errors: any[] } }> => {
    const response = await apiClient.get('/v1/jira/my-tickets');
    return response.data;
  },
};
