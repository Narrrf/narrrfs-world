FROM php:8.1-apache

# Fix Apache FQDN warning
RUN echo "ServerName narrrfs.world" >> /etc/apache2/apache2.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy files
COPY ./public /var/www/html
COPY ./api /var/www/html/api
COPY ./discord-tools /var/www/html/discord-tools

# ✅ Ensure db/ exists to avoid build failures
RUN mkdir -p /var/www/html/db

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/db

WORKDIR /var/www/html
