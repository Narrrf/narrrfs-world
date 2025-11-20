# Space Invaders HUD Refactoring Plan

## Overview
Move all canvas-drawn HUD elements to HTML overlays, matching Snake and Tetris styling, ensuring mobile and desktop visibility with maximum 3 lines for main HUD.

## Current State Analysis

### ✅ Already in HTML (Partially Implemented)
1. **Main HUD Container** (`#space-invaders-hud-container`)
   - Line 1: Health, Ammo, Speed Boost ✅
   - Line 2: Wave/Phase, Auto-Shoot, Weapon Type ✅
   - Line 3: Multi-Shot, Special Ammo, Mouse Control, Game Speed ✅

2. **Boss HUD** (`#space-invaders-boss-hud-container`) ✅
   - Boss name, HP bar, HP text

3. **Phoenix HUD** (`#space-invaders-phoenix-hud-container`) ✅
   - Phoenix wave indicator

### ❌ Still on Canvas (Need to Move)
1. **drawHealth()** - Health, ammo, speed boost timer, weapon ready indicator
   - **Status**: Partially moved, but still drawing on canvas
   - **Action**: Remove canvas drawing, update HTML elements only

2. **drawPhaseInfo()** - Wave number, phase info, auto-shoot status, weapon type, mouse control, multi-shot upgrades, special weapon ammo
   - **Status**: Partially moved, but still drawing on canvas
   - **Action**: Remove canvas drawing, update HTML elements only

3. **drawBoss()** - Boss health bar, boss name, wave number
   - **Status**: HTML structure exists, but canvas drawing still active
   - **Action**: Remove canvas drawing, update HTML elements only

4. **drawPhoenixWaveInfo()** - Phoenix wave indicator
   - **Status**: HTML structure exists, but canvas drawing still active
   - **Action**: Remove canvas drawing, update HTML elements only

5. **drawScorePopups()** - Animated score popups (e.g., "+10", "+50")
   - **Status**: Currently drawn on canvas
   - **Action**: Create HTML-based popup system (similar to Snake/Tetris)

6. **drawAchievementPopups()** - Achievement unlock notifications
   - **Status**: Currently drawn on canvas
   - **Action**: Create HTML-based popup system (similar to Snake/Tetris)

7. **drawNotification()** - General notifications (including Phoenix wave announcements)
   - **Status**: Currently drawn on canvas
   - **Action**: Move to HTML overlay (temporary notification system)

8. **drawCountdown()** - Game start countdown (3, 2, 1, GO!)
   - **Status**: HTML element exists (`#space-invaders-countdown`), but canvas drawing may still be active
   - **Action**: Ensure HTML-only implementation

9. **drawGameOverScreen()** - Game over screen
   - **Status**: Modal exists, but canvas drawing may still be active
   - **Action**: Ensure HTML-only implementation

10. **drawVictoryScreen()** - Victory screen
    - **Status**: Modal exists, but canvas drawing may still be active
    - **Action**: Ensure HTML-only implementation

11. **drawHeatBar()** - Weapon heat bar
    - **Status**: Need to verify if this exists
    - **Action**: If exists, move to HTML (add to Line 3 or separate element)

12. **drawExplosionDangerZones()** - Danger zone warnings
    - **Status**: Visual effect on canvas (may stay on canvas)
    - **Action**: Keep on canvas (visual effect, not HUD)

13. **drawPlayerDamageEffect()** - Player damage flash
    - **Status**: Visual effect on canvas (may stay on canvas)
    - **Action**: Keep on canvas (visual effect, not HUD)

## Implementation Plan

### Phase 1: Complete Main HUD Migration
**Goal**: Remove all canvas drawing from `drawHealth()` and `drawPhaseInfo()`, update HTML elements only.

**Changes in `public/scripts/space-cheese-invaders.js`**:
1. Create `updateSpaceInvadersMainHUD()` function:
   - Update `#space-invaders-health` with health value
   - Update `#space-invaders-ammo` with bomb/laser/speed boost ammo
   - Update `#space-invaders-speed-boost` with timer (show/hide based on active state)
   - Update `#space-invaders-weapon-ready` with weapon ready indicator (show/hide)
   - Update `#space-invaders-wave-phase` with wave/phase info
   - Update `#space-invaders-auto-shoot` with auto-shoot status
   - Update `#space-invaders-weapon-type` with weapon type
   - Update `#space-invaders-multi-shot` with multi-shot upgrade status (show/hide)
   - Update `#space-invaders-special-ammo` with special weapon ammo (show/hide)
   - Update `#space-invaders-mouse-control` with mouse control status (show/hide)
   - Update `#space-invaders-game-speed` with game speed multiplier (show/hide)

