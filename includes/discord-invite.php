<?php
// Discord Invite Code Configuration
// This file provides the Discord invite code from environment variables

// Read the Discord invite code from environment variable with fallback
$discord_invite_code = getenv('DISCORD_INVITE_CODE') ?: 'hx4EvgBG';

// Full Discord URL
$discord_url = "https://discord.gg/$discord_invite_code";
?>
