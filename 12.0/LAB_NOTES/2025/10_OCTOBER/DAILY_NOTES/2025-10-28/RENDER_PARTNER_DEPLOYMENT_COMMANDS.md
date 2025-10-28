# 🚀 PARTNER PORTAL - RENDER DEPLOYMENT COMMANDS

**Date:** October 28, 2025  
**Time:** 23:55  
**Purpose:** Step-by-step commands to deploy Partner Portal to production  

---

## 📋 **DEPLOYMENT SEQUENCE**

### **STEP 1: Git Push (Run Locally First!)**

```powershell
cd C:\xampp-server\htdocs\narrrfs-world

git status

git add .

git commit -m "🤝 Partner Portal System - Production Deployment

✅ Complete partner management system
✅ Admin interface with full CRUD
✅ Image upload system (logo + banner)
✅ Beautiful frontend showcase page
✅ Site-wide navigation integration
✅ Local testing bypass + production auth
✅ 3 demo partners included
✅ Under Cheese-struction banner

Ready for production with admin customization!"

git push origin render-deploy
```

**Wait for Render auto-deployment to complete (~2-3 minutes)**

---

## 🗄️ **STEP 2: Create Partners Table on Render**

**SSH into Render, then run:**

```bash
cd /var/www/html/db

# Backup current database first
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# Create partners table with SQL script
cat > create_partners.sql << 'EOF'
CREATE TABLE IF NOT EXISTS tbl_partners (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  partner_name TEXT NOT NULL,
  partner_slug TEXT UNIQUE NOT NULL,
  logo_filename TEXT,
  banner_filename TEXT,
  short_description TEXT,
  long_description TEXT,
  partner_type TEXT,
  discord_url TEXT,
  twitter_url TEXT,
  website_url TEXT,
  additional_info TEXT,
  is_featured INTEGER DEFAULT 0,
  is_active INTEGER DEFAULT 1,
  display_order INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_partners_active ON tbl_partners(is_active);
CREATE INDEX IF NOT EXISTS idx_partners_featured ON tbl_partners(is_featured);
CREATE INDEX IF NOT EXISTS idx_partners_order ON tbl_partners(display_order);
CREATE INDEX IF NOT EXISTS idx_partners_slug ON tbl_partners(partner_slug);
EOF

# Execute SQL to create table
sqlite3 narrrf_world.sqlite < create_partners.sql

# Verify table created
echo ".tables" | sqlite3 narrrf_world.sqlite | grep partners

echo "✅ Table created!"
```

---

## 📝 **STEP 3: Insert Demo Partners**

**Run these INSERT commands on Render:**

```bash
# Still in /var/www/html/db

# Insert Partner 1: Gensuki
sqlite3 narrrf_world.sqlite "INSERT INTO tbl_partners (partner_name, partner_slug, short_description, long_description, partner_type, discord_url, twitter_url, website_url, is_featured, display_order) VALUES ('Gensuki', 'gensuki', 'Premium NFT collection with exclusive benefits and community perks.', 'Gensuki is a premium NFT collection offering exclusive benefits to holders. Partner with Narrrf''s World to provide special discounts and cross-community collaboration.', 'NFT Project', 'https://discord.gg/gensuki', 'https://twitter.com/gensuki', 'https://gensuki.io', 1, 1);"

echo "✅ Partner 1 inserted (Gensuki)"

# Insert Partner 2: Golden Baboons
sqlite3 narrrf_world.sqlite "INSERT INTO tbl_partners (partner_name, partner_slug, short_description, long_description, partner_type, discord_url, twitter_url, website_url, is_featured, display_order) VALUES ('Golden Baboons', 'golden-baboons', 'Elite gaming community with competitive tournaments and exclusive events.', 'Golden Baboons represents an elite gaming community focused on competitive play and exclusive events. Our partnership brings together skilled players for cross-community tournaments.', 'Gaming Community', 'https://discord.gg/goldenbaboons', 'https://twitter.com/goldenbaboons', 'https://goldenbaboons.gg', 1, 2);"

echo "✅ Partner 2 inserted (Golden Baboons)"

# Insert Partner 3: Placeholder
sqlite3 narrrf_world.sqlite "INSERT INTO tbl_partners (partner_name, partner_slug, short_description, long_description, partner_type, discord_url, twitter_url, website_url, is_featured, display_order) VALUES ('[Partner Name]', 'partner-placeholder-3', 'Add your partner description through the admin interface.', 'This is a placeholder. Use the Partners tab in admin interface to update with real partner information, logos, and social links.', 'Community Partner', 'https://discord.gg/placeholder', 'https://twitter.com/placeholder', 'https://example.com', 0, 3);"

echo "✅ Partner 3 inserted (Placeholder)"
```

