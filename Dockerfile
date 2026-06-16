FROM php:8.4-cli

# Install system dependencies + PostgreSQL + zip extensions
RUN apt-get update && apt-get install -y \
    git curl libpq-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy app files
COPY . .

# Install PHP dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chmod -R 775 storage bootstrap/cache

EXPOSE ${PORT:-8000}

# At startup: cache config, run migrations, seed presets, serve
CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan migrate --force \
    && php artisan db:seed --class=CategorySeeder --force \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
