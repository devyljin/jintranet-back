#!/bin/bash

# Script pour installer le certificat SSL de Caddy dans le système macOS
# Usage: ./install-ssl-cert.sh

set -e

echo "🔧 Installation du certificat SSL Caddy pour localhost"
echo ""

# Vérifier que Docker est démarré
if ! docker compose ps | grep -q "Up"; then
    echo "⚠️  Les conteneurs Docker ne sont pas démarrés"
    echo "Démarrage des conteneurs..."
    docker compose up -d
    sleep 5
fi

# Extraire le certificat root depuis le conteneur
echo "📦 Extraction du certificat root depuis Caddy..."
docker compose exec php cat /data/caddy/pki/authorities/local/root.crt > /tmp/caddy-root.crt

# Afficher les informations du certificat
echo ""
echo "📜 Informations du certificat:"
openssl x509 -in /tmp/caddy-root.crt -noout -subject -issuer -dates
echo ""
echo "Empreinte SHA-256:"
openssl x509 -in /tmp/caddy-root.crt -noout -fingerprint -sha256
echo ""

# Vérifier si un certificat Caddy existe déjà
EXISTING_CERTS=$(security find-certificate -c "Caddy Local Authority" -a /Library/Keychains/System.keychain 2>/dev/null | grep -c "labl" || echo "0")

if [ "$EXISTING_CERTS" -gt 0 ]; then
    echo "⚠️  $EXISTING_CERTS ancien(s) certificat(s) Caddy trouvé(s) dans le trousseau"
    echo ""
    echo "Pour nettoyer les anciens certificats:"
    echo "1. Ouvrez 'Trousseau d'accès' (Applications → Utilitaires)"
    echo "2. Sélectionnez 'Système' dans la barre latérale"
    echo "3. Recherchez 'Caddy Local Authority'"
    echo "4. Supprimez TOUS les anciens certificats"
    echo ""
    read -p "Avez-vous nettoyé les anciens certificats? (o/N) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Oo]$ ]]; then
        echo "❌ Installation annulée. Veuillez d'abord nettoyer les anciens certificats."
        exit 1
    fi
fi

# Installer le certificat
echo ""
echo "🔐 Installation du certificat dans le trousseau système..."
echo "Mot de passe administrateur requis:"
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/caddy-root.crt

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Certificat installé avec succès!"
    echo ""
    echo "🧪 Test de la connexion HTTPS..."
    sleep 2

    if curl -s -I https://localhost > /dev/null 2>&1; then
        echo "✅ HTTPS fonctionne correctement!"
        echo ""
        echo "Vous pouvez maintenant utiliser https://localhost dans:"
        echo "  • Votre navigateur"
        echo "  • Bruno"
        echo "  • Toute autre application"
    else
        echo "⚠️  Le certificat est installé mais curl rencontre encore une erreur"
        echo ""
        echo "Solutions:"
        echo "1. Redémarrez votre navigateur/Bruno"
        echo "2. Vérifiez que le certificat est bien dans le trousseau:"
        echo "   security find-certificate -c 'Caddy Local Authority' /Library/Keychains/System.keychain"
        echo ""
        echo "Test avec le certificat explicite:"
        curl --cacert /tmp/caddy-root.crt -I https://localhost 2>&1 | head -10
    fi
else
    echo "❌ Erreur lors de l'installation du certificat"
    exit 1
fi
