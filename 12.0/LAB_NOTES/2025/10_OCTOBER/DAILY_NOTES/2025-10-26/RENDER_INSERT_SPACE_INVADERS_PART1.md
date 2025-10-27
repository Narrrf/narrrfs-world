# 🚀 RENDER SPACE INVADERS DEFINITIONS - PART 1

**Run these commands on Render to insert Space Invaders achievement definitions:**

```bash
cd /var/www/html/db

# PART 1: Kill-based (4) + Score-based (4) + Skill-based (5) = 13 achievements
cat > insert_space_part1.sql << 'EOF'
INSERT INTO tbl_space_invaders_achievements (user_id, achievement_key, achievement_title, achievement_description, achievement_icon) VALUES
('ACHIEVEMENT_DEFINITIONS', 'firstKill', 'First Blood', 'Destroyed your first 100 invaders!', '🎯'),
('ACHIEVEMENT_DEFINITIONS', 'killStreak8', 'Killing Spree', '25 kills in a row!', '🔥'),
('ACHIEVEMENT_DEFINITIONS', 'killStreak15', 'Rampage', '50 kills in a row!', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'killStreak25', 'Unstoppable', '100 kills in a row!', '💀'),
('ACHIEVEMENT_DEFINITIONS', 'score2500', 'Getting Started', 'Reached 1,000 DSPOINC!', '⭐'),
('ACHIEVEMENT_DEFINITIONS', 'score7500', 'Rising Star', 'Reached 5,000 DSPOINC!', '🌟'),
('ACHIEVEMENT_DEFINITIONS', 'score15000', 'Space Ace', 'Reached 10,000 DSPOINC!', '🚀'),
('ACHIEVEMENT_DEFINITIONS', 'score30000', 'Legend', 'Reached 20,000 DSPOINC - Maximum Score!', '👑'),
('ACHIEVEMENT_DEFINITIONS', 'perfectWave', 'Perfect Wave', 'Cleared 5 waves without taking damage!', '✨'),
('ACHIEVEMENT_DEFINITIONS', 'noHitRun60', 'Untouchable', '5 minutes without taking damage!', '🛡️'),
('ACHIEVEMENT_DEFINITIONS', 'comboMaster8', 'Combo Master', 'Achieved 4x score multiplier!', '💥'),
('ACHIEVEMENT_DEFINITIONS', 'speedDemon20k', 'Speed Demon', 'Reached 5,000 DSPOINC in under 3 minutes!', '⚡'),
('ACHIEVEMENT_DEFINITIONS', 'survivor10min', 'Ultimate Survivor', 'Survived for 20 minutes!', '⏰');
EOF

sqlite3 narrrf_world.sqlite < insert_space_part1.sql
echo "Part 1 complete - 13 achievements inserted"
```

