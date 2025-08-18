// 🔄 Reset Boss Spawning - Restore original configuration
// Run this after testing to reset boss spawning back to normal

console.log('🔄 Boss Spawning Reset Script Loaded');

// Function to reset boss spawning back to normal
window.resetBossSpawningToNormal = function() {
  console.log('🔄 RESETTING BOSS SPAWNING TO NORMAL...');
  
  // This function will show you exactly what to change back
  console.log('🔄 MANUAL RESET REQUIRED - Follow these steps:');
  console.log('');
  console.log('🔄 STEP 1: In space-cheese-invaders.js, change line ~1765:');
  console.log('🔄 FROM: if (waveNumber % 5 === 0) {');
  console.log('🔄 TO:   if (waveNumber % 50 === 0) {');
  console.log('');
     console.log('🔄 STEP 2: Reset boss type thresholds around line ~810:');
   console.log('🔄 FROM: if (waveNumber >= 20) {');
   console.log('🔄 TO:   if (waveNumber >= 200) {');
   console.log('');
   console.log('🔄 FROM: } else if (waveNumber >= 15) {');
   console.log('🔄 TO:   } else if (waveNumber >= 150) {');
   console.log('');
   console.log('🔄 FROM: } else if (waveNumber >= 10) {');
   console.log('🔄 TO:   } else if (waveNumber >= 100) {');
   console.log('');
   console.log('🔄 STEP 2.5: Reset boss HP back to normal around line ~810:');
   console.log('🔄 FROM: bossMaxHealth = 2000 + (bossLevel * 1000); // 3000, 4000, 5000, 6000');
   console.log('🔄 TO:   bossMaxHealth = 200 + (bossLevel * 100); // 300, 400, 500, 600');
  console.log('');
  console.log('🔄 STEP 3: Remove testing comments from top of file');
  console.log('🔄 STEP 4: Remove this reset script from HTML');
  console.log('');
  console.log('🔄 After making these changes, bosses will spawn every 50 waves as intended.');
};

// Function to show current testing configuration
window.showCurrentTestingConfig = function() {
     console.log('🧪 CURRENT TESTING CONFIGURATION:');
   console.log('🧪 Boss spawning: Every 5 waves (TESTING MODE)');
   console.log('🧪 Boss types:');
   console.log('🧪   Wave 5: Cheese King (3,000 HP)');
   console.log('🧪   Wave 10: Cheese Emperor (4,000 HP)');
   console.log('🧪   Wave 15: Cheese God (5,000 HP)');
   console.log('🧪   Wave 20: Cheese Destroyer (6,000 HP)');
  console.log('');
  console.log('🧪 NORMAL CONFIGURATION (after reset):');
  console.log('🧪 Boss spawning: Every 50 waves');
  console.log('🧪 Boss types:');
  console.log('🧪   Wave 50: Cheese King');
  console.log('🧪   Wave 100: Cheese Emperor');
  console.log('🧪   Wave 150: Cheese God');
  console.log('🧪   Wave 200: Cheese Destroyer');
};

// Add to window for easy access
window.bossResetHelper = {
  resetBossSpawningToNormal,
  showCurrentTestingConfig
};

console.log('🔄 Available commands:');
console.log('🔄 resetBossSpawningToNormal() - Show reset instructions');
console.log('🔄 showCurrentTestingConfig() - Show current vs normal config');
