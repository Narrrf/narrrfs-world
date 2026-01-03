# 🥽 Meta Quest VR Headset Testing Plan - December 29, 2025

**Status:** 📋 **TESTING PLAN CREATED**  
**Date:** December 29, 2025  
**Purpose:** Comprehensive testing checklist for Meta Quest VR headset with three.js 3D Riddle Game  
**Target Device:** Meta Quest (Quest 2, Quest 3, or Quest Pro)

---

## 🎯 **OVERVIEW**

This plan outlines comprehensive testing procedures for Meta Quest VR headset compatibility with our three.js 3D Riddle Game. The game already has WebXR support implemented, but needs thorough testing on actual Quest hardware.

**Current VR Implementation Status:**
- ✅ WebXR renderer enabled (`renderer.xr.enabled = true`)
- ✅ VR session management (`startVRSession()`, `endVRSession()`)
- ✅ VR input provider (`vr-input-provider.js`)
- ✅ VR button in options menu
- ✅ Floor-level tracking (`local-floor`)
- ✅ Hand tracking support (optional)
- ✅ VR controller input handling

---

## 📋 **PRE-TESTING SETUP CHECKLIST**

### **1. Meta Quest Headset Setup:**
- [ ] **Headset Charged:** Ensure Quest is fully charged (at least 80%)
- [ ] **Quest Link/Developer Mode:** 
  - [ ] Enable Developer Mode in Quest settings
  - [ ] Install Meta Quest Developer Hub (if testing via Link/Air Link)
  - [ ] OR: Use Quest Browser for direct WebXR testing (recommended)
- [ ] **Quest Browser:** 
  - [ ] Ensure Quest Browser is updated to latest version
  - [ ] Verify WebXR support enabled (should be enabled by default)
- [ ] **Network Setup:**
  - [ ] Quest connected to same network as development server
  - [ ] OR: Quest Link/Air Link configured if testing desktop version

### **2. Development Environment Setup:**
- [ ] **Local Server Running:**
  - [ ] Vite dev server running (`npm run dev` or `vite`)
  - [ ] Server accessible on network (not just localhost)
  - [ ] Check local IP address (e.g., `http://192.168.1.100:5173`)
- [ ] **HTTPS/HTTP:**
  - [ ] WebXR requires HTTPS in production
  - [ ] For local testing: Use HTTP on local network (Quest Browser allows this)
  - [ ] OR: Set up local HTTPS certificate (more complex)
- [ ] **Game URL:**
  - [ ] Game accessible via local network IP
  - [ ] Test URL: `http://[YOUR_LOCAL_IP]:5173` (adjust port if needed)

### **3. Code Verification:**
- [ ] **VR Code Status:**
  - [ ] Verify `renderer.xr.enabled = true` in `main.js` (line 1617)
  - [ ] Verify `startVRSession()` function exists (line 1648)
  - [ ] Verify VR button exists in options menu
  - [ ] Verify `vr-input-provider.js` exists and is imported
- [ ] **Console Logging:**
  - [ ] Check browser console for VR availability messages
  - [ ] Look for: `🥽 [VR] WebXR support: ✅ Available` or `❌ Not available`

---

## 🧪 **TESTING PROCEDURES**

### **Phase 1: Connection & Initialization**

#### **1.1 Connect to Game:**
- [ ] **Open Quest Browser:**
  - [ ] Launch Quest Browser from Quest home menu
  - [ ] Navigate to game URL: `http://[YOUR_LOCAL_IP]:5173`
  - [ ] Verify game loads correctly
- [ ] **Initial Load Test:**
  - [ ] Game should load without errors
  - [ ] Check console for VR support detection
  - [ ] Expected: `🥽 [VR] WebXR support: ✅ Available`
- [ ] **Document Results:**
  - [ ] Take screenshot of initial load
  - [ ] Note any console errors or warnings
  - [ ] Record Quest model (Quest 2, Quest 3, Quest Pro)

#### **1.2 VR Button Visibility:**
- [ ] **Access Options Menu:**
  - [ ] Press P or Escape to open pause menu
  - [ ] Click "Options" button
  - [ ] Look for "Enter VR" button in VR section
