# Use the Apache-based PHP image for web applications
FROM php:8.4-apache

# Install SQLite and its dependencies
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# Enable Apache mod_rewrite for URL rewriting (if needed)
RUN a2enmod rewrite

# Set the default PHP configuration (production mode)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy application files into the container
COPY . /var/www/html/

# Set proper permissions for the web files (be careful with permissions)
RUN chmod -R 755 /var/www/html

RUN php /var/www/html/init_db.php

# Set the working directory (optional)
WORKDIR /var/www/html

# Run the Apache server, which will keep the container running
CMD ["apache2-foreground"]