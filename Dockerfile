FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    libicu-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite intl

# Install xdebug code coverage driver (build from source to bypass pecl.php.net)
RUN curl -fsSL https://github.com/xdebug/xdebug/archive/refs/tags/3.4.2.tar.gz -o /tmp/xdebug.tar.gz \
    && mkdir /tmp/xdebug \
    && tar -xzf /tmp/xdebug.tar.gz -C /tmp/xdebug --strip-components=1 \
    && cd /tmp/xdebug \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/xdebug /tmp/xdebug.tar.gz

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
