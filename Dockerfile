# Use a build stage to install dependencies
FROM composer:2 as build

WORKDIR /app

# Copy source files
COPY . .

# Install dependencies
# Use a cache mount to speed up subsequent builds
RUN --mount=type=cache,id=composer,target=/root/.composer/cache composer install --no-dev --optimize-autoloader

# Use a production-ready PHP image with Apache
FROM php:8.2-apache

# Railway provides the PORT environment variable
# Let's make sure Apache listens on the correct port
ARG PORT=8000
RUN sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Install required extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy application files from the build stage
COPY --from=build /app /var/www/html

# Set the document root to the public directory
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite

# Set correct permissions for the web server
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/database /var/www/html/cache

# The default CMD for php:apache is to run apache2-foreground, which is what we want.
# No need to specify a CMD unless we want to override it.
