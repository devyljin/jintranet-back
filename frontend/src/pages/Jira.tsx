import { useState, useEffect } from 'react';
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
    attachments: [],
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [createdTicket, setCreatedTicket] = useState<JiraTicket | null>(null);
  const [searchKey, setSearchKey] = useState('');
  const [searchedTicket, setSearchedTicket] = useState<JiraTicket | null>(null);
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [myTickets, setMyTickets] = useState<JiraTicket[]>([]);
  const [loadingMyTickets, setLoadingMyTickets] = useState(false);

  // Charger les tickets de l'utilisateur au montage du composant
  useEffect(() => {
    loadMyTickets();
  }, []);

  const loadMyTickets = async () => {
    setLoadingMyTickets(true);
    try {
      const response = await jiraApi.getMyTickets();
      if (response.success) {
        setMyTickets(response.data.tickets);
        console.log('Mes tickets chargés:', response.data.total);
      }
    } catch (err: any) {
      console.error('Erreur lors du chargement des tickets:', err);
    } finally {
      setLoadingMyTickets(false);
    }
  };

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

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (files) {
      const fileArray = Array.from(files);

      if (fileArray.length > 10) {
        setError('Maximum 10 fichiers autorisés');
        e.target.value = '';
        return;
      }

      setSelectedFiles(fileArray);
      setFormData({
        ...formData,
        attachments: fileArray,
      });
      setError('');
    }
  };

  const removeFile = (index: number) => {
    const newFiles = selectedFiles.filter((_, i) => i !== index);
    setSelectedFiles(newFiles);
    setFormData({
      ...formData,
      attachments: newFiles,
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      console.log('Envoi du ticket avec données:', formData);
      console.log('Fichiers attachés:', selectedFiles.length);

      const response = await jiraApi.createTicket(formData);

      let successMessage = `Ticket ${response.ticket.key} créé avec succès !`;

      if (response.ticket.attachments) {
        successMessage += ` (${response.ticket.attachments} fichier(s) joint(s))`;
      } else if (response.ticket.attachments_count !== undefined) {
        successMessage += ` (${response.ticket.attachments_count} fichier(s) joint(s)`;
        if (response.ticket.attachments_failed && response.ticket.attachments_failed > 0) {
          successMessage += `, ${response.ticket.attachments_failed} échoué(s)`;
        }
        successMessage += ')';
      }

      setSuccess(successMessage);

      // Récupérer les détails du ticket créé
      const ticket = await jiraApi.getTicket(response.ticket.key);
      setCreatedTicket(ticket);

      // Recharger la liste des tickets de l'utilisateur
      loadMyTickets();

      // Réinitialiser le formulaire et les fichiers
      setFormData({
        summary: '',
        description: '',
        projectKey: 'WEB',
        issueType: 'Task',
        attachments: [],
      });
      setSelectedFiles([]);

      // Réinitialiser l'input file
      const fileInput = document.getElementById('attachments') as HTMLInputElement;
      if (fileInput) {
        fileInput.value = '';
      }
    } catch (err: any) {
      console.error('Error creating ticket:', err);
      console.log('Error response:', err.response?.data);
      setError(
        err.response?.data?.message ||
        err.response?.data?.errors ||
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
        err.response?.data?.errors ||
        'Ticket non trouvé'
      );
    } finally {
      setLoading(false);
    }
  };

  const renderTicketCard = (ticket: JiraTicket) => (
    <div className="ticket-card" onClick={() => navigate(`/ticket/${ticket.key}`)} style={{ cursor: 'pointer' }}>
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
        {ticket.attachmentsCount && ticket.attachmentsCount > 0 && (
          <div className="ticket-meta-item">
            <strong>📎 Pièces jointes:</strong> {ticket.attachmentsCount}
          </div>
        )}
        {ticket.commentsCount && ticket.commentsCount > 0 && (
          <div className="ticket-meta-item">
            <strong>💬 Commentaires:</strong> {ticket.commentsCount}
          </div>
        )}
      </div>
      <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
        <button
          onClick={(e) => {
            e.stopPropagation();
            navigate(`/ticket/${ticket.key}`);
          }}
          className="btn-primary"
          style={{ flex: 1 }}
        >
          Voir les détails
        </button>
        <a
          href={ticket.url || `https://agrume.atlassian.net/browse/${ticket.key}`}
          target="_blank"
          rel="noopener noreferrer"
          className="ticket-link"
          onClick={(e) => e.stopPropagation()}
          style={{ flex: 1, textAlign: 'center', textDecoration: 'none' }}
        >
          Voir dans Jira →
        </a>
      </div>
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

          {(error && Array.isArray(error)) ? error.map((e) => <div className="alert alert-error">{e}</div>) :(error && <div className="alert alert-error">{error}</div>) }
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
            <div className="form-group">
              <label htmlFor="attachments">Pièces jointes (optionnel - max 10 fichiers)</label>
              <input
                id="attachments"
                name="attachments"
                type="file"
                onChange={handleFileChange}
                multiple
                accept="image/*,.pdf,.doc,.docx,.txt,.xlsx,.csv"
                disabled={loading}
              />
              <small style={{ color: '#666', fontSize: '12px' }}>
                Formats acceptés : Images, PDF, documents Office (max 10 fichiers)
              </small>

              {selectedFiles.length > 0 && (
                <div style={{
                  marginTop: '10px',
                  padding: '10px',
                  backgroundColor: '#e3f2fd',
                  borderRadius: '4px',
                  border: '1px solid #90caf9'
                }}>
                  <strong>Fichiers sélectionnés ({selectedFiles.length}) :</strong>
                  <ul style={{ margin: '5px 0', paddingLeft: '20px' }}>
                    {selectedFiles.map((file, index) => (
                      <li key={index} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '5px' }}>
                        <span>
                          {file.name} <small>({(file.size / 1024).toFixed(2)} KB)</small>
                        </span>
                        <button
                          type="button"
                          onClick={() => removeFile(index)}
                          style={{
                            background: '#f44336',
                            color: 'white',
                            border: 'none',
                            borderRadius: '3px',
                            padding: '2px 8px',
                            cursor: 'pointer',
                            fontSize: '12px'
                          }}
                        >
                          ✕
                        </button>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
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

        {/*<div className="jira-section">*/}
        {/*  <h2>Rechercher un ticket</h2>*/}

        {/*  <form onSubmit={handleSearch} className="search-form">*/}
        {/*    <div className="search-input-group">*/}
        {/*      <input*/}
        {/*        type="text"*/}
        {/*        value={searchKey}*/}
        {/*        onChange={(e) => setSearchKey(e.target.value)}*/}
        {/*        placeholder="Ex: WEB-123"*/}
        {/*        disabled={loading}*/}
        {/*      />*/}
        {/*      <button type="submit" className="btn-primary" disabled={loading || !searchKey}>*/}
        {/*        {loading ? 'Recherche...' : 'Rechercher'}*/}
        {/*      </button>*/}
        {/*    </div>*/}
        {/*  </form>*/}

        {/*  {searchedTicket && (*/}
        {/*    <div className="searched-ticket">*/}
        {/*      {renderTicketCard(searchedTicket)}*/}
        {/*    </div>*/}
        {/*  )}*/}
        {/*</div>*/}

        <div className="jira-section">
          <h2>Mes tickets ({myTickets.length})</h2>

          {loadingMyTickets && (
            <div style={{ textAlign: 'center', padding: '20px' }}>
              <p>Chargement de vos tickets...</p>
            </div>
          )}

          {!loadingMyTickets && myTickets.length === 0 && (
            <div className="alert alert-info">
              Vous n'avez créé aucun ticket pour le moment.
            </div>
          )}

          {!loadingMyTickets && myTickets.length > 0 && (
            <div className="my-tickets-list">
              {myTickets.map((ticket) => (
                <div key={ticket.key} style={{ marginBottom: '20px' }}>
                  {renderTicketCard(ticket)}
                </div>
              ))}
            </div>
          )}

          {!loadingMyTickets && myTickets.length > 0 && (
            <button
              onClick={loadMyTickets}
              className="btn-secondary"
              style={{ marginTop: '10px' }}
            >
              🔄 Actualiser
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
