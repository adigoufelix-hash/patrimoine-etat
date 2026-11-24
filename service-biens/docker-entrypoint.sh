#!/bin/sh
set -e
cd /var/www/html

# Clé d'application : générée au premier démarrage si absente des variables d'environnement
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Base de données : on crée la base du service si elle n'existe pas encore
# (plusieurs services peuvent démarrer en même temps : les erreurs « existe déjà » sont ignorées)
if [ -n "$DB_HOST" ]; then
  php -r '
    $h = getenv("DB_HOST"); $port = getenv("DB_PORT") ?: "5432";
    $u = getenv("DB_USERNAME"); $w = getenv("DB_PASSWORD"); $db = getenv("DB_DATABASE");
    try {
      $pdo = new PDO("pgsql:host=$h;port=$port;dbname=postgres", $u, $w);
      $existe = $pdo->query("SELECT 1 FROM pg_database WHERE datname = " . $pdo->quote($db))->fetchColumn();
      if (! $existe) { $pdo->exec("CREATE DATABASE \"$db\""); echo "Base $db créée.\n"; }
    } catch (Throwable $e) { /* on laisse migrate --force gerer les erreurs */ }
  ' || true

  # Migrations : on réessaie tant que la base n'est pas joignable (démarrage simultané des services)
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
