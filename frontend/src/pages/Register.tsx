import { useState, FormEvent } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { authApi } from '../api/auth';
import '../styles/Auth.css';

export default function Register() {
  const [formData, setFormData] = useState({
    username: '',
    password: '',
    confirmPassword: '',
    name: '',
    surname: '',
    birthdate: '',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');

    // Validation
    if (formData.password !== formData.confirmPassword) {
      setError('Les mots de passe ne correspondent pas');
      return;
    }

    if (formData.password.length < 6) {
      setError('Le mot de passe doit contenir au moins 6 caractères');
      return;
    }

    setLoading(true);

    try {
      await authApi.register({
        username: formData.username,
        password: formData.password,
        name: formData.name || undefined,
        surname: formData.surname || undefined,
        birthdate: formData.birthdate || undefined,
      });

      // Rediriger vers la page de connexion
      navigate('/login', {
        state: { message: 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.' }
      });
    } catch (err: any) {
      console.error('Registration error:', err);
      setError(
        err.response?.data?.message ||
        'Une erreur est survenue lors de la création du compte.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Créer un compte</h1>
          <p>Rejoignez Intragrume</p>
        </div>

        {error && (
          <div className="auth-error">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="username">Nom d'utilisateur *</label>
            <input
              id="username"
              name="username"
              type="text"
              value={formData.username}
              onChange={handleChange}
              required
              autoFocus
              placeholder="Choisissez un nom d'utilisateur"
              disabled={loading}
            />
          </div>

          <div className="form-row">
            <div className="form-group">
              <label htmlFor="name">Prénom</label>
              <input
                id="name"
                name="name"
                type="text"
                value={formData.name}
                onChange={handleChange}
                placeholder="Votre prénom"
                disabled={loading}
              />
            </div>

            <div className="form-group">
              <label htmlFor="surname">Nom</label>
              <input
                id="surname"
                name="surname"
                type="text"
                value={formData.surname}
                onChange={handleChange}
                placeholder="Votre nom"
                disabled={loading}
              />
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="birthdate">Date de naissance</label>
            <input
              id="birthdate"
              name="birthdate"
              type="date"
              value={formData.birthdate}
              onChange={handleChange}
              disabled={loading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Mot de passe *</label>
            <input
              id="password"
              name="password"
              type="password"
              value={formData.password}
              onChange={handleChange}
              required
              placeholder="Minimum 6 caractères"
              disabled={loading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="confirmPassword">Confirmer le mot de passe *</label>
            <input
              id="confirmPassword"
              name="confirmPassword"
              type="password"
              value={formData.confirmPassword}
              onChange={handleChange}
              required
              placeholder="Confirmez votre mot de passe"
              disabled={loading}
            />
          </div>

          <button
            type="submit"
            className="auth-button"
            disabled={loading}
          >
            {loading ? 'Création...' : 'Créer mon compte'}
          </button>
        </form>

        <div className="auth-footer">
          <p>
            Déjà un compte ?{' '}
            <Link to="/login">Se connecter</Link>
          </p>
        </div>
      </div>
    </div>
  );
}
