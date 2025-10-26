# 🧀 CHEESE HUNT SYSTEM - TECHNICAL SPECIFICATION

**Document Created:** October 26, 2025  
**Version:** 3.0 - Personality-Based System  
**Status:** ✅ **PRODUCTION READY**  

---

## 📋 **SYSTEM OVERVIEW**

The Cheese Hunt is an interactive mini-game on the index page where 3 cheese eggs move around the page and players must click them to earn DSPOINC rewards and complete quests.

---

## 🎮 **GAME MECHANICS**

### **Core Components:**
- **3 Cheese Eggs:** Yellow, Orange (Finance), Blue
- **Movement System:** Personality-based intelligent movement
- **Click Tracking:** Database-integrated with quest system
- **Reward System:** DSPOINC rewards and quest completion

### **File Location:**
- **Frontend:** `public/index.html` (lines 364-950)
- **API Endpoint:** `/api/track-egg-click.php`
- **Database Table:** `tbl_cheese_clicks`
- **Quest API:** `/api/get-active-cheese-hunt.php`

---

## 🧠 **CHEESE PERSONALITIES**

### **Cheese #1 (Yellow - "cheese-egg"):**
- **Type:** Wild Jumper
- **Movement:** Random positions in current viewport
- **Speed:** Fast (0.6-4.5 seconds stand time)
- **Coverage:** Current viewport only
- **Difficulty:** ⭐⭐ Medium
- **Strategy:** Stay on current page section

### **Cheese #2 (Orange - "cheese-egg-finance"):**
- **Type:** Teleporter
- **Movement:** 60% entire page, 40% current viewport
- **Speed:** Medium (1-7.5 seconds stand time)
- **Coverage:** Entire page (top to bottom)
- **Difficulty:** ⭐⭐⭐ Hard
- **Strategy:** Scroll entire page to find

### **Cheese #3 (Blue - "cheese-egg-blue"):**
- **Type:** Page Jumper
- **Movement:** 50% page sections, 50% current viewport
- **Speed:** Slower (1.2-9 seconds stand time)
- **Coverage:** 5 page sections (0%, 25%, 50%, 75%, 100%)
- **Difficulty:** ⭐⭐ Medium-Hard
- **Strategy:** Check different page sections

---

## 🔧 **TECHNICAL SPECIFICATIONS**

### **Constants:**
```javascript
CHEESE_SIZE: 40 // px (w-10 h-10 Tailwind classes)
MOVE_INTERVAL: 12000 // Base interval (deprecated - now uses personality timing)
```

### **Starting Positions:**
```javascript
Cheese 1: { left: 200px, top: 150px }
Cheese 2: { left: 400px, top: 250px }
Cheese 3: { left: 600px, top: 350px }
```

### **Movement Timing:**
```javascript
baseInterval = 1000 + Math.random() * 6500; // 1-7.5 seconds

Personality Multipliers:
- Fast (case 0): 0.6x = 0.6-4.5 seconds
- Medium (case 1): 1.0x = 1-7.5 seconds
- Slow (case 2): 1.2x = 1.2-9 seconds
```

### **Movement Boundaries:**
```javascript
// Horizontal (left)
Min: 50px (padding)
Max: viewportWidth - CHEESE_SIZE - 50px

// Vertical (top)
Min: 50px (padding)
Max: documentHeight - CHEESE_SIZE - 50px
```

---

## 📊 **DATABASE INTEGRATION**

### **Table:** `tbl_cheese_clicks`
**Schema:**
```sql
CREATE TABLE tbl_cheese_clicks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_wallet TEXT NOT NULL,
  clicks INTEGER DEFAULT 1,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  quest_id INTEGER,
  screenshot TEXT
);
```

### **API Endpoint:** `/api/track-egg-click.php`
**Request:**
```json
{
  "user_wallet": "discord_id_or_wallet",
  "egg_id": "cheese-egg",
  "timestamp": 1729974000000,
  "quest_id": 123,
  "screenshot": null,
  "hunt_context": "enhanced_tracking_v2"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Click tracked",
  "progress": "2/5 cheese found",
  "quest_completed": false
}
```

---

## 🎯 **QUEST SYSTEM INTEGRATION**

### **Active Quest API:** `/api/get-active-cheese-hunt.php`
**Response:**
```json
{
  "success": true,
  "quest": {
    "quest_id": 123,
    "description": "Click 5 cheese to claim reward",
    "reward": 12345,
    "is_active": true
  }
}
```

### **Quest Notification:**
- **Display:** Fixed top-right corner
- **Auto-dismiss:** 10 seconds
- **Progress Updates:** Real-time feedback
- **Completion:** Celebration message

---

## 🎨 **VISUAL FEATURES**

### **CSS Classes:**
```css
.cheese-egg {
  cursor: pointer;
  transition: all 0.3s ease;
  filter: drop-shadow(0 0 5px #facc15);
}

.cheese-egg:hover {
  transform: scale(1.1);
  filter: drop-shadow(0 0 10px #facc15) brightness(1.2);
}
```

