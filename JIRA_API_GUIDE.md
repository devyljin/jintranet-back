# Guide d'utilisation de l'API Jira

## Configuration requise

Assurez-vous que votre fichier `.env` contient les configurations Jira :

```env
JIRA_BASE_URL=https://votre-instance.atlassian.net
JIRA_EMAIL=votre.email@example.com
JIRA_API_TOKEN=votre_token_api
```

## Pages disponibles

### 1. Formulaire de création de ticket
**URL:** `/ticket/create`

Interface complète pour créer des tickets avec :
- Support de plusieurs fichiers (max 10)
- Prévisualisation des fichiers sélectionnés
- Types de fichiers acceptés : images, PDF, documents Office
- Validation en temps réel

### 2. Page de test de l'API
**URL:** `/ticket/test`

Page de diagnostic avec :
- Test de connexion à Jira
- Test de listing des tickets
- Test de création de ticket simple
- Test de création avec fichiers
- Affichage détaillé des réponses JSON

### 3. Liste des tickets
**URL:** `/ticket/list`

Affichage de tous les tickets avec :
- Filtres par projet et JQL
- Pagination
- Liens directs vers Jira

## Routes API disponibles

### POST `/api/v1/jira/tickets` - Créer un ticket

**Paramètres (FormData):**
- `summary` (requis) : Titre du ticket (5-200 caractères)
- `description` (requis) : Description (min 10 caractères)
- `author` (requis) : Nom de l'auteur
- `project_key` (optionnel) : Clé du projet (défaut: WEB)
- `issue_type` (optionnel) : Type de ticket (Task, Bug, Story, Epic)
- `attachment` (optionnel) : Un seul fichier
- `attachments[]` (optionnel) : Plusieurs fichiers (max 10)

**Exemples avec curl:**

```bash
# Ticket simple sans fichier
curl -X POST http://localhost/api/v1/jira/tickets \
  -F "summary=Ticket de test" \
  -F "description=Ceci est une description de test" \
  -F "author=Jean Dupont"

# Ticket avec un fichier
curl -X POST http://localhost/api/v1/jira/tickets \
  -F "summary=Bug avec capture" \
  -F "description=Voir la capture d'écran" \
  -F "author=Marie Martin" \
  -F "attachment=@/path/to/screenshot.png"

# Ticket avec plusieurs fichiers
curl -X POST http://localhost/api/v1/jira/tickets \
  -F "summary=Documentation complète" \
  -F "description=Voir les documents joints" \
  -F "author=Pierre Durand" \
  -F "attachments[]=@/path/to/doc1.pdf" \
  -F "attachments[]=@/path/to/doc2.pdf" \
  -F "attachments[]=@/path/to/screenshot.png"
```

### GET `/api/v1/jira/tickets` - Lister les tickets

**Paramètres de requête:**
- `project` : Filtrer par projet
- `jql` : Requête JQL personnalisée
- `page` : Numéro de page (défaut: 1)
- `per_page` : Tickets par page (défaut: 20, max: 100)

**Exemple:**
```bash
curl "http://localhost/api/v1/jira/tickets?project=WEB&page=1&per_page=20"
```

### GET `/api/v1/jira/tickets/{ticketKey}` - Récupérer un ticket

**Exemple:**
```bash
curl "http://localhost/api/v1/jira/tickets/WEB-123"
```

### GET `/api/v1/jira/connection` - Tester la connexion

**Exemple:**
```bash
curl "http://localhost/api/v1/jira/connection"
```

## Utilisation depuis JavaScript/Frontend

### Créer un ticket simple

```javascript
const createTicket = async () => {
  const formData = new FormData();
  formData.append('summary', 'Problème de connexion');
  formData.append('description', 'Les utilisateurs ne peuvent pas se connecter');
  formData.append('author', 'Jean Dupont');

  const response = await fetch('/api/v1/jira/tickets', {
    method: 'POST',
    body: formData
  });

  const result = await response.json();

  if (result.success) {
    console.log('Ticket créé:', result.ticket.key);
    console.log('URL:', result.ticket.url);
  }
};
```

### Créer un ticket avec fichiers

```javascript
const createTicketWithFiles = async (files) => {
  const formData = new FormData();
  formData.append('summary', 'Bug interface');
  formData.append('description', 'Voir captures jointes');
  formData.append('author', 'Marie Martin');

  // Ajouter plusieurs fichiers
  files.forEach(file => {
    formData.append('attachments[]', file);
  });

  const response = await fetch('/api/v1/jira/tickets', {
    method: 'POST',
    body: formData
  });

  const result = await response.json();

  if (result.success) {
    console.log('Ticket créé:', result.ticket.key);
    console.log('Fichiers ajoutés:', result.ticket.attachments_count);
  }
};
```

### Exemple avec formulaire HTML

```html
<form id="ticketForm">
  <input type="text" name="summary" required>
  <textarea name="description" required></textarea>
  <input type="text" name="author" required>
  <input type="file" name="attachments[]" multiple>
  <button type="submit">Créer</button>
</form>

<script>
document.getElementById('ticketForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(e.target);

  const response = await fetch('/api/v1/jira/tickets', {
    method: 'POST',
    body: formData
  });

  const result = await response.json();

  if (result.success) {
    alert(`Ticket ${result.ticket.key} créé !`);
    window.open(result.ticket.url, '_blank');
  } else {
    alert(`Erreur: ${result.message}`);
  }
});
</script>
```

## Diagnostic des problèmes

### 1. Vérifier la configuration
Accédez à `/ticket/test` et cliquez sur "Tester la connexion"

### 2. Vérifier les logs
Consultez les logs Symfony pour voir les erreurs détaillées

### 3. Vérifier les variables d'environnement
```bash
# Dans le terminal
php bin/console debug:container --env-vars | grep JIRA
```

### 4. Erreurs courantes

**Erreur 404 sur `/api/v1/jira/tickets`**
- Vérifiez que le serveur Symfony est démarré
- Vérifiez que les routes sont bien chargées

**Erreur 500 lors de la création**
- Vérifiez les credentials Jira dans `.env`
- Vérifiez que le projet existe dans Jira
- Consultez les logs pour plus de détails

**Les fichiers ne s'uploadent pas**
- Vérifiez la taille maximale dans `php.ini` (upload_max_filesize, post_max_size)
- Vérifiez que les permissions du dossier temporaire sont correctes
- Limitez à 10 fichiers maximum

## Support des types de fichiers

Par défaut, les types suivants sont acceptés :
- Images : .jpg, .jpeg, .png, .gif, .bmp, .svg
- Documents : .pdf, .doc, .docx, .txt
- Tableurs : .xlsx, .csv

Pour modifier les types acceptés, éditez le template `templates/ticket/create.html.twig` et ajustez l'attribut `accept` de l'input file.
