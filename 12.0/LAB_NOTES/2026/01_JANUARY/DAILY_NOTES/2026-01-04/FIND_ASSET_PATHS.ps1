# 🔍 Find All Asset Paths in Three.js Files
# Purpose: Find all asset paths that need to be updated for production

Write-Host "🔍 Searching for asset paths in three.js files..." -ForegroundColor Cyan

$threeJsPath = "C:\xampp-server\htdocs\narrrfs-world\public\three.js"
$results = @()

# Search for texture paths
Write-Host "`n📁 Searching for texture paths..." -ForegroundColor Yellow
$texturePaths = Get-ChildItem -Path $threeJsPath -Filter "*.js" -Recurse | 
    Select-String -Pattern '"/textures/|"/public/textures/|\./public/textures/' | 
    Select-Object -Property Path, LineNumber, Line

foreach ($match in $texturePaths) {
    $results += [PSCustomObject]@{
        File = $match.Path.Replace($threeJsPath + "\", "")
        Line = $match.LineNumber
        Content = $match.Line.Trim()
        Type = "Texture"
    }
}

# Search for sound paths
Write-Host "🔊 Searching for sound paths..." -ForegroundColor Yellow
$soundPaths = Get-ChildItem -Path $threeJsPath -Filter "*.js" -Recurse | 
    Select-String -Pattern '"/sounds/|"/public/sounds/|\./public/sounds/' | 
    Select-Object -Property Path, LineNumber, Line

foreach ($match in $soundPaths) {
    $results += [PSCustomObject]@{
        File = $match.Path.Replace($threeJsPath + "\", "")
        Line = $match.LineNumber
        Content = $match.Line.Trim()
        Type = "Sound"
    }
}

# Search for audio paths
Write-Host "🎵 Searching for audio paths..." -ForegroundColor Yellow
$audioPaths = Get-ChildItem -Path $threeJsPath -Filter "*.js" -Recurse | 
    Select-String -Pattern '"/audio/|"/public/audio/|\./public/audio/' | 
    Select-Object -Property Path, LineNumber, Line

foreach ($match in $audioPaths) {
    $results += [PSCustomObject]@{
        File = $match.Path.Replace($threeJsPath + "\", "")
        Line = $match.LineNumber
        Content = $match.Line.Trim()
        Type = "Audio"
    }
}

# Search for model file extensions
Write-Host "🎮 Searching for model paths (.glb, .gltf, .fbx)..." -ForegroundColor Yellow
$modelPaths = Get-ChildItem -Path $threeJsPath -Filter "*.js" -Recurse | 
    Select-String -Pattern '\.glb|\.gltf|\.fbx' | 
    Select-Object -Property Path, LineNumber, Line

foreach ($match in $modelPaths) {
    $results += [PSCustomObject]@{
        File = $match.Path.Replace($threeJsPath + "\", "")
        Line = $match.LineNumber
        Content = $match.Line.Trim()
        Type = "Model"
    }
}

# Group by file
Write-Host "`n📊 Results grouped by file:" -ForegroundColor Green
$grouped = $results | Group-Object -Property File | Sort-Object Name

foreach ($group in $grouped) {
    Write-Host "`n📄 $($group.Name) ($($group.Count) matches)" -ForegroundColor Cyan
    foreach ($item in $group.Group | Select-Object -First 5) {
        Write-Host "  Line $($item.Line): $($item.Content.Substring(0, [Math]::Min(80, $item.Content.Length)))" -ForegroundColor Gray
    }
    if ($group.Count -gt 5) {
        Write-Host "  ... and $($group.Count - 5) more" -ForegroundColor DarkGray
    }
}

# Summary
Write-Host "`n📈 Summary:" -ForegroundColor Green
Write-Host "  Total matches: $($results.Count)" -ForegroundColor White
Write-Host "  Files with paths: $($grouped.Count)" -ForegroundColor White
Write-Host "  Texture paths: $(($results | Where-Object { $_.Type -eq 'Texture' }).Count)" -ForegroundColor White
Write-Host "  Sound paths: $(($results | Where-Object { $_.Type -eq 'Sound' }).Count)" -ForegroundColor White
Write-Host "  Audio paths: $(($results | Where-Object { $_.Type -eq 'Audio' }).Count)" -ForegroundColor White
Write-Host "  Model paths: $(($results | Where-Object { $_.Type -eq 'Model' }).Count)" -ForegroundColor White

# Export to CSV
$csvPath = "C:\xampp-server\htdocs\narrrfs-world\12.0\LAB_NOTES\2026\01_JANUARY\DAILY_NOTES\2026-01-04\ASSET_PATHS_FOUND.csv"
$results | Export-Csv -Path $csvPath -NoTypeInformation
Write-Host "`n💾 Results exported to: $csvPath" -ForegroundColor Green

Write-Host "`n✅ Search complete!" -ForegroundColor Green

