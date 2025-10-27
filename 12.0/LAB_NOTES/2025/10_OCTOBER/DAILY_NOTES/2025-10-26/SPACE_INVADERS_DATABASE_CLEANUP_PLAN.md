# 👾 SPACE INVADERS DATABASE CLEANUP PLAN

**Date:** October 27, 2025 - 00:05  
**Status:** 📋 **READY TO EXECUTE**  
**Purpose:** Clean old definitions and insert new 28 achievements locally

---

## 🚨 **CURRENT PROBLEM**

### **What We See in Screenshot:**
- ✅ Total: 28 achievements (correct)
- ❌ Descriptions show OLD values:
  - "Reached 30,000 points!" (should be 1,000!)
  - "Reached 75,000 points!" (should be 5,000!)
  - "Defeated Boss 3" (should be "Defeated Cheese Emperor"!)
  - "Defeated Boss 5" (should be "Defeated Cheese God"!)
  - "Reached 50k points in under 3 minutes!" (should be 5k!)

### **Root Cause:**
Database still has OLD achievement definitions from before our fixes!

---

## 🔧 **CLEANUP STEPS (LOCAL)**

### **Step 1: Backup Database**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world\db
cp narrrf_world.sqlite narrrf_world_backup_space_invaders_cleanup.sqlite
```

### **Step 2: Delete Old Definitions**
```powershell
echo "DELETE FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
```

### **Step 3: Verify Deletion**
```powershell
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
# Expected: 0
```

### **Step 4: Insert New Definitions (2 parts)**
Create SQL files with new 28 achievements and correct thresholds.

### **Step 5: Verify New Definitions**
```powershell
echo "SELECT COUNT(*) FROM tbl_space_invaders_achievements WHERE user_id = 'ACHIEVEMENT_DEFINITIONS';" | sqlite3 narrrf_world.sqlite
# Expected: 28
```

### **Step 6: Test on Profile Page**
Hard refresh `http://localhost/public/profile.html` and verify new descriptions show!

---

## 📊 **ALL 28 NEW ACHIEVEMENT DEFINITIONS**

### **Organized by Category:**

**Kill-based (4):**
1. firstKill - First Blood - 100 kills
2. killStreak8 - Killing Spree - 25 combo
3. killStreak15 - Rampage - 50 combo
4. killStreak25 - Unstoppable - 100 combo

**Score-based (4):**
5. score2500 - Getting Started - 1,000 DSPOINC
6. score7500 - Rising Star - 5,000 DSPOINC
7. score15000 - Space Ace - 10,000 DSPOINC
8. score30000 - Legend - 20,000 DSPOINC

**Skill-based (5):**
9. perfectWave - Perfect Wave - 5 perfect waves
10. noHitRun60 - Untouchable - 5 min no damage
11. comboMaster8 - Combo Master - 4x multiplier
12. speedDemon20k - Speed Demon - 5k in 3 min
13. survivor10min - Ultimate Survivor - 20 min

**Boss-based (4):**
14. bossKiller1 - Boss Novice - 1 boss (Cheese King)
15. bossKiller2 - Boss Veteran - 2 bosses (Cheese Emperor)
16. bossKiller3 - Boss Slayer - 3 bosses (Cheese God)
17. bossKiller4 - Boss Destroyer - 4 bosses (Cheese Destroyer)

**Phoenix-based (4):**
18. phoenixHunter - Phoenix Hunter - 10 phoenixes
19. phoenixSlayer - Phoenix Slayer - 25 phoenixes
20. phoenixDestroyer - Phoenix Destroyer - 50 phoenixes
21. phoenixMaster - Phoenix Master - 100 phoenixes

**Egg-based (4):**
22. eggHunter - Egg Hunter - 50 eggs
23. eggSlayer - Egg Slayer - 100 eggs
24. eggDestroyer - Egg Destroyer - 150 eggs
25. eggMaster - Egg Master - 250 eggs

**Mini-Phoenix (3):**
26. miniPhoenixHunter - Mini-Phoenix Hunter - 25 mini
27. miniPhoenixSlayer - Mini-Phoenix Slayer - 50 mini
28. miniPhoenixMaster - Mini-Phoenix Master - 75 mini

---

**Status:** 📋 Ready to create SQL insert files!

