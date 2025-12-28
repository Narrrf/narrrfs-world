# 🎁 GIVEAWAY UPDATE - ADD DRAW TIME (94 HOURS)

**Date:** December 3, 2025  
**Time:** ~18:29  
**Status:** ⏳ **PENDING - DATABASE UPDATE REQUIRED**

---

## 📋 GIVEAWAY INFORMATION

**Giveaway ID:** `giveaway_1764782608091_6h22dwdb5`  
**Prize:** Coralix NFT - https://discord.com/channels/1332015322546311218/1332071552338104331/1445812478285185118  
**Current Status:** `active`  
**Current ends_at:** `2025-12-03T18:23:28.091Z` (1 hour from creation)  
**Current duration_minutes:** `60` (1 hour)

**Requested Change:**  
- Set draw time to **94 hours from now**
- New duration: **5640 minutes** (94 hours × 60)

---

## 🕐 TIME CALCULATION

**Current Time:** December 3, 2025 ~18:29  
**New End Time:** December 7, 2025 ~16:29 (94 hours later)  
**ISO Format:** `2025-12-07T16:29:00.000Z` (approximate - use exact calculation)

---

## 💾 SQL UPDATE COMMANDS

### **✅ LOCAL DATABASE (ALREADY UPDATED):**

**Command Executed:**
```sql
UPDATE tbl_giveaways 
SET 
    ends_at = datetime('now', '+94 hours'),
    duration_minutes = 5640
WHERE 
    giveaway_id = 'giveaway_1764782608091_6h22dwdb5'
    AND status = 'active';
```

**Result:**
- ✅ **Updated Successfully**
- New end time: `2025-12-07 15:32:38` (approximately 94 hours from update time)
- Duration: `5640` minutes (94 hours)
- Hours remaining: `93.99` hours

### **⏳ PRODUCTION DATABASE (RENDER) - NEEDS UPDATE:**

**Command to Run in Render Shell:**
```sql
UPDATE tbl_giveaways 
SET 
    ends_at = datetime('now', '+94 hours'),
    duration_minutes = 5640
WHERE 
    giveaway_id = 'giveaway_1764782608091_6h22dwdb5'
    AND status = 'active';
```

**Or using absolute path:**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "UPDATE tbl_giveaways SET ends_at = datetime('now', '+94 hours'), duration_minutes = 5640 WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5' AND status = 'active';"
```

**Note:** The `datetime('now', '+94 hours')` will calculate 94 hours from the moment you run the command, so the exact time will be set when you execute it.

---

## 🔍 VERIFICATION QUERIES

### **Before Update:**
```sql
SELECT 
    giveaway_id, 
    prize, 
    status, 
    created_at, 
    ends_at, 
    duration_minutes,
    datetime(ends_at) as ends_at_readable
FROM tbl_giveaways 
WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5';
```

### **After Update:**
```sql
SELECT 
    giveaway_id, 
    prize, 
    status, 
    created_at, 
    ends_at, 
    duration_minutes,
    datetime(ends_at) as ends_at_readable,
    ROUND((julianday(ends_at) - julianday('now')) * 24) as hours_remaining
