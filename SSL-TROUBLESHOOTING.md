# Dépannage SSL pour Intragrume

## État actuel ✅

- **HTTPS fonctionne** sur `https://localhost`
- **Certificat CA** installé dans le trousseau macOS
- **Certificat valide** jusqu'au 15 septembre 2035

## Tests rapides

### Test 1: Vérifier que le serveur répond
```bash
docker compose ps
# Le service 'php' doit être 'Up' et avoir les ports 443:443
```

### Test 2: Tester HTTPS avec curl
```bash
curl https://localhost
# Doit retourner la page Symfony sans erreur SSL
```

### Test 3: Vérifier le certificat installé
```bash
security find-certificate -c "Caddy Local Authority" /Library/Keychains/System.keychain
# Doit trouver: "Caddy Local Authority - 2025 ECC Root"
```

## Problèmes courants

### ❌ "Connection refused" sur le port 443

**Cause:** Docker n'est pas démarré

**Solution:**
```bash
docker compose up -d
```

### ❌ "SSL certificate problem: unable to get local issuer certificate"

**Cause:** Le certificat CA n'est pas installé ou est expiré

**Solution:**
```bash
# 1. Extraire le certificat actuel de Caddy
docker compose exec php cat /data/caddy/pki/authorities/local/root.crt > /tmp/caddy-root.crt

# 2. Supprimer les anciens certificats
# Ouvrir Trousseau d'accès → Système → Chercher "Caddy" → Tout supprimer

# 3. Installer le nouveau certificat
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/caddy-root.crt
```

Ou utilisez le script automatique:
```bash
./install-ssl-cert.sh
```

### ❌ Bruno ne fonctionne pas avec HTTPS

**Solutions:**

1. **Redémarrer complètement Bruno**
   ```bash
   pkill -9 "Bruno"
   # Puis relancer Bruno depuis Applications
   ```

2. **Vérifier la configuration Bruno**
   - Le fichier `Intragrume/bruno.json` doit avoir:
   ```json
   "clientCertificates": {
     "enabled": false,
     "certs": []
   }
   ```

3. **Désactiver la vérification SSL temporairement**
   - Dans Bruno: Settings → SSL → Décocher "Verify SSL certificates"
   - ⚠️ À utiliser uniquement en développement local

### ❌ Les certificats expirent régulièrement

**Cause:** Caddy renouvelle automatiquement ses certificats locaux

**Solution:**
- Exécuter `./install-ssl-cert.sh` après chaque renouvellement
- Ou ajouter au `.gitignore` et créer un hook git

## Configuration actuelle

### Certificat CA (Root)
- **Nom:** Caddy Local Authority - 2025 ECC Root
- **Validité:** 6 novembre 2025 → 15 septembre 2035
- **Type:** ECC (Elliptic Curve)
- **Emplacement:** Docker `/data/caddy/pki/authorities/local/root.crt`

### Ports
- **HTTP:** 80
- **HTTPS:** 443
- **HTTP/3:** 443 (UDP)

### Configuration Caddy
- **Auto-TLS:** Activé pour `localhost`
- **Protocoles:** HTTP/2, HTTP/3
- **OCSP Stapling:** Tenté (mais non applicable pour certificats locaux)

## URLs de test

- `https://localhost` → Page d'accueil Symfony
- `https://localhost/api/v1` → API (peut retourner 404 si pas de route)
- `https://localhost/.well-known/mercure` → Hub Mercure

## Scripts utiles

### install-ssl-cert.sh
Script automatique pour installer le certificat CA dans le système.

### Vérification manuelle du certificat
```bash
# Voir le certificat présenté par le serveur
openssl s_client -connect localhost:443 -showcerts </dev/null 2>/dev/null | openssl x509 -text -noout

# Comparer avec le CA installé
openssl x509 -in /tmp/caddy-root.crt -fingerprint -sha256
```

## En cas de problème persistant

1. **Nettoyer complètement:**
   ```bash
   # Arrêter Docker
   docker compose down

   # Supprimer tous les certificats Caddy du trousseau
   # (via Trousseau d'accès GUI)

   # Supprimer les données Caddy
   docker volume rm intragrume_caddy_data intragrume_caddy_config

   # Redémarrer
   docker compose up -d

   # Réinstaller le certificat
   ./install-ssl-cert.sh
   ```

2. **Vérifier les logs Caddy:**
   ```bash
   docker compose logs php | grep -i "tls\|cert\|ssl"
   ```

3. **Redémarrer complètement:**
   ```bash
   docker compose restart
   ```
