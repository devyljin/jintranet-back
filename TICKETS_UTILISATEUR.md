# 📋 Fonctionnalité : Mes Tickets

## 🎯 Description

Cette fonctionnalité permet à chaque utilisateur de voir tous les tickets Jira qu'il a créés, en récupérant automatiquement les tickets liés aux entités `Cross` associées à son compte.

## 🏗️ Architecture

### Backend

#### Route API : `/api/v1/jira/my-tickets`
**Méthode :** `GET`
**Authentification :** Requise
**Contrôleur :** `JiraController::getMyTickets()` (ligne 433)

#### Fonctionnement :

1. **Récupération de l'utilisateur connecté**
   ```php
   $user = $this->getUser();
   ```

2. **Récupération des entités Cross**
   ```php
   $crosses = $this->crossRepository->findBy(['sender' => $user]);
   ```

3. **Extraction des issueKeys**
   ```php
   $issueKeys = array_map(fn($cross) => $cross->getCode(), $crosses);
   ```

4. **Récupération des détails de chaque ticket**
   - Pour chaque `issueKey`, appel à `jiraClient->getIssue()`
   - Gestion des erreurs individuelles (tickets supprimés, etc.)
   - Retour de tous les tickets avec leurs détails complets

### Frontend

#### API Client : `jiraApi.getMyTickets()`
**Fichier :** `frontend/src/api/jira.ts` (ligne 98)

#### Composant React
**Fichier :** `frontend/src/pages/Jira.tsx`

**État :**
- `myTickets: JiraTicket[]` - Liste des tickets
- `loadingMyTickets: boolean` - État de chargement

**Fonctions :**
- `loadMyTickets()` - Charge les tickets de l'utilisateur
- Auto-chargement au montage du composant (`useEffect`)
- Rechargement après création d'un nouveau ticket

## 📊 Format de réponse API

### Succès avec tickets
```json
{
  "success": true,
  "data": {
    "tickets": [
      {
        "key": "WEB-123",
        "id": "12345",
        "summary": "Mon ticket",
        "description": "Description du ticket",
        "status": "In Progress",
        "priority": "High",
        "issueType": "Bug",
        "assignee": "Jean Dupont",
        "reporter": "Marie Martin",
        "created": "2024-01-15T10:30:00.000+0000",
        "updated": "2024-01-16T14:20:00.000+0000",
        "url": "https://agrume.atlassian.net/browse/WEB-123"
      }
    ],
    "total": 15,
    "errors": []
  }
}
```

### Aucun ticket
```json
{
  "success": true,
  "data": {
    "tickets": [],
    "total": 0
  },
  "message": "Aucun ticket trouvé pour cet utilisateur"
}
```

### Avec erreurs (tickets partiellement récupérés)
```json
{
  "success": true,
  "data": {
    "tickets": [
      { /* ticket 1 */ },
      { /* ticket 2 */ }
    ],
    "total": 2,
    "errors": [
      {
        "issue_key": "WEB-999",
        "error": "Ticket not found"
      }
    ]
  }
}
```

### Erreur d'authentification
```json
{
  "success": false,
  "message": "Utilisateur non authentifié"
}
```

## 🖥️ Interface utilisateur

### Section "Mes tickets"

**Affichage :**
- Titre avec compteur : `Mes tickets (15)`
- Liste des tickets avec carte complète
- Bouton "🔄 Actualiser" pour recharger

**États :**

1. **Chargement**
   ```
   Chargement de vos tickets...
   ```

2. **Aucun ticket**
   ```
   ℹ️ Vous n'avez créé aucun ticket pour le moment.
   ```

3. **Tickets affichés**
   - Une carte par ticket
   - Toutes les informations (statut, priorité, assigné, etc.)
   - Lien vers Jira

### Carte de ticket

Chaque ticket affiche :
- **En-tête** : Clé (ex: WEB-123) + Badge statut
- **Titre** : Summary du ticket
- **Description** : Texte extrait du format ADF
- **Métadonnées** :
  - Type de ticket
  - Priorité
  - Assigné à
  - Reporter
  - Date de création
- **Lien** : "Voir dans Jira →"

## 🔄 Cycle de vie

