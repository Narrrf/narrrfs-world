# 🚀 RENDER COMMANDS - TETRIS ACHIEVEMENTS UPDATE

**Date:** October 26, 2025  
**Time:** 21:50  
**Purpose:** Update Tetris achievement definitions on production  

---

## 📋 **COMMANDS TO RUN ON RENDER**

### **Copy and paste these commands one by one:**

```bash
# Step 1: Navigate to database directory
cd /var/www/html/db

# Step 2: Backup database (CRITICAL!)
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ Database backed up!"

# Step 3: Delete old Tetris achievement definitions
cat > delete_tetris_defs.sql << 'EOF'
DELETE FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';
EOF

sqlite3 narrrf_world.sqlite < delete_tetris_defs.sql

# Step 4: Verify deletion
echo "SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
# Should show: 0

# Step 5: Call API to insert new 25 achievement definitions
curl -X POST https://narrrfs.world/api/dev/init-tetris-achievements.php

# Step 6: Verify 25 new definitions inserted
echo "SELECT COUNT(*) FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
# Should show: 25

# Step 7: Check score thresholds are correct
echo "SELECT achievement_key, game_score FROM tbl_tetris_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND game_score > 0 ORDER BY game_score;" | sqlite3 narrrf_world.sqlite
# Should show: score_hunter 200, high_roller 800, point_master 1500, score_legend 2000, tetris_king 2500

# Step 8: Final backup with new definitions
cp narrrf_world.sqlite /data/narrrf_world.sqlite
echo "✅ SUCCESS! Tetris achievements updated and backed up!"
```

---

## ✅ **EXPECTED OUTPUT**

### **After Step 4 (Delete):**
```
0
```

### **After Step 5 (API Call):**
```json
{
  "success": true,
  "message": "Tetris achievement definitions initialized successfully",
  "data": {
    "achievements_inserted": 25,
    "total_definitions": 25,
    "action": "initialized"
  }
}
```

### **After Step 6 (Count):**
```
25
```

### **After Step 7 (Score Check):**
```
score_hunter|200
high_roller|800
point_master|1500
score_legend|2000
tetris_king|2500
```

---

## 🎯 **WHAT THESE COMMANDS DO**

1. **Backup database** - Safety first!
2. **Delete old definitions** - Remove 29 old achievements
3. **Verify deletion** - Confirm 0 definitions
4. **Call init API** - Insert 25 new balanced achievements
5. **Verify count** - Confirm 25 definitions
6. **Check thresholds** - Verify correct values (200-2500)
7. **Final backup** - Preserve changes for next deployment

---

## 🚨 **IMPORTANT NOTES**

- **Run commands one by one** - Don't copy all at once
- **Verify each step** - Check output matches expected
- **Backup before changes** - Step 2 is critical
- **Backup after changes** - Step 8 preserves for next deploy

---

**Status:** Ready to execute  
**Time Required:** ~2 minutes  
**Risk:** LOW (backed up)

