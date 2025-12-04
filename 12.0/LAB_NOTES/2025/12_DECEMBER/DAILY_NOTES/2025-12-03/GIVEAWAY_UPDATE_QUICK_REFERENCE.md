# 🎁 GIVEAWAY UPDATE - QUICK REFERENCE

**Date:** December 3, 2025  
**Giveaway ID:** `giveaway_1764782608091_6h22dwdb5`  
**Action:** Set draw time to 94 hours from now

---

## ✅ LOCAL DATABASE (DONE)

**Status:** ✅ **UPDATED**  
**New End Time:** `2025-12-07 15:32:38`  
**Duration:** `5640` minutes (94 hours)

---

## ⏳ PRODUCTION DATABASE (TODO)

### **SQL Command:**
```sql
UPDATE tbl_giveaways 
SET 
    ends_at = datetime('now', '+94 hours'),
    duration_minutes = 5640
WHERE 
    giveaway_id = 'giveaway_1764782608091_6h22dwdb5'
    AND status = 'active';
```

### **Render Shell Command:**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "UPDATE tbl_giveaways SET ends_at = datetime('now', '+94 hours'), duration_minutes = 5640 WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5' AND status = 'active';"
```

---

## ⚠️ RESTART BOT (REQUIRED)

**After updating production database:**
1. Go to Render dashboard
2. Find Discord bot service
3. Click "Restart"
4. Bot will reload giveaway with new timer

---

## 🔍 VERIFICATION

**Check after update:**
```sql
SELECT 
    giveaway_id, 
    datetime(ends_at) as new_end_time, 
    duration_minutes, 
    ROUND((julianday(ends_at) - julianday('now')) * 24, 2) as hours_remaining 
FROM tbl_giveaways 
WHERE giveaway_id = 'giveaway_1764782608091_6h22dwdb5';
```

**Expected:** ~94 hours remaining

---

**See full details:** `GIVEAWAY_UPDATE_SQL_COMMANDS.md`

