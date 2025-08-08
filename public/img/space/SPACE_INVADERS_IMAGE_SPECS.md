# Space Cheese Invaders - Image Specifications for Social Brain

## Game Context
We're creating a "Space Cheese Invaders" game that follows the classic Space Invaders gameplay but with a cheese theme. The game needs 4 essential PNG images to function properly.

## Required Images

### 1. cheese-ship.png (Player Ship)
**Purpose**: The player's spaceship that moves horizontally at the bottom of the screen
**Specifications**:
- **Size**: 32x32 pixels (small, fits game canvas)
- **Style**: Pixel art, retro arcade style
- **Design**: A small spaceship made of cheese
  - Main body: Triangular or rectangular cheese wedge shape
  - Color: Golden yellow (#FFD700) or orange cheese color
  - Details: Small cheese holes/texture, maybe a small cheese wheel as the cockpit
  - Orientation: Pointing upward (ready to shoot)
  - Background: Transparent PNG
- **Theme**: Should look like a cheese spaceship - could be a cheese wedge with rocket engines

### 2. cheese-invader.png (Enemy Aliens)
**Purpose**: The invading aliens that move across the screen in formation
**Specifications**:
- **Size**: 24x24 pixels (smaller than player ship)
- **Style**: Pixel art, retro arcade style
- **Design**: Alien creature made of cheese
  - Shape: Round or oval cheese blob with alien features
  - Color: Light yellow or white cheese color (#F5DEB3)
  - Details: Large alien eyes, maybe antennae, cheese texture
  - Expression: Menacing or cute alien face
  - Background: Transparent PNG
- **Theme**: Should look like a cheese alien - could be a cheese ball with big eyes

### 3. cheese-bullet.png (Projectiles)
**Purpose**: Bullets fired by both player and invaders
**Specifications**:
- **Size**: 4x8 pixels (small, fast-moving projectile)
- **Style**: Pixel art, simple design
- **Design**: Small cheese projectile
  - Shape: Small rectangle or oval
  - Color: Bright yellow (#FFFF00) or orange (#FFA500)
  - Details: Simple, maybe a small cheese texture
  - Background: Transparent PNG
- **Theme**: Should look like a small cheese bullet or cheese particle

### 4. cheese-explosion.png (Explosion Effect)
**Purpose**: Visual effect when ships are destroyed
**Specifications**:
- **Size**: 32x32 pixels (same size as ships)
- **Style**: Pixel art, explosion effect
- **Design**: Explosion made of cheese particles
  - Shape: Circular or star-shaped explosion
  - Color: Multiple cheese colors (yellow, orange, white)
  - Details: Flying cheese particles, explosion lines
  - Effect: Should look like cheese exploding into pieces
  - Background: Transparent PNG
- **Theme**: Should look like cheese exploding into smaller cheese pieces

## Technical Requirements
- **Format**: PNG with transparent background
- **Color Palette**: Cheese-themed colors (yellows, oranges, whites)
- **Style**: Consistent pixel art style across all images
- **Size**: Exact pixel dimensions as specified above
- **Quality**: High-quality pixel art, not blurry

## File Naming
All images should be saved with these exact names in the `img/space/` directory:
- `cheese-ship.png`
- `cheese-invader.png`
- `cheese-bullet.png`
- `cheese-explosion.png`

## Usage Context
These images will be used in a JavaScript canvas game where:
- The player ship moves horizontally at the bottom
- Multiple invaders move in formation at the top
- Bullets travel vertically between player and invaders
- Explosions appear when ships are hit

The images need to be small and clear enough to be recognizable at the specified pixel dimensions.
