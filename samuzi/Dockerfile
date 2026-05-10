# Use PHP 8.2 with Apache as base image
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Copy all website files to Apache document root
COPY . /var/www/html/

# Install necessary PHP extensions (if needed)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache to allow .htaccess overrides
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/website-template.conf \
    && a2enconf website-template

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]


