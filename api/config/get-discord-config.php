<?php
// Discord Configuration API Endpoint
// Serves Discord configuration as JavaScript for client-side use

header('Content-Type: application/javascript');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-cache, must-revalidate');

require_once 'discord.php';

// Output the Discord configuration as JavaScript
outputDiscordConfigJs();
?>
