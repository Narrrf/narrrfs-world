# Use PHP 8.2 with Apache as base image
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Copy all website files to Apache document root
COPY . /var/www/html/

# Install PHP extensions needed by admin uploads and image resizing
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd mysqli pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Upload limits for admin artwork uploads
RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/samuzi-uploads.ini \
    && echo "post_max_size=80M" >> /usr/local/etc/php/conf.d/samuzi-uploads.ini \
    && echo "max_file_uploads=30" >> /usr/local/etc/php/conf.d/samuzi-uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/samuzi-uploads.ini

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