- [ ] **Button State:**
  - [ ] Button should say "Enter VR" (not "Exit VR")
  - [ ] Button should be visible and clickable
- [ ] **Document Results:**
  - [ ] Screenshot of options menu with VR button
  - [ ] Note button appearance and position

---

### **Phase 2: VR Session Start**

#### **2.1 Start VR Session:**
- [ ] **Click "Enter VR" Button:**
  - [ ] Click VR button in options menu
  - [ ] Quest should prompt for VR permission
  - [ ] Accept VR permission prompt
- [ ] **VR Session Initialization:**
  - [ ] Game should transition to VR mode
  - [ ] Quest viewport should switch to VR rendering
  - [ ] Should see game world in VR
- [ ] **Console Logs:**
  - [ ] Check for: `🥽 [VR] VR session started successfully`
  - [ ] Check for: `✅ [VR] VR session started and registered with PlayerControls`
  - [ ] Check for: `🥽 [VR INPUT] Left controller active` (if controllers connected)
  - [ ] Check for: `🥽 [VR INPUT] Right controller active` (if controllers connected)
- [ ] **Document Results:**
  - [ ] Note time taken to enter VR
  - [ ] Record any errors or warnings
  - [ ] Note visual quality (clarity, frame rate)

#### **2.2 VR Initial State:**
- [ ] **Player Position:**
  - [ ] Verify player is at correct spawn position
  - [ ] Check if player height matches Quest user height
  - [ ] Verify floor level is correct (not floating or underground)
- [ ] **Camera Position:**
  - [ ] Head tracking should work (moving head moves camera)
  - [ ] Camera should be at player eye level
  - [ ] No jitter or stuttering in head movement
- [ ] **Initial View:**
  - [ ] Game world should be visible
  - [ ] Lighting should be correct
  - [ ] No black screens or rendering errors
- [ ] **Document Results:**
  - [ ] Record player starting position
  - [ ] Note any visual glitches
  - [ ] Record frame rate impression (smooth/stuttery)

---

### **Phase 3: VR Controller Input Testing**

#### **3.1 Controller Detection:**
- [ ] **Controller Connection:**
  - [ ] Verify controllers are paired with Quest
  - [ ] Turn on controllers (they should auto-connect)
  - [ ] Wait for controller detection
- [ ] **Controller Recognition:**
  - [ ] Check console for: `🥽 [VR INPUT] Controller connected: left`
  - [ ] Check console for: `🥽 [VR INPUT] Controller connected: right`
  - [ ] Verify both controllers are detected
- [ ] **Controller Visibility:**
  - [ ] Controllers should appear in VR view (if implemented)
  - [ ] OR: Controllers should be tracked but not visible (current implementation)
- [ ] **Document Results:**
  - [ ] Record controller detection time
  - [ ] Note which controllers were detected
  - [ ] Record any missing controllers

#### **3.2 Movement Input:**
- [ ] **Left Controller Thumbstick:**
  - [ ] Move left thumbstick forward → Player should move forward
  - [ ] Move left thumbstick backward → Player should move backward
  - [ ] Move left thumbstick left → Player should strafe left
  - [ ] Move left thumbstick right → Player should strafe right
  - [ ] Test diagonal movement (forward + left/right)
- [ ] **Movement Sensitivity:**
  - [ ] Movement should feel responsive
  - [ ] No lag between thumbstick input and movement
  - [ ] Movement speed should be appropriate (not too fast/slow)
- [ ] **Document Results:**
  - [ ] Record movement responsiveness
  - [ ] Note any lag or delayed response
  - [ ] Record movement speed impression

#### **3.3 Button Input:**
- [ ] **Trigger Button (Right Controller):**
  - [ ] Press trigger → Should interact/shoot (if in Level 4+)
  - [ ] Verify trigger press is detected
  - [ ] Test rapid trigger presses
- [ ] **Grip Button:**
  - [ ] Press grip → Check if any action triggers
  - [ ] Verify grip is detected (may not have action assigned)
