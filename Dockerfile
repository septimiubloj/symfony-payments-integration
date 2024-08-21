FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
  git zip unzip libpng-dev \
  libzip-dev default-mysql-client

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip gd

# Enable Apache mod_rewrite for Symfony
RUN a2enmod rewrite

RUN a2ensite 000-default

# Set working directory
WORKDIR /var/www

# Copy the composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . /var/www

# Install composer dependencies (full install to include autoloader and scripts)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader

# Update Apache config to serve the Symfony public directory
RUN sed -i 's!/var/www/html!/var/www/html/public!g' \
  /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]