# 🔍 CHECK NEW 3D MODELS - FILTER BY DATE
# Created: January 14, 2026
# Purpose: Check which 3D model files are new (modified in last X days)
# This helps identify files that need to be uploaded to Render

param(
    [Parameter(Mandatory=$false)]
    [int]$DaysAgo = 1,
    
    [Parameter(Mandatory=$false)]
    [string]$ExportCSV = ""
)

Write-Host "[CHECK] CHECKING NEW 3D MODELS (Modified in last $DaysAgo day(s))" -ForegroundColor Green
Write-Host "===================================================" -ForegroundColor Green
Write-Host ""

$projectRoot = Get-Location
$localBaseDir = "public\three.js\public\textures\3d models"

if (-not (Test-Path $localBaseDir)) {
    Write-Host "ERROR: Local directory not found: $localBaseDir" -ForegroundColor Red
    exit 1
}

Write-Host "Scanning directory: $localBaseDir" -ForegroundColor Cyan
Write-Host "Looking for files modified in last $DaysAgo day(s)..." -ForegroundColor Cyan
Write-Host ""

# Calculate cutoff date
$cutoffDate = (Get-Date).AddDays(-$DaysAgo)

# Get all files modified after cutoff date
$newFiles = Get-ChildItem -Path $localBaseDir -Recurse -File | Where-Object { 
    $_.LastWriteTime -ge $cutoffDate -and
    $_.FullName -notmatch "\.tar\.gz$" -and 
    $_.FullName -notmatch "\.rar$" -and 
    $_.FullName -notmatch "\.pdf$" -and 
    $_.FullName -notmatch "\.url$" -and
    $_.FullName -notmatch "License\.txt" -and
    $_.FullName -notmatch "Preview\.ogg" -and
    $_.FullName -notmatch "\.zip$" -and
    $_.FullName -notmatch "\.psd$" -and
    $_.FullName -notmatch "\.ma$" -and
    $_.FullName -notmatch "\.unitypackage$" -and
    $_.FullName -notmatch "\.uproject$" -and
    $_.FullName -notmatch "\.uasset$" -and
    $_.FullName -notmatch "\.umap$"
}

$totalFiles = $newFiles.Count
Write-Host "Found $totalFiles new files" -ForegroundColor Green
Write-Host ""

if ($totalFiles -eq 0) {
    Write-Host "No new files found in last $DaysAgo day(s)" -ForegroundColor Yellow
    Write-Host 'Try increasing -DaysAgo parameter (e.g., use -DaysAgo 7)' -ForegroundColor Yellow
    exit 0
}

# Calculate total size
$totalSizeMB = [math]::Round(($newFiles | Measure-Object -Property Length -Sum).Sum / 1MB, 2)
Write-Host "Total size: $totalSizeMB MB" -ForegroundColor Cyan
Write-Host ""

# Group by folder
$filesByFolder = $newFiles | Group-Object { 
    $relativePath = $_.FullName.Replace((Join-Path $projectRoot $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $folderName = ($relativePath -split "/")[0]
    $folderName
} | Sort-Object Name

Write-Host "Breakdown by folder:" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan
Write-Host ""

foreach ($folderGroup in $filesByFolder) {
    $folderName = $folderGroup.Name
    $fileCount = $folderGroup.Count
    $folderSizeMB = [math]::Round(($folderGroup.Group | Measure-Object -Property Length -Sum).Sum / 1MB, 2)
    $lastModified = ($folderGroup.Group | Sort-Object LastWriteTime -Descending | Select-Object -First 1).LastWriteTime
    
    Write-Host "[FOLDER] $folderName" -ForegroundColor Yellow
    Write-Host "   Files: $fileCount" -ForegroundColor White
    Write-Host "   Size: $folderSizeMB MB" -ForegroundColor White
    Write-Host "   Last Modified: $($lastModified.ToString('yyyy-MM-dd HH:mm:ss'))" -ForegroundColor Gray
    Write-Host ""
}

# Group by file type
$filesByType = $newFiles | Group-Object Extension | Sort-Object Count -Descending

Write-Host "Breakdown by file type:" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor Cyan
Write-Host ""

foreach ($typeGroup in $filesByType) {
    $ext = if ($typeGroup.Name) { $typeGroup.Name } else { "(no extension)" }
    $count = $typeGroup.Count
    $typeSizeMB = [math]::Round(($typeGroup.Group | Measure-Object -Property Length -Sum).Sum / 1MB, 2)
    Write-Host "  $ext : $count files ($typeSizeMB MB)" -ForegroundColor White
}

Write-Host ""

# Export to CSV if requested
if (-not [string]::IsNullOrEmpty($ExportCSV)) {
    $csvData = @()
    foreach ($file in $newFiles) {
        $relativePath = $file.FullName.Replace((Join-Path $projectRoot $localBaseDir), "").Replace("\", "/").TrimStart("/")
        $folderName = ($relativePath -split "/")[0]
        $csvData += [PSCustomObject]@{
            Folder = $folderName
            FileName = $file.Name
            RelativePath = $relativePath
            SizeMB = [math]::Round($file.Length / 1MB, 2)
            LastModified = $file.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss')
        }
    }
    $csvData | Export-Csv -Path $ExportCSV -NoTypeInformation
    Write-Host "[OK] Exported file list to: $ExportCSV" -ForegroundColor Green
    Write-Host ""
}

# List all files (first 50)
Write-Host "File list (first 50):" -ForegroundColor Cyan
Write-Host "====================" -ForegroundColor Cyan
Write-Host ""

$fileList = $newFiles | Select-Object -First 50 | ForEach-Object {
    $relativePath = $_.FullName.Replace((Join-Path $projectRoot $localBaseDir), "").Replace("\", "/").TrimStart("/")
    $sizeMB = [math]::Round($_.Length / 1MB, 2)
    Write-Host "  [FILE] $relativePath ($sizeMB MB) - $($_.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss'))" -ForegroundColor White
}

if ($totalFiles -gt 50) {
    Write-Host ""
    Write-Host "  ... and $($totalFiles - 50) more files" -ForegroundColor Gray
}

Write-Host ""
Write-Host "===================================================" -ForegroundColor Green
Write-Host "SUMMARY" -ForegroundColor Cyan
Write-Host "   Total Files: $totalFiles" -ForegroundColor White
Write-Host "   Total Size: $totalSizeMB MB" -ForegroundColor White
Write-Host "   Folders: $($filesByFolder.Count)" -ForegroundColor White
Write-Host ""
Write-Host "Next step: Run UPLOAD_NEW_3D_MODELS.ps1 to upload these files" -ForegroundColor Yellow
Write-Host ""
