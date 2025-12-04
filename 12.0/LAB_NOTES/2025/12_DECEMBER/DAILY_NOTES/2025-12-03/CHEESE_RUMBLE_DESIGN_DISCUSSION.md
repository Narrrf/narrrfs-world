# 💥 CHEESE RUMBLE - GAME DESIGN DISCUSSION

**Date:** December 3, 2025  
**Purpose:** Design new Discord game based on Cheese Race architecture  
**Status:** 🔄 **DESIGN PHASE - DISCUSSION NEEDED**

---

## 🎯 **CONCEPT OVERVIEW**

**Cheese Rumble** will be a Discord-based competitive game similar to Cheese Race, but with unique mechanics that differentiate it from the race format.

---

## 💡 **PROPOSED MECHANICS OPTIONS**

### **Option 1: Battle Royale Style**
**Concept:** Last player standing wins
- Players compete simultaneously
- Elimination-based (lose when certain condition met)
- Last remaining player wins
- Could integrate with Space Invaders (last to die wins)

### **Option 2: Point Collection Race**
**Concept:** Most points collected in time limit
- Similar to Cheese Race but different game
- Could use Tetris (most lines cleared)
- Could use Space Invaders (most kills/points)
- Winner = highest score at end

### **Option 3: Objective-Based Competition**
**Concept:** Complete objectives to win
- Set specific goals (e.g., clear 10 lines in Tetris)
- First to complete all objectives wins
- Multiple winners possible if objectives completed simultaneously

### **Option 4: Round-Based Tournament**
**Concept:** Bracket-style competition
- Multiple rounds
- Winners advance to next round
- Final round determines champion
- Best of X rounds format

### **Option 5: Team vs Team**
**Concept:** Team competition
- Players join teams
- Teams compete against each other
- Winning team shares rewards
- Could be 2v2, 3v3, or larger teams

---

## 🎮 **GAME INTEGRATION OPTIONS**

### **Game 1: Tetris**
**Mechanics:**
- Most lines cleared in time limit
- Highest score achieved
- First to clear X lines
- Most Tetrises (4-line clears)

### **Game 2: Snake**
**Mechanics:**
- Most cheese collected (same as Cheese Race)
- Longest snake length
- Highest score
- Survival time

### **Game 3: Space Invaders**
**Mechanics:**
- Most kills/points
- Last player alive (battle royale)
- First to reach X kills
- Boss defeat competition

### **Game 4: Multiple Games**
**Mechanics:**
- Players compete across multiple games
- Total score across all games
- Best performance in any single game

---

## 🤔 **DESIGN QUESTIONS**

### **1. Core Mechanic:**
- ❓ What should be the main competition style?
  - Race/Time-based?
  - Elimination-based?
  - Objective-based?
  - Points-based?
  - Something completely different?

### **2. Game Selection:**
- ❓ Which game should Cheese Rumble use?
  - Tetris?
  - Snake (different from Cheese Race)?
  - Space Invaders?
  - Multiple games?
  - New game entirely?

### **3. Duration & Format:**
- ❓ How long should a Rumble last?
  - Short (2-5 minutes)?
  - Medium (5-10 minutes)?
  - Long (10-30 minutes)?
- ❓ Single round or multiple rounds?

### **4. Player Count:**
- ❓ How many players per Rumble?
  - Small (2-5 players)?
  - Medium (5-10 players)?
  - Large (10-20+ players)?

### **5. Win Condition:**
- ❓ What determines the winner?
  - Highest score?
  - First to complete objective?
  - Last player standing?
  - Most achievements unlocked?

### **6. Rewards:**
- ❓ What should winners receive?
  - DSPOINC (same as Cheese Race)?
  - Higher/lower amounts?
  - Different reward structure?
  - Multiple winners possible?

### **7. Unique Features:**
- ❓ What makes Cheese Rumble different from Cheese Race?
  - Different game integration?
  - Different competition format?
  - Special power-ups or mechanics?
  - Team-based competition?