---

## ✅ **STEP 4: Verify Data**

```bash
# Check partner count
echo "SELECT COUNT(*) FROM tbl_partners;" | sqlite3 narrrf_world.sqlite

# Expected: 3

# List all partners
echo "SELECT partner_name, partner_type, is_featured, is_active FROM tbl_partners ORDER BY display_order;" | sqlite3 narrrf_world.sqlite

# Expected output:
# Gensuki|NFT Project|1|1
# Golden Baboons|Gaming Community|1|1
# [Partner Name]|Community Partner|0|1
```

---

## 📁 **STEP 5: Create Images Directory**

```bash
# Navigate to public directory
cd /var/www/html/public

# Create partners images directory
mkdir -p img/partners

# Set proper permissions for uploads
chmod 755 img/partners

# Verify directory exists
ls -la img/ | grep partners

# Expected: drwxr-xr-x ... img/partners

echo "✅ Images directory created with correct permissions!"
```

---

## 💾 **STEP 6: Backup Database**

```bash
# Navigate back to db directory
cd /var/www/html/db

# Copy database to /data for next deployment
cp narrrf_world.sqlite /data/narrrf_world.sqlite

echo "✅ Database backed up to /data!"
```

---

## 🧪 **STEP 7: Test Live Site**

### **Test Public Partners Page:**
```
https://narrrfs.world/public/partners.html
```

**Verify:**
- [ ] Page loads correctly
- [ ] "Under Cheese-struction" banner shows
- [ ] Featured section shows Gensuki & Golden Baboons
- [ ] All partners section shows [Partner Name]
- [ ] Click partner card → modal opens
- [ ] Social links work (Discord, Twitter, Website)
- [ ] Mobile responsive works

### **Test Admin Interface:**
```
https://narrrfs.world/public/admin-interface.html
```

**As Admin:**
1. **Login** to admin interface
2. **Go to** Partners tab
3. **Click** "Refresh List" button

**Verify:**
- [ ] 3 partners load (Gensuki, Golden Baboons, placeholder)
- [ ] Each partner shows logo placeholder
- [ ] Status badges show (Featured, Active, Order)
- [ ] Social link badges show
- [ ] All buttons visible (Edit, Upload Logo, Upload Banner, Deactivate, Delete)

### **Test Admin Functions:**

**Edit Partner:**
1. **Click** "✏️ Edit All Fields" on Gensuki
2. **Verify** form fills with data
3. **Change** short description
4. **Click** "💾 Update Partner"
5. **Verify** changes save

**Upload Logo:**
1. **Click** "🖼️ Upload Logo" on Gensuki
2. **Select** a test image
3. **Verify** upload success
4. **Refresh** partners.html
5. **Verify** logo appears on card

---

## 🔍 **TROUBLESHOOTING**

### **If Table Creation Fails:**
```bash
# Check if table already exists
echo ".schema tbl_partners" | sqlite3 narrrf_world.sqlite

# If exists, drop and recreate
echo "DROP TABLE IF EXISTS tbl_partners;" | sqlite3 narrrf_world.sqlite
# Then run create_partners.sql again
```

### **If Partners Don't Load:**
```bash
# Check table data
echo "SELECT * FROM tbl_partners;" | sqlite3 narrrf_world.sqlite

# Check table count
echo "SELECT COUNT(*) FROM tbl_partners;" | sqlite3 narrrf_world.sqlite
```

### **If Image Upload Fails:**
```bash
# Check directory exists
ls -la /var/www/html/public/img/partners

# Check permissions
stat /var/www/html/public/img/partners

# Fix permissions if needed
chmod 755 /var/www/html/public/img/partners
```

