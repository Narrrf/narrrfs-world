# 🎁 BOT RESTART SCRIPT WITH GIVEAWAY RECOVERY VERIFICATION
# This script ensures the Mad Skulz giveaway gets raffled

Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   GIVEAWAY RECOVERY - BOT RESTART PROTOCOL" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Step 1: Deploy new command
Write-Host "[STEP 1] Deploying /recover-giveaways command..." -ForegroundColor Green
Set-Location "C:\xampp-server\htdocs\narrrfs-world\discord"
node deploy-commands.js
Write-Host ""

# Step 2: Check current giveaway status
Write-Host "[STEP 2] Checking Mad Skulz giveaway status..." -ForegroundColor Green
$DB = "C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite"
Write-Host "Status before restart:" -ForegroundColor Yellow
sqlite3 $DB "SELECT giveaway_id, status, ends_at, (SELECT COUNT(*) FROM tbl_giveaway_winners w WHERE w.giveaway_id = g.giveaway_id) as winners FROM tbl_giveaways g WHERE giveaway_id='giveaway_1760718779741_pd8ef4j8h';"
Write-Host ""

# Step 3: Show participants
Write-Host "[STEP 3] Participants waiting for raffle: 16 members" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Step 4: Ready to start bot
Write-Host "[STEP 4] 🚨 READY TO START BOT 🚨" -ForegroundColor Red
Write-Host ""
Write-Host "INSTRUCTIONS:" -ForegroundColor Yellow
Write-Host "1. The bot will start in a moment" -ForegroundColor White
Write-Host "2. WATCH THE CONSOLE for [GIVEAWAY RECOVERY] messages" -ForegroundColor White
Write-Host "3. You should see:" -ForegroundColor White
Write-Host "   - 🚨 Starting recovery scan..." -ForegroundColor Cyan
Write-Host "   - 🎁 Found 1 giveaway(s) needing recovery" -ForegroundColor Cyan
Write-Host "   - 🎲 Drawing winners NOW..." -ForegroundColor Cyan
Write-Host "   - ✅ SUCCESS: Winners drawn" -ForegroundColor Green
Write-Host ""
Write-Host "4. If you DON'T see these messages, press Ctrl+C and run:" -ForegroundColor Yellow
Write-Host "   /recover-giveaways in Discord (Admin only)" -ForegroundColor Cyan
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

Read-Host "Press ENTER to start the bot"

Write-Host ""
Write-Host "🚀 STARTING BOT NOW..." -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Start bot
node index.js

# After bot stops (Ctrl+C)
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "[VERIFICATION] Checking if raffle completed..." -ForegroundColor Green
Write-Host ""

# Check if winner exists
Write-Host "Checking for winner in database:" -ForegroundColor Yellow
$winner = sqlite3 $DB "SELECT user_id, username FROM tbl_giveaway_winners WHERE giveaway_id='giveaway_1760718779741_pd8ef4j8h';"

if ($winner) {
    Write-Host "✅ SUCCESS! Winner found:" -ForegroundColor Green
    Write-Host $winner -ForegroundColor Cyan
    Write-Host ""
    Write-Host "🎉 RAFFLE COMPLETED SUCCESSFULLY! 🎉" -ForegroundColor Green
} else {
    Write-Host "⚠️ WARNING: No winner found yet!" -ForegroundColor Red
    Write-Host ""
    Write-Host "NEXT STEPS:" -ForegroundColor Yellow
    Write-Host "1. Check if recovery logs appeared in console above" -ForegroundColor White
    Write-Host "2. If not, restart bot and check again" -ForegroundColor White
    Write-Host "3. Or use manual command: /recover-giveaways in Discord" -ForegroundColor White
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan

