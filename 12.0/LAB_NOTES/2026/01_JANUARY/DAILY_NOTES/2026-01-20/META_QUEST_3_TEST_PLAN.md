# 🥽 META QUEST 3 VR TEST PLAN - LIVE PRODUCTION TEST

**Date:** January 20, 2026  
**Time:** 1:55 PM  
**Status:** 🔴 **LIVE TEST IN PROGRESS**  
**Tester:** Friend with Meta Quest 3  
**Environment:** Production (narrrfs.world)  

---

## 🎯 **TEST OBJECTIVES**

### **Primary Goals:**
1. ✅ Verify VR controller input works correctly (movement, rotation, jump, sprint)
2. ✅ Verify player can navigate all 6 levels in VR
3. ✅ Verify all interactions work (riddles, chests, portals)
4. ✅ Verify graphics quality auto-adjusts for VR performance
5. ✅ Identify any VR-specific bugs or issues

### **Secondary Goals:**
- ✅ Test comfort level (motion sickness, control responsiveness)
- ✅ Test UI visibility and readability in VR
- ✅ Test audio spatialization in VR
- ✅ Gather feedback on control mappings

---

## 🔧 **PRE-TEST SETUP**

### **Before Starting:**
- [ ] **Meta Quest 3 charged** (>50% battery recommended)
- [ ] **Wi-Fi connected** (strong signal required)
- [ ] **Guardian boundary set** (play area defined)
- [ ] **Production URL bookmarked:** `https://narrrfs.world/three.js/3d-riddle-game.html`

### **Initial Load:**
1. Open Meta Quest 3 browser
2. Navigate to production URL
3. Click "ENTER VR" button in options menu
4. Wait for VR session to start
5. **✅ Verify:** Controllers appear, headset tracking works

---

## 🎮 **CONTROLLER MAPPING REFERENCE**

### **Meta Quest 3 Controls:**
- **Left Thumbstick:**
  - **Push up:** Forward
  - **Pull down:** Backward
  - **Left/Right:** Strafe
  - **Click:** Sprint toggle
- **Right Thumbstick:**
  - **Left/Right:** Smooth turn rotation
  - **Up/Down:** (Future: Look up/down)
- **Left Controller:**
  - **X Button:** Jump
  - **Y Button:** (Future: Interact)
  - **Trigger:** (Future: Primary action)
  - **Grip:** (Future: Secondary action)
- **Right Controller:**
  - **A Button:** Jump
  - **B Button:** (Future: Menu)
  - **Trigger:** (Future: Primary action)
  - **Grip:** (Future: Secondary action)

---

## 📋 **SYSTEMATIC TEST PLAN**

### **PHASE 1: VR INITIALIZATION (5 minutes)**

#### **Test 1.1: VR Session Start**
- [ ] Click "ENTER VR" button in options menu
- [ ] **✅ Expected:** VR session starts, view switches to headset
- [ ] **✅ Expected:** Controllers appear in view
- [ ] **✅ Expected:** No errors in browser console
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 1.2: Controller Detection**
- [ ] Move both controllers around
- [ ] **✅ Expected:** Controller positions update in real-time
- [ ] **✅ Expected:** Both controllers visible and tracking
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 1.3: Graphics Quality Auto-Adjust**
- [ ] Check initial graphics quality
- [ ] **✅ Expected:** Auto-detects Quest 3 as "Low" or "Med" tier
- [ ] **✅ Expected:** Scene loads smoothly without stuttering
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 2: BASIC MOVEMENT (10 minutes)**

