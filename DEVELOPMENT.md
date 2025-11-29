# Guide de développement Intragrume

## Architecture Frontend-Backend

```
┌─────────────────────────────────────────────────────┐
│  Frontend (React)                                    │
│  http://localhost:5173                              │
│                                                      │
│  ┌──────────────────────────────────────────────┐  │
│  │  Browser                                      │  │
│  │  - React App                                  │  │
│  │  - React Router                               │  │
│  │  - TanStack Query (cache API)                │  │
│  └──────────────────────────────────────────────┘  │
│                    │                                 │
│                    │ Proxy Vite                      │
│                    │ /api/* → https://localhost      │
└────────────────────┼─────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────┐
│  Backend (Symfony)                                   │
│  https://localhost                                   │
│                                                      │
│  ┌──────────────────────────────────────────────┐  │
│  │  Caddy (Reverse Proxy + SSL)                 │  │
│  │  - HTTPS automatique                         │  │
│  │  - HTTP/2 & HTTP/3                          │  │
│  └──────────────────────────────────────────────┘  │
│                    │                                 │
│                    ▼                                 │
│  ┌──────────────────────────────────────────────┐  │
│  │  FrankenPHP + Symfony                        │  │
│  │  - API REST (/api/v1/*)                     │  │
│  │  - JWT Authentication                        │  │
│  │  - Doctrine ORM                              │  │
│  └──────────────────────────────────────────────┘  │
│                    │                                 │
│                    ▼                                 │
│  ┌──────────────────────────────────────────────┐  │
│  │  PostgreSQL                                   │  │
│  │  - Base de données                           │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

## Workflow de développement

### 1. Démarrage rapide

```bash
./start-dev.sh
```

Ce script:
- Démarre le backend (Docker)
- Vérifie les dépendances frontend
- Lance le serveur de développement React

### 2. Développement séparé

**Backend uniquement:**
```bash
docker compose up -d
docker compose logs -f php  # Voir les logs
```

**Frontend uniquement:**
```bash
cd frontend
npm run dev
```

### 3. Arrêt

**Frontend:** `Ctrl+C`

**Backend:**
```bash
docker compose down
```

## Communication Frontend-Backend

### Flux d'une requête API

1. **Frontend** - L'utilisateur déclenche une action
   ```typescript
   const { data } = useQuery({
     queryKey: ['channels'],
     queryFn: chatApi.getChannels
   });
   ```

2. **API Client (Axios)** - Ajoute le token JWT
   ```typescript
   // src/api/client.ts
   config.headers.Authorization = `Bearer ${token}`;
   ```

3. **Proxy Vite** - Redirige vers le backend
   ```
   GET http://localhost:5173/api/v1/chat/channel
   → GET https://localhost/api/v1/chat/channel
   ```

4. **Caddy** - Termine SSL et forward à FrankenPHP
   ```
   HTTPS → HTTP (interne Docker)
   ```

5. **Symfony Controller** - Traite la requête
   ```php
   #[Route('/api/v1/chat/channel', methods: ['GET'])]
   public function index() { ... }
   ```

6. **Response** - Retour en JSON
   ```
   Symfony → Caddy → Vite Proxy → React
   ```

## Ajouter une nouvelle fonctionnalité

### Backend (API Endpoint)

1. **Créer l'entité** (si nécessaire)
   ```bash
   docker compose exec php bin/console make:entity MyEntity
   ```

2. **Créer le controller**
   ```php
   // src/Controller/MyController.php
   #[Route('/api/v1/my-resource')]
   class MyController extends AbstractController
   {
       #[Route(name: 'api_my_resource_list', methods: ['GET'])]
       public function list(): JsonResponse
       {
           // ...
       }
   }
   ```

3. **Tester avec Bruno**
   - Créer un fichier `.bru` dans `Intragrume/`
   - Tester l'endpoint

### Frontend (Interface)

1. **Créer les types TypeScript**
   ```typescript
   // frontend/src/types/index.ts
   export interface MyResource {
     id: number;
     name: string;
   }
   ```

2. **Créer le client API**
   ```typescript
   // frontend/src/api/myResource.ts
   import { apiClient } from './client';

   export const myResourceApi = {
     getAll: async () => {
       const response = await apiClient.get('/my-resource');
       return response.data;
     }
   };
   ```

3. **Créer la page**
   ```typescript
   // frontend/src/pages/MyResource.tsx
   import { useQuery } from '@tanstack/react-query';
   import { myResourceApi } from '../api/myResource';

   export default function MyResource() {
     const { data } = useQuery({
       queryKey: ['myResources'],
       queryFn: myResourceApi.getAll
     });

     return <div>{/* ... */}</div>;
   }
   ```

4. **Ajouter la route**
   ```typescript
   // frontend/src/App.tsx
   <Route path="/my-resource" element={<MyResource />} />
   ```

## Debugging

### Backend

**Logs Symfony:**
```bash
docker compose logs -f php
```

**Logs SQL:**
```bash
docker compose logs -f database
```

**Shell dans le conteneur:**
```bash
docker compose exec php bash
```

**Console Symfony:**
```bash
docker compose exec php bin/console debug:router  # Routes
docker compose exec php bin/console debug:container  # Services
```

### Frontend

**React DevTools** - Extension Chrome/Firefox

**Console navigateur** - F12

**Network tab** - Voir les requêtes API

**TanStack Query DevTools** - Ajouter dans `App.tsx`:
```typescript
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';

// Dans le JSX
<ReactQueryDevtools initialIsOpen={false} />
```

## Base de données

### Migrations

**Créer une migration:**
```bash
docker compose exec php bin/console make:migration
```

**Exécuter les migrations:**
```bash
docker compose exec php bin/console doctrine:migrations:migrate
```

### Fixtures (données de test)

**Créer des fixtures:**
```bash
docker compose exec php bin/console make:fixtures
```

**Charger les fixtures:**
```bash
docker compose exec php bin/console doctrine:fixtures:load
```

## Tests

### Backend

```bash
docker compose exec php bin/phpunit
```

### Frontend

```bash
cd frontend
npm run test
```

## Performance

### Frontend

- **Code splitting** - Lazy loading des routes
- **React Query** - Cache automatique
- **Memo/useMemo** - Optimisation des rendus

### Backend

- **Doctrine Query Cache** - Cache des requêtes
- **HTTP Cache** - Headers Cache-Control
- **FrankenPHP Worker Mode** - Performance optimale

## Sécurité

### CORS

Configuré dans `config/packages/nelmio_cors.yaml`

### JWT

- Tokens stockés dans localStorage (frontend)
- Validés par LexikJWTAuthenticationBundle (backend)
- Expiration automatique

### HTTPS

- Certificats auto-signés en local
- Voir `SSL-TROUBLESHOOTING.md`

## Bonnes pratiques

### Frontend

✅ **DO:**
- Utiliser TanStack Query pour les requêtes API
- Typer toutes les props et states
- Créer des composants réutilisables
- Gérer les états de chargement et erreurs

❌ **DON'T:**
- Stocker des données sensibles en clair
- Faire des appels API directs sans cache
- Oublier la gestion d'erreur

### Backend

✅ **DO:**
- Utiliser les serialization groups
- Valider les données entrantes
- Gérer les erreurs proprement
- Documenter l'API

❌ **DON'T:**
- Exposer des données sensibles dans l'API
- Oublier la validation
- Ignorer les erreurs

## Ressources

- [Symfony Docs](https://symfony.com/doc)
- [React Docs](https://react.dev)
- [TanStack Query](https://tanstack.com/query)
- [Vite](https://vitejs.dev)