---

## 🏗️ **ARCHITECTURE REUSE**

### **✅ Can Reuse from Cheese Race:**
- Database structure (with modifications)
- Discord bot command framework
- Button interaction system
- Message embed builders
- Database persistence
- Bot startup loading
- Reward system
- Leaderboard system

### **🔄 Needs Modification:**
- Race-specific logic (replace with Rumble logic)
- Win condition determination
- Progress tracking (game-dependent)
- Scoring mechanism
- Status management

### **🆕 Needs New:**
- Rumble-specific database tables
- Rumble command structure
- Rumble button handlers
- Rumble message embeds
- Game-specific integration
- New win condition logic

---

## 📋 **PROPOSED DATABASE STRUCTURE**

### **Table 1: `tbl_cheese_rumbles`**
**Similar to `tbl_cheese_races` but for Rumbles:**
- `rumble_id` - Unique identifier
- `creator_id` - Discord ID of creator
- `creator_name` - Username of creator
- `channel_id` - Discord channel
- `message_id` - Discord message ID
- `status` - Rumble status (`waiting`, `active`, `finished`, `cancelled`)
- `max_players` - Maximum participants
- `duration` - Duration in seconds (if time-based)
- `game_type` - Which game (tetris, snake, space_invaders, etc.)
- `win_condition` - How to win (highest_score, first_objective, last_standing, etc.)
- `dspoinc_reward` - DSPOINC prize
- `role_reward` - Optional role reward
- `created_at`, `started_at`, `finished_at` - Timestamps
- `winner_id`, `winner_name` - Winner info
- `rumble_type` - Competition type (race, battle_royale, objective, etc.)

### **Table 2: `tbl_rumble_participants`**
**Similar to `tbl_race_participants` but for Rumbles:**
- `rumble_id` - Reference to rumble
- `user_id` - Discord ID
- `username` - Username
- `joined_at` - Join timestamp
- `game_score` - Current game score
- `objective_progress` - Progress towards objectives (JSON)
- `status` - Participant status
- `position` - Final ranking
- `dspoinc_earned` - Rewards earned
- `season` - Season identifier

---

## 🎨 **PROPOSED DISCORD COMMAND**

### **Command: `/cheese-rumble create`**
**Parameters:**
- `game` - Which game (tetris, snake, space_invaders)
- `type` - Rumble type (race, battle_royale, objective)
- `duration` - Duration in seconds
- `max_players` - Maximum participants
- `win_condition` - How to win
- `dspoinc_reward` - DSPOINC prize
- `role_reward` - Optional role
- `auto_start` - Auto-start when full

**Example Usage:**
```
/cheese-rumble create game:tetris type:race duration:300 max_players:10 win_condition:highest_score dspoinc_reward:5000
```

---

## 💬 **YOUR VISION**

Please share your ideas for Cheese Rumble:

1. **What makes it a "Rumble" vs a "Race"?**
   - More chaotic?
   - Different competition style?
   - More players?
   - Different mechanics?

2. **Which game should it use?**
   - Tetris?
   - Space Invaders?
   - Different game?
   - Multiple games?

3. **What's the core competition?**
   - Highest score?
   - First to finish?
   - Last standing?
   - Objective completion?

4. **Any special features?**
   - Power-ups?
   - Team battles?
   - Multiple rounds?
   - Special rewards?

5. **How should it feel different from Cheese Race?**
   - More intense?
   - Different pacing?
   - Different rewards?
   - Different atmosphere?

---

## 🚀 **NEXT STEPS**

Once we discuss and finalize the design:

1. ✅ Create database schema (new tables)
2. ✅ Create Discord command (`/cheese-rumble`)
3. ✅ Implement button handlers
4. ✅ Integrate with game API
5. ✅ Test full flow
6. ✅ Deploy to production

---

**Status:** 🔄 **WAITING FOR DESIGN DECISIONS**  
**Next:** Discuss mechanics, game selection, and unique features