#### **Test 2.1: Forward/Backward Movement**
- [ ] Push left thumbstick up (forward)
- [ ] **✅ Expected:** Player moves forward smoothly
- [ ] Pull left thumbstick down (backward)
- [ ] **✅ Expected:** Player moves backward smoothly
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 2.2: Strafing (Left/Right)**
- [ ] Push left thumbstick left
- [ ] **✅ Expected:** Player strafes left
- [ ] Push left thumbstick right
- [ ] **✅ Expected:** Player strafes right
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 2.3: Rotation (Smooth Turn)**
- [ ] Push right thumbstick left
- [ ] **✅ Expected:** Camera rotates left smoothly
- [ ] Push right thumbstick right
- [ ] **✅ Expected:** Camera rotates right smoothly
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 2.4: Sprint**
- [ ] Click left thumbstick (press down)
- [ ] **✅ Expected:** Sprint icon appears or speed increases
- [ ] Push forward while sprinting
- [ ] **✅ Expected:** Player moves faster
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 2.5: Jump**
- [ ] Press X button (left controller) or A button (right controller)
- [ ] **✅ Expected:** Player jumps
- [ ] **✅ Expected:** Gravity pulls player back down
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 3: LEVEL 1 - CHEESE TEMPLE (15 minutes)**

#### **Test 3.1: Initial Spawn**
- [ ] Player spawns in Level 1
- [ ] **✅ Expected:** Player standing on ground, not falling
- [ ] **✅ Expected:** Cheese Temple visible in distance
- [ ] **✅ Expected:** Bear trap visible to the right
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 3.2: Riddle Step 0 - Golden Stone**
- [ ] Walk to golden stone block (near spawn)
- [ ] Stand on it for 10 seconds
- [ ] **✅ Expected:** Progress bar fills up
- [ ] **✅ Expected:** Notification appears: "Step 0 complete"
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 3.3: Riddle Step 1 - Cheese Aiming**
- [ ] Look at floating cheese entity (auto-aim with headset)
- [ ] Keep looking for 10 seconds
- [ ] **✅ Expected:** Progress bar fills up
- [ ] **✅ Expected:** Notification appears: "Step 1 complete"
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 3.4: Riddle Step 2 - Unlockable Block**
- [ ] Look at unlockable block (auto-aim with headset)
- [ ] Keep looking for 10 seconds
- [ ] **✅ Expected:** Progress bar fills up
- [ ] **✅ Expected:** Portal unlocks
- [ ] **✅ Expected:** Notification appears: "Level 1 complete!"
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 3.5: Portal to Level 2**
- [ ] Walk into blue portal
- [ ] **✅ Expected:** Smooth transition to Level 2
- [ ] **✅ Expected:** No black screen or errors
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 4: LEVEL 2 - THE SPAWN (10 minutes)**

#### **Test 4.1: Initial Spawn**
- [ ] Player spawns in Level 2
- [ ] **✅ Expected:** Player standing on platform
- [ ] **✅ Expected:** Environment visible (trees, monsters on shelves)
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 4.2: Riddle Step 0 - Platform Standing**
- [ ] Stand on center platform for 10 seconds
- [ ] **✅ Expected:** Progress bar fills up
- [ ] **✅ Expected:** Lever unlocks
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 4.3: Riddle Step 1 - Lever Interaction**
- [ ] Walk to lever
- [ ] Look at lever (auto-aim with headset)
- [ ] **✅ Expected:** Lever activates
- [ ] **✅ Expected:** Inspection zones unlock
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 4.4: Riddle Step 2 - Inspection Zones**
- [ ] Walk to each inspection zone (3 total)
- [ ] Stand in each for required time
- [ ] **✅ Expected:** Progress bar fills for each zone
- [ ] **✅ Expected:** Portal unlocks after all zones
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 4.5: Portal to Level 3**
- [ ] Walk into portal
- [ ] **✅ Expected:** Smooth transition to Level 3
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 5: LEVEL 3 - THE HUNT (15 minutes)**

#### **Test 5.1: Initial Spawn**
- [ ] Player spawns in Level 3
- [ ] **✅ Expected:** Player standing on ground
- [ ] **✅ Expected:** Environment visible (trees, hunting area)
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 5.2: Riddle Step 0 - Platform Standing**
- [ ] Stand on platform for 10 seconds
- [ ] **✅ Expected:** Progress bar fills up
- [ ] **✅ Expected:** First wave of monsters spawns
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 5.3: Riddle Step 1 - Monster Hunt (Wave 1)**
- [ ] Look at monsters and use headset to aim
- [ ] **✅ Expected:** Monsters spawn and move around
- [ ] **✅ Expected:** Auto-aim helps target monsters
- [ ] **✅ Expected:** After 5 monsters killed, wave 2 spawns
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 5.4: Riddle Step 2 - Monster Hunt (Wave 2)**
- [ ] Continue hunting second wave
- [ ] **✅ Expected:** 5 more monsters spawn
- [ ] **✅ Expected:** After all killed, portal unlocks
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 5.5: Portal to Level 4**
- [ ] Walk into portal
- [ ] **✅ Expected:** Smooth transition to Level 4
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 6: LEVEL 4 - THE FIRST SHOT (15 minutes)**

