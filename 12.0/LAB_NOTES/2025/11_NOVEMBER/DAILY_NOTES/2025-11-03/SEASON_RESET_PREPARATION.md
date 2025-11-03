# 🔄 SEASON 5 RESET PREPARATION

**Date:** November 3, 2025 - Morning  
**Status:** 🚀 **PREPARATION PHASE - WORKING HOURS AHEAD**  
**Purpose:** Comprehensive season reset planning and execution

---

## 📋 **PRE-RESET CHECKLIST**

### **✅ Phase 1: Game Status Verification**

#### **Space Invaders:**
- [x] Giant Cheese Boss System Live
- [x] 10:1 Score Conversion Active
- [x] Phoenix Waves Balanced
- [x] Heart Drop System Working
- [x] Achievement System Active (28 achievements)
- [x] Role Multipliers Working

#### **Snake:**
- [x] 9-Boss Progressive System Live
- [x] Baby Boss Tutorial at 3 cheeses
- [x] Countdown Timers Working
- [x] Progressive Intelligence System
- [x] Achievement System Active (20 achievements)
- [x] Role Multipliers Working

#### **Tetris:**
- [x] Multi-Line Bonus Implemented (2→5, 3→9, 4→16)
- [x] Frozen Blocks System Implemented (8% chance)
- [x] Test Mode Ready (30% frozen for testing)
- [ ] **FINAL TESTING NEEDED** (Localhost)
- [x] Achievement System Active (25 achievements)
- [x] Role Multipliers Working

#### **Cheese Hunt:**
- [x] System Stable
- [x] Click Tracking Working
- [x] Quest Integration Active

#### **Discord Race:**
- [x] System Stable
- [x] Race Tracking Working

---

## 🔍 **SEASON RESET PROTOCOL REVIEW**

### **Reference:**
- **Rule:** `12.0/RULES/RESET_SEASON_PROTOCOL_RULE.md`
- **Created:** October 6, 2025
- **Last Used:** Season 4 Reset

### **Key Protocol Points:**
1. **ALWAYS Backup Database First**
2. **Reset ONLY 3 Main Games** (Tetris, Snake, Space Invaders)
3. **NEVER Reset Critical Data** (Cheese Hunt, Discord Race, Achievements)
4. **Copy Database to /data** after changes
5. **Create New Season** in `tbl_seasons`
6. **Document Everything**

---

## 📊 **DATABASE OPERATIONS NEEDED**

### **Tables to Reset:**
```sql
-- Reset 3 main games only
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');
```

### **Tables to PRESERVE:**
- ✅ `tbl_cheese_clicks` - Cheese Hunt data
- ✅ `tbl_race_participants` - Discord Race data
- ✅ `tbl_tetris_achievements` - Individual Tetris achievements
- ✅ `tbl_snake_achievements` - Individual Snake achievements
- ✅ `tbl_space_invaders_achievements` - Individual Space Invaders achievements
- ✅ `tbl_users` - User accounts
- ✅ `tbl_user_scores` - DSPOINC balances
- ✅ All other tables

### **Season Creation:**
```sql
-- Deactivate current season
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;

-- Create new season
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 6', datetime('now'), datetime('now', '+30 days'), 1);
```

---

## 🔧 **FINAL TESTING BEFORE RESET**

### **Tetris Testing (Priority - Not Yet Tested Live):**
- [ ] **Test on Localhost:**
  - [ ] Multi-Line Bonus: 2→5, 3→9, 4→16 DSPOINC
  - [ ] Frozen Blocks: ~3 frozen in 10 pieces (30% test mode)
  - [ ] Frozen Rotation Blocking: Try rotate → See warning
  - [ ] Frozen Visual Indicators: Blue overlay, borders, label
  - [ ] Frozen Warning Popup: "❄️ FROZEN! No Rotation! ❄️"
  - [ ] Role Multipliers: Verify with VIP 2x (32 DSPOINC for Tetris!)
  - [ ] Achievements: Verify still unlocking correctly

