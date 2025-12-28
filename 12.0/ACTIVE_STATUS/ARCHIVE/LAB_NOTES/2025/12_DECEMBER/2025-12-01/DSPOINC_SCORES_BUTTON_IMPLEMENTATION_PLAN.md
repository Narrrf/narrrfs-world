# 💰 DSPOINC Scores Button Implementation Plan

**Date:** December 1, 2025  
**Bug Reference:** #326 - Better overview over the DSPOINC rewards for each game  
**Status:** 📋 Planning Phase

---

## 🎯 OBJECTIVE

Add a "DSPOINC Scores" button to each of the 3 main games (Tetris, Snake, Space Invaders) that displays:
- Total DSPOINC rewards available in each game
- Boss level DSPOINC rewards breakdown
- Role multiplier information
- Similar functionality to existing "Game Guide" buttons

---

## 📊 CURRENT GAME GUIDE STRUCTURE

### **Existing Pattern:**
1. **Button Location:** Under game container, below Game Guide button
2. **Button Style:** Gradient background matching game theme
3. **Toggle Function:** Shows/hides expandable section
4. **Content:** Game mechanics, boss information, controls

### **Files to Modify:**
- `public/tetris.html` - Tetris game page
- `public/snake.html` - Snake game page  
- `public/space-cheese-invaders.html` - Space Invaders game page

---

## 🧩 TETRIS DSPOINC REWARDS

### **Boss Rewards (from tetris.html):**
- **Boss 1 (Cheese Block King):** 50 DSPOINC (100 VIP)
- **Boss 2 (Tetris Emperor):** 100 DSPOINC (200 VIP)
- **Boss 3 (Lightning Lord):** 150 DSPOINC (300 VIP)
- **Boss 4-8:** (Need to verify exact values)
- **Boss 9 (Ultimate Cheese God):** 1,000 DSPOINC (2,000 VIP)
- **Total All Bosses:** 3,550 DSPOINC (7,100 VIP)

### **Regular Gameplay Rewards:**
- **Line Clear:** 2 DSPOINC per line
- **Multi-Line Bonus:** 2→5, 3→9, 4→16 DSPOINC
- **Bomb Blocks:** 10 DSPOINC per bomb defused

### **Role Multipliers:**
- VIP Holder: 2.0x
- Holder: 1.5x
- Champion: 1.4x
- Season Tester: 1.3x
- Early Bird: 1.2x
- Cheese Hunter: 1.1x

---

## 🐍 SNAKE DSPOINC REWARDS

### **Boss Rewards (from snake.html):**
- **Boss 1 (Baby Boss):** 30 DSPOINC (60 VIP)
- **Boss 2-8:** 50-400 DSPOINC (progressive)
- **Boss 9 (Final Boss):** 550 DSPOINC (1,100 VIP)
- **Total All Bosses:** 1,930 DSPOINC (3,860 VIP)

### **Regular Gameplay Rewards:**
- **Food/Cheese:** 10 DSPOINC per cheese (base)
- **Golden Apples:** Used to damage bosses (5-10 per boss)

### **Role Multipliers:**
- Same as Tetris (VIP Holder: 2.0x, Holder: 1.5x, etc.)

---

## 👾 SPACE INVADERS DSPOINC REWARDS

### **Boss Rewards:**
- **Giant Cheese Boss (Wave 8):** (Need to verify exact value)
- **Phoenix Wave Bonuses:** (Need to verify)
- **Wave Completion Bonuses:** (Need to verify)

### **Regular Gameplay Rewards:**
- **Invader Kill:** 0.0002 DSPOINC per kill (10:1 conversion = 10,000 points = 1 DSPOINC)
- **Combo System:** Bonus points for streaks

### **Role Multipliers:**
- Same as Tetris/Snake

---

## 🛠️ IMPLEMENTATION PLAN

### **STEP 1: Create DSPOINC Scores Section Structure**

For each game, add:
1. **Button:** "💰 DSPOINC Scores - Rewards & Bosses"
2. **Expandable Section:** Hidden by default, shows when button clicked
3. **Content Sections:**
   - Regular Gameplay Rewards
   - Boss Rewards Breakdown
   - Role Multiplier Information
   - Total Potential Rewards

### **STEP 2: HTML Structure (Tetris Example)**

