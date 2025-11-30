# 🚀 Mise à jour - Support des fichiers pour les tickets Jira

## ✅ Ce qui a été modifié

### 1. **API (`src/api/jira.ts`)**

#### Changements dans `CreateTicketData` :
- ✅ **Ajout** du champ `author` (obligatoire)
- ✅ **Ajout** du champ `attachments?: File[]` pour les fichiers

#### Changements dans `CreateTicketResponse` :
- ✅ **Ajout** de `url: string` dans ticket
- ✅ **Ajout** de `attachments?: number`
- ✅ **Ajout** de `attachments_count?: number`
- ✅ **Ajout** de `attachments_failed?: number`

#### Fonction `createTicket` :
- ✅ **Conversion en FormData** au lieu de JSON
- ✅ **Support de plusieurs fichiers** (max 10)
- ✅ **Header** `Content-Type: multipart/form-data`

### 2. **Composant Jira (`src/pages/Jira.tsx`)**

#### État ajouté :
- ✅ `author` dans formData (pré-rempli avec le username)
- ✅ `selectedFiles: File[]` pour gérer les fichiers

#### Nouvelles fonctions :
- ✅ `handleFileChange()` - Gère la sélection de fichiers
- ✅ `removeFile()` - Supprime un fichier de la liste

#### Champs ajoutés au formulaire :
1. **Champ Auteur** (obligatoire)
   - Pré-rempli avec le nom d'utilisateur connecté
   - Modifiable

2. **Input fichiers** (optionnel)
   - Support de plusieurs fichiers (max 10)
   - Formats acceptés : images, PDF, Office, CSV
   - Preview avec nom et taille
   - Bouton pour supprimer chaque fichier

## 🎯 Fonctionnalités disponibles

### Créer un ticket sans fichier
```typescript
// Formulaire minimal
{
  summary: "Bug de connexion",
  description: "Les utilisateurs ne peuvent pas se connecter",
  author: "Jean Dupont",
  projectKey: "WEB",
  issueType: "Bug"
}
```

### Créer un ticket avec fichiers
```typescript
// Formulaire avec fichiers
{
  summary: "Bug interface",
  description: "Voir captures jointes",
  author: "Marie Martin",
  projectKey: "WEB",
  issueType: "Bug",
  attachments: [file1, file2, file3]  // Jusqu'à 10 fichiers
}
```

## 🧪 Comment tester

### 1. Démarrer le backend
```bash
cd /Users/jin/Documents/GitHub/intragrume
symfony server:start
# OU
php -S localhost:8000 -t public/
```

### 2. Démarrer le frontend
```bash
cd /Users/jin/Documents/GitHub/intragrume/frontend
npm run dev
```

### 3. Accéder à l'application
```
http://localhost:5173
```

### 4. Tester la création de ticket

1. **Connectez-vous** à l'application
2. **Allez sur la page Jira**
3. **Remplissez le formulaire** :
   - Titre (obligatoire)
   - Description (obligatoire)
   - Auteur (pré-rempli, modifiable)
   - Optionnel : Sélectionnez des fichiers

4. **Vérifiez la preview** :
   - Les fichiers sélectionnés s'affichent avec leur taille
   - Vous pouvez supprimer des fichiers individuellement

5. **Cliquez sur "Créer le ticket"**

6. **Vérifiez le résultat** :
   - Message de succès avec clé du ticket
   - Nombre de fichiers joints (si applicable)
   - Lien vers le ticket dans Jira

## 🔍 Console de débogage

Ouvrez la console navigateur (F12) pour voir :

```javascript
// Logs lors de la soumission
Envoi du ticket avec données: {...}
Fichiers attachés: 3

// Logs en cas d'erreur
Error creating ticket: {...}
Error response: {...}
```

## ⚠️ Limitations et validations

### Côté frontend :
- ✅ Maximum **10 fichiers**
- ✅ Validation de la taille affichée
- ✅ Formats : images, PDF, Office, CSV

### Côté backend :
- ✅ Maximum **10 fichiers**
- ✅ Validation des champs obligatoires
- ✅ Gestion des erreurs d'upload

## 🐛 Résolution de problèmes

### Erreur : "Le titre du ticket est obligatoire"
**Cause** : Champ summary vide ou trop court (< 5 caractères)

**Solution** : Remplissez le titre avec au moins 5 caractères

---

### Erreur : "La description du ticket est obligatoire"
**Cause** : Description vide ou trop courte (< 10 caractères)

**Solution** : Écrivez une description d'au moins 10 caractères

---

### Erreur : "Maximum 10 fichiers autorisés"
**Cause** : Plus de 10 fichiers sélectionnés

**Solution** : Limitez à 10 fichiers maximum

---

### Erreur : "Failed to fetch" ou "Network error"
**Cause** : Backend non accessible

**Solution** :
```bash
# Vérifier que le backend tourne
symfony server:status

# Vérifier l'URL dans le fichier de configuration
# frontend/src/api/client.ts
```

---

### Les fichiers ne s'uploadent pas
**Cause** : Limite PHP dépassée

**Solution** :
Vérifiez `php.ini` :
```ini
upload_max_filesize = 20M
post_max_size = 25M
max_file_uploads = 20
```

---

### Erreur CORS
**Cause** : Problème de Cross-Origin

**Solution** :
```bash
# Installer le bundle CORS (si pas déjà fait)
cd /Users/jin/Documents/GitHub/intragrume
composer require nelmio/cors-bundle
```

## 📝 Exemple de réponse API

### Succès - Ticket sans fichier
```json
{
  "success": true,
  "message": "Ticket créé avec succès",
  "ticket": {
    "key": "WEB-123",
    "id": "12345",
    "url": "https://agrume.atlassian.net/browse/WEB-123"
  }
}
```

### Succès - Ticket avec fichiers
```json
{
  "success": true,
  "message": "Ticket créé avec 3 pièce(s) jointe(s) sur 3",
  "ticket": {
    "key": "WEB-124",
    "id": "12346",
    "url": "https://agrume.atlassian.net/browse/WEB-124",
    "attachments_count": 3,
    "attachments_failed": 0
  }
}
```

### Erreur - Validation
```json
{
  "success": false,
  "errors": [
    "Le titre du ticket est obligatoire",
    "La description du ticket est obligatoire"
  ]
}
```

## 🎨 Interface utilisateur

### Nouveau champ Auteur
- Pré-rempli avec le username
- Modifiable si besoin
- Obligatoire

### Nouveau input fichiers
- Zone de sélection avec texte explicatif
- Preview des fichiers avec :
  - Nom du fichier
  - Taille en KB
  - Bouton de suppression (✕)
- Encadré bleu pour la liste des fichiers

### Message de succès amélioré
Avant :
```
Ticket WEB-123 créé avec succès !
```

Après (avec fichiers) :
```
Ticket WEB-123 créé avec succès ! (3 fichier(s) joint(s))
```

## 🚀 Prochaines améliorations possibles

- [ ] Validation de la taille des fichiers côté frontend
- [ ] Progress bar pour l'upload
- [ ] Preview des images avant upload
- [ ] Drag & drop pour les fichiers
- [ ] Compression automatique des images
- [ ] Support de plus de formats de fichiers
