FROM php:8.2.35-apache-trixie

# PHP2Shell emergency hardening for CVE-2026-109618 / CVE-2026-109619.
#
# DEVS FOR DECADES:
# Pin the patched PHP 8.2.35 Apache/Trixie runtime and disable PHP session
# upload-progress processing before request parsing. This changes deployment
# runtime security only. It does not modify application auth, database state,
# Genesis/Lab progression, Genetic Items, MouseFight, or economy state.
RUN printf '%s\n' \
    'session.upload_progress.enabled=0' \
    > /usr/local/etc/php/conf.d/narrrfs-security.ini \
    && php -r 'if (PHP_VERSION_ID !== 80235) { fwrite(STDERR, "ERROR: expected PHP 8.2.35.\\n"); exit(1); } if (ini_get("session.upload_progress.enabled") !== "0") { fwrite(STDERR, "ERROR: session.upload_progress.enabled must be 0.\\n"); exit(1); } echo "PHP security runtime gate passed\\n";'

# 2 Fix Apache FQDN warning
RUN echo "ServerName narrrfs.world" >> /etc/apache2/apache2.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# SEV-1 emergency containment is enabled at the Apache server layer so it does
# not depend on per-directory .htaccess behavior.
COPY ./deploy/apache/narrrfs-emergency-containment.conf /etc/apache2/conf-available/narrrfs-emergency-containment.conf
RUN a2enconf narrrfs-emergency-containment \
    && apache2ctl configtest

# Install sqlite3 CLI (optional: useful for debugging)
# mousefight_league_daily_production_v1
# Python 3 runs the read-only Genesis League daily snapshot publisher.
RUN apt-get update \
    && apt-get install -y sqlite3 python3 \
    && rm -rf /var/lib/apt/lists/*

# Copy static files
COPY ./public /var/www/html
COPY ./public/videos /var/www/html/videos
COPY ./api /var/www/html/api
COPY ./owner-control /var/www/html/owner-control
COPY ./league-audit/mousefight-league-v2-shadow-daily.py /var/www/html/league-audit/
COPY ./league-audit/mousefight-league-v2-shadow-baseline.py /var/www/html/league-audit/
COPY ./league-audit/mousefight-leagues-v2-shadow-snapshot-*.json /var/www/html/league-audit/
COPY ./discord-tools /var/www/html/discord-tools
COPY ./private /var/www/html/private
COPY ./scripts /var/www/html/scripts

# Copy database directory structure and migrations
COPY ./db/migrations /var/www/html/db/migrations
COPY ./db/schema.sql /var/www/html/db/schema.sql

# Ensure db/ exists (avoid build fail)
RUN mkdir -p /var/www/html/db


# ✅ Restore DB from /data if it exists (only during container start, not build)
# Moved to start.sh, not Dockerfile build-time

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/db

# Set working dir
WORKDIR /var/www/html

# Copy startup script and set it as entrypoint
COPY ./start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]