### **If Admin Can't Access:**
```bash
# Verify you're logged in as admin
# Check session in browser dev tools
# Verify admin role in database
```

---

## 📊 **EXPECTED RESULTS**

### **After Successful Deployment:**

**Public Partners Page:**
- ✅ Shows 3 demo partners
- ✅ "Under Cheese-struction" banner visible
- ✅ Featured partners in separate section
- ✅ Click cards → modals open
- ✅ Social links work

**Admin Interface:**
- ✅ Partners tab loads
- ✅ 3 partners in list (can edit all)
- ✅ Add new partner works
- ✅ Edit partner works
- ✅ Upload images works
- ✅ Delete works (with confirmation)

**Navigation:**
- ✅ All pages have Partners link
- ✅ Partners link works on all pages
- ✅ Consistent styling

---

## 🎯 **COMPLETE COMMAND SEQUENCE**

**Copy/paste this entire block into Render shell:**

```bash
# === PARTNER PORTAL DEPLOYMENT ===

echo "🚀 Starting Partner Portal deployment..."

# Navigate to database directory
cd /var/www/html/db

# Backup
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite
echo "✅ Backup created"

# Create table
cat > create_partners.sql << 'EOF'
CREATE TABLE IF NOT EXISTS tbl_partners (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  partner_name TEXT NOT NULL,
  partner_slug TEXT UNIQUE NOT NULL,
  logo_filename TEXT,
  banner_filename TEXT,
  short_description TEXT,
  long_description TEXT,
  partner_type TEXT,
  discord_url TEXT,
  twitter_url TEXT,
  website_url TEXT,
  additional_info TEXT,
  is_featured INTEGER DEFAULT 0,
  is_active INTEGER DEFAULT 1,
  display_order INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_partners_active ON tbl_partners(is_active);
CREATE INDEX IF NOT EXISTS idx_partners_featured ON tbl_partners(is_featured);
CREATE INDEX IF NOT EXISTS idx_partners_order ON tbl_partners(display_order);
CREATE INDEX IF NOT EXISTS idx_partners_slug ON tbl_partners(partner_slug);
EOF

sqlite3 narrrf_world.sqlite < create_partners.sql
echo "✅ Table created"

# Insert demo partners
sqlite3 narrrf_world.sqlite "INSERT INTO tbl_partners (partner_name, partner_slug, short_description, long_description, partner_type, discord_url, twitter_url, website_url, is_featured, display_order) VALUES ('Gensuki', 'gensuki', 'Premium NFT collection with exclusive benefits and community perks.', 'Gensuki is a premium NFT collection offering exclusive benefits to holders. Partner with Narrrf''s World to provide special discounts and cross-community collaboration.', 'NFT Project', 'https://discord.gg/gensuki', 'https://twitter.com/gensuki', 'https://gensuki.io', 1, 1);"

sqlite3 narrrf_world.sqlite "INSERT INTO tbl_partners (partner_name, partner_slug, short_description, long_description, partner_type, discord_url, twitter_url, website_url, is_featured, display_order) VALUES ('Golden Baboons', 'golden-baboons', 'Elite gaming community with competitive tournaments and exclusive events.', 'Golden Baboons represents an elite gaming community focused on competitive play and exclusive events. Our partnership brings together skilled players for cross-community tournaments.', 'Gaming Community', 'https://discord.gg/goldenbaboons', 'https://twitter.com/goldenbaboons', 'https://goldenbaboons.gg', 1, 2);"

sqlite3 narrrf_world.sqlite "INSERT INTO tbl_partners (partner_name, partner_slug, short_description, long_description, partner_type, discord_url, twitter_url, website_url, is_featured, display_order) VALUES ('[Partner Name]', 'partner-placeholder-3', 'Add your partner description through the admin interface.', 'This is a placeholder. Use the Partners tab in admin interface to update with real partner information, logos, and social links.', 'Community Partner', 'https://discord.gg/placeholder', 'https://twitter.com/placeholder', 'https://example.com', 0, 3);"

echo "✅ Demo partners inserted"

# Verify
echo "Partner count:"
echo "SELECT COUNT(*) FROM tbl_partners;" | sqlite3 narrrf_world.sqlite

echo ""
echo "Partners list:"
echo "SELECT partner_name, partner_type, is_featured FROM tbl_partners ORDER BY display_order;" | sqlite3 narrrf_world.sqlite

# Create images directory
cd /var/www/html/public
mkdir -p img/partners
chmod 755 img/partners
echo "✅ Images directory created"

# Backup database to /data
cd /var/www/html/db
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ Database backed up to /data"

echo ""
echo "🎉 PARTNER PORTAL DEPLOYMENT COMPLETE!"
echo "✅ Table created with indexes"
echo "✅ 3 demo partners inserted"
echo "✅ Images directory ready (755 permissions)"
echo "✅ Database backed up"
echo ""
echo "🌐 Test at: https://narrrfs.world/public/partners.html"
echo "⚙️ Manage at: https://narrrfs.world/public/admin-interface.html → Partners tab"
```