#### **Test 6.1: Initial Spawn**
- [ ] Player spawns in Level 4
- [ ] **✅ Expected:** Weapon appears in hand (if weapon system active)
- [ ] **✅ Expected:** Space Invaders-style environment
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 6.2: Riddle Step 0 - Platform Standing**
- [ ] Stand on platform for 10 seconds
- [ ] **✅ Expected:** Progress bar fills up
- [ ] **✅ Expected:** Cheese entities spawn
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 6.3: Riddle Step 1 - Cheese Capturing**
- [ ] Walk to cheese entities and capture them
- [ ] **✅ Expected:** Cheeses spawn and float
- [ ] **✅ Expected:** Capturing works (walk into them or shoot)
- [ ] **✅ Expected:** Progress tracked (e.g., 10/50 captured)
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 6.4: Riddle Step 2 - Portal Unlock**
- [ ] After capturing required cheeses, portal unlocks
- [ ] **✅ Expected:** Portal appears
- [ ] Walk into portal
- [ ] **✅ Expected:** Smooth transition to Level 5
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 7: LEVEL 5 - THE MEMORY (15 minutes)**

#### **Test 7.1: Initial Spawn**
- [ ] Player spawns in Level 5
- [ ] **✅ Expected:** Glyph Memory environment visible
- [ ] **✅ Expected:** Glyphs on walls, monsters in distance
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 7.2: Monster Visibility**
- [ ] Look around for monsters
- [ ] **✅ Expected:** All monsters visible (not invisible)
- [ ] **✅ Expected:** Monsters move around
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 7.3: Glyph Memory Interaction**
- [ ] Walk to glyph walls
- [ ] Look at glyphs (auto-aim with headset)
- [ ] **✅ Expected:** Glyphs light up when looked at
- [ ] **✅ Expected:** Memory game works correctly
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 7.4: Monster Hunting**
- [ ] Hunt monsters (if weapon system active)
- [ ] **✅ Expected:** Monsters can be targeted and shot
- [ ] **✅ Expected:** Sparkling effects appear on hit
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 7.5: Portal to Level 6**
- [ ] Complete riddle and unlock portal
- [ ] Walk into portal
- [ ] **✅ Expected:** Smooth transition to Level 6
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 8: LEVEL 6 - FINAL BOSS (15 minutes)**

#### **Test 8.1: Initial Spawn**
- [ ] Player spawns in Level 6
- [ ] **✅ Expected:** Final boss arena visible
- [ ] **✅ Expected:** Chest spawns in front of player
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 8.2: Chest Interaction**
- [ ] Walk to chest
- [ ] Look at chest (auto-aim with headset)
- [ ] **✅ Expected:** "Press [E] to Open" prompt appears
- [ ] **✅ Expected:** Chest can be opened (if keyboard available)
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 8.3: Chest Collision**
- [ ] Try to walk through chest
- [ ] **✅ Expected:** Player cannot walk through chest
- [ ] **✅ Expected:** Player is pushed away from chest
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 8.4: Final Boss Arena**
- [ ] Explore the arena
- [ ] **✅ Expected:** Environment is visible and explorable
- [ ] **✅ Expected:** No performance issues or crashes
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 9: MENU SYSTEMS (10 minutes)**

