# TODO - Intragrume

## 🔴 Priorité Haute

### Authentication
- [ ] Créer une vraie page de Login
- [ ] Implémenter l'endpoint `/api/v1/auth/login` dans Symfony
- [ ] Configurer LexikJWTAuthenticationBundle correctement
- [ ] Ajouter le système de refresh token
- [ ] Ajouter la route de logout
- [ ] Protéger les routes API avec `security.yaml`

### Chat
- [ ] Implémenter les messages (création, liste)
- [ ] Ajouter la pagination pour les messages
- [ ] Intégration Mercure pour les messages en temps réel
- [ ] Ajouter la possibilité de modifier un channel
- [ ] Ajouter la possibilité de supprimer un channel
- [ ] Gérer les permissions (qui peut créer/modifier/supprimer)

## 🟡 Priorité Moyenne

### Frontend
- [ ] Ajouter Tailwind CSS ou Material-UI pour le design
- [ ] Créer un composant Layout avec navigation
- [ ] Améliorer la page Dashboard
- [ ] Ajouter un système de notifications toast
- [ ] Créer une page de profil utilisateur
- [ ] Ajouter la gestion des erreurs globale (Error Boundary)
- [ ] Ajouter un loader global
- [ ] Implémenter le dark mode

### Backend
- [ ] Ajouter la validation des données (Symfony Validator)
- [ ] Implémenter les SerializationGroups proprement
- [ ] Ajouter la documentation API (OpenAPI/Swagger)
- [ ] Créer plus de fixtures pour le développement
- [ ] Ajouter les tests unitaires
- [ ] Ajouter les tests d'intégration

### Jira Integration
- [ ] Créer l'interface frontend pour Jira
- [ ] Implémenter la création de tickets depuis le frontend
- [ ] Afficher la liste des tickets
- [ ] Synchronisation bidirectionnelle Jira <-> Intragrume
- [ ] Webhooks Jira pour les mises à jour en temps réel

## 🟢 Priorité Basse

### DevOps
- [ ] Ajouter Docker Compose pour la production
- [ ] Configurer GitHub Actions (CI/CD)
- [ ] Ajouter les tests automatisés dans la CI
- [ ] Créer un Dockerfile optimisé pour la production
- [ ] Configurer le build automatique du frontend

### Tests
- [ ] Configurer Vitest pour le frontend
- [ ] Ajouter React Testing Library
- [ ] Créer des tests pour les composants critiques
- [ ] Tests E2E avec Playwright ou Cypress

### Performance
- [ ] Code splitting dans React (lazy loading)
- [ ] Optimiser les images
- [ ] Ajouter le service worker (PWA)
- [ ] Cache HTTP côté backend
- [ ] Optimisation des requêtes SQL (index, etc.)

### Fonctionnalités additionnelles
- [ ] Système de recherche global
- [ ] Upload de fichiers (avatars, attachments)
- [ ] Système de tags/labels
- [ ] Statistiques et analytics
- [ ] Export de données (CSV, PDF)

## 🔵 Nice to Have

- [ ] Internationalisation (i18n)
- [ ] Mode hors-ligne (PWA)
- [ ] Application mobile (React Native)
- [ ] Intégration Slack/Discord
- [ ] Intégration GitHub/GitLab
- [ ] Système de plugins
- [ ] Thèmes personnalisables
- [ ] Raccourcis clavier

## 🐛 Bugs connus

- [ ] L'authentification est simulée (dev-token)
- [ ] Pas de gestion d'erreur réseau robuste
- [ ] Messages d'erreur peu explicites

## 📝 Documentation à compléter

- [ ] Guide de déploiement en production
- [ ] Documentation de l'API (OpenAPI)
- [ ] Guide de contribution
- [ ] Changelog
- [ ] Architecture Decision Records (ADR)

## ✅ Fait

- [x] Setup projet React + TypeScript
- [x] Configuration Vite
- [x] Installation React Router
- [x] Installation TanStack Query
- [x] Configuration Axios
- [x] AuthContext
- [x] Page Dashboard
- [x] Page Chat avec liste des channels
- [x] Création de channels depuis le frontend
- [x] Configuration CORS
- [x] Proxy API Vite
- [x] Documentation (README, DEVELOPMENT.md)
- [x] Script de démarrage (start-dev.sh)
