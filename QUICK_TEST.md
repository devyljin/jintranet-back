# Test rapide de l'authentification

## Vérification du backend

```bash
# 1. Vérifier que les services Docker sont actifs
docker compose ps

# 2. Créer un utilisateur de test via curl
curl -k -X POST https://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "password123"
  }'

# Réponse attendue :
# {"message":"User created successfully","user":"testuser"}
```

## Test de connexion via curl

```bash
# 3. Tester le login
curl -k -X POST https://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "password123"
  }'

# Réponse attendue :
# {"token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."}
```

## Test avec le frontend

```bash
# 4. Démarrer le frontend (si pas déjà fait)
cd frontend
npm run dev
```

Ensuite :
1. Ouvrir http://localhost:5173
2. Cliquer sur "Créer un compte"
3. Créer un compte avec :
   - Username: `testuser`
   - Password: `password123`
4. Vous serez redirigé vers `/login`
5. Entrer les mêmes identifiants
6. Vous devriez être connecté et redirigé vers `/dashboard`

## Débogage en cas de problème

### Ouvrir la console développeur (F12)

1. Aller sur l'onglet **Network**
2. Tenter de se connecter
3. Regarder les requêtes :

**Requête `/api/login` :**
- Status: 200 OK
- Response: `{ "token": "..." }`

**Requête `/api/me` :**
- Status: 200 OK
- Headers: `Authorization: Bearer ...`
- Response: `{ "id": 1, "username": "testuser", "roles": [...] }`

### Erreurs courantes

**❌ 401 Unauthorized sur `/api/login`**
- Vérifier que l'utilisateur existe
- Vérifier le mot de passe

**❌ 401 Unauthorized sur `/api/me`**
- Le token n'est pas valide
- Vérifier les clés JWT : `ls -la config/jwt/`

**❌ CORS error**
- Vérifier `config/packages/nelmio_cors.yaml`
- L'origine doit être autorisée

**❌ SSL certificate error**
- Utiliser `https://localhost` (pas `http`)
- Accepter le certificat auto-signé dans le navigateur

**❌ Network error / Failed to fetch**
- Le backend n'est pas démarré : `docker compose up -d`
- Mauvaise URL : vérifier `frontend/.env`

## Vérifier les logs

```bash
# Logs du backend PHP
docker compose logs php -f

# Logs de la base de données
docker compose logs database -f
```

## Nettoyer et réessayer

```bash
# Vider le cache Symfony
docker compose exec php php bin/console cache:clear

# Nettoyer localStorage du navigateur
# Dans la console du navigateur :
localStorage.clear()
```
