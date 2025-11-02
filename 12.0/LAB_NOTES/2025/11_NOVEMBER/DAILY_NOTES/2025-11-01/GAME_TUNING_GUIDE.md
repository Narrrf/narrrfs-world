# 🎮 GAME TUNING GUIDE - SEASON 5 PREPARATION

**Date:** November 1, 2025  
**Purpose:** Review and optimize all 5 games before Season 5 reset  
**Status:** 🔄 **READY TO BEGIN**  

---

## 🎯 TUNING PHILOSOPHY

### **Core Principles:**
- **Balance:** Fair rewards for effort and skill
- **Scalability:** Role multipliers work across all skill levels
- **Achievement Attainability:** Challenging but achievable
- **Mobile Compatibility:** Smooth performance on all devices
- **User Experience:** Fun and engaging gameplay

---

## 🧩 TETRIS - TUNING CHECKLIST

### **Current System (Season 4):**
- **Base Rewards:** 4 DSPOINC per line
- **Bomb Lines:** 20 DSPOINC (5x multiplier)
- **Role Multipliers:** VIP 2.0x → Cheese Hunter 1.1x
- **Achievements:** 25 total (200-2500 score thresholds)
- **Max Score:** ~2,500 (realistic skilled player)

### **Tuning Questions:**
1. Is 4 DSPOINC per line too generous or too stingy?
2. Are bomb lines (20 DSPOINC) balanced?
3. Do role multipliers create fair progression? (VIP gets 8 DSPOINC/line)
4. Are achievement thresholds realistic?
5. Mobile experience smooth?

### **Potential Adjustments:**
- [ ] Base DSPOINC per line
- [ ] Bomb line multiplier
- [ ] Achievement score thresholds
- [ ] Particle effect performance
- [ ] Mobile controls

---

## 🐍 SNAKE - TUNING CHECKLIST

### **Current System (Season 4):**
- **Base Rewards:** 10 DSPOINC per cheese
- **Role Multipliers:** VIP 2.0x → Cheese Hunter 1.1x
- **Achievements:** 20 total (200-3500 score thresholds)
- **Max Score:** ~3,920 (grid limit = 14x14 = 196 cells, 20 DSPOINC each)
- **Personalities:** Wild Jumper, Teleporter, Page Jumper

### **Tuning Questions:**
1. Is 10 DSPOINC per cheese balanced?
2. Do role multipliers create fair progression? (VIP gets 20 DSPOINC/cheese)
3. Are achievement thresholds realistic?
4. Are cheese personalities fun and balanced?
5. Mobile experience smooth?

### **Potential Adjustments:**
- [ ] Base DSPOINC per cheese
- [ ] Achievement score thresholds
- [ ] Cheese personality frequencies
- [ ] Movement speed balance
- [ ] Mobile controls

---

## 👾 SPACE INVADERS - TUNING CHECKLIST

### **Current System (Season 4):**
- **Base Rewards:** 1 DSPOINC per invader kill
- **Boss System:** 4 bosses with increasing rewards
- **Role Multipliers:** VIP 2.0x → Cheese Hunter 1.1x
- **Achievements:** 28 total (1k-20k score thresholds)
- **Max Score:** ~20,000 (realistic skilled player at Boss 4)

### **Tuning Questions:**
1. Is 1 DSPOINC per invader kill balanced?
2. Are boss rewards appropriate?
3. Do role multipliers create fair progression?
4. Are achievement thresholds realistic?
5. Is combo system balanced?

### **Potential Adjustments:**
- [ ] Base DSPOINC per kill
- [ ] Boss reward values
- [ ] Combo multiplier scaling
- [ ] Achievement score thresholds
- [ ] Upgrade system balance

---

## 🧀 CHEESE HUNT - TUNING CHECKLIST

### **Current System (Season 4):**
- **Base Rewards:** Variable (quest-based)
- **Personalities:** Wild Jumper, Teleporter, Page Jumper
- **Stand Time:** 1-7.5 seconds (variable)
- **Cheese Size:** 40px
- **Page Coverage:** Full page

### **Tuning Questions:**
1. Are cheese personalities balanced?
2. Is stand time (1-7.5s) appropriate?
3. Is cheese size (40px) good for visibility?
4. Are DSPOINC rewards fair?
5. Mobile experience smooth?

### **Potential Adjustments:**
- [ ] Personality distribution
- [ ] Stand time ranges
- [ ] Cheese size
- [ ] Reward values
- [ ] Mobile click detection

---

## 🏁 DISCORD CHEESE RACE - TUNING CHECKLIST

