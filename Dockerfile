FROM php:8.2-cli-alpine

# Install dependencies
RUN apk add --no-cache sqlite \
    && docker-php-ext-install pdo_sqlite

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application
COPY . .

# Setup database and cache directories
RUN mkdir -p database cache && \
    chmod 775 database cache

# Initialize database if it doesn't exist (will run at build time)
RUN if [ ! -f database/database.sqlite ]; then \
    touch database/database.sqlite && \
    sqlite3 database/database.sqlite < database/schema.sql; \
    fi

# Expose port
EXPOSE 8000

# Run built-in PHP server from public directory
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000} -t public"]
