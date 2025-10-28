FROM php:8.2-cli-alpine

# Install dependencies
RUN apk add --no-cache sqlite-libs sqlite-dev sqlite \
    && docker-php-ext-install pdo_sqlite

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy all source files (including composer.json and public/)
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Ensure .env exists
RUN cp -n .env.example .env || true

# Setup database and cache directories
RUN mkdir -p database cache && \
    chmod 775 database cache && \
    touch database/database.sqlite && \
    sqlite3 database/database.sqlite ".read database/schema.sql" || true

# Expose port
EXPOSE 8000

# Run PHP dev server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000} -t public"]
