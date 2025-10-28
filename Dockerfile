FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    sqlite \
    sqlite-dev \
    mysql-client \
    mariadb-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_sqlite pdo_mysql \
    && docker-php-ext-enable gd pdo_sqlite pdo_mysql

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

# Ensure vendor autoload exists
RUN if [ ! -f "vendor/autoload.php" ]; then \
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
        php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
        php -r "unlink('composer-setup.php');" && \
        composer install --no-dev --optimize-autoloader --ignore-platform-reqs; \
    fi

# Set proper permissions and ensure directories exist
RUN mkdir -p cache && \
    mkdir -p database && \
    touch database/database.sqlite && \
    sqlite3 database/database.sqlite < database/schema.sql && \
    chown -R www-data:www-data /app && \
    chmod -R 755 cache database && \
    chmod 666 database/database.sqlite

# Expose port (default: 8000)
EXPOSE 8000

# Start PHP built-in server from the public directory
CMD ["sh", "-c", "sqlite3 database/database.sqlite < database/schema.sql 2>/dev/null || true && php -S 0.0.0.0:8000 -t public/"]