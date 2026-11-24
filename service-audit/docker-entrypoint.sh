#!/bin/sh
set -e
cd /var/www/html

# Clé d'application : générée au premier démarrage si absente des variables d'environnement
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Migrations : uniquement pour les services qui ont une base (DB_HOST renseigné).
# On réessaie tant que la base n'est pas joignable (démarrage simultané des services).
if [ -n "$DB_HOST" ]; then
  essais=0
  until php artisan migrate --force; do
    essais=$((essais + 1))
    if [ "$essais" -ge 30 ]; then
      echo "Échec des migrations après 30 tentatives."
      exit 1
    fi
    echo "Base de données indisponible, nouvelle tentative dans 5 s..."
    sleep 5
  done
  if [ "$SEED_DEMO" = "true" ]; then
    php artisan db:seed --force || true
  fi
fi

php artisan config:cache || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --no-reload