FROM tbl_giveaways 
WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5';
```

---

## ⚠️ IMPORTANT NOTES

### **Bot Restart Required:**
After updating the production database, the Discord bot **MUST be restarted** for the timer to update. The bot loads giveaways on startup (line 1632-1634 in `discord/index.js`) and sets timers based on `ends_at`.

### **Timer System:**
The bot uses `setTimeout` based on `ends_at` field:
- Timer is set when giveaway is loaded (bot startup in `client.once('ready')` event)
- Timer calculates: `remainingTime = endsAt.getTime() - now.getTime()`
- Timer is stored in memory (`activeGiveaways` Map)
- **If database is updated while bot is running, the in-memory timer is NOT updated automatically**

### **Bot Startup Process:**
```javascript
// From discord/index.js line 1632-1634
const { loadGiveawaysFromDatabase, initGiveawayModule } = require('./commands/giveaway.js');
initGiveawayModule(client);
await loadGiveawaysFromDatabase(queryDb, client);
```

**What happens on startup:**
1. Bot loads all active giveaways from database
2. Calculates remaining time: `endsAt.getTime() - now.getTime()`
3. Sets `setTimeout` for each giveaway
4. Stores giveaway in `activeGiveaways` Map

### **After Database Update:**

**✅ REQUIRED: Restart Discord Bot**
- Restart the Discord bot on Render
- Bot will reload giveaways from database on startup
- New timer will be set based on updated `ends_at`
- The giveaway will auto-end at the new time

**❌ NOT SUFFICIENT:**
- Just updating the database (timer won't update until restart)
- The in-memory timer is already set and won't change

---

## 📝 STEP-BY-STEP INSTRUCTIONS

### **✅ 1. Local Database (COMPLETED):**
```bash
# Already executed successfully
sqlite3 db/narrrf_world.sqlite "UPDATE tbl_giveaways SET ends_at = datetime('now', '+94 hours'), duration_minutes = 5640 WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5' AND status = 'active';"
```

**Verification (Local):**
```bash
sqlite3 -header -column db/narrrf_world.sqlite "SELECT giveaway_id, prize, datetime(ends_at) as new_end_time, duration_minutes, ROUND((julianday(ends_at) - julianday('now')) * 24, 2) as hours_remaining FROM tbl_giveaways WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5';"
```

**Result:** ✅ Updated - New end time: `2025-12-07 15:32:38`, Duration: `5640` minutes

### **⏳ 2. Update Production Database (Render):**

**Option A: Via Render Shell (Recommended)**
```bash
# Connect to Render shell, then run:
sqlite3 /var/www/html/db/narrrf_world.sqlite "UPDATE tbl_giveaways SET ends_at = datetime('now', '+94 hours'), duration_minutes = 5640 WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5' AND status = 'active';"
```

**Option B: Via SQLite Command Line**
```bash
# If you have direct database access:
cd /var/www/html
sqlite3 db/narrrf_world.sqlite
# Then run:
UPDATE tbl_giveaways SET ends_at = datetime('now', '+94 hours'), duration_minutes = 5640 WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5' AND status = 'active';
.exit
```

**Verify Production Update:**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT giveaway_id, prize, datetime(ends_at) as new_end_time, duration_minutes, ROUND((julianday(ends_at) - julianday('now')) * 24, 2) as hours_remaining FROM tbl_giveaways WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5';"
```

### **⏳ 3. Restart Discord Bot (REQUIRED):**

**After updating production database, restart the bot:**
- Go to Render dashboard
- Find the Discord bot service
- Click "Restart" or "Manual Deploy" → "Restart"
- Bot will reload giveaways on startup
- New timer will be set based on updated `ends_at`

**What happens on restart:**
1. Bot connects to Discord
2. `client.once('ready')` event fires
3. `loadGiveawaysFromDatabase()` is called
4. Bot reads updated `ends_at` from database
5. Calculates new `remainingTime`
6. Sets new `setTimeout` for 94 hours
7. Giveaway will auto-end at new time

### **4. Verify Bot Timer:**
After restart, check bot logs for:
```
[GIVEAWAY] Restored giveaway giveaway_1764782608091_6h22dwdb5, ends in X seconds
```
The "X seconds" should be approximately 338,400 seconds (94 hours).

---

## 🔧 ALTERNATIVE: UPDATE VIA BOT COMMAND (If Available)

If there's a command to update giveaway end time, that would be preferred as it would:
- Update the database
- Update the active giveaway map
- Update the timer automatically
- Update the Discord message embed

**Check for commands like:**
- `/giveaway update`
- `/giveaway extend`
- `/giveaway set-time`

---

## ✅ EXPECTED RESULT

After update:
- **ends_at:** `2025-12-07T16:29:00.000Z` (approximately)
- **duration_minutes:** `5640` (94 hours)
- **Status:** Still `active`
- **Timer:** Bot will auto-end giveaway in 94 hours

---

**Status:** ⏳ **READY FOR DATABASE UPDATE**  
**Date:** December 3, 2025  
**Next Step:** Update database, then restart bot