- [ ] **Thumbstick Press:**
  - [ ] Press thumbstick → Check if any action triggers
  - [ ] Verify press is detected
- [ ] **X/Y Buttons (Left Controller):**
  - [ ] Press X button → Check if any action triggers
  - [ ] Press Y button → Check if any action triggers
- [ ] **A/B Buttons (Right Controller):**
  - [ ] Press A button → Check if any action triggers
  - [ ] Press B button → Check if any action triggers (might exit VR)
- [ ] **Document Results:**
  - [ ] Create button mapping chart
  - [ ] Record which buttons work and which don't
  - [ ] Note any missing button functionality

#### **3.4 Rotation & Head Tracking:**
- [ ] **Head Rotation:**
  - [ ] Turn head left/right → Camera should rotate smoothly
  - [ ] Look up/down → Camera should pitch smoothly
  - [ ] Tilt head → Camera should roll (if supported)
  - [ ] No jitter or stuttering in head movement
- [ ] **Controller Rotation:**
  - [ ] Move controllers → Check if controller rotation is tracked
  - [ ] Controller positions should update in real-time
- [ ] **Document Results:**
  - [ ] Record head tracking smoothness
  - [ ] Note any tracking drift
  - [ ] Record controller tracking quality

---

### **Phase 4: Gameplay Testing**

#### **4.1 Basic Movement:**
- [ ] **Walking:**
  - [ ] Walk forward through game world
  - [ ] Test walking speed (should feel natural)
  - [ ] Test turning while walking
- [ ] **Sprinting:**
  - [ ] Test sprint functionality (if mapped to button)
  - [ ] Sprint speed should be faster than walking
- [ ] **Jumping:**
  - [ ] Test jump functionality (if mapped to button)
  - [ ] Jump height should feel appropriate
  - [ ] Landing should feel natural
- [ ] **Document Results:**
  - [ ] Record gameplay feel (comfortable/uncomfortable)
  - [ ] Note any motion sickness symptoms
  - [ ] Record any performance issues

#### **4.2 Level Testing:**
- [ ] **Level 1 (Cheese Temple):**
  - [ ] Load Level 1 in VR
  - [ ] Test movement around level
  - [ ] Test interaction with objects (chests, bear traps)
  - [ ] Test riddle completion mechanics
- [ ] **Level 2 (The Spawn):**
  - [ ] Load Level 2 in VR
  - [ ] Test platform mechanics
  - [ ] Test lever interactions
- [ ] **Level 3 (The Hunt):**
  - [ ] Load Level 3 in VR
  - [ ] Test monster hunting mechanics
  - [ ] Test movement and combat (if applicable)
- [ ] **Level 4+ (Weapon Levels):**
  - [ ] Load Level 4 in VR
  - [ ] Test weapon shooting mechanics
  - [ ] Test weapon switching
  - [ ] Verify shooting works with VR controllers
- [ ] **Document Results:**
  - [ ] Record which levels work well in VR
  - [ ] Note any level-specific issues
  - [ ] Record any broken mechanics

#### **4.3 Interaction Testing:**
- [ ] **Chest Opening:**
  - [ ] Approach chest in VR
  - [ ] Test "Press E to Open" interaction
  - [ ] Verify chest opens correctly
  - [ ] Verify DSPOINC reward appears
- [ ] **Riddle Interactions:**
  - [ ] Test standing on trigger blocks
  - [ ] Test aiming at targets (if applicable)
  - [ ] Verify riddle completion works
- [ ] **UI Interactions:**
  - [ ] Test pause menu access (if possible)
  - [ ] Test options menu (if accessible)
  - [ ] Verify HUD displays correctly in VR
- [ ] **Document Results:**
  - [ ] Record interaction successes/failures
  - [ ] Note any broken interactions
  - [ ] Record UI visibility issues

---

### **Phase 5: Performance & Quality Testing**

#### **5.1 Frame Rate:**
- [ ] **Frame Rate Check:**
  - [ ] Observe frame rate during gameplay
  - [ ] Should maintain 72 FPS (Quest 2) or 90 FPS (Quest 3)
  - [ ] Check for frame drops or stuttering
