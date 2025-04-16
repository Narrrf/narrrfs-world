FROM php:8.1-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy source code into the container
COPY ./public /var/www/html
COPY ./api /var/www/html/api
COPY ./discord-tools /var/www/html/discord-tools
COPY ./db /var/www/html/db

# Set correct file permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/db

# Optional: Set working directory
WORKDIR /var/www/html

# Expose HTTP port
EXPOSE 80