### **Current System (Season 4):**
- **Max Players:** 50
- **Scheduling:** Up to 48 hours advance
- **Winner Selection:** Random from finishers
- **Leaderboard:** All-time top 10 displayed
- **DSPOINC Rewards:** Winner gets prize pool

### **Tuning Questions:**
1. Is 50 max players appropriate?
2. Is 48-hour scheduling sufficient?
3. Are race rewards balanced?
4. Is Discord bot integration smooth?
5. Are race mechanics fair?

### **Potential Adjustments:**
- [ ] Max player count
- [ ] Scheduling window
- [ ] Reward distribution
- [ ] Race duration
- [ ] Winner selection algorithm

---

## 🎯 ROLE MULTIPLIER SYSTEM

### **Current Multipliers (All Games):**
```javascript
const roleMultipliersByID = {
  '1332016526848692345': 2.0,  // 🎴 VIP Holder
  '1402668301414563971': 1.5,  // 🏆 Holder
  '1332017420591697972': 1.4,  // Champion
  '1417279348989497532': 1.3,  // Season Tester
  '1332017614108758148': 1.2,  // Early Bird
  '1399651053682692208': 1.1,  // 🧀 Cheese Hunter
  '1332108350518857842': 1.3   // WL
};
```

### **Tuning Questions:**
1. Are multipliers fair? (2.0x for VIP vs 1.1x for Cheese Hunter)
2. Is progression rewarding?
3. Do lower tiers feel valuable?
4. Are higher tiers worth the investment?
5. Should any multipliers be adjusted?

### **Potential Adjustments:**
- [ ] VIP Holder multiplier (currently 2.0x)
- [ ] Holder multiplier (currently 1.5x)
- [ ] Champion multiplier (currently 1.4x)
- [ ] Season Tester multiplier (currently 1.3x)
- [ ] Early Bird multiplier (currently 1.2x)
- [ ] Cheese Hunter multiplier (currently 1.1x)

---

## 🏆 ACHIEVEMENT SYSTEM REVIEW

### **Current Achievement Counts:**
- **Tetris:** 25 achievements (200-2500 score range)
- **Snake:** 20 achievements (200-3500 score range)
- **Space Invaders:** 28 achievements (1k-20k score range)
- **Total:** 73 achievements

### **Tuning Questions:**
1. Are all achievements unlockable?
2. Are thresholds too easy or too hard?
3. Is progression rewarding?
4. Do icons make sense?
5. Are descriptions clear?

### **Potential Adjustments:**
- [ ] Achievement score thresholds
- [ ] Achievement descriptions
- [ ] Achievement icons
- [ ] Unlock order
- [ ] Difficulty curve

---

## 🔄 SEASON 5 RESET PLAN

### **What Gets Reset:**
```sql
-- Reset 3 main games only
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');

-- Deactivate Season 4
UPDATE tbl_seasons SET is_active = 0 WHERE is_active = 1;

-- Create Season 5
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 5', datetime('now'), datetime('now', '+30 days'), 1);
```

### **What Gets Preserved:**
- ✅ **Cheese Hunt data** - tbl_cheese_clicks (ALWAYS preserve)
- ✅ **Discord Race data** - tbl_race_participants (ALWAYS preserve)
- ✅ **Individual Achievements** - tbl_tetris_achievements, tbl_snake_achievements, tbl_space_invaders_achievements
- ✅ **User accounts** - tbl_users (NEVER reset)
- ✅ **User roles** - tbl_user_roles (NEVER reset)
- ✅ **Store inventory** - tbl_user_inventory (NEVER reset)

---

## 📊 PRE-RESET DATA COLLECTION

### **Season 4 Final Statistics:**

**To Document:**
- Total games played (Tetris, Snake, Space Invaders)
- Unique players per game
- Highest scores per game
- Top 10 players per game
- Total DSPOINC earned in Season 4
- Most unlocked achievements
- Community participation rate

