# 🎯 LEVEL 4 MONSTER WAVES PLAN — After Cheese Waves

**Document Created:** November 22, 2025  
**Document Updated:** November 23, 2025  
**Level:** Cheese Temple — Level 4 "The First Shot"  
**Status:** ✅ **IMPLEMENTED - MONSTERS WORKING**  
**Step:** Step 2 (after cheese waves, before portal completion)

---

## 📋 OVERVIEW

### Objective
After completing the cheese waves (Step 1), players face a new challenge: **Monster Wave Shooting**. Similar to the cheese waves, but with animated monsters that spawn in **groups of 3 (troops)** and require shooting to defeat. This adds variety and uses the extensive monster model library that isn't currently used in gameplay.

### Flow Summary
1. **Current Step 1 (Cheese Waves):** Shoot 50 cheeses in 12 waves + final wave (COMPLETED ✅)
2. **Proposed Step 2 (Monster Waves):** Shoot monsters in waves of 3 (troops) - **NEW STEP**
3. **Proposed Step 3 (Portal Completion):** Enter portal to complete level (renamed from current Step 2)

---

## 🎮 PROPOSED GAMEPLAY MECHANICS

### Monster Wave System (Similar to Cheese Waves)

#### **Wave Structure:**
- **10 waves** of 3 monsters each = **30 monsters total**
- Each wave spawns as a **troop** (3 monsters together)
- 3-second countdown between waves (same as cheese waves)
- Wave completion: Next wave spawns only after all 3 monsters in current wave are defeated

#### **Monster Selection Strategy:**
Use monsters that are **NOT currently used in Level 3** gameplay:
- **Big Monsters (Not Used in L3):** Bunny, Monkroose, Cactoro, Orc_Skull, Birb, Fish (6 types)
- **Blob Monsters (Never Used):** All 18 blob types (GreenBlob, PinkBlob, Cat, Chicken, Dog, GreenSpikyBlob, Wizard, Mushnub, Mushnub_Evolved, Alien, Orc, Fish, Ninja, Pigeon, Yeti, Cactoro, Birb) 
- **Flying Monsters (Never Used):** All 7+ flying types (Alpaking, Armabee, Dragon, Ghost, Glub, Goleling, plus evolved versions)

**Total Available:** ~31+ unique monster types!

#### **Progressive Wave System (With Exciting Size Variations):**

**Wave 1-2: Easy Troops (Big Monsters - Large & Intimidating)**
- Bunny, Monkroose, Cactoro (Wave 1)
- Orc_Skull, Birb, Fish (Wave 2)
- Speed: 1.0x, Size: **120%** (LARGE - makes them impressive and easier to see/hit), Health: 1 shot
- Ground patrol, slower movement

**Wave 3-4: Medium Troops (Mix Big + Blob - Size Variation)**
- Mix of Big + Blob monsters
- Speed: 1.2x, Size: **110%** (Still large, but slightly smaller), Health: 1 shot
- **Size Variation:** One monster per wave is 130% (bigger), one is 100% (normal), one is 90% (smaller) - creates visual excitement!
- Slightly faster movement

**Wave 5-6: Hard Troops (Blob Monsters - Medium Size)**
- GreenBlob, PinkBlob, Cat (Wave 5)
- Chicken, Dog, GreenSpikyBlob (Wave 6)
- Speed: 1.4x, Size: **100%** (Normal size), Health: 1 shot
- **Size Variation:** Mix of 110%, 100%, 90% within each wave
- Faster movement, more erratic

**Wave 7-8: Very Hard Troops (Advanced Blob - Smaller & Faster)**
- Wizard, Mushnub, Mushnub_Evolved (Wave 7)
- Alien, Orc, Fish (Wave 8) - Blob variants
- Speed: 1.6x, Size: **85%** (Smaller, harder to hit), Health: 1 shot
- **Size Variation:** Mix of 95%, 85%, 75% within each wave
- Very fast, aggressive movement

