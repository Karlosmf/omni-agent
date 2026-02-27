#!/bin/bash

# exit sequence on failure
set -e

echo "🚀 Iniciando despliegue de Luopan (v3.0.0)"

# Pull latest code
echo "📦 Descargando cambios de git..."
git pull origin main

# Install dependencies (if any composer/npm changes)
echo "🔒 Instalando dependencias de Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "⚙️ Instalando de NPM y compilando assets..."
npm ci
npm run build

# Clear/Cache
echo "🧹 Limpiando caché y optimizando (config, rutas, vistas)..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# Database migrations
echo "🗄️ Corriendo migraciones de base de datos..."
php artisan migrate --force

echo "✅ ¡Despliegue finalizado exitosamente!"