- [ ] **Performance Bottlenecks:**
  - [ ] Test in areas with many objects
  - [ ] Test during combat/action sequences
  - [ ] Note any performance degradation
- [ ] **Document Results:**
  - [ ] Record frame rate impressions (smooth/stuttery)
  - [ ] Note specific areas with performance issues
  - [ ] Record Quest model (affects performance)

#### **5.2 Visual Quality:**
- [ ] **Resolution:**
  - [ ] Check visual clarity
  - [ ] Text should be readable
  - [ ] Objects should be clear
- [ ] **Lighting:**
  - [ ] Lighting should look correct
  - [ ] Shadows should render properly
  - [ ] No flickering or artifacts
- [ ] **Rendering:**
  - [ ] No visual glitches or artifacts
  - [ ] Textures should load correctly
  - [ ] Models should render properly
- [ ] **Document Results:**
  - [ ] Record visual quality rating (1-10)
  - [ ] Note any visual glitches
  - [ ] Record any rendering errors

#### **5.3 Audio:**
- [ ] **Audio Playback:**
  - [ ] Background music should play
  - [ ] Sound effects should work
  - [ ] Audio should be spatial (3D audio)
- [ ] **Audio Quality:**
  - [ ] Audio should be clear
  - [ ] No distortion or crackling
  - [ ] Volume should be appropriate
- [ ] **Document Results:**
  - [ ] Record audio quality rating
  - [ ] Note any audio issues
  - [ ] Record any missing audio

---

### **Phase 6: Exit VR & Stability**

#### **6.1 Exit VR:**
- [ ] **Exit Methods:**
  - [ ] Test "Exit VR" button in options menu
  - [ ] Test B button (if mapped to exit)
  - [ ] Test Oculus button (Quest system button)
- [ ] **Exit Process:**
  - [ ] VR session should end cleanly
  - [ ] Game should return to desktop mode
  - [ ] No crashes or errors
- [ ] **Document Results:**
  - [ ] Record exit method that works
  - [ ] Note any exit issues
  - [ ] Record return to desktop state

#### **6.2 Re-entry Testing:**
- [ ] **Re-enter VR:**
  - [ ] Exit VR and re-enter VR
  - [ ] Verify VR session starts correctly again
  - [ ] Verify game state is preserved
- [ ] **Multiple Sessions:**
  - [ ] Test entering/exiting VR multiple times
  - [ ] Verify stability over multiple sessions
- [ ] **Document Results:**
  - [ ] Record re-entry success rate
  - [ ] Note any issues with multiple sessions
  - [ ] Record any memory leaks

#### **6.3 Long Session Testing:**
- [ ] **Extended Play:**
  - [ ] Play in VR for 15-30 minutes
  - [ ] Monitor for performance degradation
  - [ ] Watch for overheating (Quest device)
- [ ] **Stability:**
  - [ ] Game should remain stable
  - [ ] No crashes or freezes
  - [ ] Performance should remain consistent
- [ ] **Document Results:**
  - [ ] Record session duration
  - [ ] Note any stability issues
  - [ ] Record any performance degradation

---

## 🐛 **KNOWN ISSUES TO WATCH FOR**

### **Common VR Issues:**
- [ ] **Floor Height:** Player floating or underground (floor tracking issue)
- [ ] **Controller Tracking Lost:** Controllers not detected or tracking lost
- [ ] **Motion Sickness:** User experiences nausea (comfort settings needed)
- [ ] **Performance:** Frame rate drops or stuttering
- [ ] **Audio:** Missing or distorted audio
- [ ] **UI Visibility:** UI elements not visible or positioned incorrectly
- [ ] **Interaction:** Buttons or interactions not working
- [ ] **Exit Issues:** Can't exit VR mode properly

### **Quest-Specific Issues:**
- [ ] **Guardian System:** Quest Guardian boundary interfering
- [ ] **Hand Tracking:** Hand tracking conflicting with controllers
- [ ] **Quest Browser:** Browser limitations or WebXR support issues
- [ ] **Network:** Network latency affecting performance

---

## 📊 **TESTING RESULTS TEMPLATE**