**Wave 9-10: Extreme Troops (Flying Monsters - HUGE & CRAZY)**
- Alpaking, Armabee, Dragon (Wave 9)
- Ghost, Glub, Goleling (Wave 10)
- Speed: 2.0x, Size: **150%** (HUGE - makes them impressive and dangerous!), Health: 1 shot
- **Full 3D Movement:** Can fly anywhere in XYZ space (entire gamefield)
- **Crazy Opponents:** Unpredictable flight patterns, can dive, swoop, circle player
- **Movement Range:** Full arena height (y: 0 to 15 units), full XZ bounds
- Fastest movement, hardest to track and hit

**Final Wave: Boss Troop (Evolved Flying Monsters - MASSIVE & DEADLY)**
- 3 evolved flying monsters (Alpaking_Evolved, Armabee_Evolved, Dragon_Evolved)
- Speed: 2.5x, Size: **180%** (MASSIVE - truly boss-level size!), Health: 2 shots each
- **Full 3D Movement:** Complete XYZ freedom over entire gamefield
- **Can Shoot at Player:** Flying monsters fire projectiles at player
- **Game Over on Hit:** If player gets hit by monster projectile = instant game over
- **Maximum Difficulty:** Fast, huge, aerial movement, requires multiple hits, AND can kill player
- **Boss Mechanics:** 
  - Shoot projectiles every 2-3 seconds
  - Projectiles are visible (colored orbs/beams)
  - Player must dodge while shooting back
  - Creates intense boss battle experience

#### **Monster Behavior:**
- **Troop Formation:** Monsters spawn in a triangle/group formation (3 together)
- **Ground Monsters (Waves 1-8):** 
  - Movement: Similar to Level 3 monsters - random waypoint patrol system
  - Animation: Full walk/run animation loops
  - AI Difficulty: Progressive from basic patrol to unpredictable movement
- **Flying Monsters (Waves 9-10 & Final):**
  - **Full 3D Movement:** Complete XYZ freedom over entire gamefield
  - **Movement Range:** 
    - X: Full arena width (0 to 160 units)
    - Z: Full arena depth (0 to 160 units)  
    - Y: Full height range (0 to 15 units - can fly from ground to high above)
  - **Crazy Flight Patterns:**
    - Can dive down at player
    - Can swoop in circles
    - Can fly high above and drop down
    - Can fly behind player and attack from behind
    - Unpredictable 3D waypoint system
  - **Animation:** Full fly animation loops
  - **Size:** HUGE (150% in waves 9-10, 180% in final wave) - makes them impressive and dangerous
  - **Final Wave Shooting:**
    - Can fire projectiles at player
    - Projectile cooldown: 2-3 seconds per monster
    - Projectiles are visible (colored orbs/beams with trail)
    - Player hit = instant game over
    - Creates intense boss battle where player must dodge while shooting

#### **Shooting System:**
- Uses same weapon system as cheese waves
- Raycasting from camera center (crosshair)
- Hit indicator: Monster-specific explosion effect
- Sound: Different hit sound for monsters (could use different SFX)
- Monster disappears when shot (or requires 2 shots for final wave)

#### **Visual Feedback:**
- HUD displays:
  - Current wave: "Monster Wave X/10" or "FINAL BOSS WAVE"
  - Wave progress: "Wave Progress: X/3" (monsters in current wave)
  - Total progress: "🎯 Monsters Defeated: X/30 (X%)"
- Toast notifications for each monster defeated
- Different explosion effects based on monster type

#### **Rewards:**
- Each monster defeated: **+50 DSPOINC** (same as cheese)
- Total: 30 monsters × 50 = **1,500 DSPOINC base**
- After all 30 monsters defeated:
  - Unlocks trait `CHEESE_TEMPLE_LEVEL4_STEP2` (renamed from current step)
  - Shows completion toast: "Monster Waves Complete! Portal opening..."
  - Portal appears for Step 3 (completion screen)

---

## 📊 MONSTER AVAILABILITY ANALYSIS

### **Level 2 Display Models (Total: ~40+ models)**
All monsters shown as statues/previews in Level 2, but NOT used in gameplay:

