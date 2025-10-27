# 🚀 RENDER PRODUCTION COMMANDS - SPACE INVADERS CLEANUP

**Date:** October 27, 2025  
**Time:** 01:00  
**Purpose:** Clean up old Space Invaders user achievements on production  
**Status:** ✅ **READY TO EXECUTE**  

---

## 🎯 **WHAT THESE COMMANDS DO**

### **Purpose:**
Delete old Space Invaders user achievement records that contain outdated descriptions (e.g., "Reached 30,000 points!", "Defeated Boss 5").

### **Safe:**
- ✅ Only deletes user achievement records
- ✅ Preserves the 28 achievement definitions
- ✅ Players will re-unlock with correct descriptions
- ✅ Database backed up before changes

---

## 📋 **RENDER COMMANDS TO RUN**

### **Step 1: Navigate and Backup**
```bash
cd /var/www/html/db

# Backup current database
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

echo "✅ Backup created!"
```

---

### **Step 2: Delete Old User Achievements**
```bash
# Delete all Space Invaders user achievements (NOT definitions)
echo "DELETE FROM tbl_space_invaders_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

echo "✅ Old user achievements deleted!"
```

---

### **Step 3: Verify Deletion**
```bash
# Should show 0
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

echo "Expected: 0"
```

---

### **Step 4: Verify Definitions Still Exist**
```bash
# Should show 28
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite

echo "Expected: 28"
```

---

### **Step 5: Verify Correct Descriptions**
```bash
# Check sample definitions
echo "SELECT achievement_key, achievement_title, achievement_description FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND achievement_key IN ('score2500', 'score7500', 'bossKiller3') ORDER BY achievement_key;" | sqlite3 narrrf_world.sqlite

echo "Expected:"
echo "bossKiller3|Boss Slayer|Defeated Cheese God - Master Warrior!"
echo "score2500|Getting Started|Reached 1,000 DSPOINC!"
echo "score7500|Rising Star|Reached 5,000 DSPOINC!"
```

---

### **Step 6: Copy to /data**
```bash
# Copy updated database to /data
cp narrrf_world.sqlite /data/narrrf_world.sqlite

echo "✅ SUCCESS! Database updated and backed up to /data!"
```

---

## 🧪 **VERIFICATION**

### **After Running Commands:**

**Profile Page Should Show:**
- Total Achievements: 28
- Unlocked: 0 (for players who had old achievements)
- Locked: 28
- All descriptions should be CORRECT:
  - "Getting Started: Reached 1,000 DSPOINC!" ✅
  - "Rising Star: Reached 5,000 DSPOINC!" ✅
  - "Boss Slayer: Defeated Cheese God - Master Warrior!" ✅

**After Playing:**
- Achievements re-unlock with correct descriptions
- Profile page shows accurate information
- All 28 achievements can be earned

---

## 🚨 **WHAT NOT TO WORRY ABOUT**

### **Players Losing Achievements:**
- ✅ Players will quickly re-earn achievements while playing
- ✅ New unlocks will have correct descriptions
- ✅ Achievement system is more accurate now
- ✅ Better user experience long-term

### **Definitions:**
- ✅ All 28 definitions remain in database
- ✅ Definitions are correct (already verified)
- ✅ API loads from these definitions
- ✅ No risk to achievement system

---

## 📋 **COMPLETE COMMAND SEQUENCE**

**Copy and paste this entire block into Render shell:**

```bash
cd /var/www/html/db && \
cp narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite && \
echo "✅ Backup created!" && \
echo "DELETE FROM tbl_space_invaders_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite && \
echo "✅ Old user achievements deleted!" && \
echo "User achievements count:" && \
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id != 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite && \
echo "Definitions count (should be 28):" && \
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite && \
echo "Sample definitions:" && \
echo "SELECT achievement_key, achievement_description FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS' AND achievement_key IN ('score2500', 'bossKiller3') ORDER BY achievement_key;" | sqlite3 narrrf_world.sqlite && \
cp narrrf_world.sqlite /data/narrrf_world.sqlite && \
echo "✅ SUCCESS! Database updated and backed up to /data!"
```

---

## 🎯 **EXPECTED OUTPUT**

```
✅ Backup created!
✅ Old user achievements deleted!
User achievements count:
0
Definitions count (should be 28):
28
Sample definitions:
bossKiller3|Defeated Cheese God - Master Warrior!
score2500|Reached 1,000 DSPOINC!
✅ SUCCESS! Database updated and backed up to /data!
```

---

**SPACE INVADERS PRODUCTION CLEANUP - READY TO EXECUTE! 🚀**

---

**Commands Created:** October 27, 2025 - 01:00  
**Status:** Ready for production execution  
**Next:** Run commands on Render shell

