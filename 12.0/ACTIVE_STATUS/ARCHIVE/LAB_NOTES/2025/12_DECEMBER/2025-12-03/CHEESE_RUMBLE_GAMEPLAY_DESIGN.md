# 💥 CHEESE RUMBLE - GAMEPLAY DESIGN DOCUMENT

**Date:** December 3, 2025  
**Status:** ✅ **GAMEPLAY MECHANICS UNDERSTOOD - DESIGN IN PROGRESS**

---

## 🎯 **CORE CONCEPT**

**Cheese Rumble** is a text-based battle royale where mice compete in rounds of randomly generated cheese-themed fights. Players watch their mice characters engage in funny, creative battles until only one mouse remains victorious!

---

## 🎮 **GAMEPLAY MECHANICS**

### **Phase 1: Join & Setup**
1. Admin creates rumble with `/cheese-rumble create`
2. Members join via Discord button
3. Rumble starts automatically or manually
4. All participants are "alive" at start

### **Phase 2: Round-Based Elimination**
- Rounds continue automatically
- Each round has multiple events (kills, eliminations, special events)
- Events happen randomly between players
- Players get eliminated one by one
- Story narratives posted for each event
- Rounds continue until 1 player remains

### **Phase 3: Victory & Rewards**
- Last remaining mouse wins
- Winner receives DSPOINC reward
- Final leaderboard shown
- Stats displayed (kills, eliminations, etc.)

---

## 🧀 **CHEESE THEMED EVENTS**

### **Kill Events (Player Kills Another Player):**

#### **Weapon-Based Kills:**
- "🧀 **mouse_X** threw a giant wheel of cheese at **mouse_Y**'s head! **mouse_Y** is now in cheese heaven! 🧀"
- "🔫 **mouse_X** found a cheese gun and shot **mouse_Y**! They rest in peace with unlimited cheese! 🧀"
- "⚔️ **mouse_X** used a cheese sword to slice **mouse_Y** in half! Too much cheese, not enough mercy! 🧀"
- "🛡️ **mouse_X** crushed **mouse_Y** with a massive cheese shield! Shield bash, cheese style! 🧀"
- "💣 **mouse_X** threw a stinky cheese bomb at **mouse_Y**! The smell alone was lethal! 🧀"

#### **Cheese-Related Kills:**
- "🧀 **mouse_X** lured **mouse_Y** into a cheese trap! **mouse_Y** got stuck in the fondue! 🧀"
- "🐁 **mouse_X** pushed **mouse_Y** into a vat of molten cheese! Extra crispy mouse! 🧀"
- "🧀 **mouse_X** fed **mouse_Y** so much cheese they exploded! Too cheesy to handle! 🧀"
- "🪤 **mouse_X** set a cheese mousetrap that caught **mouse_Y**! Classic trap, cheesy outcome! 🧀"
- "🧀 **mouse_X** challenged **mouse_Y** to a cheese-eating contest! **mouse_Y** couldn't handle the heat! 🧀"

#### **Sneaky Kills:**
- "🌙 **mouse_X** caught **mouse_Y** sleeping and buried them in cheese! Sweet dreams forever! 🧀"
- "🔪 **mouse_X** stabbed **mouse_Y** with a cheese knife while they were eating! Backstabbed with dairy! 🧀"
- "🧀 **mouse_X** poisoned **mouse_Y**'s cheese! **mouse_Y** took one bite and... rest in peace! 🧀"
- "👻 **mouse_X** scared **mouse_Y** so bad they ran into a cheese grater! Shredded! 🧀"

### **Self-Elimination Events (Player Dies by Accident):**

#### **Cheese Overdose:**
- "🧀 **mouse_X** ate too much cheese and exploded! Too much of a good thing! 💥"
- "🍽️ **mouse_X** got stuck in a cheese wheel and couldn't get out! Trapped in deliciousness! 🧀"
- "🧀 **mouse_X** tried to eat a cheese the size of a house! Ambition was their downfall! 🏠"
- "💨 **mouse_X** got cheese poisoning from expired cheese! Always check the expiration date! 🧀"

#### **Cheese-Related Accidents:**
- "🧀 **mouse_X** slipped on a banana peel covered in cheese! Classic slip, cheesy style! 🍌"
- "⚡ **mouse_X** got zapped by an electric cheese wire! Shocking way to go! ⚡"
- "🔥 **mouse_X** tried to melt cheese with a flamethrower and set themselves on fire! Too hot to handle! 🔥"
- "🧀 **mouse_X** fell into a cheese factory machine! Industrial accident! 🏭"

### **Special Events (Non-Lethal, Adds Flavor):**

#### **Item Acquisition:**
- "🎁 **mouse_X** found a golden cheese wheel! Extra protection for next round! 🧀"
- "🛡️ **mouse_X** discovered a cheese armor! They're now invincible to cheese attacks! 🧀"
- "⚔️ **mouse_X** picked up a legendary cheese sword! Fear the cheese blade! 🧀"
- "🍯 **mouse_X** found a healing cheese! Health restored! 🧀"

#### **Cheese Powers:**
- "✨ **mouse_X** gained the power of cheese teleportation! Zoom zoom! ✨"
- "🧀 **mouse_X** learned the ancient cheese magic! They can now summon cheese storms! 🌩️"
- "🐁 **mouse_X** transformed into a cheese monster! Rawr! 🧀"

#### **Funny Situations:**
- "🧀 **mouse_X** got stuck in a cheese wheel and had to roll away! Rolling in style! 🧀"
- "🎉 **mouse_X** found a party cheese! The fans love them! 🎉"
- "🧀 **mouse_X** made friends with a cheese fairy! They're protected now! ✨"

