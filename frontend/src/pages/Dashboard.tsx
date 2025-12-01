import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function Dashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <div style={{ padding: '2rem' }}>
      <header style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '2rem' , width:"95vw"}}>
        <h1>Intragrume Dashboard</h1>
        <div>
          <span>Bienvenue, {user?.username}</span>
          <button onClick={handleLogout} style={{ marginLeft: '1rem', cursor: 'pointer' }}>
            Déconnexion
          </button>
        </div>
      </header>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '1rem' }}>
        <div style={{ border: '1px solid #ccc', padding: '1rem', borderRadius: '8px' }}>
          <h2>Chat</h2>
          <p>Accédez aux channels de discussion</p>
          <a href="/chat">Ouvrir le chat →</a>
        </div>

        <div style={{ border: '1px solid #ccc', padding: '1rem', borderRadius: '8px' }}>
          <h2>Jira</h2>
          <p>Gérez vos tickets Jira</p>
          <a href="/jira">Ouvrir Jira →</a>
        </div>
      </div>
    </div>
  );
}