### **Test Session Information:**
```
Date: ____________________
Tester: __________________
Quest Model: Quest 2 / Quest 3 / Quest Pro / Other: _______
Quest Firmware Version: ____________________
Quest Browser Version: ____________________
Network: Local / Air Link / Quest Link
Game Version: ____________________
```

### **Phase 1: Connection & Initialization**
- Connection: ✅ Success / ❌ Failed
- VR Button Visible: ✅ Yes / ❌ No
- Initial Load: ✅ Success / ❌ Failed
- Notes: _________________________________

### **Phase 2: VR Session Start**
- VR Session Start: ✅ Success / ❌ Failed
- Time to Enter VR: _______ seconds
- Initial Position: ✅ Correct / ❌ Floating / ❌ Underground
- Head Tracking: ✅ Working / ❌ Not Working
- Notes: _________________________________

### **Phase 3: Controller Input**
- Controllers Detected: ✅ Both / ✅ Left Only / ✅ Right Only / ❌ None
- Movement: ✅ Working / ❌ Not Working
- Buttons: ✅ Working / ❌ Not Working
- Button Mapping: _________________________________
- Notes: _________________________________

### **Phase 4: Gameplay**
- Movement Feel: ✅ Comfortable / ⚠️ Okay / ❌ Uncomfortable
- Motion Sickness: ✅ None / ⚠️ Mild / ❌ Severe
- Levels Tested: _________________________________
- Interactions: ✅ Working / ❌ Not Working
- Notes: _________________________________

### **Phase 5: Performance**
- Frame Rate: ✅ Smooth / ⚠️ Stuttery / ❌ Poor
- Visual Quality: _______ / 10
- Audio Quality: ✅ Good / ⚠️ Okay / ❌ Poor
- Notes: _________________________________

### **Phase 6: Stability**
- Exit VR: ✅ Clean / ❌ Issues
- Re-entry: ✅ Works / ❌ Failed
- Long Session: ✅ Stable / ❌ Crashed
- Notes: _________________________________

---

## 🎯 **PRIORITY TESTING AREAS**

### **Critical (Must Test):**
1. **VR Session Start/Stop** - Core functionality
2. **Controller Movement** - Essential for gameplay
3. **Head Tracking** - Essential for VR experience
4. **Basic Interactions** - Chest opening, riddles
5. **Performance** - Frame rate and stability

### **Important (Should Test):**
1. **Button Mapping** - All controller buttons
2. **Level Compatibility** - All 6 levels
3. **Weapon System** - Level 4+ shooting mechanics
4. **UI Visibility** - Menus and HUD in VR
5. **Audio** - Spatial audio and sound effects

### **Nice to Have (Optional):**
1. **Extended Play** - Long session stability
2. **Multiple Sessions** - Re-entry reliability
3. **Comfort Settings** - Motion sickness prevention
4. **Advanced Interactions** - Complex riddle mechanics

---

## 📝 **DOCUMENTATION REQUIREMENTS**

### **During Testing:**
- [ ] Take screenshots of key moments (VR button, entering VR, gameplay)
- [ ] Record console logs (especially VR-related messages)
- [ ] Note any errors or warnings
- [ ] Record Quest device information (model, firmware version)
- [ ] Document button mappings discovered
- [ ] Record performance impressions

### **After Testing:**
- [ ] Create comprehensive test results document
- [ ] List all issues found (with priority)
- [ ] Document working features
- [ ] Create bug reports for critical issues
- [ ] Update VR implementation documentation
- [ ] Note any code changes needed

---

## 🔧 **QUICK FIXES CHECKLIST**

If issues are found during testing, here are quick fixes to try:

### **If VR Button Not Visible:**
- [ ] Check if `checkVRSupport()` returned true
- [ ] Verify Quest Browser supports WebXR
- [ ] Check console for VR availability messages

### **If VR Session Won't Start:**
- [ ] Check Quest permissions (VR access allowed)
- [ ] Verify HTTPS requirement (if testing production)
- [ ] Check console for session start errors
- [ ] Verify `startVRSession()` function is accessible

