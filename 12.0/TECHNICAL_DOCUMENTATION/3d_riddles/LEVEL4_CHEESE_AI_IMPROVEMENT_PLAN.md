# 🎯 LEVEL 4 CHEESE AI IMPROVEMENT PLAN

**Date:** November 19, 2025  
**Status:** 📋 **PLANNING PHASE** — Comprehensive AI enhancement plan  
**Goal:** Make Level 4 cheese hunting more challenging and engaging with progressive AI difficulty

---

## 🎯 OBJECTIVES

### Primary Goals:
1. **Limit Visible Cheeses:** Only 4 cheeses visible at a time (not 1-5 random)
2. **Smarter Spawning:** Strategic spawn positions, not all at once
3. **Progressive AI Difficulty:** Cheeses get smarter from wave to wave (like Snake game)
4. **Hunting Challenge:** Player must actively hunt, not just stand and shoot
5. **Preserve Existing Code:** No breaking changes to traits, rewards, or core functionality

---

## 📊 CURRENT SYSTEM ANALYSIS

### Current Behavior:
- **Spawn Count:** Random 1-5 cheeses per batch
- **Spawn Timing:** 0.5 seconds after each cheese is hit
- **Progressive Difficulty:** Already exists (size, speed, dodging, smartness scale with progress)
- **AI Properties:**
  - `aggressiveness`: 0.3 → 0.8 (30% → 80%)
  - `smartness`: 0.4 → 0.9 (40% → 90%)
  - `moveSpeed`: 12 (base) → 30 (2.5x multiplier)
  - `dodgeDistance`: 15 units
  - `targetChangeInterval`: 2-4 seconds

### Current AI Features:
- ✅ Dodging when player is close
- ✅ Smart pathfinding (goes to opposite side)
- ✅ Mad mode system (random 18% chance)
- ✅ Dynamic speed variation
- ✅ Jumping and bobbing animation

---

## 🚀 PROPOSED IMPROVEMENTS

### 1. WAVE-BASED SPAWNING SYSTEM

#### **Current:** Spawns 1-5 cheeses randomly after each hit
#### **New:** Wave-based system with 4 cheeses per wave

**Implementation:**
- **Wave Structure:** Divide 50 cheeses into ~12-13 waves (4 cheeses per wave)
- **Wave Completion:** New wave spawns only when all 4 cheeses in current wave are caught
- **Wave Spacing:** 1-2 second delay between waves (gives player breathing room)
- **Wave Tracking:** `level4RiddleState.currentWave` (1-13)

**Benefits:**
- Predictable challenge (always 4 cheeses to hunt)
- Clear wave progression
- Better pacing (not overwhelming)

---

### 2. PROGRESSIVE AI DIFFICULTY (WAVE-BASED)

#### **Current:** Difficulty scales with total cheeses caught (0-50)
#### **New:** Difficulty scales with wave number (1-13), with per-cheese micro-improvements

**Wave Difficulty Formula:**
```javascript
const waveNumber = Math.floor(cheesesCaught / 4) + 1; // Wave 1-13
const waveProgress = (cheesesCaught % 4) / 4; // 0-1 within wave
const totalProgress = (waveNumber - 1) / 12; // 0-1 across all waves
```

**Progressive AI Properties (Wave-Based):**

| Wave | Aggressiveness | Smartness | Dodge Distance | Speed Multiplier | Size Multiplier | Target Change Speed |
|------|---------------|-----------|----------------|------------------|-----------------|---------------------|
| 1-2  | 0.2 (20%)     | 0.3 (30%) | 12 units       | 1.0x             | 1.0 (100%)      | 3-5 seconds         |
| 3-4  | 0.35 (35%)    | 0.45 (45%)| 15 units       | 1.2x             | 0.95 (95%)      | 2.5-4 seconds      |
| 5-6  | 0.5 (50%)     | 0.6 (60%) | 18 units       | 1.4x             | 0.9 (90%)       | 2-3.5 seconds      |
| 7-8  | 0.65 (65%)    | 0.75 (75%)| 20 units       | 1.6x             | 0.85 (85%)      | 1.5-3 seconds       |
| 9-10 | 0.8 (80%)     | 0.85 (85%)| 22 units       | 1.8x             | 0.75 (75%)      | 1-2.5 seconds      |
| 11-12| 0.9 (90%)     | 0.95 (95%)| 25 units       | 2.0x             | 0.7 (70%)       | 0.8-2 seconds       |
| 13   | 1.0 (100%)    | 1.0 (100%)| 30 units       | 2.5x             | 0.6 (60%)       | 0.5-1.5 seconds    |

**Micro-Improvements Within Wave:**
- Each cheese in a wave is slightly smarter than the previous
- Formula: `baseValue + (waveProgress * 0.1)` (10% improvement per cheese in wave)

