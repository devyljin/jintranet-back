# Intragrume

Application web fullstack avec backend Symfony et frontend React.

## Architecture

- **Backend**: Symfony 7 + API Platform + PostgreSQL
- **Frontend**: React 18 + TypeScript + Vite
- **Infrastructure**: Docker + Caddy (HTTPS local)

## Fonctionnalités

### Chat
- Système de channels de discussion
- Support des channels publics/privés
- Hiérarchie de channels (sous-channels)

### Jira Integration
- Connexion à Jira Cloud
- Gestion des tickets

### Authentication
- JWT Authentication
- API sécurisée

## Démarrage rapide

### Prérequis

- Docker & Docker Compose
- Node.js 18+ (pour le frontend)

### Backend (Symfony)

```bash
# Démarrer les conteneurs
docker compose up -d

# Installer les dépendances
docker compose exec php composer install

# Créer la base de données
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate

# L'API est accessible sur https://localhost
```

### Frontend (React)

```bash
cd frontend
npm install
npm run dev

# Le frontend est accessible sur http://localhost:5173
```

### SSL/HTTPS

Pour configurer SSL en local:

```bash
./install-ssl-cert.sh
```

Voir `SSL-TROUBLESHOOTING.md` pour plus de détails.

## API Endpoints

### Chat
- `GET /api/v1/chat/channel` - Liste des channels
- `POST /api/v1/chat/channel/new` - Créer un channel

### Jira
- `/api/v1/jira/*` - Endpoints Jira

## Configuration

### Backend (.env)

```env
DATABASE_URL="postgresql://app:!ChangeMe!@database:5432/app"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JIRA_BASE_URL=https://your-domain.atlassian.net
JIRA_EMAIL=your-email
JIRA_API_TOKEN=your-token
```

### Frontend (frontend/.env)

```env
VITE_API_BASE_URL=/api/v1
```

## Structure du projet

```
.
├── src/                    # Code source Symfony
│   ├── Controller/        # Controllers API
│   ├── Entity/           # Entités Doctrine
│   └── Repository/       # Repositories
├── frontend/              # Application React
│   ├── src/
│   │   ├── api/         # API clients
│   │   ├── pages/       # Pages React
│   │   └── contexts/    # React contexts
│   └── package.json
├── config/               # Configuration Symfony
├── docker-compose.yaml   # Configuration Docker
└── Intragrume/          # Collections Bruno (API testing)
```

## Testing

### API avec Bruno

Ouvrez le dossier `Intragrume/` avec Bruno pour tester l'API.

### Frontend

```bash
cd frontend
npm run test
```

## Documentation

- [Frontend README](./README-FRONTEND.md)
- [SSL Troubleshooting](./SSL-TROUBLESHOOTING.md)

## Développement

### Ajouter un endpoint API

1. Créer un controller dans `src/Controller/`
2. Ajouter les routes avec l'attribut `#[Route]`
3. Tester avec Bruno
4. Créer le client API dans `frontend/src/api/`

### Ajouter une page frontend

1. Créer le composant dans `frontend/src/pages/`
2. Ajouter la route dans `frontend/src/App.tsx`
3. Créer les appels API nécessaires

## Production

### Build frontend

```bash
cd frontend
npm run build
```

Les fichiers statiques seront dans `frontend/dist/`

### Deploy

Le projet est conçu pour être déployé avec Docker.

## Support

Pour les problèmes SSL/HTTPS, consultez `SSL-TROUBLESHOOTING.md`.
