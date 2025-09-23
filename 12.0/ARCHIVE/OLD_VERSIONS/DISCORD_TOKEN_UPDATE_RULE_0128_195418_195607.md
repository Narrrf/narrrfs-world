# Discord Token Update Rule - 0128

## 🎯 **RULE OBJECTIVE**
Maintain consistent Discord invite codes across the entire Narrrfs World project by following standardized update procedures.

## 🔑 **CURRENT DISCORD INVITE CODE**
- **Active Code:** `qYYNGJrR43`
- **Full URL:** `https://discord.gg/qYYNGJrR43`
- **Environment Variable:** `DISCORD_INVITE_CODE` (set on Render)

## 📋 **UPDATE PROCEDURE**

### **Phase 1: Environment Variable Update**
1. Update `DISCORD_INVITE_CODE` environment variable on Render
2. Verify the new code is active and accessible

### **Phase 2: Configuration Files Update**
Update these files with the new invite code as fallback:

#### **PHP Configuration Files**
- `narrrfs-world/public/discord-invite.php`
- `narrrfs-world/api/config/get-discord-config.php`

#### **JavaScript Configuration Files**
- `narrrfs-world/public/discord-config.js`

### **Phase 3: HTML Files Update**
Update all hardcoded Discord invite links in these files:
- `narrrfs-world/public/404.html`
- `narrrfs-world/public/faq.html`
- `narrrfs-world/public/Bingo.html`
- `narrrfs-world/public/experiment-x.html`
- `narrrfs-world/public/privacy-policy.html`
- `narrrfs-world/public/space-invaders-test.html`
- `narrrfs-world/public/mint.html`
- `narrrfs-world/public/whitepaper-pro.html`
- `narrrfs-world/public/project-updates.html`
- `narrrfs-world/public/hytopia.html`
- `narrrfs-world/public/index.html`

### **Phase 4: Verification**
1. Test Discord invite links on live site
2. Verify console logs show correct invite code
3. Confirm all pages redirect to correct Discord server

## 🚫 **PROHIBITED ACTIONS**
- Never hardcode old invite codes
- Never skip environment variable updates
- Never update only some files (must be comprehensive)

## ✅ **SUCCESS CRITERIA**
- All Discord links use the new invite code
- Environment variable is properly set
- Console logs confirm correct code loading
- No broken Discord redirects

## 🔄 **MAINTENANCE**
- Review and update this rule when invite codes change
- Document all changes in this file
- Ensure all team members follow this procedure

## 📝 **CHANGE LOG**
- **2025-01-28:** Created rule, updated to `qYYNGJrR43`
- **Previous:** `CR5mYu49` (deprecated)

---
*This rule ensures consistency and prevents broken Discord links across the entire Narrrfs World project.*