---

## 🧪 **VERIFICATION COMMANDS**

**After running deployment, verify everything:**

```bash
# Check table structure
echo ".schema tbl_partners" | sqlite3 narrrf_world.sqlite

# Check indexes
echo ".indexes tbl_partners" | sqlite3 narrrf_world.sqlite

# Expected output:
# idx_partners_active
# idx_partners_featured
# idx_partners_order
# idx_partners_slug

# Check data
echo "SELECT id, partner_name, is_featured, is_active FROM tbl_partners;" | sqlite3 narrrf_world.sqlite

# Expected:
# 1|Gensuki|1|1
# 2|Golden Baboons|1|1
# 3|[Partner Name]|0|1

# Check directory
ls -la /var/www/html/public/img/partners

# Expected: drwxr-xr-x (755 permissions)

# Test API
curl https://narrrfs.world/api/user/get-partners.php

# Expected: {"success":true,"partners":{...},"total":3}
```

---

## 🎯 **POST-DEPLOYMENT TESTING**

### **Public Page Test:**
1. Open browser: `https://narrrfs.world/public/partners.html`
2. Verify page loads
3. See "Under Cheese-struction" banner
4. See Gensuki and Golden Baboons in Featured
5. See [Partner Name] in All Partners
6. Click a card → modal opens
7. Test social links

### **Admin Interface Test:**
1. Open browser: `https://narrrfs.world/public/admin-interface.html`
2. Login as admin
3. Click "🤝 Partners" tab
4. Click "🔄 Refresh List"
5. Verify 3 partners load
6. Click "✏️ Edit All Fields" on any partner
7. Verify form fills with data
8. Make a test edit
9. Save and verify

---

## 🔧 **IF SOMETHING GOES WRONG**

### **Database Issues:**
```bash
# Drop and recreate table
cd /var/www/html/db
echo "DROP TABLE IF EXISTS tbl_partners;" | sqlite3 narrrf_world.sqlite
# Then run create_partners.sql again
```

### **Permission Issues:**
```bash
# Fix directory permissions
chmod 755 /var/www/html/public/img/partners
chown www-data:www-data /var/www/html/public/img/partners
```

### **Data Issues:**
```bash
# Clear and re-insert
echo "DELETE FROM tbl_partners;" | sqlite3 narrrf_world.sqlite
# Then run INSERT commands again
```

---

## 📝 **SIMPLIFIED VERSION (One Command Block)**

**If you want to run everything in one go:**

