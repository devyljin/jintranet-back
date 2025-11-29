# Intragrume Frontend

Frontend React + TypeScript pour l'application Intragrume.

## Stack technique

- **React 18** avec TypeScript
- **Vite** - Build tool et dev server
- **React Router** - Routing côté client
- **TanStack Query** (React Query) - Gestion de l'état serveur et cache
- **Axios** - Client HTTP
- **Context API** - Gestion de l'authentification

## Démarrage rapide

### Installation

```bash
cd frontend
npm install
```

### Développement

```bash
npm run dev
```

L'application sera accessible sur http://localhost:5173

### Build production

```bash
npm run build
```

## Structure du projet

```
src/
├── api/           # Clients API et configuration Axios
├── components/    # Composants réutilisables
├── contexts/      # Contexts React (Auth, etc.)
├── pages/         # Pages/routes de l'application
├── types/         # Types TypeScript
└── App.tsx        # Point d'entrée
```

## Configuration

Le proxy API est configuré pour rediriger `/api/*` vers `https://localhost`.

## Fonctionnalités implémentées

- ✅ Authentification JWT
- ✅ Dashboard
- ✅ Chat (liste et création de channels)
- ✅ Connexion à l'API Symfony

## TODO

- [ ] Page de login
- [ ] Intégration Jira
- [ ] Messages en temps réel
- [ ] Design (Tailwind/MUI)
