import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { jiraApi, type JiraTicket } from '../api/jira';
import '../styles/TicketDetail.css';

export default function TicketDetail() {
  const { ticketKey } = useParams<{ ticketKey: string }>();
  const navigate = useNavigate();

  const [ticket, setTicket] = useState<JiraTicket | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (ticketKey) {
      loadTicket(ticketKey);
    }
  }, [ticketKey]);

  const loadTicket = async (key: string) => {
    setLoading(true);
    setError('');

    try {
      const data = await jiraApi.getTicket(key);
      setTicket(data);
    } catch (err: any) {
      console.error('Erreur lors du chargement du ticket:', err);
      setError(err.response?.data?.message || 'Erreur lors du chargement du ticket');
    } finally {
      setLoading(false);
    }
  };

  const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  };

  const getFileIcon = (mimeType: string): string => {
    if (mimeType.startsWith('image/')) return '🖼️';
    if (mimeType.includes('pdf')) return '📄';
    if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
    if (mimeType.includes('excel') || mimeType.includes('sheet')) return '📊';
    if (mimeType.includes('zip') || mimeType.includes('compressed')) return '📦';
    return '📎';
  };

  if (loading) {
    return (
      <div className="ticket-detail-container">
        <div style={{ textAlign: 'center', padding: '50px' }}>
          <p>Chargement du ticket...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="ticket-detail-container">
        <div className="alert alert-error">
          <h3>❌ Erreur</h3>
          <p>{error}</p>
          <button onClick={() => navigate('/jira')} className="btn-primary">
            ← Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  if (!ticket) {
    return (
      <div className="ticket-detail-container">
        <div className="alert alert-warning">
          <p>Ticket non trouvé</p>
          <button onClick={() => navigate('/jira')} className="btn-primary">
            ← Retour à la liste
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="ticket-detail-container">
      {/* En-tête */}
      <header className="ticket-detail-header">
        <div>
          <button onClick={() => navigate('/jira')} className="btn-back">
            ← Retour
          </button>
          <h1 className="ticket-key">{ticket.key}</h1>
          <span className={`ticket-status status-${ticket.status.toLowerCase().replace(/\s+/g, '-')}`}>
            {ticket.status}
          </span>
        </div>
        <a
          href={ticket.url}
          target="_blank"
          rel="noopener noreferrer"
          className="btn-primary"
        >
          Voir dans Jira →
        </a>
      </header>

      {/* Contenu principal */}
      <div className="ticket-detail-content">
        {/* Informations principales */}
        <section className="ticket-info-section">
          <h2>{ticket.summary}</h2>

          <div className="ticket-metadata">
            <div className="metadata-item">
              <span className="label">Type:</span>
              <span className="badge badge-type">{ticket.issueType}</span>
            </div>
            <div className="metadata-item">
              <span className="label">Priorité:</span>
              <span className={`badge badge-priority priority-${ticket.priority.toLowerCase()}`}>
                {ticket.priority}
              </span>
            </div>
            <div className="metadata-item">
              <span className="label">Assigné à:</span>
              <span>{ticket.assignee}</span>
            </div>
            <div className="metadata-item">
              <span className="label">Reporter:</span>
              <span>{ticket.reporter}</span>
            </div>
            <div className="metadata-item">
              <span className="label">Créé le:</span>
              <span>{new Date(ticket.created).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              })}</span>
            </div>
            <div className="metadata-item">
              <span className="label">Mis à jour:</span>
              <span>{new Date(ticket.updated).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              })}</span>
            </div>
          </div>

          <div className="ticket-description">
            <h3>Description</h3>
            <p>{ticket.description || 'Aucune description'}</p>
          </div>
        </section>

        {/* Pièces jointes */}
        {ticket.attachments && ticket.attachments.length > 0 && (
          <section className="attachments-section">
            <h3>📎 Pièces jointes ({ticket.attachmentsCount})</h3>
            <div className="attachments-list">
              {ticket.attachments.map((attachment) => (
                <div key={attachment.id} className="attachment-card">
                  {attachment.thumbnail && attachment.mimeType.startsWith('image/') ? (
                    <div className="attachment-thumbnail">
                      <img src={attachment.thumbnail} alt={attachment.filename} />
                    </div>
                  ) : (
                    <div className="attachment-icon">
                      <span className="file-icon">{getFileIcon(attachment.mimeType)}</span>
                    </div>
                  )}

                  <div className="attachment-info">
                    <h4 className="attachment-filename">{attachment.filename}</h4>
                    <div className="attachment-meta">
                      <span className="file-size">{formatFileSize(attachment.size)}</span>
                      <span className="separator">•</span>
                      <span className="file-author">{attachment.author}</span>
                      <span className="separator">•</span>
                      <span className="file-date">
                        {new Date(attachment.created).toLocaleDateString('fr-FR')}
                      </span>
                    </div>
                  </div>

                  <a
                    href={attachment.content}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn-download"
                    download
                  >
                    ⬇️ Télécharger
                  </a>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Commentaires */}
        {ticket.comments && ticket.comments.length > 0 && (
          <section className="comments-section">
            <h3>💬 Commentaires ({ticket.commentsCount})</h3>
            <div className="comments-list">
              {ticket.comments.map((comment) => (
                <div key={comment.id} className="comment-card">
                  <div className="comment-header">
                    <div className="comment-author">
                      <div className="author-avatar">
                        {comment.author.charAt(0).toUpperCase()}
                      </div>
                      <div>
                        <strong>{comment.author}</strong>
                        {comment.authorEmail && (
                          <span className="author-email">{comment.authorEmail}</span>
                        )}
                      </div>
                    </div>
                    <div className="comment-date">
                      {new Date(comment.created).toLocaleDateString('fr-FR', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                      })}
                      {comment.updated !== comment.created && (
                        <span className="edited"> (modifié)</span>
                      )}
                    </div>
                  </div>
                  <div className="comment-body">
                    <p>{comment.body}</p>
                  </div>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Sections vides */}
        {(!ticket.attachments || ticket.attachments.length === 0) && (
          <section className="empty-section">
            <p>📎 Aucune pièce jointe</p>
          </section>
        )}

        {(!ticket.comments || ticket.comments.length === 0) && (
          <section className="empty-section">
            <p>💬 Aucun commentaire</p>
          </section>
        )}
      </div>
    </div>
  );
}
