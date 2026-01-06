# 🧩 GLYPH MEMORY - DATABASE TABLE CREATED

**Date:** January 6, 2026  
**Status:** ✅ **TABLE CREATED - READY FOR TESTING**  
**Action:** Created `tbl_glyph_memory_scores` table in local database

---

## ✅ **TABLE CREATION COMPLETE**

### **Database Table:**
- **Name:** `tbl_glyph_memory_scores`
- **Location:** `db/narrrf_world.sqlite`
- **Status:** ✅ **CREATED AND VERIFIED**

### **Table Structure:**
```sql
CREATE TABLE tbl_glyph_memory_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discord_id TEXT NOT NULL,
    discord_name TEXT,
    difficulty TEXT NOT NULL,           -- 'easy', 'medium', 'hard'
    time_ms INTEGER NOT NULL,           -- Completion time in milliseconds
    pairs_matched INTEGER NOT NULL,      -- Number of pairs (6, 8, or 12)
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    season TEXT                         -- Season identifier
);
```

### **Indexes Created:**
- ✅ `idx_glyph_discord_id` - Fast user lookup
- ✅ `idx_glyph_difficulty` - Fast difficulty filtering
- ✅ `idx_glyph_time` - Fast time-based queries
- ✅ `idx_glyph_season` - Season-based queries

---

## 🧪 **TESTING CHECKLIST**

### **Before Testing:**
- [x] Database table created ✅
- [x] API updated to handle glyph_memory ✅
- [x] Frontend updated with score submission ✅

### **Testing Steps:**

#### **1. Check Discord Login:**
Open browser console and check:
```javascript
localStorage.getItem("discord_id")
localStorage.getItem("discord_name")
```

**If null/undefined:** You need to login via Discord first (visit profile page and login)

#### **2. Test Without Discord Login:**
1. Clear Discord login: `localStorage.removeItem("discord_id")`
2. Play a game and complete it
3. Check console: Should see "🧩 Not logged in - score saved to localStorage only"
4. Verify: Best time saved in localStorage (existing behavior)

#### **3. Test With Discord Login:**
1. Login via Discord on profile page
2. Verify login: `localStorage.getItem("discord_id")` should return your Discord ID
3. Play a game and complete it
4. Check browser console: Should see "✅ Glyph Memory score saved to database: ..."
5. Check database:
   ```sql
   SELECT * FROM tbl_glyph_memory_scores ORDER BY timestamp DESC LIMIT 5;
   ```

---

## 🔍 **TROUBLESHOOTING**

### **Issue: "Not logged in - score saved to localStorage only"**
**Solution:** Login via Discord on profile page first

### **Issue: API Error 400 - Missing fields**
**Check:** Browser console for exact error message
**Verify:** All required fields are being sent:
- `game: 'glyph_memory'`
- `discord_id` (from localStorage)
- `difficulty` (easy/medium/hard)
- `time_ms` (milliseconds)
- `pairs_matched` (6, 8, or 12)

### **Issue: API Error 500 - Table not found**
**Solution:** Table is now created - try again

### **Issue: No console messages**
**Check:** 
1. Browser console is open
2. Console filter is not hiding messages
3. JavaScript errors are not blocking execution

---

## 📊 **VERIFY DATABASE RECORDS**

### **Check All Scores:**
```sql
SELECT * FROM tbl_glyph_memory_scores ORDER BY timestamp DESC;
```

### **Check Scores by Difficulty:**
```sql
SELECT difficulty, COUNT(*) as count, MIN(time_ms) as best_time_ms 
FROM tbl_glyph_memory_scores 
GROUP BY difficulty;
```

### **Check Your Scores:**
```sql
SELECT * FROM tbl_glyph_memory_scores 
WHERE discord_id = 'YOUR_DISCORD_ID' 
ORDER BY timestamp DESC;
```

---

## 🎯 **NEXT STEPS**

1. **Test the game again** - Table is now created
2. **Check browser console** - Look for success/error messages
3. **Verify Discord login** - Make sure you're logged in
4. **Check database** - Verify records are being created

---

**Status:** ✅ **TABLE CREATED - READY FOR TESTING**  
**Date:** January 6, 2026  
**Next:** Test game with Discord login

