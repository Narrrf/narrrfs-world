# 🎨 Level 3 Theming Overview & Options

**Date:** February 5, 2026  
**Status:** 📋 **REFERENCE – Ready for Theming Work**  
**Context:** Wall collision complete; ready for visual theming

---

## 🎯 Current Level 3 State

### Arena
- **Size:** 160×160 units (massive hunting ground)
- **Origin:** `level3Config.origin` (typically x:0, y:0, z:0)
- **Spawn:** Center of arena (x: 0, y: 1, z: 750)

### Floor
- **Material:** `MeshBasicMaterial` – solid color, no texture
- **Color:** `0xaaaaaa` (light gray stone)
- **Purpose:** Zero flickering (no texture animation)
- **Location:** `buildLevel3HuntArena()` lines ~19636–19684

### Moving Walls
- **Model:** `textures/3d models/tetris/block_I.glb` (Tetris block)
- **Material:** Original GLB materials (white/light gray)
- **Effect:** UV wobble animation (water-like distortion)
- **Count:** 4 walls (2 on X-axis, 2 on Z-axis)
- **Location:** `createLevel3MovingWalls()` lines ~19806–19878

### Lighting
- **Ambient:** `0xffffff` @ 0.35
- **Directional:** `0xfff2d0` @ 1.4 (warm tint)
- **Rim Light:** `0xff7b39` @ 0.65 (warm orange, back-left)
- **Back Light:** `0x57c1ff` @ 0.55 (cool blue, front-right)
- **Location:** `buildLevel3HuntArena()` lines ~19746–19776

### Sky System
- **Per-level:** Configurable via God Mode
- **Default:** Day, Cloud Density 0.7, Star Count 1500, Lensflare enabled
- **Apply:** `skySystem.applyLevelEnvironment(LEVEL_IDS.LEVEL3)`

### Grass System
- **Per-level:** Configurable via God Mode
- **Apply:** `grassSystem.applyLevelEnvironment(LEVEL_IDS.LEVEL3)`

### Corner Bosses (Feb 5, 2026)
- **Models:** Cheese Destroyer, Cheese Emperor, Cheese God Cake, Cheese King (same as Level 4)
- **Position:** 4 corners, 70 units from origin, scale 8.0, Y offset 7.0
- **Features:** Idle bounce, mythical speaks (toaster), collision
- **Notes:** See `LEVEL_3_CORNER_BOSSES_2026-02-05.md`

---

## 🎨 Theming Options

### 1. **Floor Theming**
| Option | Description | Implementation |
|--------|-------------|----------------|
| **Cheese Stone Texture** | Repeating stone pattern (like Level 1) | Load texture, apply to floor material; may need `MeshStandardMaterial` + polygon offset |
| **Color Change** | Warmer/cooler floor tone | Change `0xaaaaaa` to e.g. `0xbb9966` (warm stone) or `0x8b7355` (brown) |
| **Pattern Tiles** | Subtle grid or tile pattern | Canvas texture or procedural pattern |
| **Emissive Glow** | Subtle glow lines (arena feel) | Add emissive to material |

### 2. **Wall Theming**
| Option | Description | Implementation |
|--------|-------------|----------------|
| **Unified Color** | Single theme (e.g. dark stone) | Override materials in `createLevel3MovingWalls()` with `MeshStandardMaterial` |
| **Theme Colors** | Hunt/arena palette (e.g. bronze, stone) | Per-wall or per-axis color override |
| **Different Model** | Replace Tetris block with custom slab | New GLB path, same scaling logic |
| **Emissive Edges** | Glow on wall edges | Add emissive to material |
| **Texture Override** | Stone/brick texture on walls | Load texture, apply to wall materials |

### 3. **Lighting Theming**
| Option | Description | Implementation |
|--------|-------------|----------------|
| **Dusk Hunt** | Warmer, lower sun (hunt at twilight) | Adjust directional color + position; set sky to dusk |
| **Arena Spotlights** | Dramatic contrast | Add point lights, reduce ambient |
| **Cooler/Warmer** | Shift overall temperature | Adjust `0xfff2d0` (warm) vs `0xe8f4ff` (cool) |
| **Fog/Atmosphere** | Scene fog for depth | `scene.fog = new THREE.Fog(0x87ceeb, 50, 300)` |

### 4. **Sky Theming**
| Option | Description | Implementation |
|--------|-------------|----------------|
| **Fixed Dusk** | Hunt at sunset | `skySystem.setTime(18, 0)` on Level 3 load |
| **Fixed Dawn** | Hunt at sunrise | `skySystem.setTime(6, 0)` |
| **Overcast** | Cloudy hunt | `skySystem.setCloudDensity(0.9)` |
| **Night Hunt** | Moonlit arena | `skySystem.setTime(0, 0)` |

### 5. **Atmosphere**
| Option | Description | Implementation |
|--------|-------------|----------------|
| **Scene Fog** | Distance fog for depth | Add `scene.fog` when entering Level 3 |
| **Particle Effects** | Dust, leaves, embers | Particle system (new module or simple) |
| **Ground Type** | Grass vs bare ground | `grassSystem.applyLevelEnvironment()` – set `groundType` |

---

## 📁 Key Code Locations

| Element | File | Function / Area |
|---------|------|-----------------|
| Floor | `main.js` | `buildLevel3HuntArena()` ~19636 |
| Walls | `main.js` | `createLevel3MovingWalls()` ~19806 |
| Lighting | `main.js` | `buildLevel3HuntArena()` ~19746 |
| Sky | `sky-system.js` | `applyLevelEnvironment()` |
| Grass | `grass-system.js` | `applyLevelEnvironment()` |
| Level 3 config | `main.js` | `level3Config`, `LEVEL3_MOVING_WALLS` |
| Corner bosses | `main.js` | `createLevel3CornerBosses()` ~19914, `checkLevel3CornerBossMythicalSpeaks()` ~20039 |

---

## 🧀 Suggested Theme: "Twilight Hunt Arena"

**Concept:** Hunt feels like dusk – warm stone floor, bronze-tinted walls, low golden sun, subtle fog.

1. **Floor:** Warm stone `0xbb9966` or add subtle cheese-stone texture
2. **Walls:** Dark bronze `0x4a3728` or stone gray `0x5a5a5a`
3. **Lighting:** Dusk – directional `0xffaa66`, lower position; rim `0xff6633`
4. **Sky:** `setTime(18, 30)` (6:30 PM)
5. **Fog:** `scene.fog = new THREE.Fog(0x87ceeb, 100, 250)` (light blue distance fog)

---

## ✅ Next Steps

1. Choose a theme direction (e.g. Twilight Hunt, Dawn Arena, Night Hunt)
2. Implement floor color/texture change
3. Implement wall material override (if desired)
4. Adjust lighting colors and positions
5. Set sky time of day for Level 3
6. Optionally add fog or particles

---

**Status:** 📋 **REFERENCE – Ready for Implementation**
