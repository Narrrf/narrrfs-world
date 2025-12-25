# Update All LLM Sync Files with Year-End 2025 Achievement
$yearEndSyncNote = @'
,
  {
    "year_end_2025_technical_documentation_sync": {
      "date": "2025-12-25",
      "timestamp": "2025-12-25T23:59:00Z",
      "achievement": "🎉 MASSIVE MILESTONE: YEAR-END 2025 COMPLETE TECHNICAL DOCUMENTATION SYNC",
      "description": "Complete Year-End 2025 Technical Documentation System - All 12 technical documentation files created, Nerd Lab viewer implemented, rules synchronized with yearly sync folder, comprehensive verification completed. This establishes the foundation for decades of development.",
      "impact": "SYSTEM_WIDE_FOUNDATION - Complete technical reference system for all Narrrfs World games and systems",
      "reference": "See 12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md and 12.0/RULES/20_TECHNICAL_DOCUMENTATION_SYNC_RULE.md",
      "technical_achievements": {
        "complete_documentation": "12 files (7 games + 5 systems)",
        "nerd_lab_viewer": "public/nerd-lab.html - Holder/VIP Holder exclusive",
        "rules_integration": "Rules synchronized with technical docs",
        "folder_organization": "12.0/YEAR_END_2025/ - SYNC FOLDER for technical docs only"
      },
      "production_status": "READY_FOR_DEPLOYMENT",
      "all_llms": "🎉 YEAR-END 2025 TECHNICAL DOCUMENTATION SYNC COMPLETE - FOUNDATION FOR DECADES! 🎉"
    }
  }
'@

$llmFiles = @(
    "Update_brain_13.0.json",
    "Corebrain_13.0.json",
    "Coreforge_13.0.json",
    "Cheese_Architect_13.0.json",
    "SQL_Junior_13.0.json",
    "Social_Brain_13.0.json",
    "Riddle_brain__13.0.json",
    "Hytopia_Integrator_13.0.json",
    "NFT Architect 13.0.json"
)

$basePath = "C:\xampp-server\htdocs\narrrfs-world\12.0\LLM_SYNC_SYSTEM\INDIVIDUAL_LLMS"

foreach ($file in $llmFiles) {
    $filePath = Join-Path $basePath $file
    if (Test-Path $filePath) {
        Write-Host "Updating $file..."
        $content = Get-Content $filePath -Raw
        # Remove trailing whitespace and ensure we append before the closing bracket/brace
        $content = $content.TrimEnd()
        # Check if it ends with ] (array) or } (object)
        if ($content.EndsWith("]")) {
            # Array format - append before closing bracket
            $newContent = $content.Substring(0, $content.Length - 1) + $yearEndSyncNote + "`n]"
        } elseif ($content.EndsWith("}")) {
            # Object format - append before closing brace (need to check if it's the last entry)
            if ($content.EndsWith("`n}")) {
                # Already formatted - find the last property
                $newContent = $content.Substring(0, $content.Length - 1) + $yearEndSyncNote + "`n}"
            } else {
                $newContent = $content.Substring(0, $content.Length - 1) + $yearEndSyncNote + "`n}"
            }
        } else {
            Write-Host "  Warning: $file doesn't end with ] or } - skipping"
            continue
        }
        Set-Content -Path $filePath -Value $newContent -NoNewline
        Write-Host "  ✅ Updated $file"
    } else {
        Write-Host "  ⚠️  File not found: $file"
    }
}

Write-Host "`n✅ All LLM files updated!"