#### **Test 9.1: Pause Menu**
- [ ] Press Menu button on controller (or keyboard ESC)
- [ ] **✅ Expected:** Pause menu appears
- [ ] **✅ Expected:** Menu is readable and not too close/far
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 9.2: Options Menu**
- [ ] Open Options menu from Pause menu
- [ ] **✅ Expected:** Options menu appears
- [ ] Navigate through tabs (General, Audio, Controls, Graphics, etc.)
- [ ] **✅ Expected:** All tabs accessible and readable
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 9.3: Graphics Quality Toggle**
- [ ] Open Graphics tab in Options
- [ ] Try changing graphics quality (Auto/Low/Med/High)
- [ ] **✅ Expected:** Graphics quality changes apply
- [ ] **✅ Expected:** FPS remains stable
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

---

### **PHASE 10: COMFORT & PERFORMANCE (10 minutes)**

#### **Test 10.1: Motion Sickness Check**
- [ ] Play for 10 minutes continuously
- [ ] **✅ Check:** Any dizziness or discomfort?
- [ ] **✅ Check:** Is movement speed comfortable?
- [ ] **✅ Check:** Is rotation speed comfortable?
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 10.2: Frame Rate Check**
- [ ] Observe frame rate in different areas
- [ ] **✅ Expected:** Smooth 72fps (Quest 3 target)
- [ ] **✅ Expected:** No stuttering or lag
- [ ] **✅ Expected:** Auto-adjust kicks in if FPS drops
- [ ] **Result:** _______________________
- [ ] **Notes:** _______________________

#### **Test 10.3: Battery Life**
- [ ] Check Quest 3 battery after 1 hour
- [ ] **Result:** Battery used: _____% per hour
- [ ] **Notes:** _______________________

---

## 🐛 **BUG TRACKING TEMPLATE**

### **Bug Report Format:**
For any bugs found, document using this format:

```markdown
#### **BUG #XX: [Short Description]**
- **Severity:** 🔴 Critical / 🟡 High / 🔵 Medium / 🟢 Low
- **Level:** Level X or All Levels
- **Steps to Reproduce:**
  1. Step 1
  2. Step 2
  3. Step 3
- **Expected Behavior:** What should happen
- **Actual Behavior:** What actually happened
- **Screenshots/Video:** (if possible)
- **Workaround:** (if any)
- **Priority:** Immediate / High / Medium / Low
```

---

## 📊 **BUG LOG**

### **Bugs Found During Testing:**

_(Fill in as bugs are discovered)_

---

## ✅ **POST-TEST CHECKLIST**

### **After Testing:**
- [ ] Review all test results
- [ ] Document all bugs found
- [ ] Prioritize bugs by severity
- [ ] Gather tester feedback on comfort
- [ ] Gather tester feedback on controls
- [ ] Create bug fix plan
- [ ] Update QUICK_STATUS.md with test results

---

## 📝 **TESTER FEEDBACK**

### **Overall Impression:**
_______________________

### **Control Comfort (1-10):**
_______________________

### **Graphics Quality (1-10):**
_______________________

### **Fun Factor (1-10):**
_______________________

### **Suggestions:**
_______________________

---

## 🎯 **SUCCESS CRITERIA**

### **Test is Successful If:**
- ✅ Player can complete at least 3 levels without critical bugs
- ✅ Movement and rotation work smoothly
- ✅ No motion sickness reported
- ✅ No crashes or game-breaking bugs
- ✅ Frame rate remains stable (>60fps)
- ✅ All interactions work (riddles, portals, chests)

### **Test is Failed If:**
- ❌ Game crashes or freezes
- ❌ Player cannot move or control camera
- ❌ Severe motion sickness reported
- ❌ Critical bugs prevent level completion
- ❌ Frame rate drops below 30fps consistently

---

## 🚀 **NEXT STEPS**

### **After Successful Test:**
1. Celebrate! 🎉
2. Document all findings
3. Create bug fix priority list
4. Plan next VR test session
5. Consider adding more VR-specific features

### **After Failed Test:**
1. Document all critical bugs
2. Rollback to stable version if needed
3. Create emergency fix plan
4. Retest on local environment first
5. Schedule another test when fixed

---

**End of Meta Quest 3 Test Plan**  
**Good luck with the test! 🥽🎮**