2. Remove canvas drawing code from `drawHealth()`:
   - Remove `ctx.fillText()` calls for health, ammo, speed boost, weapon ready

3. Remove canvas drawing code from `drawPhaseInfo()`:
   - Remove `ctx.fillText()` calls for phase info, auto-shoot, weapon type, mouse control, multi-shot, special ammo

4. Call `updateSpaceInvadersMainHUD()` in `gameLoop()` or `draw()` function

### Phase 2: Complete Boss & Phoenix HUD Migration
**Goal**: Remove canvas drawing from `drawBoss()` and `drawPhoenixWaveInfo()`, update HTML elements only.

**Changes in `public/scripts/space-cheese-invaders.js`**:
1. Create `updateSpaceInvadersBossHUD()` function:
   - Show `#space-invaders-boss-hud-container` when boss is active
   - Update `#space-invaders-boss-name` with boss name and wave number
   - Update `#space-invaders-boss-hp-fill` width based on boss health percentage
   - Update `#space-invaders-boss-hp-text` with current/max HP
   - Hide `#space-invaders-boss-hud-container` when boss is defeated

2. Create `updateSpaceInvadersPhoenixHUD()` function:
   - Show `#space-invaders-phoenix-hud-container` when Phoenix wave is active
   - Update `#space-invaders-phoenix-text` with Phoenix wave info
   - Hide `#space-invaders-phoenix-hud-container` when Phoenix wave ends

3. Remove canvas drawing code from `drawBoss()`:
   - Remove boss health bar drawing
   - Remove boss name/wave text drawing
   - Keep boss sprite/visual rendering on canvas

4. Remove canvas drawing code from `drawPhoenixWaveInfo()`:
   - Remove Phoenix wave text drawing
   - Keep Phoenix entity rendering on canvas

5. Call `updateSpaceInvadersBossHUD()` and `updateSpaceInvadersPhoenixHUD()` in `gameLoop()`

### Phase 3: Score Popups System
**Goal**: Create HTML-based score popup system (similar to Snake/Tetris).

**Changes in `public/space-cheese-invaders.html`**:
1. Add score popups container:
```html
<!-- Score Popups Container (positioned absolutely over canvas) -->
<div id="space-invaders-score-popups-container" class="absolute pointer-events-none z-50" style="top: 0; left: 0; right: 0; bottom: 0;"></div>
```

**Changes in `public/scripts/space-cheese-invaders.js`**:
1. Create `showScorePopupHTML(x, y, score, color)` function:
   - Create a new `<div>` element for the popup
   - Position it at canvas coordinates (x, y)
   - Animate it upward with fade-out
   - Remove after animation completes
   - Style: `bg-black/85`, `border-2 border-yellow-400`, `rounded-lg`, `p-1`, `text-white`, `font-bold`, `text-sm`

2. Modify `drawScorePopups()`:
   - Instead of drawing on canvas, call `showScorePopupHTML()` for each popup
   - Keep popup data structure (array of popup objects)

3. Update popup creation logic:
   - When score is added, create HTML popup instead of canvas popup
   - Use canvas coordinates to position HTML popup

### Phase 4: Achievement Popups System
**Goal**: Create HTML-based achievement popup system (similar to Snake/Tetris).

**Changes in `public/space-cheese-invaders.html`**:
1. Add achievement popups container:
```html
<!-- Achievement Popups Container (centered, above canvas) -->
<div id="space-invaders-achievement-popups-container" class="absolute pointer-events-none z-50 w-full flex justify-center" style="top: 20%;"></div>
```

**Changes in `public/scripts/space-cheese-invaders.js`**:
1. Create `showAchievementPopupHTML(achievement)` function:
   - Create a new `<div>` element for the achievement
   - Center it horizontally
   - Animate it with scale and fade
   - Remove after animation completes
   - Style: `bg-black/85`, `border-2 border-yellow-400`, `rounded-lg`, `p-3`, `text-white`, `font-bold`, matching Snake/Tetris achievement styling

2. Modify `drawAchievementPopups()`:
   - Instead of drawing on canvas, call `showAchievementPopupHTML()` for each achievement
   - Keep achievement data structure (array of achievement objects)

3. Update achievement creation logic:
   - When achievement is unlocked, create HTML popup instead of canvas popup

### Phase 5: Notification System
**Goal**: Move general notifications (including Phoenix wave announcements) to HTML.

