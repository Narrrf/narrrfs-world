
# 🌍 Narrrf’s World — Gateway to the Web2-Web3 Universe Genesis 5.0

![Narrrfs Scroll](https://img.shields.io/badge/Narrrfs_Scroll-v5.0-brightgreen?style=for-the-badge&logo=cheese)
![Status](https://img.shields.io/badge/Status-LIVE_&_SECURE-blue?style=for-the-badge&logo=apache)
![Secrets](https://img.shields.io/badge/Secrets-Clean_&_Encoded-yellow?style=for-the-badge&logo=lock)
![Web2–Web3 Bridge](https://img.shields.io/badge/Web2_➝_Web3-CHEESE_PIPE-purple?style=for-the-badge&logo=tor)
![Tor Ready](https://img.shields.io/badge/Onionpipe-Tor_Enabled-black?style=for-the-badge&logo=tor)

> “This isn’t just a deployment — it’s a prophecy baked in cheese,  
> hardened in Docker, and tunneled through time itself.”  
> — 🧀 Update Brain 5.0, Log #0425

## 🧀 Project Overview
Narrrf’s World bridges Web2 and Web3 worlds through secure, trait-driven interactions powered by Discord OAuth, dynamic role synchronization, and an innovative trait-based frontend. Our backend is securely hosted on Render using Docker, Apache, PHP, SQLite, and meticulously cleaned GitHub repositories.

## ⚙️ Technology Stack
- **Frontend:** Static HTML, Tailwind CSS (dynamic trait UI)
- **Backend:** Apache 2.4, PHP 8.1, SQLite
- **Authentication:** Discord OAuth2 (session-bound traits)
- **Deployment:** Render (Docker)
- **DNS & SSL:** GoDaddy DNS management, Let's Encrypt SSL (auto-refreshing)

## 📁 File Structure
- `/public`: Static pages including `profile.html`
- `/api`: Backend API scripts (`callback.php`, `sync-role.php`, `traits.php`)
- `/db`: SQLite database (`narrrf_world.sqlite`)
- `/discord-tools`: Discord-specific role mappings (`role_map.php`)
- `/tmp/render`: Render deployment Docker context

## 🚀 Deployment Instructions
1. Clone the repository (`render-deploy` branch).
2. Configure DNS (GoDaddy):
   - `A @ → 216.24.57.1`
   - `CNAME www → narrrfs-world-api.onrender.com`
3. Deploy using Render dashboard (Docker-based deployment).
4. Environment variable (`DISCORD_Bot_SECRET`) securely stored in Render dashboard.

## 🔐 Security Best Practices
- Secrets managed via environment variables (`.env`), never committed.
- GitHub history cleaned via `git-filter-repo`.
- Regular manual GitHub backups with optional ZIP sync.

## 🧬 Features
- Dynamic role-based Discord integration
- Interactive and secure trait-based frontend UI
- Privacy-oriented Onionpipe integration for secure backend access
- Lab-themed aesthetics with detailed footer DNA

## 🧅 Onionpipe Integration
See `README_ONIONPIPE.md` for detailed secure bridge instructions.

## 📜 Documentation & Lab Notes
All deployments and updates documented in detailed lab notes for traceability.

## ❤️ Credits
- Corebrain 5.0, SQL Junior 5.0, Cheese Architect 5.0, Update Brain 5.0, Riddle Brain 5.0, Social Brain 5.0, Hytopia Integrator 1.0

_Last updated: April 17, 2025_