```html
<!-- 📖 Game Guide Toggle Button -->
<div class="text-center mt-4">
  <button id="toggle-tetris-guide-btn" onclick="toggleTetrisGuide()" class="...">
    📖 Tetris Game Guide - Bosses & Mechanics
  </button>
</div>

<!-- 💰 DSPOINC Scores Toggle Button (NEW) -->
<div class="text-center mt-4">
  <button id="toggle-tetris-dspoin-scores-btn" onclick="toggleTetrisDSPOINScores()" class="bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 text-white font-bold py-2 px-6 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
    💰 DSPOINC Scores - Rewards & Bosses
  </button>
</div>

<!-- 💰 TETRIS DSPOINC SCORES (Expandable) -->
<div id="tetrisDSPOINScores" class="hidden mt-4 p-6 bg-gradient-to-br from-gray-900 to-yellow-900 rounded-xl border-2 border-yellow-400 shadow-2xl">
  <div class="flex justify-between items-center mb-4">
    <h3 class="text-2xl font-bold text-yellow-300">💰 Tetris DSPOINC Rewards</h3>
    <button onclick="toggleTetrisDSPOINScores()" class="text-yellow-400 hover:text-yellow-300 text-2xl font-bold">✕</button>
  </div>
  
  <!-- Regular Gameplay Rewards -->
  <div class="mb-6">
    <h4 class="text-xl font-bold text-green-300 mb-3">🎮 Regular Gameplay</h4>
    <div class="space-y-2 text-sm text-gray-300">
      <div class="flex justify-between">
        <span>Line Clear:</span>
        <span class="text-green-400 font-bold">2 DSPOINC per line</span>
      </div>
      <div class="flex justify-between">
        <span>2 Lines (Multi-Line):</span>
        <span class="text-green-400 font-bold">5 DSPOINC</span>
      </div>
      <div class="flex justify-between">
        <span>3 Lines (Multi-Line):</span>
        <span class="text-green-400 font-bold">9 DSPOINC</span>
      </div>
      <div class="flex justify-between">
        <span>4 Lines (Multi-Line):</span>
        <span class="text-green-400 font-bold">16 DSPOINC</span>
      </div>
      <div class="flex justify-between">
        <span>💣 Bomb Defused:</span>
        <span class="text-green-400 font-bold">10 DSPOINC</span>
      </div>
    </div>
  </div>
  
  <!-- Boss Rewards -->
  <div class="mb-6">
    <h4 class="text-xl font-bold text-purple-300 mb-3">👑 Boss Rewards</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
      <div class="bg-yellow-900/30 p-3 rounded-lg border border-yellow-500/50">
        <div class="text-yellow-400 font-bold mb-1">🧀 Boss 1: Cheese Block King</div>
        <div class="text-xs text-green-400">50 DSPOINC (100 VIP!)</div>
      </div>
      <div class="bg-purple-900/30 p-3 rounded-lg border border-purple-500/50">
        <div class="text-purple-400 font-bold mb-1">👑 Boss 2: Tetris Emperor</div>
        <div class="text-xs text-green-400">100 DSPOINC (200 VIP!)</div>
      </div>
      <div class="bg-cyan-900/30 p-3 rounded-lg border border-cyan-500/50">
        <div class="text-cyan-400 font-bold mb-1">⚡ Boss 3: Lightning Lord</div>
        <div class="text-xs text-green-400">150 DSPOINC (300 VIP!)</div>
      </div>
      <!-- Boss 4-8 cards -->
      <div class="bg-red-900/30 p-3 rounded-lg border border-red-500/50 text-center">
        <div class="text-red-400 font-bold text-lg mb-1">🏆 Boss 9: ULTIMATE CHEESE GOD</div>
        <div class="text-lg text-green-400 font-bold">1,000 DSPOINC (2,000 VIP!)</div>
      </div>
    </div>
    <div class="mt-3 text-center text-sm text-yellow-400 font-semibold">
      Total All Bosses: 3,550 DSPOINC (7,100 VIP!)
    </div>
  </div>
  
  <!-- Role Multipliers -->
  <div class="mb-6">
    <h4 class="text-xl font-bold text-blue-300 mb-3">🏆 Role Multipliers</h4>
    <div class="space-y-2 text-sm text-gray-300">
      <div class="flex justify-between">
        <span class="text-yellow-400">🟡 VIP Holder:</span>
        <span class="text-green-400 font-bold">2.0x all rewards</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-300">⚪ Holder:</span>
        <span class="text-green-400 font-bold">1.5x all rewards</span>
      </div>
      <div class="flex justify-between">
        <span class="text-red-400">🔴 Champion:</span>
        <span class="text-green-400 font-bold">1.4x all rewards</span>
      </div>
      <div class="flex justify-between">
        <span class="text-green-400">🟢 Season Tester:</span>
        <span class="text-green-400 font-bold">1.3x all rewards</span>
      </div>
      <div class="flex justify-between">
        <span class="text-blue-400">🔵 Early Bird:</span>
        <span class="text-green-400 font-bold">1.2x all rewards</span>
      </div>
      <div class="flex justify-between">
        <span class="text-orange-400">🟠 Cheese Hunter:</span>
        <span class="text-green-400 font-bold">1.1x all rewards</span>
      </div>
    </div>
  </div>
  
  <!-- Total Potential -->
  <div class="bg-green-900/30 p-4 rounded-lg border border-green-500/50 text-center">
    <div class="text-green-400 font-bold text-lg mb-2">💰 Maximum Potential Per Game</div>
    <div class="text-2xl text-yellow-400 font-bold">3,550+ DSPOINC</div>
    <div class="text-sm text-gray-300 mt-2">(7,100+ DSPOINC with VIP Holder role!)</div>
  </div>
</div>
```