```bash
cd /var/www/html/db && \
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite && \
cat > create_partners.sql << 'EOF'
CREATE TABLE IF NOT EXISTS tbl_partners (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  partner_name TEXT NOT NULL,
  partner_slug TEXT UNIQUE NOT NULL,
  logo_filename TEXT,
  banner_filename TEXT,
  short_description TEXT,
  long_description TEXT,
  partner_type TEXT,
  discord_url TEXT,
  twitter_url TEXT,
  website_url TEXT,
  additional_info TEXT,
  is_featured INTEGER DEFAULT 0,
  is_active INTEGER DEFAULT 1,
  display_order INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_partners_active ON tbl_partners(is_active);
CREATE INDEX IF NOT EXISTS idx_partners_featured ON tbl_partners(is_featured);
CREATE INDEX IF NOT EXISTS idx_partners_order ON tbl_partners(display_order);
CREATE INDEX IF NOT EXISTS idx_partners_slug ON tbl_partners(partner_slug);
EOF
sqlite3 narrrf_world.sqlite < create_partners.sql && \
sqlite3 narrrf_world.sqlite "INSERT INTO tbl_partners (partner_name, partner_slug, short_description, long_description, partner_type, discord_url, twitter_url, website_url, is_featured, display_order) VALUES ('Gensuki', 'gensuki', 'Premium NFT collection with exclusive benefits and community perks.', 'Gensuki is a premium NFT collection offering exclusive benefits to holders. Partner with Narrrf''s World to provide special discounts and cross-community collaboration.', 'NFT Project', 'https://discord.gg/gensuki', 'https://twitter.com/gensuki', 'https://gensuki.io', 1, 1), ('Golden Baboons', 'golden-baboons', 'Elite gaming community with competitive tournaments and exclusive events.', 'Golden Baboons represents an elite gaming community focused on competitive play and exclusive events. Our partnership brings together skilled players for cross-community tournaments.', 'Gaming Community', 'https://discord.gg/goldenbaboons', 'https://twitter.com/goldenbaboons', 'https://goldenbaboons.gg', 1, 2), ('[Partner Name]', 'partner-placeholder-3', 'Add your partner description through the admin interface.', 'This is a placeholder. Use the Partners tab in admin interface to update with real partner information, logos, and social links.', 'Community Partner', 'https://discord.gg/placeholder', 'https://twitter.com/placeholder', 'https://example.com', 0, 3);" && \
echo "Partner count:" && \
echo "SELECT COUNT(*) FROM tbl_partners;" | sqlite3 narrrf_world.sqlite && \
cd /var/www/html/public && \
mkdir -p img/partners && \
chmod 755 img/partners && \
cd /var/www/html/db && \
cp narrrf_world.sqlite /data/narrrf_world.sqlite && \
echo "🎉 PARTNER PORTAL DEPLOYMENT COMPLETE!"
```

---

## 🎉 **SUCCESS INDICATORS**

### **If Everything Works:**
- ✅ Table created: 1 tbl_partners
- ✅ Indexes created: 4 indexes
- ✅ Data inserted: 3 partners
- ✅ Directory created: img/partners (755)
- ✅ Database backed up: /data/narrrf_world.sqlite
- ✅ Public page: partners.html loads with data
- ✅ Admin interface: Partners tab shows all 3 partners
- ✅ Images: Can upload logos and banners
- ✅ Navigation: Partners link on all pages

---

## 🚀 **DEPLOYMENT WORKFLOW**

### **Timeline:**

1. **Local:** `git add . && git commit && git push` (2 min)
2. **Render:** Auto-deploy completes (2-3 min)
3. **SSH Render:** Run database commands (3 min)
4. **Test:** Verify everything works (5 min)
5. **Customize:** Add real partner data (ongoing)

**Total Time:** ~10-15 minutes for complete deployment

---

## 📋 **POST-DEPLOYMENT CHECKLIST**

### **Immediate (After Deployment):**
- [ ] Visit https://narrrfs.world/public/partners.html
- [ ] Verify page loads without errors
- [ ] Check browser console for errors
- [ ] Test modal popups
- [ ] Test on mobile device

### **Admin Tasks (When Ready):**
- [ ] Login as admin
- [ ] Go to Partners tab
- [ ] Edit Gensuki with real data
- [ ] Upload Gensuki logo
- [ ] Edit Golden Baboons with real data
- [ ] Upload Golden Baboons logo
- [ ] Delete or customize placeholder partner

### **When Ready to Go Live:**
- [ ] Remove "Under Cheese-struction" banner from partners.html
- [ ] Announce partners page to community
- [ ] Share on Discord/Twitter

---

**🤝 PARTNER PORTAL - READY FOR PRODUCTION DEPLOYMENT! 🚀**

---

**Commands Created:** October 28, 2025 - 23:55  
**Status:** Ready to execute on Render  
**Next:** Git push → Run Render commands → Test live → Customize!