**Commands to Run:**
```sql
-- Tetris stats
SELECT COUNT(*) as total_games, COUNT(DISTINCT discord_id) as unique_players, MAX(score) as highest_score 
FROM tbl_tetris_scores WHERE game = 'tetris';

-- Snake stats
SELECT COUNT(*) as total_games, COUNT(DISTINCT discord_id) as unique_players, MAX(score) as highest_score 
FROM tbl_tetris_scores WHERE game = 'snake';

-- Space Invaders stats
SELECT COUNT(*) as total_games, COUNT(DISTINCT discord_id) as unique_players, MAX(score) as highest_score 
FROM tbl_tetris_scores WHERE game = 'space_invaders';

-- Top 10 Tetris
SELECT discord_id, MAX(score) as best_score 
FROM tbl_tetris_scores WHERE game = 'tetris' 
GROUP BY discord_id ORDER BY best_score DESC LIMIT 10;

-- Top 10 Snake
SELECT discord_id, MAX(score) as best_score 
FROM tbl_tetris_scores WHERE game = 'snake' 
GROUP BY discord_id ORDER BY best_score DESC LIMIT 10;

-- Top 10 Space Invaders
SELECT discord_id, MAX(score) as best_score 
FROM tbl_tetris_scores WHERE game = 'space_invaders' 
GROUP BY discord_id ORDER BY best_score DESC LIMIT 10;
```

---

## 🎮 GAME TUNING NOTES

### **What to Look For:**

**Balance Issues:**
- Games too easy or too hard?
- Rewards too generous or too stingy?
- Role multipliers creating unfair advantages?
- Achievement thresholds unrealistic?

**Performance Issues:**
- Mobile lag or stuttering?
- Particle effects too heavy?
- Load times too long?
- Memory leaks?

**User Experience Issues:**
- Controls not responsive?
- Visual feedback unclear?
- Mobile layout problems?
- Instructions confusing?

---

## 📋 TUNING RECOMMENDATIONS TEMPLATE

### **For Each Game:**

**What's Working Well:**
- [List positive aspects]
- [What players enjoy]
- [Systems functioning correctly]

**What Needs Improvement:**
- [Balance issues]
- [Performance concerns]
- [UX problems]

**Recommended Changes:**
- [Specific adjustments]
- [Implementation approach]
- [Testing plan]

**Expected Impact:**
- [Player experience improvement]
- [Balance improvement]
- [Performance improvement]

---

## 🔧 IMPLEMENTATION PROTOCOL

### **For Each Tuning Change:**

1. **Document the issue** - What needs fixing?
2. **Propose solution** - How to fix it?
3. **Test locally** - Does it work?
4. **Verify no breaking changes** - Does everything else still work?
5. **Deploy to production** - Push the fix
6. **Monitor community feedback** - Are players happy?

---

## 🚨 CRITICAL REMINDERS

### **Game Tuning Rules:**
- ✅ **ADDITIVE ONLY** - Don't break existing features
- ✅ **TEST THOROUGHLY** - Verify all changes work
- ✅ **NO BREAKING CHANGES** - Preserve existing gameplay
- ✅ **DOCUMENT EVERYTHING** - Record all changes
- ✅ **COMMUNITY FIRST** - Consider player experience

### **Season Reset Rules:**
- ✅ **BACKUP FIRST** - Always backup database
- ✅ **PRESERVE DATA** - Never reset Cheese Hunt, Discord Race, Achievements
- ✅ **VERIFY COMMANDS** - Test on local database first
- ✅ **DOCUMENT STATS** - Save Season 4 final statistics
- ✅ **COPY TO /data/** - Always save database after reset

---

## 📅 EXPECTED TIMELINE

### **Morning (Game Review):**
- ⏱️ 1-2 hours - Test all 5 games
- 📝 Document findings
- 🎯 Create tuning recommendations

### **Afternoon (Game Tuning):**
- ⏱️ 2-3 hours - Implement changes
- 🧪 Test all modifications
- 🚀 Deploy improvements

### **Evening (Season Reset):**
- ⏱️ 1 hour - Prepare reset
- 📊 Document Season 4 stats
- 🔄 Execute reset commands
- ✅ Verify Season 5 active

---

## 🎯 SESSION SUCCESS CRITERIA

### **Game Tuning Complete When:**
- ✅ All 5 games reviewed
- ✅ Balance issues identified
- ✅ Tuning changes implemented
- ✅ No breaking changes introduced
- ✅ All games tested and working

### **Season Reset Complete When:**
- ✅ Season 4 statistics documented
- ✅ Database backed up
- ✅ Reset commands executed
- ✅ Season 5 created and active
- ✅ Leaderboards cleared for 3 main games
- ✅ Preserved data verified intact
- ✅ Frontend updated with Season 5 messaging

---

**🎮 READY TO START GAME TUNING SESSION! 🎮**

**Focus:** Optimize all 5 games for best Season 5 experience  
**Status:** 🔄 **AWAITING USER INPUT ON TUNING NEEDS**  

**Tell me which games you want to tune and what aspects need adjustment!** 🚀🧀

