# Dépannage - Intragrume

## 🐳 Problème: Docker ne démarre pas

### Symptôme
```bash
docker compose up -d
# Aucun conteneur ne démarre
docker compose ps
# Affiche: NAME IMAGE COMMAND SERVICE CREATED STATUS PORTS (vide)
```

### Causes possibles

1. **Docker Desktop n'est pas lancé**
   - Solution: Démarrer Docker Desktop

2. **Les conteneurs ont été arrêtés**
   - Solution: `docker compose up -d`

3. **Les conteneurs sont en erreur**
   - Diagnostic: `docker compose logs`

### Solutions par ordre

#### 1. Vérifier Docker Desktop

```bash
docker info
```

Si erreur → Démarrer Docker Desktop

#### 2. Démarrer les conteneurs

```bash
docker compose up -d
```

#### 3. Vérifier l'état

```bash
# Attendre quelques secondes
sleep 5

# Vérifier l'état
docker compose ps

# Devrait afficher:
# STATUS: Up X seconds (healthy)
```

#### 4. Voir les logs si problème

```bash
# Tous les logs
docker compose logs

# Logs en temps réel
docker compose logs -f

# Logs d'un service spécifique
docker compose logs php
docker compose logs database
```

## 🔴 Erreurs courantes

### Erreur: Port déjà utilisé

**Symptôme:**
```
Error: bind: address already in use
```

**Cause:** Un autre service utilise le port 80, 443 ou 5432

**Solution:**
```bash
# Trouver qui utilise le port
lsof -i :443
lsof -i :80
lsof -i :5432

# Arrêter le service ou changer le port dans compose.yaml
```

### Erreur: Base de données non accessible

**Symptôme:**
```
Connection refused
SQLSTATE[08006] [7]
```

**Solution:**
```bash
# Vérifier que la DB est démarrée
docker compose ps database

# Voir les logs
docker compose logs database

# Recréer la base si nécessaire
docker compose exec php bin/console doctrine:database:drop --force
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
```

### Erreur: SSL/HTTPS ne fonctionne pas

Voir `SSL-TROUBLESHOOTING.md`

### Erreur: Permission denied

**Solution:**
```bash
# macOS/Linux
chmod +x start-dev.sh
chmod +x install-ssl-cert.sh

# Si problème de volumes Docker
docker compose down -v
docker compose up -d
```

## 🚀 Commandes de diagnostic

### État général

```bash
# État des conteneurs
docker compose ps

# Utilisation des ressources
docker stats

# Liste de tous les conteneurs (même arrêtés)
docker ps -a
```

### Logs

```bash
# Tous les logs
docker compose logs

# Logs en temps réel
docker compose logs -f

# Dernières 50 lignes
docker compose logs --tail=50

# Logs d'un service
docker compose logs php
docker compose logs database
```

### Réseau

```bash
# Vérifier les ports ouverts
lsof -i :80
lsof -i :443
lsof -i :5432

# Tester le backend
curl https://localhost
curl https://localhost/api/v1/chat/channel

# Tester la base de données
docker compose exec database psql -U app -d app -c "SELECT version();"
```

### Espace disque

```bash
# Voir l'espace utilisé par Docker
docker system df

# Nettoyer (attention: supprime les images non utilisées)
docker system prune

# Nettoyer tout (attention: supprime TOUT)
docker system prune -a --volumes
```

## 🔄 Réinitialisation complète

Si rien ne fonctionne, réinitialiser complètement:

```bash
# 1. Tout arrêter
docker compose down -v

# 2. Supprimer les images locales (optionnel)
docker rmi app-php

# 3. Nettoyer Docker
docker system prune -f

# 4. Rebuild from scratch
docker compose build --no-cache

# 5. Redémarrer
docker compose up -d

# 6. Recréer la base de données
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:fixtures:load
```

## 🐛 Debug avancé

### Entrer dans un conteneur

```bash
# Shell dans le conteneur PHP
docker compose exec php bash

# Dans le conteneur, vous pouvez:
ls -la                           # Explorer les fichiers
bin/console debug:router         # Voir les routes
bin/console cache:clear          # Vider le cache
tail -f var/log/dev.log         # Voir les logs Symfony
```

### Reconstruire les conteneurs

```bash
# Rebuild sans cache
docker compose build --no-cache

# Rebuild et redémarrer
docker compose up -d --build
```

### Vérifier les variables d'environnement

```bash
# Dans le conteneur
docker compose exec php env

# Vérifier une variable spécifique
docker compose exec php bash -c 'echo $DATABASE_URL'
```

## 📱 Frontend ne se connecte pas au backend

### Vérifier le proxy Vite

```bash
# Vérifier vite.config.ts
cat frontend/vite.config.ts

# Devrait contenir:
# proxy: {
#   '/api': {
#     target: 'https://localhost',
#     ...
#   }
# }
```

### Tester manuellement