#### **Big Monsters (17 total):**
- ✅ **Used in Level 3:** Demon, Frog, Orc, Dino, Ninja, BlueDemon, MushroomKing, Tribal, Alien, Yeti (10)
- ⭐ **Available for Level 4:** Bunny, Monkroose, Cactoro, Orc_Skull, Birb, Fish (6)
- 📦 **Duplicate:** Cactoro appears twice in Level 2 list (shelf 7 & 21)

#### **Blob Monsters (18 total):**
- ⭐ **All Available for Level 4:** GreenBlob, PinkBlob, Cat, Chicken, Dog, GreenSpikyBlob, Wizard, Mushnub, Mushnub_Evolved, Alien, Orc, Fish, Ninja, Pigeon, Yeti, Cactoro, Birb (17 unique types)

#### **Flying Monsters (7+ total):**
- ⭐ **All Available for Level 4:** Alpaking, Armabee, Dragon, Ghost, Glub, Goleling, plus evolved versions (Alpaking_Evolved, Armabee_Evolved, Dragon_Evolved, Glub_Evolved, Goleling_Evolved) = ~12 types total

### **Total Unique Monsters Available:**
- **Big (unused):** 6 types
- **Blob:** 17 types
- **Flying:** 12 types
- **Total: ~35 unique monster types available!**

---

## 🎯 PROPOSED WAVE BREAKDOWN

### **10 Waves × 3 Monsters = 30 Monsters**

| Wave | Type | Monsters | Difficulty | Speed | Size | Notes |
|------|------|----------|------------|-------|------|-------|
| 1 | Big | Bunny, Monkroose, Cactoro | Easy | 1.0x | **120%** (LARGE) | Ground patrol, impressive size |
| 2 | Big | Orc_Skull, Birb, Fish | Easy | 1.0x | **120%** (LARGE) | Ground patrol, impressive size |
| 3 | Mixed | Cactoro (Big), GreenBlob, PinkBlob | Medium | 1.2x | **110%** (varied: 130%/100%/90%) | Size variation creates excitement |
| 4 | Mixed | Birb (Big), Cat, Chicken | Medium | 1.2x | **110%** (varied: 130%/100%/90%) | Size variation creates excitement |
| 5 | Blob | Dog, GreenSpikyBlob, Wizard | Hard | 1.4x | **100%** (varied: 110%/100%/90%) | Normal size, faster movement |
| 6 | Blob | Mushnub, Mushnub_Evolved, Alien | Hard | 1.4x | **100%** (varied: 110%/100%/90%) | Normal size, faster movement |
| 7 | Blob | Orc (Blob), Fish (Blob), Ninja (Blob) | Very Hard | 1.6x | **85%** (varied: 95%/85%/75%) | Smaller, very fast |
| 8 | Blob | Pigeon, Yeti (Blob), Cactoro (Blob) | Very Hard | 1.6x | **85%** (varied: 95%/85%/75%) | Smaller, very fast |
| 9 | Flying | Alpaking, Armabee, Dragon | Extreme | 2.0x | **150%** (HUGE) | Full 3D XYZ movement, crazy opponents |
| 10 | Flying | Ghost, Glub, Goleling | Extreme | 2.0x | **150%** (HUGE) | Full 3D XYZ movement, crazy opponents |
| **Final** | Flying (Evolved) | Alpaking_Evolved, Armabee_Evolved, Dragon_Evolved | Boss | 2.5x | **180%** (MASSIVE) | Full 3D XYZ, **can shoot at player**, 2 hits each, game over on hit |

---

## 🔧 TECHNICAL IMPLEMENTATION PLAN

### **New State Variables:**
```javascript
const level4RiddleState = {
  // ... existing cheese wave state ...
  step2Active: false, // NEW: Monster waves active
  monsterWavesComplete: false,
  currentMonsterWave: 1,
  monstersInCurrentWave: 0,
  monstersDefeated: 0,
  monsterWaveCountdownActive: false,
  monsterWaveCountdownTime: 0,
  step2TraitUnlocked: false
};

const level4State = {
  // ... existing state ...
  monsters: [], // NEW: Array of active monsters
  monsterWaveQueue: [] // NEW: Queue of monsters to spawn
};
```

