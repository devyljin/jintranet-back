# Guide de configuration Jira

## Problème actuel

Votre fichier `.env` contient des valeurs de placeholder pour Jira :

```env
JIRA_BASE_URL=https://zoubidou.atlassian.net
JIRA_EMAIL=ma.super@adresse.mail
JIRA_API_TOKEN=JRRTOKEN
```

Ces valeurs ne fonctionneront pas. Vous devez les remplacer par vos vrais identifiants Jira.

## Étape 1 : Obtenir vos identifiants Jira

### 1.1 URL de base Jira

Votre URL Jira est visible quand vous êtes connecté à Jira. Par exemple :
- Si vous voyez `https://votre-entreprise.atlassian.net/...` dans la barre d'adresse
- Alors votre `JIRA_BASE_URL` est `https://votre-entreprise.atlassian.net`

**Note :** Dans votre cas, vous aviez mentionné "agrume" dans le code, donc ce serait probablement :
```env
JIRA_BASE_URL=https://agrume.atlassian.net
```

### 1.2 Email Jira

C'est l'email que vous utilisez pour vous connecter à Jira.

Exemple :
```env
JIRA_EMAIL=votre.nom@entreprise.com
```

### 1.3 Générer un API Token

1. **Connectez-vous** à votre compte Atlassian : https://id.atlassian.com/manage-profile/security/api-tokens

2. **Cliquez sur "Create API token"**

3. **Donnez un nom** au token (ex: "Intragrume API")

4. **Copiez le token** généré (⚠️ vous ne pourrez plus le voir après !)

5. **Collez-le** dans votre `.env` :
   ```env
   JIRA_API_TOKEN=ATATT3xFfGF0... (votre token réel)
   ```

## Étape 2 : Mettre à jour votre .env

Éditez le fichier `.env` à la racine du projet :

```env
# Remplacez ces valeurs par les vôtres
JIRA_BASE_URL=https://agrume.atlassian.net
JIRA_EMAIL=votre.email@example.com
JIRA_API_TOKEN=ATATT3xFfGF0T9X... (votre vrai token)
JIRA_DEFAULT_PROJECT_KEY=WEB
JIRA_DEFAULT_ISSUE_TYPE=Task
```

## Étape 3 : Redémarrer les services

Après avoir modifié le `.env`, vous devez redémarrer les services Docker :

```bash
# Arrêter les services
docker compose down

# Redémarrer les services
docker compose up -d

# Vider le cache Symfony
docker compose exec php php bin/console cache:clear
```

## Étape 4 : Tester la connexion

### Option A : Via l'interface web

1. Connectez-vous à l'application : http://localhost:5173
2. Allez sur la page Jira : http://localhost:5173/jira
3. Essayez de créer un ticket

### Option B : Via curl (backend uniquement)

Vous devez d'abord vous connecter pour obtenir un token JWT :

```bash
# 1. Se connecter et récupérer le token
TOKEN=$(curl -k -X POST https://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"votre_user","password":"votre_pass"}' \
  | jq -r '.token')

# 2. Tester la connexion Jira
curl -k -H "Authorization: Bearer $TOKEN" \
  https://localhost/api/v1/jira/connection | jq

# 3. Créer un ticket de test
curl -k -X POST https://localhost/api/v1/jira/tickets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "summary": "Test ticket",
    "description": "Ceci est un ticket de test",
    "projectKey": "WEB",
    "issueType": "Task"
  }' | jq
```

### Option C : Via le debug controller (non protégé)

Pour faciliter le debug, vous pouvez temporairement désactiver la protection JWT sur les routes de debug.

Éditez `config/packages/security.yaml` :

```yaml
access_control:
    - { path: ^/api/(login|register), roles: PUBLIC_ACCESS }
    - { path: ^/api/v1/debug, roles: PUBLIC_ACCESS }  # Ajouter cette ligne
    - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

Puis testez :

```bash
curl -k https://localhost/api/v1/debug/jira/connection | jq
```

## Erreurs courantes

### ❌ "401 Unauthorized"

**Cause :** Token API invalide ou email incorrect

**Solution :**
- Vérifiez que le token API est correct
- Vérifiez que l'email correspond à votre compte Jira
- Générez un nouveau token API si nécessaire

### ❌ "404 Not Found" ou "Could not resolve host"

**Cause :** URL Jira incorrecte

**Solution :**
- Vérifiez l'URL : `https://votre-domaine.atlassian.net` (sans slash à la fin)
- Testez l'URL dans votre navigateur

### ❌ "403 Forbidden"

**Cause :** Vous n'avez pas les permissions sur le projet

**Solution :**
- Vérifiez que vous avez accès au projet WEB dans Jira
- Vérifiez que votre utilisateur peut créer des tickets

### ❌ "SSL certificate problem"

**Cause :** Problème de certificat SSL

**Solution :**
- Utilisez `curl -k` pour ignorer les erreurs SSL (dev uniquement)
- En production, configurez correctement les certificats

## Vérification de la configuration

Une fois configuré, vous devriez pouvoir :

1. ✅ Tester la connexion : `{"success": true, "message": "Connection successful"}`
2. ✅ Créer un ticket via l'interface web
3. ✅ Rechercher un ticket existant
4. ✅ Voir les tickets dans Jira

## Aide supplémentaire

Si vous avez toujours des problèmes :

1. **Vérifiez les logs** :
   ```bash
   docker compose logs php | grep -i jira
   ```

2. **Vérifiez les variables d'environnement** :
   ```bash
   docker compose exec php php bin/console debug:container --parameters | grep JIRA
   ```

3. **Testez manuellement l'API Jira** :
   ```bash
   curl -u "votre.email@example.com:VOTRE_TOKEN" \
     https://agrume.atlassian.net/rest/api/3/myself
   ```

   Si cette commande fonctionne, vos identifiants sont corrects.