### **Environmental Events:**
- "🌊 **mouse_X** got swept away by a wave of cheese fondue! Drowned in deliciousness! 🧀"
- "🏔️ **mouse_X** fell off a cheese mountain! Too high to survive! 🧀"
- "🌋 **mouse_X** got caught in a cheese volcano eruption! Molten dairy disaster! 🧀"
- "❄️ **mouse_X** froze in a cheese freezer! Too cold to continue! ❄️"

---

## 📊 **ROUND STRUCTURE**

### **Each Round Contains:**

1. **Event Generation** (3-5 events per round)
   - Random selection of event types
   - Random player selection
   - Event messages posted to Discord

2. **Status Update**
   - Players remaining count
   - Round number
   - Current era/theme

3. **Time Between Rounds**
   - 5-10 seconds delay
   - Builds suspense
   - Allows members to react

4. **Continue Until Winner**
   - Rounds continue automatically
   - Stop when 1 player remains
   - Announce winner immediately

---

## 🏆 **WINNER & STATS**

### **Final Display Shows:**

1. **Winner Announcement**
   - Tag winner
   - Show final score/stats
   - DSPOINC reward amount

2. **Leaderboard (Top 5)**
   - 1st place (Winner)
   - 2nd place
   - 3rd place
   - 4th place
   - 5th place

3. **Statistics:**
   - **Total Players:** X
   - **Most Kills:** Player name (X kills)
   - **Longest Survivor:** Player name
   - **Most Cheese Collected:** Player name (if tracked)

---

## 🎨 **STORY VARIATIONS SYSTEM**

### **Event Pool Structure:**

Each event type has multiple variations:

**Example: Kill Events**
- 10+ weapon-based variations
- 10+ cheese-themed variations
- 10+ sneaky/stealth variations
- Total: 30+ kill event variations

**Example: Self-Elimination Events**
- 10+ cheese overdose variations
- 10+ accident variations
- Total: 20+ self-elimination variations

**Example: Special Events**
- 10+ item acquisition variations
- 10+ power-up variations
- 10+ funny situation variations
- Total: 30+ special event variations

### **Total Event Pool:**
- **Kill Events:** 30+ variations
- **Self-Eliminations:** 20+ variations
- **Special Events:** 30+ variations
- **Environmental Events:** 15+ variations
- **Total:** 95+ unique event messages

---

## 📋 **DATABASE TRACKING**

### **Additional Fields Needed:**

#### **tbl_cheese_rumbles:**
- `current_round` - Current round number
- `players_alive` - Count of alive players
- `events_log` - JSON array of all events (for replay)

#### **tbl_rumble_participants:**
- `status` - `alive`, `eliminated`, `winner`
- `kills` - Number of kills made
- `eliminated_by` - User ID who eliminated them (if killed)
- `elimination_reason` - Story text of how they died
- `eliminated_in_round` - Round number they died in
- `final_position` - Final ranking (1 = winner)

---

## ⚙️ **ROUND LOGIC**

### **Algorithm:**

1. **Start Round**
   - Get all alive players
   - Calculate events for this round (3-5 events)
   - Determine if round should eliminate players

2. **Generate Events**
   - Random event type selection
   - Random player selection (from alive players)
   - For kills: Select random target (different player)
   - For eliminations: Remove player from alive list
   - For special events: Apply effect (if any)

3. **Post Events**
   - Format event messages with member tags
   - Post to Discord channel
   - Update database

4. **Check Win Condition**
   - If 1 player alive: Declare winner, end rumble
   - If 2+ players alive: Continue to next round
   - If 0 players alive: Declare tie (rare, but handle)

5. **Next Round**
   - Wait 5-10 seconds
   - Increment round number
   - Repeat from step 1

---

## 🎯 **CONFIGURATION OPTIONS**

### **Same as Cheese Race:**
- ✅ Duration (max time limit)
- ✅ Max Players
- ✅ DSPOINC Reward
- ✅ Role Reward
- ✅ Tag Role
- ✅ Auto Start
- ✅ Comment/Description

### **New Options:**
- **Events Per Round:** 3-5 (configurable)
- **Round Delay:** 5-10 seconds (configurable)
- **Kill Weight:** Probability of kill events (configurable)
- **Special Event Weight:** Probability of special events (configurable)

---

## 🚀 **IMPLEMENTATION PHASES**

### **Phase 1: Core Structure**
- Create database tables
- Create Discord command
- Join/leave system
- Basic rumble creation

### **Phase 2: Round System**
- Round generation logic
- Event pool system
- Event selection algorithm
- Round progression

### **Phase 3: Story System**
- Event message templates
- Player name insertion
- Tag system
- Message formatting

### **Phase 4: Elimination Logic**
- Player elimination tracking
- Kill counting
- Winner determination
- Final leaderboard

### **Phase 5: Polish & Testing**
- Add more story variations
- Test edge cases
- Optimize round timing
- Add statistics

---

## 💡 **UNIQUE FEATURES (vs Paid Bot)**

### **What Makes Us Better:**

1. **Cheese Theme** - All stories cheese/mouse themed
2. **More Variations** - 95+ unique event messages
3. **Funnier Stories** - Creative, laugh-out-loud moments
4. **Member Tagging** - Players see their participation
5. **Cheese Stats** - Track cheese-specific achievements
6. **Integrated System** - Part of Narrrf's World ecosystem
7. **Custom Rewards** - DSPOINC rewards tied to our economy

---

**Status:** ✅ **GAMEPLAY DESIGN COMPLETE**  
**Next:** Create detailed implementation plan with event pool

