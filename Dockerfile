FROM php:8.3-fpm

# Installer Nginx et les dépendances système
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier tout le projet dans le conteneur
COPY . /var/www/html/

# Définir les permissions appropriées
RUN chown -R www-data:www-data /var/www/html

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Installer les dépendances Node.js et construire les assets
RUN npm install && npm run build

# Créer le lien de stockage
RUN php artisan storage:link

# Générer la clé d'application si elle n'existe pas
RUN if [ ! -f .env ]; then cp .env.example .env; fi && php artisan key:generate --no-interaction

# Copier la configuration Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Exposer le port 80
EXPOSE 80

# Démarrer PHP-FPM et Nginx
CMD service nginx start && php-fpm
