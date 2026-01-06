# 🚨 Safe Fix for 3.6GB Commit - Minimal Commands
# This script removes large files from Git tracking WITHOUT checking the commit
# Date: January 4, 2026

Write-Host "🚨 Starting safe cleanup of 3.6GB commit..." -ForegroundColor Yellow
Write-Host ""

# Step 1: Kill any stuck Git processes
Write-Host "Step 1: Checking for stuck Git processes..." -ForegroundColor Cyan
$gitProcesses = Get-Process | Where-Object {$_.ProcessName -like "*git*"} -ErrorAction SilentlyContinue
if ($gitProcesses) {
    Write-Host "   Found $($gitProcesses.Count) Git process(es), killing..." -ForegroundColor Yellow
    $gitProcesses | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
    Write-Host "   ✅ Git processes killed" -ForegroundColor Green
} else {
    Write-Host "   ✅ No stuck Git processes found" -ForegroundColor Green
}

# Step 2: Navigate to repo
Set-Location "C:\xampp-server\htdocs\narrrfs-world"
Write-Host ""
Write-Host "Step 2: Removed large directories from Git tracking..." -ForegroundColor Cyan
Write-Host "   (Files stay on disk, only removed from Git)" -ForegroundColor Gray

# Remove large directories from tracking (files stay on disk)
git rm -r --cached "public/three.js/public/textures/3d models/" 2>&1 | Out-Null
git rm -r --cached "public/three.js/public/sounds/" 2>&1 | Out-Null
git rm -r --cached "public/three.js/public/audio/" 2>&1 | Out-Null
git rm -r --cached "three.js/" 2>&1 | Out-Null

Write-Host "   ✅ Large directories removed from tracking" -ForegroundColor Green

# Step 3: Check status (minimal output)
Write-Host ""
Write-Host "Step 3: Checking Git status..." -ForegroundColor Cyan
$status = git status --short 2>&1
$removedCount = ($status | Select-String "D ").Count
Write-Host "   Found $removedCount file(s) marked for removal" -ForegroundColor Yellow

# Step 4: Commit the removal
Write-Host ""
Write-Host "Step 4: Committing removal..." -ForegroundColor Cyan
git commit -m "Remove large asset files from Git tracking (3.6GB cleanup)" 2>&1 | Out-Null
Write-Host "   ✅ Removal committed" -ForegroundColor Green

# Step 5: Clean up Git garbage (aggressive)
Write-Host ""
Write-Host "Step 5: Cleaning Git garbage (this may take 2-5 minutes)..." -ForegroundColor Cyan
Write-Host "   Running: git gc --prune=now --aggressive" -ForegroundColor Gray
git gc --prune=now --aggressive 2>&1 | Out-Null
Write-Host "   ✅ Git garbage cleaned" -ForegroundColor Green

# Step 6: Check repository size (minimal)
Write-Host ""
Write-Host "Step 6: Checking repository size..." -ForegroundColor Cyan
$repoSize = git count-objects -vH 2>&1 | Select-String "size-pack"
Write-Host "   $repoSize" -ForegroundColor Yellow

Write-Host ""
Write-Host "✅ Cleanup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "   1. Verify: git status (should be clean)" -ForegroundColor White
Write-Host "   2. Push: git push origin render-deploy" -ForegroundColor White
Write-Host "   3. Large files are now ignored (check .gitignore)" -ForegroundColor White
Write-Host ""
Write-Host "Note: Large files are still in Git history but won't be in future commits." -ForegroundColor Gray
Write-Host "      For complete history cleanup, use BFG Repo-Cleaner (advanced)." -ForegroundColor Gray

