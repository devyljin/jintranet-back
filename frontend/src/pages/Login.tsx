import { useState, FormEvent, useEffect } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { authApi } from '../api/auth';
import '../styles/Auth.css';

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    if (location.state?.message) {
      setSuccess(location.state.message);
    }
  }, [location]);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await authApi.login({ username, password });

      // Stocker temporairement le token pour que l'intercepteur puisse l'utiliser
      localStorage.setItem('token', response.token);

      // Récupérer les infos utilisateur (maintenant que le token est stocké)
      const user = await authApi.getMe();

      // Stocker le token et l'utilisateur dans le contexte
      login(response.token, user);

      // Rediriger vers le dashboard
      navigate('/dashboard');
    } catch (err: any) {
      console.error('Login error:', err);
      // Nettoyer le token en cas d'erreur
      localStorage.removeItem('token');
      setError(
        err.response?.data?.message ||
        'Identifiants incorrects. Veuillez réessayer.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Connexion</h1>
          <p>Connectez-vous à votre compte Intragrume</p>
        </div>

        {error && (
          <div className="auth-error">
            {error}
          </div>
        )}

        {success && (
          <div className="auth-success">
            {success}
          </div>
        )}

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="username">Nom d'utilisateur</label>
            <input
              id="username"
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
              autoFocus
              placeholder="Entrez votre nom d'utilisateur"
              disabled={loading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Mot de passe</label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              placeholder="Entrez votre mot de passe"
              disabled={loading}
            />
          </div>

          <button
            type="submit"
            className="auth-button"
            disabled={loading}
          >
            {loading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>

        <div className="auth-footer">
          <p>
            Pas encore de compte ?{' '}
            <Link to="/register">Créer un compte</Link>
          </p>
        </div>
      </div>
    </div>
  );
}
