<?php
/**
 * Samuzi NFT Admin - Get Settings
 * Returns saved dashboard settings in the format dashboard.html expects:
 * { success: true, settings: {...} }
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    $settings_file = 'settings.json';
    $settings = [];

    if (file_exists($settings_file)) {
        $decoded = json_decode(file_get_contents($settings_file), true);
        $settings = is_array($decoded) ? $decoded : [];
    }

    $default_settings = [
        'company_name' => 'Samuzi NFT',
        'phone_number' => '',
        'email_address' => 'Samuzinft@gmail.com',
        'sender_email' => 'onboarding@resend.dev',
        'from_email' => 'onboarding@resend.dev',
        'from_name' => 'Samuzi NFT Website',
        'resend_api_key' => '',
        'slider_speed' => 5,

        'bubble_effect' => 'off',
        'bubble_color' => 'gold',
        'bubble_opacity' => 'medium',
        'bubble_speed' => 'medium',

        'hero_background' => 'background1.png',
        'background_transparency' => 'full',

        'bubble_pages' => [
            'index' => true,
            'projects' => true,
            'about' => true,
            'contact' => true,
            'legal' => true
        ],

        'page_backgrounds' => [
            'index' => 'background1.png',
            'projects' => 'background1.png',
            'about' => 'background1.png',
            'contact' => 'background1.png',
            'legal' => 'background1.png'
        ]
    ];

    $settings = array_replace_recursive($default_settings, $settings);

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'settings' => $settings,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading settings: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>