```bash
# Backend accessible ?
curl https://localhost/api/v1/chat/channel

# Depuis le frontend (dans la console navigateur)
fetch('/api/v1/chat/channel')
  .then(r => r.json())
  .then(console.log)
```

### Vérifier CORS

```bash
# Les headers doivent inclure:
curl -I https://localhost/api/v1/chat/channel

# Vérifier la config CORS
cat config/packages/nelmio_cors.yaml
```

## 💾 Problèmes de base de données

### Reset complet de la DB

```bash
docker compose exec php bin/console doctrine:database:drop --force
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
```

### Migrations en erreur

```bash
# Voir l'état des migrations
docker compose exec php bin/console doctrine:migrations:status

# Marquer une migration comme exécutée (sans l'exécuter)
docker compose exec php bin/console doctrine:migrations:version VERSION --add

# Rollback
docker compose exec php bin/console doctrine:migrations:migrate prev
```

## 🔧 Autres problèmes

### Composer en erreur

```bash
# Vider le cache Composer
docker compose exec php composer clear-cache

# Réinstaller les dépendances
docker compose exec php composer install --no-cache
```

### Cache Symfony bloqué

```bash
# Vider le cache
docker compose exec php bin/console cache:clear

# Vider et warmup
docker compose exec php bin/console cache:clear
docker compose exec php bin/console cache:warmup
```

### Frontend en erreur

```bash
cd frontend

# Supprimer node_modules et réinstaller
rm -rf node_modules package-lock.json
npm install

# Vider le cache Vite
rm -rf node_modules/.vite
```

## 📞 Obtenir de l'aide

Si le problème persiste:

1. **Collecter les informations:**
   ```bash
   docker compose ps > debug.txt
   docker compose logs >> debug.txt
   docker --version >> debug.txt
   ```

2. **Vérifier les fichiers de configuration:**
   - `compose.yaml`
   - `frontend/vite.config.ts`
   - `config/packages/nelmio_cors.yaml`

3. **Reproduire le problème** et noter les étapes exactes

## ⚛️ Problèmes Frontend React

### Erreur: "doesn't provide an export named"

**Symptôme:**
```
Uncaught SyntaxError: The requested module '/src/types/index.ts' 
doesn't provide an export named: 'User'
```

**Cause:** Cache de Vite corrompu ou HMR (Hot Module Replacement) en erreur

**Solution:**

1. **Nettoyer le cache Vite:**
   ```bash
   cd frontend
   rm -rf node_modules/.vite .vite
   ```

2. **Redémarrer le serveur:**
   ```bash
   npm run dev
   ```

3. **Si le problème persiste, vérifier les imports:**
   ```bash
   # Vérifier que le type est bien exporté
   cat src/types/index.ts | grep "export interface User"
   
   # Vérifier les imports dans les autres fichiers
   grep -r "import.*User.*from" src/
   ```

4. **Hard refresh du navigateur:**
   - Chrome/Firefox: `Cmd+Shift+R` (Mac) ou `Ctrl+Shift+R` (Windows)
   - Ou ouvrir les DevTools → Network → Cocher "Disable cache"

### Erreur: Port déjà utilisé

**Symptôme:**
```
Port 5173 is in use, trying another one...
```

**Solution:**
```bash
# Trouver et tuer le processus
lsof -ti :5173 | xargs kill -9

# Ou tuer tous les processus Node
pkill -9 node

# Redémarrer
npm run dev
```

### Erreur: Module not found

**Symptôme:**
```
Failed to resolve module specifier "react"
Module not found: Can't resolve 'axios'
```

**Solution:**
```bash
cd frontend

# Réinstaller les dépendances
rm -rf node_modules package-lock.json
npm install

# Redémarrer
npm run dev
```

### Erreur CORS / API non accessible

**Symptôme:**
```
Access to fetch at 'https://localhost/api/...' has been blocked by CORS
net::ERR_SSL_PROTOCOL_ERROR
```

**Solution:**

1. **Vérifier que le backend est démarré:**
   ```bash
   docker compose ps
   curl https://localhost/api/v1/chat/channel
   ```

2. **Vérifier le proxy Vite:**
   ```bash
   cat vite.config.ts
   # Doit contenir:
   # proxy: { '/api': { target: 'https://localhost', ... } }
   ```

3. **Vérifier CORS dans Symfony:**
   ```bash
   cat ../config/packages/nelmio_cors.yaml
   ```

4. **Installer le certificat SSL:**
   ```bash
   cd ..
   ./install-ssl-cert.sh
   ```

### Frontend lent / Ne répond pas

**Solution:**
```bash
cd frontend

# Vérifier les processus Node
ps aux | grep node

# Nettoyer et redémarrer
rm -rf node_modules/.vite
npm run dev
```

### Erreur de build

**Symptôme:**
```
npm run build
# Erreurs TypeScript ou de compilation
```

**Solution:**
```bash
# Vérifier les erreurs TypeScript
npx tsc --noEmit

# Nettoyer et rebuilder
rm -rf dist node_modules/.vite
npm run build
```
