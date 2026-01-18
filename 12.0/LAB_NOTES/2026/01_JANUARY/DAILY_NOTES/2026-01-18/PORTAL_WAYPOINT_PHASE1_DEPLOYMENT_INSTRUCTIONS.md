# 📋 PORTAL WAYPOINT REGISTER - PHASE 1 DEPLOYMENT INSTRUCTIONS

**Date:** January 18, 2026  
**Phase:** Phase 1 - Database & Backend  
**Status:** ✅ **READY FOR DEPLOYMENT**

---

## 📦 **FILES CREATED:**

1. **SQL Script:** `SQL_CREATE_PORTAL_WAYPOINT_TABLE.sql`
   - Database table creation
   - Indexes for performance
   - Sample data (optional)
   - Verification queries

2. **API Endpoint:** `api/user/portal-waypoint.php`
   - GET: Fetch messages
   - POST: Add message
   - PUT: Update message (optional)
   - DELETE: Delete message (optional)

---

## 🔧 **LOCAL DEPLOYMENT (XAMPP):**

### **Step 1: Create Database Table**

**Option A: Using SQLite Browser**
1. Open DB Browser for SQLite
2. Open database: `c:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite`
3. Go to "Execute SQL" tab
4. Copy contents from `SQL_CREATE_PORTAL_WAYPOINT_TABLE.sql`
5. Click "Execute" (Play button)
6. Verify: Check "Browse Data" → select `portal_waypoint_messages` table

**Option B: Using PHP Script**
1. Create a test file: `test-create-portal-table.php`
2. Add this code:
```php
<?php
$dbPath = __DIR__ . '/db/narrrf_world.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS portal_waypoint_messages (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      portal_id TEXT NOT NULL,
      discord_id TEXT NOT NULL,
      username TEXT NOT NULL,
      message TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Create indexes
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_portal_waypoint_portal_id ON portal_waypoint_messages(portal_id)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_portal_waypoint_created_at ON portal_waypoint_messages(created_at DESC)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_portal_waypoint_discord_id ON portal_waypoint_messages(discord_id)");

echo "✅ Table and indexes created successfully!";
?>
```
3. Run: `http://localhost/test-create-portal-table.php`
4. Should see: "✅ Table and indexes created successfully!"

### **Step 2: Test API Endpoint**

**Using Browser (GET Request):**
```
http://localhost/api/user/portal-waypoint.php?action=get&portal_id=LEVEL4_CENTER_PORTAL&limit=10
```

**Expected Response:**
```json
{
  "success": true,
  "messages": [],
  "total_count": 0,
  "portal_id": "LEVEL4_CENTER_PORTAL",
  "limit": 10
}
```

### **Step 3: Test POST Request (Add Message)**

**Using JavaScript Console in Browser:**
1. Open game: `http://localhost/public/three.js/3d-riddle-game.html`
2. Open DevTools Console (F12)
3. Run this code:

```javascript
// Test adding a message
fetch('http://localhost/api/user/portal-waypoint.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    action: 'add',
    portal_id: 'LEVEL4_CENTER_PORTAL',
    discord_id: '123456789',
    username: 'TestUser',
    message: 'This is a test message!'
  })
})
.then(res => res.json())
.then(data => {
  console.log('✅ API Response:', data);
})
.catch(err => {
  console.error('❌ Error:', err);
});
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Entry added successfully",
  "entry": {
    "id": 1,
    "portal_id": "LEVEL4_CENTER_PORTAL",
    "discord_id": "123456789",
    "username": "TestUser",
    "message": "This is a test message!",
    "created_at": "2026-01-18 16:30:00",
    "updated_at": "2026-01-18 16:30:00"
  },
  "entry_id": 1
}
```

### **Step 4: Verify Data in Database**

**Using SQLite Browser:**
1. Refresh database
2. Go to "Browse Data" tab
3. Select `portal_waypoint_messages` table
4. You should see your test message

**Using SQL Query:**
```sql
SELECT * FROM portal_waypoint_messages ORDER BY created_at DESC;
```

---

## 🌐 **RENDER DEPLOYMENT (Production):**

### **Step 1: Connect to Render**

1. Open terminal/command prompt
2. Connect to Render shell:
```bash
render shell
```

### **Step 2: Create Database Table**

1. Connect to SQLite database:
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite
```

2. Create table (paste this SQL):
```sql
CREATE TABLE IF NOT EXISTS portal_waypoint_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  portal_id TEXT NOT NULL,
  discord_id TEXT NOT NULL,
  username TEXT NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

3. Create indexes:
```sql
CREATE INDEX IF NOT EXISTS idx_portal_waypoint_portal_id 
  ON portal_waypoint_messages(portal_id);

CREATE INDEX IF NOT EXISTS idx_portal_waypoint_created_at 
  ON portal_waypoint_messages(created_at DESC);

CREATE INDEX IF NOT EXISTS idx_portal_waypoint_discord_id 
  ON portal_waypoint_messages(discord_id);
```

4. Verify table was created:
```sql
.tables
```
Should show `portal_waypoint_messages` in the list.

5. Verify schema:
```sql
.schema portal_waypoint_messages
```
Should show the table structure.

6. Exit SQLite:
```sql
.exit
```

7. Exit Render shell:
```bash
exit
```

### **Step 3: Upload API Endpoint**

**Option A: Git Push (Recommended)**
1. Ensure `api/user/portal-waypoint.php` is committed to Git
2. Push to repository:
```bash
git add api/user/portal-waypoint.php
git commit -m "Add portal waypoint register API endpoint"
git push origin main
```
3. Render will auto-deploy from Git

