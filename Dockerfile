FROM php:8.2-cli

# Install dependencies sistem + ekstensi PHP yang dibutuhkan
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT