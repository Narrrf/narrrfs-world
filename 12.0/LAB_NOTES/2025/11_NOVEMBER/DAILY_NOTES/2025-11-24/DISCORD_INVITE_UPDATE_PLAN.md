# 🚨 DISCORD INVITE UPDATE PLAN - November 24, 2025

## 🎯 **UPDATE OBJECTIVE**

**Update all Discord invite links from current fallback code to new invite code:**
- **OLD Fallback:** `dSJDkDhPKZ`
- **OLD Reference (in rules):** `CvstbUQ5yX`
- **NEW Invite Code:** `zgjAwzuDqV`
- **Full New URL:** `https://discord.gg/zgjAwzuDqV`

---

## 📋 **MANDATORY UPDATE CHECKLIST**

### **✅ STEP 1: UPDATE FALLBACK FILES (CRITICAL)**

These files contain fallback values that are used when the environment variable is not set:

#### **1.1 JavaScript Config File:**
- **File:** `public/discord-config.js`
- **Line 9:** Change `inviteCode: 'dSJDkDhPKZ'` → `inviteCode: 'zgjAwzuDqV'`
- **Line 49:** Update console.warn message fallback reference
- **Line 185:** Update check for old invite code (if still references CvstbUQ5yX)

#### **1.2 PHP Fallback Files:**
- **File:** `public/discord-invite.php`
  - **Line 6:** Change `'dSJDkDhPKZ'` → `'zgjAwzuDqV'`

- **File:** `api/config/discord.php`
  - **Line 9:** Change `'dSJDkDhPKZ'` → `'zgjAwzuDqV'`
  - **Line 13:** Change `'dSJDkDhPKZ'` → `'zgjAwzuDqV'`

- **File:** `api/config/get-discord-config.php`
  - **Line 13:** Change `'dSJDkDhPKZ'` → `'zgjAwzuDqV'`

- **File:** `api/admin/get-discord-config.php`
  - **Line 32:** Change `'dSJDkDhPKZ'` → `'zgjAwzuDqV'`
  - **Line 63:** Change `'dSJDkDhPKZ'` → `'zgjAwzuDqV'`

---

### **✅ STEP 2: SEARCH FOR HARDCODED INVITE LINKS**

Search for any hardcoded Discord invite links in HTML, JS, and PHP files:

#### **2.1 Search Commands:**
```bash
# Search for old invite codes
grep -r "dSJDkDhPKZ" . --include="*.html" --include="*.js" --include="*.php"
grep -r "CvstbUQ5yX" . --include="*.html" --include="*.js" --include="*.php"
grep -r "discord.gg" . --include="*.html" --include="*.js" --include="*.php"
```

#### **2.2 Files to Check:**
- `public/index.html`
- `public/profile.html`
- `public/project-updates.html`
- `public/space-cheese-invaders.html`
- `public/privacy-policy.html`
- `public/hytopia.html`
- `public/Bingo.html`
- `public/whitepaper-pro.html`
- `public/experiment-x.html`
- `public/404.html`
- `public/mint.html`
- `public/faq.html`
- `public/js/role-gate.js`
- `public/README.md`
- Any other HTML/JS files in `public/` directory

---

### **✅ STEP 3: UPDATE ENVIRONMENT VARIABLE (USER ACTION)**

**⚠️ USER WILL UPDATE THIS IN RENDER ENVIRONMENT:**
- **Environment Variable:** `DISCORD_INVITE_CODE`
- **New Value:** `zgjAwzuDqV`
- **Location:** Render Dashboard → Environment Variables
- **Action Required:** Update the `DISCORD_INVITE_CODE` variable in Render

**Note:** Once the environment variable is updated in Render, all API endpoints will automatically use the new value. The fallback files are only used if the environment variable is not set.

---

### **✅ STEP 4: VERIFICATION**

#### **4.1 Verify No Old Invite Codes Remain:**
```bash
# Should return NO results after update
grep -r "dSJDkDhPKZ" . --include="*.html" --include="*.js" --include="*.php"
grep -r "CvstbUQ5yX" . --include="*.html" --include="*.js" --include="*.php"
```

#### **4.2 Test Local Development:**
1. Check `http://localhost/public/discord-invite.php` - Should show new invite
2. Check `http://localhost/api/config/get-discord-config.php` - Should return new invite
3. Check browser console on any page - Should show new invite code in logs

#### **4.3 Test Production (After Render Update):**
1. Check `https://narrrfs.world/api/config/get-discord-config.php` - Should return new invite
2. Check any page with Discord links - Should use new invite
3. Verify Discord invite link works: `https://discord.gg/zgjAwzuDqV`

---

## 🔧 **FILES TO UPDATE SUMMARY**

### **Priority 1 - Fallback Files (Must Update):**
1. ✅ `public/discord-config.js` - Line 9
2. ✅ `public/discord-invite.php` - Line 6
3. ✅ `api/config/discord.php` - Lines 9, 13
4. ✅ `api/config/get-discord-config.php` - Line 13
5. ✅ `api/admin/get-discord-config.php` - Lines 32, 63

### **Priority 2 - Search and Update (If Found):**
- Any HTML files with hardcoded Discord links
- Any JavaScript files with hardcoded Discord links
- Any PHP files with hardcoded Discord links (besides fallbacks)

---

## 🚀 **DEPLOYMENT PROCESS**

### **1. Local Testing:**
- Update all fallback files
- Test locally to ensure new invite code works
- Verify no old invite codes remain

### **2. Git Commit:**
```bash
git add .
git commit -m "URGENT: Update Discord invite fallback to zgjAwzuDqV

- Updated all PHP fallback files
- Updated JavaScript config fallback
- Ready for Render environment variable update"
```

### **3. Push to Repository:**
```bash
git push origin render-deploy
```

### **4. Render Environment Update (USER ACTION):**
- Go to Render Dashboard
- Navigate to Environment Variables
- Update `DISCORD_INVITE_CODE` to `zgjAwzuDqV`
- Save changes (will trigger redeploy)

---

## 📝 **UPDATE TEMPLATE**

### **For Each Fallback File:**
```php
// OLD
$discord_invite_code = getenv('DISCORD_INVITE_CODE') ?: 'dSJDkDhPKZ';

// NEW
$discord_invite_code = getenv('DISCORD_INVITE_CODE') ?: 'zgjAwzuDqV';
```

### **For JavaScript Config:**
```javascript
// OLD
inviteCode: 'dSJDkDhPKZ', // Fallback Discord invite code

// NEW
inviteCode: 'zgjAwzuDqV', // Fallback Discord invite code
```

---

## ✅ **SUCCESS CRITERIA**

- [ ] All fallback files updated with new invite code
- [ ] No old invite codes remain in codebase
- [ ] Local testing successful
- [ ] Git commit created
- [ ] Code pushed to repository
- [ ] Render environment variable updated (USER ACTION)
- [ ] Production verification successful
- [ ] New Discord invite link works: `https://discord.gg/zgjAwzuDqV`

---

## 🚨 **CRITICAL NOTES**

1. **Environment Variable:** User will update `DISCORD_INVITE_CODE` in Render themselves
2. **Fallback Values:** Must be updated in code for local development and as backup
3. **Dynamic System:** The `discord-config.js` system automatically updates links on page load
4. **No Breaking Changes:** This update only changes the invite code, no functionality changes

---

## 📊 **FILES STATUS**

### **Files Requiring Update:**
- [ ] `public/discord-config.js`
- [ ] `public/discord-invite.php`
- [ ] `api/config/discord.php`
- [ ] `api/config/get-discord-config.php`
- [ ] `api/admin/get-discord-config.php`

### **Files to Search (May Need Update):**
- [ ] All HTML files in `public/` directory
- [ ] All JavaScript files in `public/js/` directory
- [ ] Any other files with hardcoded Discord links

---

**PLAN CREATED:** November 24, 2025  
**STATUS:** ✅ **READY FOR IMPLEMENTATION**  
**NEW INVITE CODE:** `zgjAwzuDqV`  
**FULL URL:** `https://discord.gg/zgjAwzuDqV`

