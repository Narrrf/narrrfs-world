// 🧪 Boss Test Helper - Temporary testing utility
// This file helps test all 4 boss types quickly

console.log('🧪 Boss Test Helper Loaded!');

// Quick wave advancement for testing
window.advanceToBoss = function(targetWave) {
  if (typeof waveNumber !== 'undefined') {
    console.log(`🧪 ADVANCING TO WAVE ${targetWave} for boss testing...`);
    
    // Set wave number to trigger boss spawn
    waveNumber = targetWave - 1;
    
    // Force next wave to be boss wave
    if (typeof spawnNewWave === 'function') {
      spawnNewWave();
    }
    
    console.log(`🧪 Ready to test boss at wave ${targetWave}!`);
  } else {
    console.log('❌ Game not initialized yet. Start the game first.');
  }
};

// Test all 4 bosses in sequence
window.testAllBosses = function() {
  console.log('🧪 TESTING ALL 4 BOSSES IN SEQUENCE:');
  console.log('🧪 Wave 5: Cheese King');
  console.log('🧪 Wave 10: Cheese Emperor'); 
  console.log('🧪 Wave 15: Cheese God');
  console.log('🧪 Wave 20: Cheese Destroyer');
  
  if (typeof waveNumber !== 'undefined') {
    waveNumber = 4; // Start at wave 4, next wave (5) will be boss
    console.log('🧪 Set to wave 4 - next wave will spawn Cheese King boss!');
  }
};

// Reset to normal boss spawning (every 50 waves)
window.resetBossSpawning = function() {
  console.log('🧪 RESETTING BOSS SPAWNING TO NORMAL (every 50 waves)');
  
  // This will need to be manually changed back in the main file:
  // Change "if (waveNumber % 5 === 0)" back to "if (waveNumber % 50 === 0)"
  // And reset boss type thresholds back to original values
  
  console.log('🧪 MANUAL RESET REQUIRED:');
  console.log('🧪 1. Change "waveNumber % 5" back to "waveNumber % 50"');
  console.log('🧪 2. Reset boss type thresholds: 200, 150, 100');
  console.log('🧪 3. Remove testing comments');
};

// Display current testing status
window.showBossTestStatus = function() {
  console.log('🧪 BOSS TESTING STATUS:');
  console.log('🧪 Current wave:', typeof waveNumber !== 'undefined' ? waveNumber : 'Not started');
  console.log('🧪 Boss spawning: Every 5 waves (TESTING MODE)');
  console.log('🧪 Next boss wave:', typeof waveNumber !== 'undefined' ? Math.ceil((waveNumber + 1) / 5) * 5 : 'N/A');
  
  if (typeof boss !== 'undefined' && boss) {
    console.log('🧪 Current boss:', boss.name, 'Type:', boss.type);
  } else {
    console.log('🧪 No boss currently active');
  }
};

// Add to window for easy access
window.bossTestHelper = {
  advanceToBoss,
  testAllBosses,
  resetBossSpawning,
  showBossTestStatus
};

console.log('🧪 Available commands:');
console.log('🧪 advanceToBoss(waveNumber) - Jump to specific boss wave');
console.log('🧪 testAllBosses() - Start testing sequence');
console.log('🧪 resetBossSpawning() - Show reset instructions');
console.log('🧪 showBossTestStatus() - Show current status');