---

### 3. ENHANCED AI BEHAVIORS

#### **A. Predictive Dodging**
**Current:** Dodges when player is within 15 units
**New:** 
- **Early Detection:** Starts dodging at 25-30 units (wave-dependent)
- **Predictive Movement:** Anticipates player aim direction
- **Evasive Patterns:** Uses zigzag, spiral, or sudden direction changes

**Implementation:**
```javascript
// Calculate player aim direction (from camera forward vector)
const playerAimDirection = camera.getWorldDirection(new THREE.Vector3());
const cheeseToPlayer = new THREE.Vector3().subVectors(playerPosition, cheesePosition);
const angleToPlayer = cheeseToPlayer.angleTo(playerAimDirection);

// If player is aiming at cheese, dodge more aggressively
if (angleToPlayer < 0.3) { // Within 30 degrees of aim
  // Trigger aggressive dodge
}
```

#### **B. Group Coordination**
**Current:** Each cheese acts independently
**New:**
- **Formation Patterns:** Cheeses try to spread out (not cluster)
- **Cover Each Other:** When one cheese is being aimed at, others move to distract
- **Tactical Positioning:** Some cheeses stay far, others get close to bait shots

**Implementation:**
```javascript
// Calculate distance to other cheeses
const otherCheeses = level4State.cheeses.filter(c => c !== this && c.mesh.visible);
const avgDistance = otherCheeses.reduce((sum, c) => sum + this.mesh.position.distanceTo(c.mesh.position), 0) / otherCheeses.length;

// If too close to other cheeses, move away
if (avgDistance < 20) {
  // Pick target away from other cheeses
}
```

#### **C. Height Variation Strategy**
**Current:** Fixed height with bobbing
**New:**
- **Smart Height Changes:** Cheeses fly higher when player is aiming at them
- **Ground Hugging:** Some cheeses stay low to make aiming harder
- **Vertical Dodging:** Sudden vertical movements when shot at

**Implementation:**
```javascript
// If player is aiming at cheese, increase height
if (isPlayerAimingAtMe) {
  this.baseHeight = Math.min(15, this.baseHeight + 2); // Fly higher
} else {
  this.baseHeight = Math.max(6, this.baseHeight - 0.5); // Return to normal
}
```

#### **D. Speed Burst System**
**Current:** Dynamic speed variation (40-100%)
**New:**
- **Burst Dodging:** Short speed bursts when player shoots nearby
- **Recovery Period:** Slower after burst (tactical exhaustion)
- **Wave-Based Burst Intensity:** Stronger bursts in later waves

**Implementation:**
```javascript
// When shot detected nearby, trigger speed burst
if (shotDetectedNearby) {
  this.speedBurstTimer = 0.5; // 0.5 second burst
  this.speedBurstMultiplier = 2.0 + (waveNumber * 0.1); // 2.0x to 3.3x
}
```

---

### 4. SMART SPAWN POSITIONING

#### **Current:** Random spawn positions within arena
#### **New:** Strategic spawn positions based on player location and wave difficulty

**Spawn Strategies:**

**Wave 1-4 (Easy):**
- Spawn in visible areas (not behind player)
- Spread out evenly
- Medium distance from player (30-50 units)

**Wave 5-8 (Medium):**
- Spawn in mixed positions (some close, some far)
- Some spawn behind player (requires turning)
- Use arena corners and edges

**Wave 9-13 (Hard):**
- Spawn in difficult positions (behind player, high up, far corners)
- Use cover (spawn behind walls if possible)
- Tactical spacing (not all visible at once)

**Implementation:**
```javascript
function getSmartSpawnPosition(waveNumber, playerPosition, existingCheeses) {
  const spawnStrategies = [
    'visible_front',    // Easy waves
    'mixed_positions',  // Medium waves
    'tactical_hard'     // Hard waves
  ];
  
  const strategy = waveNumber <= 4 ? spawnStrategies[0] :
                   waveNumber <= 8 ? spawnStrategies[1] :
                   spawnStrategies[2];
  
  // Calculate spawn position based on strategy
  // Ensure minimum distance from other cheeses (20 units)
  // Ensure minimum distance from player (15 units)
}
```

---

### 5. VISUAL FEEDBACK FOR DIFFICULTY

#### **New Features:**
- **Wave Indicator:** HUD shows current wave (e.g., "Wave 5/13")
- **Difficulty Color:** Cheeses glow with different colors based on wave
  - Waves 1-4: Normal yellow
  - Waves 5-8: Slight orange tint
  - Waves 9-13: Red tint (dangerous)
- **Speed Lines:** Visual effect on fast-moving cheeses (later waves)

---

## 🔧 IMPLEMENTATION CHECKLIST