### **New Constants:**
```javascript
const LEVEL4_MONSTER_WAVES_COUNT = 10; // 10 waves
const LEVEL4_MONSTERS_PER_WAVE = 3; // 3 monsters per wave (troops)
const LEVEL4_TOTAL_MONSTERS = 30; // 30 monsters total
const LEVEL4_DSPOINC_PER_MONSTER = 50; // Same as cheese
const LEVEL4_MONSTER_WAVE_COUNTDOWN = 3; // 3 seconds between waves
const LEVEL4_FINAL_MONSTER_WAVE_HEALTH = 2; // Final wave: 2 hits per monster

// Monster wave definitions
const LEVEL4_MONSTER_WAVE_QUEUE = [
  // Wave 1: Big monsters (easy)
  [
    "/textures/3d models/Monster 1/Big/glTF/Bunny.gltf",
    "/textures/3d models/Monster 1/Big/glTF/Monkroose.gltf",
    "/textures/3d models/Monster 1/Big/glTF/Cactoro.gltf"
  ],
  // Wave 2: Big monsters (easy)
  [
    "/textures/3d models/Monster 1/Big/glTF/Orc_Skull.gltf",
    "/textures/3d models/Monster 1/Big/glTF/Birb.gltf",
    "/textures/3d models/Monster 1/Big/glTF/Fish.gltf"
  ],
  // Wave 3-10: ... (see wave breakdown above)
  // Final Wave: Evolved flying monsters
  [
    "/textures/3d models/Monster 1/Flying/glTF/Alpaking_Evolved.gltf",
    "/textures/3d models/Monster 1/Flying/glTF/Armabee_Evolved.gltf",
    "/textures/3d models/Monster 1/Flying/glTF/Dragon_Evolved.gltf"
  ]
];
```

### **Core Functions Needed:**
1. `spawnMonsterWave(waveNumber)` - Spawns 3 monsters in triangle formation with size variation
2. `spawnLevel4Monster(monsterPath, position, waveDifficulty, sizeMultiplier)` - Spawns individual monster with custom size
3. `checkMonsterHit(raycaster)` - Checks if player shot a monster
4. `defeatMonster(monster)` - Handles monster defeat (explosion, reward, removal)
5. `updateLevel4Monsters(delta)` - Updates monster movement and AI (ground + flying)
6. `updateFlyingMonsterMovement(monster, delta)` - Special 3D movement for flying monsters
7. `updateMonsterShooting(monster, delta)` - Handles final wave monster projectile shooting
8. `checkMonsterProjectileHit(player)` - Checks if monster projectile hit player (game over)
9. `startMonsterWaves()` - Transitions from cheese waves to monster waves
10. `completeMonsterWaves()` - Handles completion (unlock trait, show portal)

### **Monster Spawning Logic:**
- **Troop Formation:** Spawn 3 monsters in triangle pattern (equilateral triangle, ~10 units apart)
- **Spawn Position:** Random area in arena, similar to cheese waves
- **Anti-Clustering:** Maintain minimum distance from each other and player
- **Size Variation (Waves 3-8):** Within each wave, spawn monsters with varied sizes:
  - One monster: +20% size (bigger)
  - One monster: Base size (normal)
  - One monster: -10% size (smaller)
  - Creates visual excitement and variety
- **Flying Monster Spawning (Waves 9-10 & Final):**
  - **Height Variation:** Spawn at random heights (y: 3-12 units)
  - **Full Arena Access:** Can spawn anywhere in XYZ space
  - **HUGE Size:** 150% (waves 9-10) or 180% (final wave) - makes them impressive
  - **Initial Flight Pattern:** Start with aggressive dive or swoop toward player

### **Monster Movement:**
- **Ground Monsters (Waves 1-8):** Similar to Level 3 - random waypoint patrol system
  - Speed: Based on wave difficulty (1.0x → 1.6x)
  - AI: Progressive difficulty - faster direction changes in harder waves
