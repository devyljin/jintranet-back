# Quick Start - Intragrume

## 🚀 Démarrage ultra-rapide

```bash
# Démarrer tout (backend + frontend)
./start-dev.sh
```

Ouvrez http://localhost:5173 dans votre navigateur.

## 🛠 Commandes utiles

### Backend (Symfony)

```bash
# Démarrer
docker compose up -d

# Arrêter
docker compose down

# Voir les logs
docker compose logs -f php

# Shell dans le conteneur
docker compose exec php bash

# Console Symfony
docker compose exec php bin/console [commande]

# Base de données
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:fixtures:load

# Cache
docker compose exec php bin/console cache:clear
```

### Frontend (React)

```bash
cd frontend

# Installer les dépendances
npm install

# Démarrer le serveur de dev
npm run dev

# Build pour la production
npm run build

# Preview du build
npm run preview

# Linter
npm run lint
```

### Git

```bash
# Voir le statut
git status

# Ajouter les changements
git add .

# Commit
git commit -m "feat: description"

# Push
git push
```

## 📝 Convention de commits

Utilisez le format suivant:

```
feat: Ajouter une fonctionnalité
fix: Corriger un bug
docs: Mise à jour documentation
style: Changement de style (formatage)
refactor: Refactoring du code
test: Ajouter/modifier des tests
chore: Tâches de maintenance
```

## 🔗 URLs importantes

| Service | URL | Description |
|---------|-----|-------------|
| Frontend | http://localhost:5173 | Interface React |
| Backend | https://localhost | API Symfony |
| API Chat | https://localhost/api/v1/chat/channel | Endpoints chat |
| Database | localhost:5432 | PostgreSQL |

## 🐛 Dépannage rapide

### Le frontend ne se connecte pas au backend

1. Vérifier que le backend est démarré: `docker compose ps`
2. Vérifier les logs: `docker compose logs php`
3. Tester l'API manuellement: `curl https://localhost/api/v1/chat/channel`

### Problème SSL/HTTPS

```bash
./install-ssl-cert.sh
```

Voir `SSL-TROUBLESHOOTING.md` pour plus de détails.

### Erreur de base de données

```bash
# Recréer la base
docker compose exec php bin/console doctrine:database:drop --force
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
```

### Réinitialiser complètement le projet

```bash
# Arrêter et supprimer les conteneurs
docker compose down -v

# Supprimer node_modules
rm -rf frontend/node_modules

# Redémarrer
./start-dev.sh
```

## 📚 Documentation complète

- **README.md** - Vue d'ensemble du projet
- **DEVELOPMENT.md** - Guide de développement détaillé
- **SSL-TROUBLESHOOTING.md** - Aide SSL/HTTPS
- **TODO.md** - Liste des tâches à faire

## 💡 Tips

### Hot Reload

- **Frontend**: Sauvegardez vos fichiers `.tsx` et le navigateur se rafraîchit automatiquement
- **Backend**: FrankenPHP en mode worker recharge automatiquement le code PHP

### Débugger les requêtes API

1. Ouvrez les DevTools du navigateur (F12)
2. Onglet "Network"
3. Filtrez par "XHR" pour voir les appels API
4. Ou utilisez l'extension React Query DevTools (à ajouter)

### Bruno pour tester l'API

Ouvrez le dossier `Intragrume/` avec Bruno pour tester l'API backend directement.

## ⚡ Raccourcis

```bash
# Alias utiles (ajoutez à votre .bashrc/.zshrc)
alias dc="docker compose"
alias dce="docker compose exec php"
alias dcl="docker compose logs -f"
alias fe="cd frontend && npm run dev"
```

Puis utilisez:
```bash
dc up -d           # Démarrer
dce bin/console    # Console Symfony
dcl php            # Logs
fe                 # Frontend
```