- [ ] **Test on Production (If Approved):**
  - [ ] Frozen Blocks: ~1 frozen in 12 pieces (8% production)
  - [ ] Multi-Line Bonus: Verify scoring
  - [ ] Community Feedback: Not frustrating?

### **All Games Final Check:**
- [ ] **Space Invaders:** Quick play test (boss spawns, scoring works)
- [ ] **Snake:** Quick play test (boss spawns, countdowns work)
- [ ] **Tetris:** Full testing session (frozen blocks, multi-line bonus)
- [ ] **Cheese Hunt:** Click tracking test
- [ ] **Discord Race:** Verify race participation tracking

---

## 💾 **BACKUP PROCEDURE**

### **Before Reset:**
```bash
# 1. Backup live database
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# 2. Verify backup
ls -la /data/narrrf_world_backup_*.sqlite

# 3. Document backup location
echo "Backup created: /data/narrrf_world_backup_[TIMESTAMP].sqlite"
```

### **After Reset:**
```bash
# Copy reset database to /data for next deployment
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite

# Verify copy
ls -la /data/narrrf_world.sqlite
```

---

## 📝 **DOCUMENTATION TO UPDATE**

### **Before Reset:**
- [ ] Update `QUICK_STATUS.md` with final season 5 status
- [ ] Update `LLM_SYNC_STATUS_GENESIS_12.0.json`
- [ ] Update individual LLM sync files (all 10 LLMs)
- [ ] Create Season 5 Summary Lab Note
- [ ] Document all Season 5 achievements

### **After Reset:**
- [ ] Create Season 6 Launch Lab Note
- [ ] Update `QUICK_STATUS.md` with Season 6 status
- [ ] Update LLM sync files with reset completion
- [ ] Document reset execution (commands used, results)
- [ ] Community announcement prep

---

## 🎯 **RESET EXECUTION PLAN**

### **Step-by-Step:**
1. **Final Testing** (All games, especially Tetris)
2. **Community Announcement** (Season reset coming)
3. **Database Backup** (Critical!)
4. **Reset Execution** (SQL commands)
5. **Verification** (Check all data)
6. **Database Copy** (To /data for next deploy)
7. **Documentation** (Lab notes, LLM sync)
8. **Community Confirmation** (Season 6 live!)

---

## ⚠️ **CRITICAL REMINDERS**

### **NEVER FORGET:**
- ❌ **NEVER reset** Cheese Hunt data
- ❌ **NEVER reset** Discord Race data
- ❌ **NEVER reset** Individual achievements
- ❌ **NEVER reset** User accounts or DSPOINC balances
- ✅ **ALWAYS backup** before reset
- ✅ **ALWAYS verify** preserved data
- ✅ **ALWAYS copy** to /data after reset

---

## 📊 **SEASON 5 SUMMARY**

### **Major Achievements:**
- ✅ **Space Invaders:** Giant Cheese Boss System (9 waves)
- ✅ **Snake:** 9-Boss Progressive System (Baby Boss + 8 regular)
- ✅ **Tetris:** Frozen Blocks + Multi-Line Bonus
- ✅ **All Games:** Role-based multipliers, achievements, perfect balance

### **Player Impact:**
- More exciting gameplay (bosses, frozen blocks)
- Better rewards (multi-line bonus, boss rewards)
- Fair balancing (10:1 Space Invaders, Math.round(), progressive difficulty)

---

## 🚀 **NEXT STEPS**

1. **Test Tetris Features** (Localhost - Priority!)
2. **Final Game Testing** (All 5 games)
3. **Review Reset Protocol** (One more time)
4. **Execute Reset** (When ready)
5. **Launch Season 6** (Community announcement!)

---

**Lab Note Created:** November 3, 2025 - Morning  
**Status:** Active Preparation  
**Estimated Work Hours:** 2-4 hours  
**Next:** Final Testing → Reset Execution