- **Flying Monsters (Waves 9-10 & Final):**
  - **Full 3D Movement:** Complete XYZ freedom over entire gamefield
  - **Movement Bounds:**
    - X: 0 to 160 units (full arena width)
    - Z: 0 to 160 units (full arena depth)
    - Y: 0 to 15 units (ground to high above)
  - **Crazy Flight Patterns:**
    - Random 3D waypoint system
    - Can dive down at player (y: 15 → 2)
    - Can swoop in circles around player
    - Can fly high above and drop down
    - Can fly behind player and attack from behind
    - Unpredictable direction changes
    - Can hover at different heights
  - **Speed:** 2.0x (waves 9-10) or 2.5x (final wave)
  - **Size:** HUGE (150% or 180%) - makes them impressive and dangerous
  - **AI:** Very aggressive, constantly moving, hard to track

### **Shooting Detection:**
- Use existing raycasting system from cheese waves
- Check intersection with monster mesh bounds
- Different hit detection for flying vs ground monsters
- Visual feedback: Monster-specific explosion effect
- **Final Wave Special:** 
  - Monsters require 2 hits to defeat
  - First hit: Monster flashes red, shows damage
  - Second hit: Monster explodes and is defeated

### **Monster Shooting System (Final Wave Only):**
- **Projectile System:** Flying monsters fire projectiles at player
- **Projectile Appearance:** Visible colored orbs/beams with trail effect
- **Shooting Frequency:** Every 2-3 seconds per monster
- **Projectile Speed:** Fast (10-15 units/second)
- **Projectile Damage:** Instant game over if player is hit
- **Collision Detection:** Check if projectile hits player (within player radius)
- **Visual Feedback:** 
  - Projectile trail (colored beam)
  - Warning indicator when projectile is incoming
  - Game over screen when hit
- **Dodging Mechanic:** Player must move/dodge while shooting back
- **Boss Battle Feel:** Creates intense combat where player must balance offense and defense

---

## 🎨 VISUAL DESIGN

### **Monster Appearance:**
- **Animation:** Full walk/run/fly animations (use existing Level 3 animation system)
- **Size Scaling:** Progressive size variation for excitement:
  - **Waves 1-2:** 120% (LARGE - impressive and easier to see)
  - **Waves 3-4:** 110% base with variation (130%/100%/90% per wave)
  - **Waves 5-6:** 100% base with variation (110%/100%/90% per wave)
  - **Waves 7-8:** 85% base with variation (95%/85%/75% per wave)
  - **Waves 9-10:** 150% (HUGE - impressive flying monsters)
  - **Final Wave:** 180% (MASSIVE - boss-level size)
- **Size Variation System:** Within waves 3-8, each wave has 3 different sizes:
  - Biggest: +20% from base
  - Normal: Base size
  - Smallest: -10% from base
  - Creates visual excitement and variety
- **Color Coding:** Could add wave-based color tint (similar to cheese wave colors)
  - Waves 1-2: Normal colors
  - Waves 3-4: Slight red tint
  - Waves 5-6: Orange tint
  - Waves 7-8: Red tint
  - Waves 9-10: Dark red tint (HUGE flying monsters)
  - Final Wave: Bright red glow (MASSIVE boss monsters)

### **Explosion Effects:**
- Different explosion effect for each monster type
- Particle system: Monster-specific particles (could use monster color/material)
- Fade out over 0.5-1 second

### **HUD Display:**
```
Monster Wave X/10
Wave Progress: 2/3
🎯 Monsters Defeated: 8/30 (27%)
```

---

## 💰 REWARDS & TRAITS

### **Per Monster Reward:**
- **+50 DSPOINC** per monster defeated (same as cheese)
- Total: 30 monsters × 50 = **1,500 DSPOINC base**

### **Step Completion:**
- **Trait:** `CHEESE_TEMPLE_LEVEL4_STEP2` (unlocked after all 30 monsters defeated)
- **DSPOINC:** 1,500 base (multiplied by role multiplier)
- **Total Level 4 Rewards (with monster waves):**
  - Step 0: +100 DSPOINC
  - Step 1 (Cheese): +2,500 DSPOINC (50 × 50)
  - Step 2 (Monsters): +1,500 DSPOINC (30 × 50) - **NEW**
  - Step 3 (Portal): +200 DSPOINC
  - **Grand Total: 4,300 DSPOINC base** (VIP: 8,600 with 2.0x)