### Phase 1: Wave System (Preserve Existing Code)
- [ ] Add `currentWave` to `level4RiddleState`
- [ ] Modify `spawnLevel4Cheeses()` to spawn exactly 4 cheeses
- [ ] Update `captureLevel4Cheese()` to check wave completion
- [ ] Add wave completion check before spawning new wave
- [ ] Test: Ensure all 50 cheeses still spawn correctly

### Phase 2: Progressive AI Difficulty
- [ ] Create `calculateWaveDifficulty(waveNumber)` function
- [ ] Update `FloatingCheese` constructor to accept wave-based difficulty
- [ ] Modify `spawnLevel4Cheeses()` to pass wave difficulty
- [ ] Test: Verify difficulty scales correctly across waves

### Phase 3: Enhanced AI Behaviors
- [ ] Add predictive dodging (player aim detection)
- [ ] Add group coordination (spread out, cover each other)
- [ ] Add smart height variation
- [ ] Add speed burst system
- [ ] Test: Verify AI feels smarter and more challenging

### Phase 4: Smart Spawning
- [ ] Create `getSmartSpawnPosition()` function
- [ ] Update `spawnLevel4Cheeses()` to use smart positioning
- [ ] Test: Verify spawns are strategic and challenging

### Phase 5: Visual Feedback
- [ ] Add wave indicator to HUD
- [ ] Add difficulty-based color tinting
- [ ] Add speed lines effect (optional)
- [ ] Test: Verify visual feedback is clear

### Phase 6: Testing & Balancing
- [ ] Test all 50 cheeses spawn correctly
- [ ] Test wave progression (1-13 waves)
- [ ] Test difficulty scaling (easy → hard)
- [ ] Test AI behaviors (dodging, coordination, etc.)
- [ ] Test traits and rewards (ensure nothing broken)
- [ ] Balance difficulty (adjust values if too easy/hard)

---

## 🚨 PRESERVATION REQUIREMENTS

### Must Not Break:
- ✅ Trait unlocking (`CHEESE_TEMPLE_LEVEL4_STEP0/1/2`)
- ✅ DSPOINC rewards (50 per cheese, 100 for Step 0, etc.)
- ✅ Progress tracking (`cheesesCaught` counter)
- ✅ Portal activation (after 50 cheeses)
- ✅ Step completion logic
- ✅ Explosion effects
- ✅ HUD updates
- ✅ Audio systems

### Code Safety:
- All existing functions remain functional
- New code is additive (doesn't replace existing logic)
- Backward compatible (can revert if needed)
- Extensive testing before deployment

---

## 📝 USER REQUIREMENTS (November 19, 2025)

1. **Wave Count:** ✅ **12 waves + 2 extra** - Last 2 cheeses should be "big aggressive"
   - 12 waves × 4 cheeses = 48 cheeses
   - Final wave: 2 extra big aggressive cheeses = 50 total

2. **Difficulty Curve:** ✅ **Progressive harder, last 2 waves very tricky to catch**
   - Steep difficulty curve
   - Final 2 waves extremely challenging

3. **Visual Feedback:** ✅ **Different colors for stages** (like Snake bosses)
   - Color indicates aggressiveness level
   - Similar to Snake boss color system

4. **Group Coordination:** ✅ **Super intelligent and uncalculable**
   - Make variables that make player "little crazy"
   - Cheese unpredictable but always reachable
   - Every time it's a challenge

5. **Spawn Timing:** ✅ **3 second countdown popup** between waves
   - Like Snake/Tetris countdown
   - Gives player breathing room

6. **Mad Mode:** ✅ **Keep existing mad mode + add abstract variants with colors**
   - Preserve current mad mode system
   - Add color variants for different mad mode types

---

## 🎯 SUCCESS CRITERIA

### Technical:
- ✅ Exactly 4 cheeses visible at a time (except last wave)
- ✅ All 50 cheeses spawn correctly
- ✅ Difficulty scales smoothly from wave 1 to 13
- ✅ AI feels progressively smarter
- ✅ No broken traits or rewards

### Gameplay:
- ✅ Player must actively hunt (not just stand and shoot)
- ✅ Cheeses are challenging but fair
- ✅ Clear sense of progression (waves get harder)
- ✅ Satisfying difficulty curve
- ✅ Fun and engaging experience

---

## 📚 RELATED DOCUMENTATION

- **Current Implementation:** `three.js/main.js` (FloatingCheese class, spawnLevel4Cheeses, captureLevel4Cheese)
- **Riddle Documentation:** `RIDDLE_01_THE_FIRST_SHOT_LEVEL_4.md`
- **Technical Documentation:** `HYTOPIA_THREE_TECH_DOCUMENTATION.md` (Section 22: Weapon Viewmodel)

---

**Status:** 📋 **READY FOR USER REVIEW** — Awaiting feedback on questions before implementation

