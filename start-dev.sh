#!/bin/bash

# Script pour démarrer l'environnement de développement complet

set -e

echo "🚀 Démarrage de l'environnement de développement Intragrume"
echo ""

# Vérifier que Docker est démarré
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker n'est pas démarré. Veuillez démarrer Docker Desktop."
    exit 1
fi

# Démarrer le backend
echo "📦 Démarrage du backend (Symfony + PostgreSQL)..."
docker compose up -d

# Attendre que les services soient prêts
echo "⏳ Attente du démarrage des services..."
sleep 5

# Vérifier que les services sont en cours d'exécution
if docker compose ps | grep -q "Up"; then
    echo "✅ Backend démarré sur https://localhost"
else
    echo "❌ Erreur lors du démarrage du backend"
    docker compose logs --tail=20
    exit 1
fi

# Vérifier si le frontend est installé
if [ ! -d "frontend/node_modules" ]; then
    echo "📦 Installation des dépendances frontend..."
    cd frontend
    npm install
    cd ..
fi

# Démarrer le frontend
echo "⚛️  Démarrage du frontend (React)..."
echo ""
echo "Le frontend démarrera sur http://localhost:5173"
echo "Le backend API est accessible sur https://localhost/api/v1"
echo ""
echo "Pour arrêter:"
echo "  - Frontend: Ctrl+C"
echo "  - Backend: docker compose down"
echo ""
echo "---"
echo ""

cd frontend
npm run dev
