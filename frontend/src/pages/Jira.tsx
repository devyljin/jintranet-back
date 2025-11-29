import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { jiraApi, type CreateTicketData, type JiraTicket } from '../api/jira';
import '../styles/Jira.css';

export default function Jira() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const [formData, setFormData] = useState<CreateTicketData>({
    summary: '',
    description: '',
    projectKey: 'WEB',
    issueType: 'Task',
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [createdTicket, setCreatedTicket] = useState<JiraTicket | null>(null);
  const [searchKey, setSearchKey] = useState('');
  const [searchedTicket, setSearchedTicket] = useState<JiraTicket | null>(null);

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const response = await jiraApi.createTicket(formData);

      setSuccess(`Ticket ${response.ticket.key} créé avec succès !`);

      // Récupérer les détails du ticket créé
      const ticket = await jiraApi.getTicket(response.ticket.key);
      setCreatedTicket(ticket);

      // Réinitialiser le formulaire
      setFormData({
        summary: '',
        description: '',
        projectKey: 'WEB',
        issueType: 'Task',
      });
    } catch (err: any) {
      console.error('Error creating ticket:', err);
      setError(
        err.response?.data?.message ||
        err.response?.data?.error ||
        'Erreur lors de la création du ticket'
      );
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSearchedTicket(null);
    setLoading(true);

    try {
      const ticket = await jiraApi.getTicket(searchKey);
      setSearchedTicket(ticket);
    } catch (err: any) {
      console.error('Error fetching ticket:', err);
      setError(
        err.response?.data?.message ||
        err.response?.data?.error ||
        'Ticket non trouvé'
      );
    } finally {
      setLoading(false);
    }
  };

  const renderTicketCard = (ticket: JiraTicket) => (
    <div className="ticket-card">
      <div className="ticket-header">
        <h3>{ticket.key}</h3>
        <span className={`ticket-status status-${ticket.status.toLowerCase()}`}>
          {ticket.status}
        </span>
      </div>
      <h4>{ticket.summary}</h4>
      <p className="ticket-description">{ticket.description}</p>
      <div className="ticket-meta">
        <div className="ticket-meta-item">
          <strong>Type:</strong> {ticket.issueType}
        </div>
        <div className="ticket-meta-item">
          <strong>Priorité:</strong> {ticket.priority}
        </div>
        <div className="ticket-meta-item">
          <strong>Assigné à:</strong> {ticket.assignee}
        </div>
        <div className="ticket-meta-item">
          <strong>Reporter:</strong> {ticket.reporter}
        </div>
        <div className="ticket-meta-item">
          <strong>Créé le:</strong> {new Date(ticket.created).toLocaleDateString('fr-FR')}
        </div>
      </div>
      <a
        href={`https://agrume.atlassian.net/browse/${ticket.key}`}
        target="_blank"
        rel="noopener noreferrer"
        className="ticket-link"
      >
        Voir dans Jira →
      </a>
    </div>
  );

  return (
    <div className="jira-container">
      <header className="jira-header">
        <div>
          <h1>Jira Ticket Manager</h1>
          <p>Créez et gérez vos tickets Jira</p>
        </div>
        <div className="header-actions">
          <button onClick={() => navigate('/dashboard')} className="btn-secondary">
            ← Dashboard
          </button>
          <span className="user-info">Connecté: {user?.username}</span>
          <button onClick={handleLogout} className="btn-logout">
            Déconnexion
          </button>
        </div>
      </header>

      <div className="jira-content">
        <div className="jira-section">
          <h2>Créer un ticket</h2>

          {error && <div className="alert alert-error">{error}</div>}
          {success && <div className="alert alert-success">{success}</div>}

          <form onSubmit={handleSubmit} className="jira-form">
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="projectKey">Projet</label>
                <select
                  id="projectKey"
                  name="projectKey"
                  value={formData.projectKey}
                  onChange={handleChange}
                  disabled={loading}
                >
                  <option value="WEB">WEB</option>
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="issueType">Type de ticket</label>
                <select
                  id="issueType"
                  name="issueType"
                  value={formData.issueType}
                  onChange={handleChange}
                  disabled={loading}
                >
                  <option value="Task">Task</option>
                  <option value="Bug">Bug</option>
                  <option value="Story">Story</option>
                </select>
              </div>
            </div>

            <div className="form-group">
              <label htmlFor="summary">Résumé *</label>
              <input
                id="summary"
                name="summary"
                type="text"
                value={formData.summary}
                onChange={handleChange}
                required
                placeholder="Ex: Corriger le bug de connexion"
                disabled={loading}
              />
            </div>

            <div className="form-group">
              <label htmlFor="description">Description *</label>
              <textarea
                id="description"
                name="description"
                value={formData.description}
                onChange={handleChange}
                required
                rows={6}
                placeholder="Décrivez le ticket en détail..."
                disabled={loading}
              />
            </div>

            <button type="submit" className="btn-primary" disabled={loading}>
              {loading ? 'Création...' : 'Créer le ticket'}
            </button>
          </form>

          {createdTicket && (
            <div className="created-ticket">
              <h3>Ticket créé</h3>
              {renderTicketCard(createdTicket)}
            </div>
          )}
        </div>

        <div className="jira-section">
          <h2>Rechercher un ticket</h2>

          <form onSubmit={handleSearch} className="search-form">
            <div className="search-input-group">
              <input
                type="text"
                value={searchKey}
                onChange={(e) => setSearchKey(e.target.value)}
                placeholder="Ex: WEB-123"
                disabled={loading}
              />
              <button type="submit" className="btn-primary" disabled={loading || !searchKey}>
                {loading ? 'Recherche...' : 'Rechercher'}
              </button>
            </div>
          </form>

          {searchedTicket && (
            <div className="searched-ticket">
              {renderTicketCard(searchedTicket)}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
