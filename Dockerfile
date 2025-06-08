FROM php:8.1-apache

# Fix Apache FQDN warning
RUN echo "ServerName narrrfs.world" >> /etc/apache2/apache2.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# Install sqlite3 CLI
RUN apt-get update && apt-get install -y sqlite3

# Copy files
COPY ./public /var/www/html
COPY ./api /var/www/html/api
COPY ./discord-tools /var/www/html/discord-tools
COPY ./db/schema.sql /var/www/html/db/schema.sql

# Ensure db/ exists
RUN mkdir -p /var/www/html/db

# Create SQLite DB from schema
RUN sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/schema.sql

# Restore DB from /data to /var/www/html/db only if it exists
RUN mkdir -p /var/www/html/db && \
    if [ -f /data/narrrf_world.sqlite ]; then \
      cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/db

WORKDIR /var/www/html

# At the end of Dockerfile
COPY ./start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]


