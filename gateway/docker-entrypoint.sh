#!/bin/sh
set -e
cd /var/www/html

# Sur Render il n'y a pas de fichier .env : les variables d'environnement suffisent.
# On en cree un vide pour que artisan key:generate puisse y ecrire la cle si besoin.
[ -f .env ] || touch .env

# Cle d'application : generee au premier demarrage si absente des variables d'environnement
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Base de donnees : on cree la base du service si elle n'existe pas encore
if [ -n "$DB_HOST" ]; then
  php -r '
    $h = getenv("DB_HOST"); $port = getenv("DB_PORT") ?: "5432";
    $u = getenv("DB_USERNAME"); $w = getenv("DB_PASSWORD"); $db = getenv("DB_DATABASE");
    try {
      $pdo = new PDO("pgsql:host=$h;port=$port;dbname=postgres", $u, $w);
      $existe = $pdo->query("SELECT 1 FROM pg_database WHERE datname = " . $pdo->quote($db))->fetchColumn();
      if (! $existe) { $pdo->exec("CREATE DATABASE \"$db\""); echo "Base $db creee.\n"; }
    } catch (Throwable $e) { /* migrate gere les erreurs */ }
  ' || true

  essais=0
  until php artisan migrate --force; do
    essais=$((essais + 1))
    if [ "$essais" -ge 30 ]; then
      echo "Echec des migrations apres 30 tentatives."
      exit 1
    fi
    echo "Base de donnees indisponible, nouvelle tentative dans 5 s..."
    sleep 5
  done
  if [ "$SEED_DEMO" = "true" ]; then
    php artisan db:seed --force || true
  fi
fi

php artisan config:cache || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" --no-reload