---

## 🎯 DIFFICULTY PROGRESSION

### **Wave Difficulty Scale:**

| Wave Range | Monster Types | Speed | Size | Movement | AI | Special Features |
|------------|---------------|-------|------|----------|-----|----------------|
| 1-2 | Big (ground) | 1.0x | **120%** (LARGE) | Basic patrol | Simple | Impressive large size |
| 3-4 | Mixed | 1.2x | **110%** (varied) | Faster patrol | Basic | Size variation (130%/100%/90%) |
| 5-6 | Blob | 1.4x | **100%** (varied) | Fast, erratic | Medium | Size variation (110%/100%/90%) |
| 7-8 | Advanced Blob | 1.6x | **85%** (varied) | Very fast | Advanced | Size variation (95%/85%/75%) |
| 9-10 | Flying | 2.0x | **150%** (HUGE) | **Full 3D XYZ** | Very Advanced | **Crazy opponents, full arena flight** |
| Final | Evolved Flying | 2.5x | **180%** (MASSIVE) | **Full 3D XYZ + Shooting** | Boss Level | **Can shoot at player, 2 hits, game over on hit** |

---

## 🔄 INTEGRATION WITH EXISTING SYSTEM

### **Step Renaming:**
- **Current Step 1:** Cheese Waves (unchanged)
- **New Step 2:** Monster Waves (NEW)
- **Current Step 2 → Step 3:** Portal Completion (renamed)

### **Flow Changes:**
1. Step 0: Hidden cheese stone (unchanged)
2. Step 1: Cheese waves (unchanged) → Completes → **NEW: Start monster waves**
3. **NEW Step 2:** Monster waves (30 monsters in 10 waves of 3) → Completes → Portal appears
4. Step 3: Portal completion (renamed from Step 2)

### **State Management:**
- Reuse existing Level 4 wave system architecture
- Add monster-specific state variables
- Monster waves follow same pattern as cheese waves (countdown, wave completion, etc.)

---

## ✅ IMPLEMENTATION CHECKLIST

### **Phase 1: Planning & Setup**
- [x] Analyze available monsters
- [x] Create wave breakdown
- [x] Define constants and state structure
- [ ] Finalize monster wave queue (select exact monsters per wave)
- [ ] Review with team/user

### **Phase 2: Core Implementation**
- [ ] Add monster wave state variables
- [ ] Create monster spawning functions
- [ ] Implement troop formation system
- [ ] Add monster movement/AI system
- [ ] Implement shooting detection for monsters
- [ ] Add monster defeat/explosion effects

### **Phase 3: Integration**
- [ ] Integrate with cheese wave completion
- [ ] Update HUD for monster waves
- [ ] Add trait unlock system
- [ ] Implement reward system
- [ ] Update portal trigger (move to Step 3)

### **Phase 4: Polish**
- [ ] Balance difficulty progression
- [ ] Add visual effects (explosions, particles)
- [ ] Test all 10 waves
- [ ] Verify rewards and traits
- [ ] Update documentation

---

## 💡 DESIGN CONSIDERATIONS

### **Fun Factor:**
- **Variety:** Different monster types keep it interesting
- **Progressive Difficulty:** Challenges increase naturally
- **Troop Formation:** 3 monsters together creates dynamic combat
- **Flying Monsters:** Adds vertical challenge (new dimension)

### **Balance:**
- **Total Monsters:** 30 is reasonable (similar to cheese count)
- **Waves:** 10 waves of 3 feels manageable
- **Rewards:** 1,500 DSPOINC matches cheese reward scale
- **Difficulty:** Progressive but always beatable

### **Replayability:**
- Different monster combinations per wave
- Random spawn positions
- Unpredictable movement patterns
- Multiple attempts to master

---

## 📝 NOTES & DISCUSSION POINTS