### **If Controllers Not Detected:**
- [ ] Ensure controllers are turned on
- [ ] Check controller battery levels
- [ ] Verify controllers are paired with Quest
- [ ] Check console for controller connection messages

### **If Movement Not Working:**
- [ ] Verify thumbstick input is being detected
- [ ] Check `vr-input-provider.js` thumbstick mapping
- [ ] Verify `PlayerControls` is using VR input
- [ ] Check console for movement state logs

### **If Floor Height Wrong:**
- [ ] Check `renderer.xr.setReferenceSpaceType('local-floor')`
- [ ] Verify Quest floor calibration is correct
- [ ] Check if player height needs adjustment
- [ ] Verify spawn position Y coordinate

---

## 🎯 **SUCCESS CRITERIA**

### **Minimum Viable VR Experience:**
- ✅ VR session starts successfully
- ✅ Controllers are detected
- ✅ Movement works with thumbsticks
- ✅ Head tracking works
- ✅ Game world is visible
- ✅ Basic interactions work
- ✅ Performance is acceptable (60+ FPS)

### **Good VR Experience:**
- ✅ All above criteria met
- ✅ Smooth frame rate (72+ FPS)
- ✅ All buttons mapped and working
- ✅ UI is visible and accessible
- ✅ Audio works correctly
- ✅ No motion sickness issues
- ✅ Stable over extended play

### **Excellent VR Experience:**
- ✅ All above criteria met
- ✅ 90 FPS maintained
- ✅ Perfect controller mapping
- ✅ All game features work in VR
- ✅ Comfortable for long sessions
- ✅ No bugs or issues

---

## 📚 **RESOURCES & REFERENCES**

### **Quest-Specific Documentation:**
- Meta Quest Developer Hub: https://developer.oculus.com/
- WebXR Device API: https://www.w3.org/TR/webxr/
- Three.js WebXR: https://threejs.org/docs/#manual/en/introduction/WebXR

### **Code References:**
- VR Implementation: `three.js/main.js` (lines 1616-1720)
- VR Input Provider: `three.js/vr-input-provider.js`
- VR Button: `three.js/main.js` (lines 12961-12996)
- Player Controls: `three.js/player-controls.js` (VR-ready architecture)

### **Related Documentation:**
- 3D Hytopia Technical Doc: `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md`
- Player Controls: `three.js/player-controls.js` (comments)

---

## ✅ **TESTING CHECKLIST SUMMARY**

### **Pre-Testing:**
- [ ] Quest charged and ready
- [ ] Developer mode enabled (if needed)
- [ ] Quest Browser updated
- [ ] Local server running
- [ ] Game accessible via network
- [ ] VR code verified

### **Testing:**
- [ ] Phase 1: Connection & Initialization
- [ ] Phase 2: VR Session Start
- [ ] Phase 3: Controller Input
- [ ] Phase 4: Gameplay
- [ ] Phase 5: Performance
- [ ] Phase 6: Stability

### **Documentation:**
- [ ] Test results recorded
- [ ] Issues documented
- [ ] Screenshots taken
- [ ] Console logs saved
- [ ] Bug reports created (if needed)

---

**Status:** 📋 **READY FOR TESTING**  
**Next Steps:** Execute testing checklist, document results, create bug reports for any issues found

---

## 🔄 **POST-TESTING TASKS**

After completing testing:

1. **Create Test Results Document:**
   - Fill out testing results template
   - Include screenshots and console logs
   - Document all issues found

2. **Prioritize Issues:**
   - Critical: Blocks VR gameplay
   - High: Major functionality broken
   - Medium: Minor issues or inconveniences
   - Low: Nice-to-have improvements

3. **Create Bug Reports:**
   - For each issue found, create detailed bug report
   - Include reproduction steps
   - Include expected vs actual behavior

4. **Update Documentation:**
   - Update VR implementation documentation
   - Document button mappings
   - Document Quest-specific notes

5. **Plan Fixes:**
   - Create todo list for fixes
   - Prioritize based on severity
   - Assign fixes to development tasks

---

**🧀 READY TO TEST META QUEST VR WITH THREE.JS 3D RIDDLE GAME! 🥽**

