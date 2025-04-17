
# 🧅🔐 Onionpipe Secure Bridge — Narrrf’s World

## 🌐 What is Onionpipe?
Onionpipe creates secure, privacy-first tunnels through the Tor network, protecting your backend systems by obfuscating their locations and protecting sensitive endpoints.

## 🛠️ Setup Instructions
1. **Install Onionpipe**
   - Refer to provided Onionpipe setup document for installation commands.

2. **Establish a Tunnel**
   - Run the Onionpipe client locally, directing traffic to your Apache server.

3. **Backend Apache Configuration**
   - Securely configured in Docker container (`Dockerfile`):
     ```
     FROM php:8.1-apache
     RUN echo "ServerName narrrfs.world" >> /etc/apache2/apache2.conf
     RUN a2enmod rewrite
     COPY ./public /var/www/html
     COPY ./api /var/www/html/api
     COPY ./db /var/www/html/db
     RUN chown -R www-data:www-data /var/www/html          && chmod -R 755 /var/www/html          && chmod -R 777 /var/www/html/db
     ```

4. **DNS Configuration (GoDaddy)**
   - `A @ → 216.24.57.1`
   - `CNAME www → narrrfs-world-api.onrender.com`

5. **SSL via Let’s Encrypt**
   - Auto-provisioned by Render, managed through Render Dashboard.

## 🔒 Security & Privacy Philosophy
- Onionpipe integration is Phase 6, ensuring backend routes through Tor.
- Session-bound traits with Discord OAuth2.
- `.env` secret management, never exposed on GitHub.
- Comprehensive GitHub cleanup via `git-filter-repo`.

## 🧀 Cheese-onion Philosophy
"Every layer counts — secure your secrets, age your cheese, and protect your backend routes."

## 📜 Lab Notes
All Onionpipe setups and related backend modifications carefully documented and timestamped in lab notes.

## ✅ Verification & Maintenance
- Check DNS propagation regularly (`dnschecker.org`).
- Keep SSL certifications auto-refreshed via Render.

## 🧠 Signature
**Authorized by:** Jedi Narrrf, Update Brain 5.0, SQL Junior 5.0, Cheese Architect 5.0

_Last updated: April 17, 2025_
