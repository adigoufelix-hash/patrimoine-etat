FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# OPcache pour le serveur PHP intégré (SAPI CLI) : évite de relire des milliers de fichiers à chaque requête
RUN { echo 'opcache.enable_cli=1'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=16'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/zz-patrimoine-opcache.ini
# Plusieurs workers pour php artisan serve (PHP_CLI_SERVER_WORKERS est lu par Laravel)
ENV PHP_CLI_SERVER_WORKERS=8
WORKDIR /var/www/html
