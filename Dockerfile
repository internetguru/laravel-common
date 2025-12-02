FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Install xdebug code coverage driver
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy the application code into the container
COPY . /app

# Install PHP dependencies
RUN composer install --prefer-dist --no-interaction

# Run the PHPUnit tests by default
CMD ["./vendor/bin/phpunit", "--coverage-text"]
