#!/usr/bin/env bash
set -e

echo "🚀 Iniciando despliegue de Ecosistema Ureña..."

# 1. Activar modo mantenimiento (con reintento sugerido de 60s)
php artisan down --retry=60 || true

# 2. Descargar últimos cambios desde la rama main de GitHub
echo "📥 Actualizando código desde GitHub (origin/main)..."
git fetch origin main
git reset --hard origin/main

# 3. Instalar dependencias de Composer para producción
echo "📦 Instalando dependencias de PHP (Composer)..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 4. Asegurar enlace simbólico de almacenamiento público
php artisan storage:link || true

# 5. Ejecutar migraciones de base de datos
if [ "$1" == "--fresh" ]; then
    echo "🌱 Inicializando base de datos en blanco con Seeders (--fresh)..."
    php artisan migrate:fresh --seed --force
else
    echo "🗄️ Ejecutando migraciones pendientes..."
    php artisan migrate --force
fi

# 6. Limpiar y reconstruir cachés de producción
echo "⚡ Optimizando cachés de Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Reiniciar workers de colas (si existen activos)
php artisan queue:restart || true

# 8. Desactivar modo mantenimiento
php artisan up

echo "✅ Despliegue completado con éxito."