### **STEP 3: JavaScript Toggle Function**

Add to each game's script section:

```javascript
// Tetris DSPOINC Scores toggle function
function toggleTetrisDSPOINScores() {
  const scores = document.getElementById('tetrisDSPOINScores');
  if (scores) {
    if (scores.classList.contains('hidden')) {
      scores.classList.remove('hidden');
      scores.classList.add('animate-fade-in');
      scores.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      scores.classList.add('hidden');
    }
  }
}

// Snake DSPOINC Scores toggle function
function toggleSnakeDSPOINScores() {
  const scores = document.getElementById('snakeDSPOINScores');
  if (scores) {
    if (scores.classList.contains('hidden')) {
      scores.classList.remove('hidden');
      scores.classList.add('animate-fade-in');
      scores.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      scores.classList.add('hidden');
    }
  }
}

// Space Invaders DSPOINC Scores toggle function
function toggleSpaceInvadersDSPOINScores() {
  const scores = document.getElementById('spaceInvadersDSPOINScores');
  if (scores) {
    if (scores.classList.contains('hidden')) {
      scores.classList.remove('hidden');
      scores.classList.add('animate-fade-in');
      scores.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      scores.classList.add('hidden');
    }
  }
}
```

---

## 📋 IMPLEMENTATION CHECKLIST

### **For Each Game (Tetris, Snake, Space Invaders):**

- [ ] **Add DSPOINC Scores Button**
  - [ ] Place below Game Guide button
  - [ ] Match game theme colors
  - [ ] Add hover effects

- [ ] **Create Expandable Section**
  - [ ] Regular gameplay rewards breakdown
  - [ ] Boss rewards grid/cards
  - [ ] Role multiplier information
  - [ ] Total potential rewards summary

- [ ] **Add Toggle Function**
  - [ ] JavaScript function to show/hide section
  - [ ] Smooth scroll to section when opened
  - [ ] Close button in section header

- [ ] **Verify Boss Reward Values**
  - [ ] Check all 9 bosses for each game
  - [ ] Verify total calculations
  - [ ] Confirm VIP multiplier values

- [ ] **Test Functionality**
  - [ ] Button click toggles section
  - [ ] Section displays correctly
  - [ ] Close button works
  - [ ] Mobile responsive

---

## 🎨 STYLING GUIDELINES

### **Button Styling:**
- **Tetris:** Yellow/Orange gradient (`from-yellow-600 to-orange-600`)
- **Snake:** Green/Emerald gradient (`from-green-600 to-emerald-600`)
- **Space Invaders:** Pink/Purple gradient (`from-pink-500 to-purple-500`)

### **Section Styling:**
- Match existing Game Guide section styling
- Use game-specific color themes
- Maintain consistent spacing and layout
- Ensure mobile responsiveness

---

## 🔍 DATA VERIFICATION NEEDED

### **Before Implementation:**
1. **Verify Boss Rewards:**
   - [ ] Tetris Boss 4-8 exact values
   - [ ] Snake Boss 2-8 exact values
   - [ ] Space Invaders boss rewards

2. **Verify Regular Rewards:**
   - [ ] Space Invaders kill-to-DSPOINC conversion
   - [ ] Space Invaders wave/boss bonuses
   - [ ] Any other game-specific rewards

3. **Calculate Totals:**
   - [ ] Total boss rewards per game
   - [ ] Maximum potential per game
   - [ ] VIP multiplier totals

---

## 📝 NOTES

- **Bug #326 Reference:** This addresses the request for better DSPOINC reward overview
- **Season 6 Ready:** Implementation should be ready for Season 6 launch
- **Consistent Pattern:** Follow existing Game Guide button pattern for consistency
- **User Experience:** Clear, organized display of all reward opportunities

---

## 🚀 NEXT STEPS

1. **Verify all boss reward values** from game scripts
2. **Create HTML structure** for each game
3. **Add JavaScript toggle functions**
4. **Test on all 3 game pages**
5. **Deploy to production**

---

**Status:** Ready for implementation after data verification  
**Priority:** Medium (Bug #326 - Season 6 feature)  
**Estimated Time:** 2-3 hours for all 3 games

