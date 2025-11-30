# 🔧 Diagnostic des problèmes de création de tickets Jira

## 🚨 Problème actuel
Le frontend ne peut pas créer de tickets et ne peut pas joindre de documents.

## ✅ Étapes de diagnostic

### 1. Vérifier que le serveur Symfony est démarré

Ouvrez un terminal et exécutez :

```bash
cd /Users/jin/Documents/GitHub/intragrume
symfony server:start
# OU
php -S localhost:8000 -t public/
```

### 2. Accéder à la page de diagnostic HTML

Ouvrez votre navigateur et allez à :

```
http://localhost:8000/test-jira.html
```

**OU si vous utilisez un autre port :**

```
http://localhost/test-jira.html
```

Cette page va tester automatiquement toutes les fonctionnalités une par une.

### 3. Suivre les tests dans l'ordre

#### Test 0 : Route API de base
- Cliquez sur "Tester /api/v1/jira/test"
- ✅ **Si ça marche :** Les routes API sont accessibles
- ❌ **Si ça échoue :** Le serveur ne répond pas ou les routes ne sont pas chargées

**Solution si échec :**
```bash
# Vider le cache Symfony
php bin/console cache:clear

# Redémarrer le serveur
symfony server:stop
symfony server:start
```

#### Test 1 : Connexion Jira
- Cliquez sur "Tester la connexion"
- ✅ **Si ça marche :** Les credentials Jira sont corrects
- ❌ **Si ça échoue :** Problème de configuration

**Solution si échec :**

Vérifiez votre fichier `.env` ou `.env.local` :

```env
JIRA_BASE_URL=https://votre-instance.atlassian.net
JIRA_EMAIL=votre.email@example.com
JIRA_API_TOKEN=votre_token_api_jira
```

**Comment obtenir un token API Jira :**
1. Allez sur https://id.atlassian.com/manage-profile/security/api-tokens
2. Cliquez sur "Create API token"
3. Copiez le token généré
4. Collez-le dans votre `.env`

#### Test 2 : Création ticket simple
- Cliquez sur "Créer un ticket simple"
- ✅ **Si ça marche :** L'API de création fonctionne
- ❌ **Si ça échoue :** Problème avec le service JiraClient

**Erreurs courantes :**
- `Project not found` : Le projet "WEB" n'existe pas dans votre Jira
- `Field required` : Un champ obligatoire manque
- `Unauthorized` : Token invalide ou expiré

#### Test 3 : Création avec formulaire
- Remplissez le formulaire
- Optionnellement, ajoutez un fichier
- Cliquez sur "Créer le ticket"
- ✅ **Si ça marche :** Le formulaire fonctionne avec fichier
- ❌ **Si ça échoue :** Problème d'upload de fichier

**Solutions si échec :**

Vérifiez les limites PHP :
```bash
# Vérifier la configuration PHP
php -i | grep -E "(upload_max_filesize|post_max_size)"
```

Modifiez `php.ini` si nécessaire :
```ini
upload_max_filesize = 20M
post_max_size = 25M
max_file_uploads = 20
```

#### Test 4 : Plusieurs fichiers
- Sélectionnez plusieurs fichiers (max 10)
- Cliquez sur "Créer avec fichiers"
- ✅ **Si ça marche :** Support multi-fichiers fonctionne
- ❌ **Si ça échoue :** Problème de gestion multiple

#### Test 5 : Liste des tickets
- Cliquez sur "Lister les tickets"
- ✅ **Si ça marche :** La lecture fonctionne
- ❌ **Si ça échoue :** Problème de permissions

## 🔍 Utiliser la console du navigateur

**Ouvrez la console (F12)** et regardez :

### Messages d'erreur typiques et solutions

#### 1. `Failed to fetch` ou `Network error`
**Cause :** Le serveur ne répond pas

**Solution :**
```bash
# Vérifier que le serveur tourne
ps aux | grep php
# OU
symfony server:status

# Démarrer le serveur si nécessaire
symfony server:start -d
```

#### 2. `404 Not Found` sur `/api/v1/jira/tickets`
**Cause :** Les routes ne sont pas chargées

**Solution :**
```bash
# Vérifier les routes
php bin/console debug:router | grep jira

# Vider le cache
php bin/console cache:clear

# Vérifier que les attributs Route sont bien reconnus
composer dump-autoload
```

#### 3. `500 Internal Server Error`
**Cause :** Erreur côté serveur

**Solution :**
```bash
# Regarder les logs
tail -f var/log/dev.log

# OU si vous utilisez Symfony server
symfony server:log
```

#### 4. `CORS error`
**Cause :** Problème de Cross-Origin

**Solution :** Installez le bundle CORS
```bash
composer require nelmio/cors-bundle
```

## 📋 Checklist complète

- [ ] Serveur Symfony démarré
- [ ] `.env` configuré avec credentials Jira
- [ ] Token API Jira valide
- [ ] Projet "WEB" existe dans Jira
- [ ] Cache Symfony vidé
- [ ] Route `/api/v1/jira/test` accessible
- [ ] Route `/api/v1/jira/tickets` accessible (GET et POST)
- [ ] Connexion Jira réussie
- [ ] Création ticket simple fonctionne
- [ ] Upload fichier fonctionne
- [ ] Upload multi-fichiers fonctionne

## 🐛 Commandes de débogage utiles

```bash
# Voir toutes les routes Jira
php bin/console debug:router | grep jira

# Voir les logs en temps réel
tail -f var/log/dev.log

# Vérifier la configuration des services
php bin/console debug:container JiraClient

# Vérifier les variables d'environnement
php bin/console debug:container --env-vars | grep JIRA

# Tester manuellement avec curl
curl -X POST http://localhost:8000/api/v1/jira/tickets \
  -F "summary=Test curl" \
  -F "description=Test description" \
  -F "author=Test User"
```

## 📞 Si rien ne fonctionne

### Essayez cette version minimale

Créez un fichier `test-minimal.php` dans `public/` :

```php
<?php
// test-minimal.php
header('Content-Type: application/json');

$response = [
    'success' => true,
    'message' => 'PHP fonctionne !',
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'files' => $_FILES
];

echo json_encode($response, JSON_PRETTY_PRINT);
```

Accédez à : `http://localhost:8000/test-minimal.php`

Si ça fonctionne, le problème vient de Symfony. Si ça ne fonctionne pas, le problème vient du serveur web.

## 📝 Logs importants à vérifier

1. **Logs Symfony :** `var/log/dev.log` ou `var/log/prod.log`
2. **Logs serveur :** `symfony server:log`
3. **Logs PHP :** Vérifiez `php.ini` pour `error_log`
4. **Console navigateur :** F12 > Console et Network

## ✉️ Informations à fournir si le problème persiste

1. Message d'erreur exact de la console navigateur
2. Contenu de `var/log/dev.log` (dernières lignes)
3. Résultat de `php bin/console debug:router | grep jira`
4. Version de PHP : `php -v`
5. Version de Symfony : `php bin/console --version`
6. Résultat des tests de la page `test-jira.html`