### 1. Chargement initial
```javascript
useEffect(() => {
  loadMyTickets();  // Appelé au montage
}, []);
```

### 2. Après création d'un ticket
```javascript
// Après succès de createTicket()
loadMyTickets();  // Recharge la liste
```

### 3. Actualisation manuelle
```javascript
// Bouton "🔄 Actualiser"
onClick={loadMyTickets}
```

## 🔐 Sécurité

### Authentification
- Route protégée : nécessite un utilisateur connecté
- Vérification : `$this->getUser()`
- Retour 401 si non authentifié

### Isolation des données
- Chaque utilisateur ne voit QUE ses tickets
- Filtrage par : `findBy(['sender' => $user])`
- Impossible d'accéder aux tickets d'autres utilisateurs

## 🚀 Exemples d'utilisation

### Depuis le frontend

```javascript
// Dans un composant React
const response = await jiraApi.getMyTickets();

if (response.success) {
  console.log(`${response.data.total} ticket(s) trouvé(s)`);
  console.log('Premier ticket:', response.data.tickets[0]);

  if (response.data.errors.length > 0) {
    console.warn('Certains tickets n\'ont pas pu être récupérés');
  }
}
```

### Depuis l'API directement

```bash
# Avec authentification
curl -X GET http://localhost/api/v1/jira/my-tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

## 📈 Performance

### Optimisations possibles

1. **Mise en cache**
   ```php
   // TODO: Cache Redis pour éviter les appels multiples
   ```

2. **Récupération batch**
   ```php
   // TODO: API Jira batch pour récupérer plusieurs tickets en une requête
   ```

3. **Pagination**
   ```php
   // Actuellement : tous les tickets
   // Future : pagination pour grands volumes
   ```

## 🐛 Gestion des erreurs

### Erreurs individuelles
- Si un ticket Jira est supprimé → ajouté dans `errors[]`
- Les autres tickets sont quand même retournés
- Aucun blocage total

### Logs
Tous les événements sont loggés :
```php
$this->logger->info('Récupération des tickets utilisateur', [
  'user_id' => $user->getId(),
  'ticket_count' => count($issueKeys)
]);
```

## 🔗 Liens entre entités

```
User (1) ----< Cross (N)
         |
         └─ Cross.code → Jira issueKey
```

**Flux :**
1. User crée un ticket Jira
2. Service JiraClient crée une entité Cross
3. Cross.code = issueKey du ticket créé
4. Route `/my-tickets` récupère tous les Cross de l'user
5. Pour chaque Cross, récupère les détails Jira

## 📝 Logs utiles

### Création d'un ticket
```
[INFO] Création d'un ticket Jira
  project: WEB
  summary: Mon ticket

[INFO] Ticket Jira créé avec succès
  issueId: 12345
  issueKey: WEB-123
```

### Récupération des tickets
```
[INFO] Récupération des tickets utilisateur
  user_id: 42
  ticket_count: 15
  issue_keys: ["WEB-123", "WEB-124", ...]
```

### Erreur sur un ticket
```
[WARNING] Erreur lors de la récupération du ticket
  issue_key: WEB-999
  error: Ticket not found
```

## 🎨 Personnalisation

### Modifier l'ordre d'affichage

Dans `JiraController::getMyTickets()`, ligne 447 :
```php
// Actuellement : pas de tri spécifique
$crosses = $this->crossRepository->findBy(['sender' => $user]);

// Pour trier par date de création (plus récent d'abord) :
$crosses = $this->crossRepository->findBy(
  ['sender' => $user],
  ['id' => 'DESC']  // Tri décroissant
);
```

### Ajouter des filtres

```php
// Par statut
$status = $request->query->get('status');
if ($status) {
  // Filtrer les tickets récupérés
}

// Par période
$from = $request->query->get('from');
$to = $request->query->get('to');
```

## ✨ Améliorations futures

- [ ] Pagination (limite de tickets par page)
- [ ] Filtres (statut, priorité, date)
- [ ] Tri (par date, priorité, statut)
- [ ] Recherche dans les tickets
- [ ] Mise en cache des résultats
- [ ] Statistiques (nombre par statut, etc.)
- [ ] Export CSV/Excel
- [ ] Notifications sur changement de statut