### **Approved Features:**
1. ✅ **Wave Count:** 10 waves (30 monsters) - APPROVED
2. ✅ **Monster Selection:** Chosen monsters are good - APPROVED
3. ✅ **Difficulty:** Progressive with exciting size variations - APPROVED
4. ✅ **Rewards:** 50 DSPOINC per monster - APPROVED
5. ✅ **Final Wave:** 2 hits per monster, can shoot at player, game over on hit - APPROVED
6. ✅ **Flying Monsters:** Full 3D XYZ movement, HUGE size (150-180%), crazy opponents - APPROVED
7. ✅ **Size Variations:** Progressive size changes for visual excitement - APPROVED

### **Implementation Notes:**
- **Size System:** Waves 1-2 large (120%), waves 3-8 varied sizes within each wave, waves 9-10 huge (150%), final massive (180%)
- **Flying Mechanics:** Full XYZ movement over entire gamefield (0-160 XZ, 0-15 Y)
- **Final Wave Danger:** Monsters shoot projectiles, player must dodge while shooting back
- **Boss Battle Feel:** Final wave creates intense combat experience

### **Alternative Ideas:**
- **Mixed Waves:** Some waves could mix 2 ground + 1 flying monster
- **Boss Monster:** Final wave could be 1 huge boss instead of 3 evolved
- **Power-ups:** Special waves could drop temporary power-ups
- **Time Challenge:** Optional timer mode for extra rewards

---

**Document Version:** 1.0  
**Last Updated:** November 22, 2025  
**Status:** ✅ **IMPLEMENTED - NOVEMBER 23, 2025**

---

## ✅ **IMPLEMENTATION COMPLETE**

### **Implementation Date:** November 23, 2025

### **Key Achievements:**
- ✅ **Monster Wave System** - 10 waves of 3 monsters each
- ✅ **Progressive Difficulty** - Size and speed scaling implemented
- ✅ **Final Boss Wave** - Flying monsters with projectiles
- ✅ **Weapon Integration** - Both weapon slots work correctly
- ✅ **Movement System** - Monsters move and rotate like Level 3
- ✅ **Rendering Fix** - SkeletonUtils.clone() for proper visibility
- ✅ **GOD Mode Integration** - G key cycles through all 4 steps

### **Critical Discovery:**
- **Issue:** Monsters were spawning but invisible (10+ debugging attempts)
- **Root Cause:** Standard `gltf.scene.clone(true)` doesn't preserve skeleton structure
- **Solution:** Use `SkeletonUtils.clone(gltf.scene)` for animated GLTF models
- **Rule Created:** `12.0/RULES/14_GLTF_SKELETON_CLONING_RULE.md`

### **Technical Details:**
- **Import Added:** `import * as SkeletonUtils from "three/examples/jsm/utils/SkeletonUtils.js";`
- **Cloning Method:** Changed from `gltf.scene.clone(true)` to `SkeletonUtils.clone(gltf.scene)`
- **Material Setup:** Enhanced material updates after cloning
- **Spawn Positions:** Fixed to spawn in front of player (positive Z direction)
- **Movement Rotation:** Added rotation to face movement direction (like Level 3)

### **Wave 1 Monsters (Proven Working):**
- Demon (`/textures/3d models/Monster 1/Big/glTF/Demon.gltf`)
- Frog (`/textures/3d models/Monster 1/Big/glTF/Frog.gltf`)
- Orc (`/textures/3d models/Monster 1/Big/glTF/Orc.gltf`)

### **Testing Status:**
- ✅ Monsters are visible
- ✅ Monsters are shootable
- ✅ Animations work correctly
- ✅ Movement works correctly
- ✅ Multiple instances work (3 per wave)

---

## 🎯 NEXT STEPS

1. ✅ **Implementation Complete** - Monster waves working
2. 🔄 **Test all 10 waves** - Verify all waves work correctly
3. 🔄 **Test final boss wave** - Verify flying monsters and projectiles
4. 🔄 **Add more monster variety** - Expand waves 2-10 with different monsters
5. 🔄 **Enhance final wave** - Add more epic boss battle features

