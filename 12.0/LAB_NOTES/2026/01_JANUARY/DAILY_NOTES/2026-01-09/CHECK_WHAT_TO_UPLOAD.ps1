# CHECK WHAT WILL BE UPLOADED VIA API
# Created: January 9, 2026
# Purpose: Breakdown of files that will be uploaded, excluding already uploaded 3D models

Write-Host "ANALYZING FILES TO UPLOAD VIA API" -ForegroundColor Green
Write-Host "====================================" -ForegroundColor Green
Write-Host ""

$localBaseDir = "public\three.js\public"

Write-Host "Scanning local assets..." -ForegroundColor Cyan
Write-Host ""

# Get all files (excluding archives, PDFs, .url, etc.)
$allFiles = Get-ChildItem -Path $localBaseDir -Recurse -File | Where-Object { 
    $_.FullName -notmatch "\.tar\.gz$" -and 
    $_.FullName -notmatch "\.rar$" -and 
    $_.FullName -notmatch "\.pdf$" -and 
    $_.FullName -notmatch "\.url$" -and
    $_.FullName -notmatch "License\.txt" -and
    $_.FullName -notmatch "Preview\.ogg" -and
    $_.FullName -notmatch "License-free\.txt" -and
    $_.FullName -notmatch "license.*\.txt" -and
    $_.FullName -notmatch "\.zip$"
}

Write-Host "BREAKDOWN BY MAIN DIRECTORY:" -ForegroundColor Yellow
Write-Host ""

# Group by main directory
$byMainDir = $allFiles | Group-Object {
    $relPath = $_.FullName.Replace((Join-Path (Get-Location) $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $parts = $relPath.Split("/")
    if ($parts.Length -gt 0) { $parts[0] } else { "root" }
} | Sort-Object Count -Descending

$totalFiles = 0
$totalSize = 0

foreach ($group in $byMainDir) {
    $dirName = $group.Name
    $fileCount = $group.Count
    $dirSize = ($group.Group | Measure-Object -Property Length -Sum).Sum
    $dirSizeMB = [math]::Round($dirSize / 1MB, 2)
    
    $totalFiles += $fileCount
    $totalSize += $dirSize
    
    Write-Host "  DIRECTORY: $dirName" -ForegroundColor Cyan
    Write-Host "    Files: $fileCount" -ForegroundColor White
    Write-Host "    Size: $dirSizeMB MB" -ForegroundColor White
    
    # If textures, break down subdirectories
    if ($dirName -eq "textures") {
        Write-Host "    Subdirectories:" -ForegroundColor Yellow
        
        $textureSubDirs = $group.Group | Group-Object {
            $relPath = $_.FullName.Replace((Join-Path (Get-Location) $localBaseDir), "").Replace("\", "/").TrimStart("/")
            $parts = $relPath.Split("/")
            if ($parts.Length -gt 1) { "$($parts[0])/$($parts[1])" } else { $parts[0] }
        } | Sort-Object Count -Descending
        
        foreach ($subDir in $textureSubDirs) {
            $subDirSize = ($subDir.Group | Measure-Object -Property Length -Sum).Sum
            $subDirSizeMB = [math]::Round($subDirSize / 1MB, 2)
            $status = if ($subDir.Name -match "3d models") { "(ALREADY UPLOADED - ~1,330 files)" } else { "(NEW - NEEDS UPLOAD)" }
            Write-Host "      - $($subDir.Name): $($subDir.Count) files, $subDirSizeMB MB $status" -ForegroundColor $(if ($subDir.Name -match "3d models") { "Yellow" } else { "Green" })
        }
    }
    Write-Host ""
}

$totalSizeMB = [math]::Round($totalSize / 1MB, 2)

# Calculate what's NEW vs already uploaded
$newFiles = $allFiles | Where-Object {
    $relPath = $_.FullName.Replace((Join-Path (Get-Location) $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $relPath -notmatch "^textures/3d models/"
}
$newFilesCount = $newFiles.Count
$newFilesSize = ($newFiles | Measure-Object -Property Length -Sum).Sum
$newFilesSizeMB = [math]::Round($newFilesSize / 1MB, 2)

$alreadyUploaded = $allFiles | Where-Object {
    $relPath = $_.FullName.Replace((Join-Path (Get-Location) $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $relPath -match "^textures/3d models/"
}
$alreadyUploadedCount = $alreadyUploaded.Count
$alreadyUploadedSize = ($alreadyUploaded | Measure-Object -Property Length -Sum).Sum
$alreadyUploadedSizeMB = [math]::Round($alreadyUploadedSize / 1MB, 2)

Write-Host "====================================" -ForegroundColor Green
Write-Host "SUMMARY:" -ForegroundColor Yellow
Write-Host "  Total files in local directory: $totalFiles files" -ForegroundColor White
Write-Host "  Total size: $totalSizeMB MB" -ForegroundColor White
Write-Host ""
Write-Host "  Already uploaded (3D models): $alreadyUploadedCount files, $alreadyUploadedSizeMB MB" -ForegroundColor Yellow
Write-Host "  NEW - Needs upload: $newFilesCount files, $newFilesSizeMB MB" -ForegroundColor Green
Write-Host ""

# Break down NEW files by directory
Write-Host "NEW FILES BREAKDOWN (WHAT WILL BE UPLOADED):" -ForegroundColor Yellow
Write-Host ""

$newByDir = $newFiles | Group-Object {
    $relPath = $_.FullName.Replace((Join-Path (Get-Location) $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $parts = $relPath.Split("/")
    if ($parts.Length -gt 1) { "$($parts[0])/$($parts[1])" } else { $parts[0] }
} | Sort-Object Count -Descending

foreach ($dir in $newByDir) {
    $dirSize = ($dir.Group | Measure-Object -Property Length -Sum).Sum
    $dirSizeMB = [math]::Round($dirSize / 1MB, 2)
    Write-Host "  $($dir.Name): $($dir.Count) files, $dirSizeMB MB" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "RECOMMENDATION:" -ForegroundColor Green
Write-Host "  Upload script will upload ALL $totalFiles files (including already uploaded 3D models)" -ForegroundColor White
Write-Host "  This is SAFE - files will be overwritten with current versions" -ForegroundColor Yellow
Write-Host ""
Write-Host "  If you want to skip 3D models (already uploaded), you can:" -ForegroundColor Yellow
Write-Host "  1. Modify upload script to exclude 'textures/3d models/' directory" -ForegroundColor Cyan
Write-Host "  2. Or just let it upload everything (overwrites existing files)" -ForegroundColor Cyan
Write-Host ""