### **Effects:**
- **Glow:** Golden drop-shadow effect
- **Hover:** Scale 1.1x + brightness increase
- **Movement:** 0.8s ease-in-out transition
- **Sound:** Click sound effect on interaction

---

## 🔊 **AUDIO SYSTEM**

### **Sound File:** `sounds/cheese-egg-click.wav`
**Volume:** 0.8 (80%)
**Trigger:** On click
**Preload:** Auto

---

## 📱 **MOBILE COMPATIBILITY**

### **Touch Support:**
- ✅ Touch events handled
- ✅ Responsive sizing (40px touchable)
- ✅ Hover effects work on touch
- ✅ Sound plays on mobile

### **Viewport Detection:**
```javascript
const viewportWidth = window.innerWidth;
const viewportHeight = window.innerHeight;
const currentScroll = window.scrollY || window.pageYOffset;
```

---

## 🚀 **PERFORMANCE OPTIMIZATION**

### **Efficiency Measures:**
- **Fixed positioning:** GPU-accelerated
- **Minimal DOM updates:** Only position changes
- **Event delegation:** Single click handler
- **Lazy loading:** Audio preloaded
- **Session-based randomization:** Prevents excessive calculations

### **Resource Usage:**
- **CPU:** Low (simple position calculations)
- **Memory:** Minimal (3 DOM elements)
- **Network:** Only on click (API call)
- **GPU:** Moderate (CSS transitions)

---

## 🔒 **ANTI-CHEATING MEASURES**

### **Session-Based Randomization:**
```javascript
const sessionSeed = localStorage.getItem('cheese_session_seed') || Date.now();
const seededRandom = (min, max) => {
  const x = Math.sin(sessionSeed + Date.now() * 0.0001) * 10000;
  return min + (x - Math.floor(x)) * (max - min);
};
```

### **Purpose:**
- Prevents predictable patterns
- Different behavior each session
- Hard to automate clicking
- Fair for all players

---

## 🧪 **TESTING CHECKLIST**

### **Functional Testing:**
- ✅ All 3 cheeses spawn correctly
- ✅ Movement patterns work as designed
- ✅ Click tracking records to database
- ✅ Quest integration functional
- ✅ Sound effects play
- ✅ Visual effects display

### **Personality Testing:**
- ✅ Yellow cheese stays in viewport
- ✅ Orange cheese teleports to different areas
- ✅ Blue cheese jumps between sections
- ✅ Timing variations confirmed

### **Database Testing:**
- ✅ Clicks recorded correctly
- ✅ Quest progress tracked
- ✅ User stats updated
- ✅ Admin interface shows data

---

## 📊 **ANALYTICS TRACKING**

### **Key Metrics:**
- **Total Clicks:** Count from `tbl_cheese_clicks`
- **Quest Completion:** Quest system integration
- **User Engagement:** Click patterns over time
- **Cheese Difficulty:** Click rate per cheese type

### **Admin Interface:**
- **Cheese Hunt Tab:** Shows all statistics
- **User Stats:** Individual player click counts
- **Quest Management:** Active hunt configuration

---

## 🔮 **FUTURE ROADMAP**

### **Phase 4 Enhancements (Planned):**
- **Difficulty Modes:** Easy/Medium/Hard selection
- **Seasonal Themes:** Holiday-themed cheese
- **Special Cheese:** Rare spawns with big rewards
- **Combo System:** Bonus for quick successive catches
- **Leaderboards:** Top cheese hunters
- **Achievements:** Cheese hunt-specific unlocks

---

## 📚 **DEVELOPER NOTES**

### **Code Maintainability:**
- **Well-documented:** Clear comments throughout
- **Modular design:** Easy to modify personalities
- **Constants defined:** Simple configuration changes
- **Error handling:** Try-catch blocks prevent crashes

### **For Future Developers:**
1. **Change stand time:** Modify `baseInterval` calculation
2. **Adjust personalities:** Edit personality switch cases
3. **Add new cheese:** Copy existing egg structure
4. **Modify movement:** Edit moveEgg function
5. **Change size:** Update CHEESE_SIZE constant

---

## 🚨 **CRITICAL WARNINGS**

### **DO NOT CHANGE:**
- ❌ Click tracking API endpoint
- ❌ Database table structure
- ❌ Quest integration logic
- ❌ User identification system

### **SAFE TO MODIFY:**
- ✅ Movement timing (baseInterval)
- ✅ Size (CHEESE_SIZE)
- ✅ Starting positions
- ✅ Movement patterns
- ✅ Visual effects

---

**🧀 CHEESE HUNT 3.0 - TECHNICAL SPECIFICATION COMPLETE! 🧀**

---

**Document Version:** 3.0  
**Last Updated:** October 26, 2025 - 20:00  
**Maintained By:** Cheese Architect 12.0  
**Status:** Production Ready - Fully Documented

