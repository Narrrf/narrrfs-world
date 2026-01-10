# Test Upload Single Critical File
# Quick test to verify upload API is working

param(
    [Parameter(Mandatory=$true)]
    [string]$BotSecret
)

$uploadUrl = "https://narrrfs.world/api/discord/upload-assets.php"
$testFile = "public\three.js\public\textures\grass\grass.jpg"

if (-not (Test-Path $testFile)) {
    Write-Host "ERROR: Test file not found: $testFile" -ForegroundColor Red
    exit 1
}

Write-Host "Testing upload of: $testFile" -ForegroundColor Cyan
$fileSize = (Get-Item $testFile).Length
Write-Host "File size: $([math]::Round($fileSize/1KB, 2)) KB" -ForegroundColor Cyan
Write-Host ""

$remotePath = "/data/public/three.js/public/textures/grass/grass.jpg"

Write-Host "Uploading..." -ForegroundColor Yellow

try {
    $response = curl.exe -X POST `
        -H "Authorization: $BotSecret" `
        -F "file=@$testFile" `
        -F "target_path=$remotePath" `
        $uploadUrl `
        2>&1
    
    $responseString = $response -join "`n"
    Write-Host ""
    Write-Host "Response:" -ForegroundColor Cyan
    Write-Host $responseString -ForegroundColor White
    
    if ($responseString -match '"success"\s*:\s*true') {
        Write-Host ""
        Write-Host "SUCCESS! Upload worked." -ForegroundColor Green
    } else {
        Write-Host ""
        Write-Host "FAILED! Check response above." -ForegroundColor Red
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}
