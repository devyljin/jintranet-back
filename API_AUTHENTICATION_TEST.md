# Test d'authentification JWT

## 1. Créer un utilisateur

```bash
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "password123",
    "name": "John",
    "surname": "Doe",
    "birthdate": "1990-01-01"
  }'
```

Réponse attendue :
```json
{
  "message": "User created successfully",
  "user": "testuser"
}
```

## 2. Se connecter et obtenir le token JWT

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "password123"
  }'
```

Réponse attendue :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

## 3. Utiliser le token pour accéder aux routes protégées

```bash
curl -X GET http://localhost/api/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

Réponse attendue :
```json
{
  "id": 1,
  "username": "testuser",
  "name": "John",
  "surname": "Doe",
  "birthdate": "1990-01-01",
  "roles": ["ROLE_USER"]
}
```

## 4. Test sans token (doit échouer)

```bash
curl -X GET http://localhost/api/me
```

Réponse attendue : 401 Unauthorized

## Notes importantes

- Le token JWT expire après un certain temps (configurable dans `lexik_jwt_authentication.yaml`)
- Toutes les routes `/api/*` (sauf `/api/login` et `/api/register`) nécessitent un token JWT valide
- Le token doit être envoyé dans le header `Authorization: Bearer {token}`
