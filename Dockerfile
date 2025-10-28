FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    sqlite-dev \
    mysql-client \
    mariadb-dev \
    && docker-php-ext-install pdo_sqlite pdo_mysql \
    && docker-php-ext-enable pdo_sqlite pdo_mysql

# Set working directory
WORKDIR /app

# Copy composer.json and install dependencies
COPY composer.json ./

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Install dependencies and generate lock file
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# Copy the rest of the application
COPY . .

# Setup environment file
COPY .env.example .env

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Set proper permissions and ensure cache directory exists
RUN mkdir -p cache && \
    chown -R www-data:www-data /app && \
    chmod -R 755 cache database

# Expose port
EXPOSE 8080

# Start PHP built-in server from the public directory
CMD ["sh", "-c", "php -S 0.0.0.0:8080 -t public/"]