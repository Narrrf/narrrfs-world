<?php
// Discord configuration - ONLY using environment variables from Render screenshot
define('DISCORD_BOT_SECRET', getenv('DISCORD_BOT_SECRET'));
define('DISCORD_CLIENT_ID', getenv('DISCORD_CLIENT_ID'));
define('DISCORD_GUILD', getenv('DISCORD_GUILD'));
define('DISCORD_SECRET', getenv('DISCORD_SECRET'));
define('DISCORD_BASE_URL', getenv('DISCORD_BASE_URL'));
define('API_URL', getenv('API_URL'));
define('DISCORD_INVITE_CODE', getenv('DISCORD_INVITE_CODE') ?: 'CR5mYu49');

// Function to output Discord configuration as JavaScript
function outputDiscordConfigJs() {
    $inviteCode = getenv('DISCORD_INVITE_CODE') ?: 'CR5mYu49';
    echo "window.DISCORD_CONFIG = {\n";
    echo "    inviteCode: '$inviteCode',\n";
    echo "    baseUrl: 'https://discord.gg/',\n";
    echo "    get fullUrl() {\n";
    echo "        return this.baseUrl + this.inviteCode;\n";
    echo "    },\n";
    echo "    version: '12.0',\n";
    echo "    lastUpdated: '2025-01-28'\n";
    echo "};\n";
    echo "// Update the global DISCORD_CONFIG object\n";
    echo "if (typeof DISCORD_CONFIG !== 'undefined') {\n";
    echo "    DISCORD_CONFIG.inviteCode = '$inviteCode';\n";
    echo "    console.log('✅ Discord config updated from server:', DISCORD_CONFIG.inviteCode);\n";
    echo "}\n";
}
?> 