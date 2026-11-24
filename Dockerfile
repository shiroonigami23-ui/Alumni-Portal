# Use official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd opcache \
    && pecl install redis apcu && docker-php-ext-enable redis apcu

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules used by vhost config
RUN a2enmod rewrite headers

# Hardened PHP + Apache defaults
RUN { \
      echo 'expose_php = Off'; \
      echo 'display_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'allow_url_include = Off'; \
      echo 'session.use_strict_mode = 1'; \
      echo 'session.cookie_httponly = 1'; \
      echo 'session.cookie_samesite = Lax'; \
      echo 'upload_max_filesize = 20M'; \
      echo 'post_max_size = 25M'; \
      echo 'max_input_vars = 2000'; \
    } > /usr/local/etc/php/conf.d/zz-security.ini \
 && { \
      echo 'opcache.enable = 1'; \
      echo 'opcache.memory_consumption = 128'; \
      echo 'opcache.interned_strings_buffer = 16'; \
      echo 'opcache.max_accelerated_files = 20000'; \
      echo 'opcache.validate_timestamps = 0'; \
      echo 'opcache.jit = tracing'; \
      echo 'opcache.jit_buffer_size = 32M'; \
      echo 'apc.enabled = 1'; \
      echo 'apc.shm_size = 64M'; \
      echo 'realpath_cache_size = 4096K'; \
      echo 'realpath_cache_ttl = 600'; \
    } > /usr/local/etc/php/conf.d/zz-performance.ini \
 && { echo 'ServerTokens Prod'; echo 'ServerSignature Off'; echo 'TraceEnable Off'; } > /etc/apache2/conf-available/zz-security.conf \
 && a2enconf zz-security

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN mkdir -p /var/www/html/storage/cache /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 770 /var/www/html/storage

# Configure Apache
COPY deployment/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -fsS http://localhost/healthz || exit 1

# Start Apache
CMD ["apache2-foreground"]
