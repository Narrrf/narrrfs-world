# UPLOAD NEW ASSETS ONLY (Skip Already Uploaded 3D Models)
# Created: January 9, 2026
# Purpose: Upload only NEW files (~122 files, ~68 MB) - skips already uploaded 3D models
# This is MUCH faster than uploading all 1,477 files

param(
    [Parameter(Mandatory=$false)]
    [string]$BotSecret = "",
    
    [Parameter(Mandatory=$false)]
    [switch]$Force = $false
)

# Just call the main upload script with Skip3DModels flag
& "$PSScriptRoot\UPLOAD_ALL_ASSETS_URGENT.ps1" -BotSecret $BotSecret -Skip3DModels -Force:$Force
