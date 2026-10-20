FROM php:8.2-apache

# Install system dependencies & extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html
WORKDIR /var/www/html

# Set document root to Laravel public folder
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Start Apache and run migrations
CMD php artisan config:cache && php artisan route:cache && php artisan migrate --force && apache2-foreground