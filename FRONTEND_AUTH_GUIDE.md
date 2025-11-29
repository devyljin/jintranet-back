# Guide d'authentification Frontend

## Vue d'ensemble

L'application Intragrume dispose maintenant d'un système d'authentification complet avec JWT :

- **Page de connexion** : `/login`
- **Page d'inscription** : `/register`
- **Dashboard** : `/dashboard` (protégé)
- **Chat** : `/chat` (protégé)

## Démarrage rapide

### 1. Démarrer l'environnement

```bash
# Démarrer le backend et le frontend
./start-dev.sh
```

L'application sera accessible sur :
- Frontend : http://localhost:5173
- Backend API : https://localhost/api

### 2. Créer un compte

1. Ouvrez http://localhost:5173
2. Vous serez automatiquement redirigé vers `/login`
3. Cliquez sur "Créer un compte"
4. Remplissez le formulaire :
   - **Nom d'utilisateur** (requis)
   - **Mot de passe** (requis, minimum 6 caractères)
   - Prénom (optionnel)
   - Nom (optionnel)
   - Date de naissance (optionnel)
5. Cliquez sur "Créer mon compte"
6. Vous serez redirigé vers la page de connexion avec un message de succès

### 3. Se connecter

1. Entrez votre nom d'utilisateur et mot de passe
2. Cliquez sur "Se connecter"
3. Vous serez redirigé vers le Dashboard

### 4. Navigation

Une fois connecté, vous pouvez :
- Accéder au Dashboard
- Naviguer vers le Chat
- Voir votre nom d'utilisateur en haut à droite
- Vous déconnecter avec le bouton "Déconnexion"

## Architecture technique

### Structure des fichiers

```
frontend/src/
├── api/
│   ├── auth.ts          # Service d'authentification
│   └── client.ts        # Client Axios avec intercepteurs JWT
├── contexts/
│   └── AuthContext.tsx  # Contexte React pour l'authentification
├── pages/
│   ├── Login.tsx        # Page de connexion
│   ├── Register.tsx     # Page d'inscription
│   └── Dashboard.tsx    # Dashboard (protégé)
├── styles/
│   └── Auth.css         # Styles pour les pages auth
└── App.tsx              # Routes et ProtectedRoute
```

### Flux d'authentification

1. **Inscription** :
   ```
   Register.tsx → authApi.register() → Backend /api/register → Redirection vers Login
   ```

2. **Connexion** :
   ```
   Login.tsx → authApi.login() → Backend /api/login → Récupération du token
   → authApi.getMe() → Backend /api/me → Stockage dans AuthContext
   → localStorage → Redirection vers Dashboard
   ```

3. **Routes protégées** :
   ```
   ProtectedRoute vérifie isAuthenticated
   → Si non authentifié : Redirection vers /login
   → Si authentifié : Affichage du composant
   ```

4. **Requêtes API** :
   ```
   Toutes les requêtes passent par apiClient (axios)
   → Intercepteur request : Ajoute "Authorization: Bearer {token}"
   → Intercepteur response : Gère les erreurs 401 (token expiré)
   ```

### Gestion du token

Le token JWT est stocké dans :
- **localStorage** : `token` et `user`
- **AuthContext** : État React global
- **Axios intercepteurs** : Ajout automatique aux headers

En cas de token invalide ou expiré :
- L'intercepteur Axios détecte le 401
- Supprime le token du localStorage
- Redirige vers `/login`

## Sécurité

### Mesures de sécurité en place

1. **HTTPS** : Le backend utilise HTTPS (certificat auto-signé en dev)
2. **JWT** : Tokens signés avec RSA (clés privée/publique)
3. **CORS** : Configuration stricte via nelmio/cors-bundle
4. **Protection des routes** : Toutes les routes `/api/*` nécessitent un token
5. **Validation** : Mot de passe minimum 6 caractères
6. **Expiration** : Les tokens expirent automatiquement

### Bonnes pratiques

- Ne jamais stocker le mot de passe en clair
- Toujours utiliser HTTPS en production
- Configurer une durée d'expiration courte pour les tokens
- Implémenter un refresh token pour les sessions longues
- Valider les données côté serveur ET client

## Personnalisation

### Modifier la durée de vie du token

Éditez `config/packages/lexik_jwt_authentication.yaml` :

```yaml
lexik_jwt_authentication:
    token_ttl: 3600  # 1 heure en secondes
```

### Ajouter des champs au formulaire d'inscription

1. Ajoutez le champ dans `frontend/src/pages/Register.tsx`
2. Mettez à jour l'interface `RegisterData` dans `frontend/src/api/auth.ts`
3. Ajoutez la propriété dans l'entité `User` côté backend

### Personnaliser les styles

Modifiez `frontend/src/styles/Auth.css` pour changer :
- Couleurs du gradient
- Styles des boutons
- Animation des formulaires
- Messages d'erreur/succès

## Troubleshooting

### Le token n'est pas accepté par l'API

1. Vérifiez que le `.env` frontend utilise `/api` et non `/api/v1`
2. Videz le cache du navigateur et localStorage
3. Vérifiez les logs du backend : `docker compose logs php`

### Erreur CORS

1. Vérifiez `config/packages/nelmio_cors.yaml`
2. Assurez-vous que l'origine est autorisée dans `.env` : `CORS_ALLOW_ORIGIN`

### Token expiré constamment

1. Vérifiez la date/heure du serveur
2. Augmentez `token_ttl` dans la config JWT
3. Implémentez un système de refresh token

### Les styles ne s'appliquent pas

1. Vérifiez que `Auth.css` est bien importé dans les pages
2. Redémarrez le serveur Vite
3. Videz le cache du navigateur

## Pour aller plus loin

### Fonctionnalités à ajouter

- [ ] Refresh token automatique
- [ ] Mot de passe oublié
- [ ] Vérification d'email
- [ ] Authentification à deux facteurs
- [ ] OAuth (Google, GitHub, etc.)
- [ ] Limitation du taux de requêtes
- [ ] Logs d'activité
- [ ] Gestion des sessions actives
- [ ] Blacklist de tokens
- [ ] Remember me

### Ressources

- [Symfony Security](https://symfony.com/doc/current/security.html)
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle)
- [React Context API](https://react.dev/reference/react/useContext)
- [Axios Interceptors](https://axios-http.com/docs/interceptors)