**Option B: Manual Upload (if not using Git auto-deploy)**
1. Use Render's dashboard to upload file
2. Or use SFTP to upload to Render

### **Step 4: Test Production API**

**Using Browser (GET Request):**
```
https://your-render-app.onrender.com/api/user/portal-waypoint.php?action=get&portal_id=LEVEL4_CENTER_PORTAL&limit=10
```

**Using cURL (POST Request):**
```bash
curl -X POST https://your-render-app.onrender.com/api/user/portal-waypoint.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "add",
    "portal_id": "LEVEL4_CENTER_PORTAL",
    "discord_id": "123456789",
    "username": "TestUser",
    "message": "Test message from production!"
  }'
```

---

## 🧪 **TESTING CHECKLIST:**

### **Local Testing:**
- [ ] Database table created successfully
- [ ] Indexes created successfully
- [ ] GET request returns empty array (before adding messages)
- [ ] POST request adds message successfully
- [ ] GET request returns added message
- [ ] Message appears in database
- [ ] Rate limiting works (1 message per minute)
- [ ] Validation works (empty message, too long message, etc.)

### **Production Testing:**
- [ ] Database table created on Render
- [ ] API endpoint accessible
- [ ] GET request works
- [ ] POST request works
- [ ] Messages persist after server restart
- [ ] CORS headers work correctly

---

## 🔍 **TROUBLESHOOTING:**

### **Issue: "Database error"**
**Solution:** 
- Check database path in API file
- Verify database file exists and has write permissions
- Check database file is not locked

### **Issue: "Table doesn't exist"**
**Solution:**
- Run CREATE TABLE SQL again
- Verify table name is correct: `portal_waypoint_messages`
- Check database connection is working

### **Issue: "CORS error in browser"**
**Solution:**
- API already has CORS headers for localhost
- For production, add your domain to CORS whitelist
- Check browser console for specific CORS error

### **Issue: "Rate limit error"**
**Solution:**
- Wait 60 seconds before posting again
- Or temporarily disable rate limiting for testing (comment out rate limit code)

### **Issue: "401 Unauthorized"**
**Solution:**
- Ensure discord_id is being sent in request
- Check user is logged in
- Verify session is active

---

## 📊 **VERIFICATION QUERIES:**

### **Count total messages:**
```sql
SELECT COUNT(*) as total_messages FROM portal_waypoint_messages;
```

### **Get all messages for Level 4 portal:**
```sql
SELECT * FROM portal_waypoint_messages 
WHERE portal_id = 'LEVEL4_CENTER_PORTAL' 
ORDER BY created_at DESC;
```

### **Get messages by specific user:**
```sql
SELECT * FROM portal_waypoint_messages 
WHERE discord_id = '123456789' 
ORDER BY created_at DESC;
```

### **Get latest 10 messages:**
```sql
SELECT * FROM portal_waypoint_messages 
ORDER BY created_at DESC 
LIMIT 10;
```

### **Delete test data (if needed):**
```sql
DELETE FROM portal_waypoint_messages WHERE username = 'TestUser';
```

---

## 🎯 **SUCCESS CRITERIA:**

✅ **Phase 1 is complete when:**
- [ ] Database table created successfully (local & production)
- [ ] API endpoint accessible and responding
- [ ] GET request returns messages correctly
- [ ] POST request adds messages successfully
- [ ] Messages persist in database
- [ ] Rate limiting works
- [ ] Validation works (all edge cases)
- [ ] Error handling works (all error cases)
- [ ] CORS works for game requests

---

## 🚀 **NEXT STEPS (Phase 2):**

After Phase 1 is complete and tested:
1. **Phase 2: Game Integration**
   - Add proximity detection
   - Create interaction prompt
   - Implement E key detection
   - Test in-game interaction

---

## 📝 **SAMPLE TEST DATA:**

**Insert sample messages for testing:**
```sql
INSERT INTO portal_waypoint_messages (portal_id, discord_id, username, message) 
VALUES 
  ('LEVEL4_CENTER_PORTAL', '111111111', 'Narrrfs', 'First visitor here! This portal is amazing! 🧀'),
  ('LEVEL4_CENTER_PORTAL', '222222222', 'CheeseKing', 'Narrrfs was here before me! Great game!'),
  ('LEVEL4_CENTER_PORTAL', '333333333', 'PortalMaster', 'This is the coolest feature ever!'),
  ('LEVEL4_CENTER_PORTAL', '444444444', 'GamePlayer', 'Love the cheese bosses in this level!'),
  ('LEVEL4_CENTER_PORTAL', '555555555', 'Explorer', 'Found all the secrets here! 🎮');
```

**Verify sample data:**
```sql
SELECT id, username, message, created_at 
FROM portal_waypoint_messages 
WHERE portal_id = 'LEVEL4_CENTER_PORTAL'
ORDER BY created_at DESC;
```

---

## 📞 **SUPPORT:**

If you encounter any issues:
1. Check console logs (browser DevTools)
2. Check PHP error logs (XAMPP logs folder)
3. Check database file permissions
4. Verify API endpoint path is correct
5. Test with simple curl/Postman requests first

---

**STATUS:** ✅ **PHASE 1 READY FOR DEPLOYMENT**  
**ESTIMATED TIME:** 30-60 minutes for full deployment and testing  
**NEXT:** Phase 2 - Game Integration

---

**🎯 Let's deploy Phase 1 and test the backend! 🎯**