**Changes in `public/space-cheese-invaders.html`**:
1. Add notification container:
```html
<!-- Notification Container (centered, above canvas) -->
<div id="space-invaders-notification-container" class="absolute pointer-events-none z-50 w-full flex justify-center hidden" style="top: 10%;"></div>
```

**Changes in `public/scripts/space-cheese-invaders.js`**:
1. Create `showNotificationHTML(message, duration, color)` function:
   - Show notification container
   - Update message text
   - Apply color styling
   - Hide after duration
   - Style: `bg-black/85`, `border-2 border-yellow-400`, `rounded-lg`, `p-2`, `text-white`, `font-bold`, `text-sm`

2. Modify `drawNotification()`:
   - Instead of drawing on canvas, call `showNotificationHTML()`
   - Remove canvas drawing code

### Phase 6: Countdown, Game Over, Victory Screens
**Goal**: Ensure countdown, game over, and victory screens are HTML-only.

**Changes in `public/scripts/space-cheese-invaders.js`**:
1. Verify `drawCountdown()`:
   - Ensure it only updates `#space-invaders-countdown` HTML element
   - Remove any canvas drawing code

2. Verify `drawGameOverScreen()`:
   - Ensure it only shows game over modal
   - Remove any canvas drawing code

3. Verify `drawVictoryScreen()`:
   - Ensure it only shows victory modal
   - Remove any canvas drawing code

### Phase 7: Heat Bar (If Exists)
**Goal**: Move heat bar to HTML if it exists.

**Changes in `public/space-cheese-invaders.html`**:
1. Add heat bar to Line 3 or separate element:
```html
<span id="space-invaders-heat-bar" class="font-bold hidden">
  <span id="space-invaders-heat-text">HEAT: </span>
  <span id="space-invaders-heat-fill" class="inline-block h-2 bg-red-500 rounded" style="width: 0%;"></span>
</span>
```

**Changes in `public/scripts/space-cheese-invaders.js`**:
1. Create `updateSpaceInvadersHeatBar()` function:
   - Update heat percentage
   - Update heat fill width
   - Show/hide based on heat level
   - Apply color based on heat level (green → yellow → red)

2. Remove canvas drawing code from `drawHeatBar()` (if exists)

## Styling Guidelines

### Matching Snake/Tetris Style
- **Container**: `bg-black/85`, `border-2 border-yellow-400`, `rounded-lg`, `p-2`, `shadow-lg`
- **Text**: `text-white`, `font-bold`, `text-sm` (mobile: `text-xs`)
- **Colors**:
  - Health: `text-red-400`
  - Ammo: `text-white`
  - Speed Boost: `text-green-400`
  - Wave/Phase: Dynamic colors (green for formation, red for attack, orange for Phoenix)
  - Auto-Shoot: `text-green-400` (ON) / `text-red-400` (OFF)
  - Weapon Type: `text-cyan-300`
  - Boss HP: Dynamic based on boss color
  - Phoenix: `text-orange-400`

### Responsive Design
- **Desktop**: Full text, all elements visible
- **Mobile**: Compact text (`text-xs`), hide less critical elements if needed
- **Maximum 3 Lines**: Main HUD should never exceed 3 lines
- **Smart Hiding**: Hide elements when not relevant (e.g., hide speed boost when inactive, hide multi-shot when not unlocked)

## Code Preservation
- **Keep all game logic**: No changes to game mechanics, scoring, or entity management
- **Keep all data structures**: Popup arrays, achievement arrays, etc.
- **Only change rendering**: Move from canvas drawing to HTML updates
- **Maintain functionality**: All features must work exactly as before

## Testing Checklist
- [ ] Main HUD displays correctly on desktop
- [ ] Main HUD displays correctly on mobile
- [ ] Boss HUD shows/hides correctly
- [ ] Phoenix HUD shows/hides correctly
- [ ] Score popups appear and animate correctly
- [ ] Achievement popups appear and animate correctly
- [ ] Notifications appear and disappear correctly
- [ ] Countdown works correctly
- [ ] Game over screen works correctly
- [ ] Victory screen works correctly
- [ ] Heat bar works correctly (if exists)
- [ ] All game mechanics unchanged
- [ ] No performance degradation
- [ ] No visual glitches

## Questions for User
1. **Heat Bar**: Does a heat bar currently exist? If so, where should it be displayed?
2. **Score Popups Position**: Should score popups appear at the kill location (on canvas) or in a fixed position (e.g., top-right)?
3. **Achievement Popups Position**: Should achievement popups appear centered (like Snake/Tetris) or at a specific location?
4. **Notification Duration**: What should be the default duration for notifications?
5. **Mobile Optimization**: Should any elements be hidden on mobile to reduce clutter?

