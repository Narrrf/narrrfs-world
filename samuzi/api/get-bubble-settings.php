<?php
/**
 * Samuzi NFT - Get Visual Effect Settings
 * Returns the current background and ambient effect configuration.
 */

// Suppress all output and warnings to ensure clean JSON response
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

try {
    $settings_file = '../admin/settings.json';
    $settings = [];

    if (file_exists($settings_file)) {
        $decoded_settings = json_decode(file_get_contents($settings_file), true);
        $settings = is_array($decoded_settings) ? $decoded_settings : [];
    }

    // Safe Samuzi defaults.
    // Saved admin settings override these values.
    $default_settings = [
        'bubble_effect' => 'off',
        'bubble_color' => 'gold',
        'bubble_opacity' => 'low',
        'bubble_speed' => 'slow',
        'hero_background' => 'phase3-coming-soon.jpg',
        'background_transparency' => 'full',
        'page_backgrounds' => [
            'index' => 'phase3-coming-soon.jpg',
            'projects' => 'phase3-coming-soon.jpg',
            'about' => 'phase3-coming-soon.jpg',
            'contact' => 'phase3-coming-soon.jpg',
            'legal' => 'phase3-coming-soon.jpg'
        ],
        'bubble_pages' => [
            'index' => false,
            'projects' => false,
            'about' => false,
            'contact' => false,
            'legal' => false
        ]
    ];

    // Recursive merge keeps nested defaults if one key is missing.
    $settings = array_replace_recursive($default_settings, $settings);

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'bubble_effect' => $settings['bubble_effect'],
        'bubble_color' => $settings['bubble_color'],
        'bubble_opacity' => $settings['bubble_opacity'],
        'bubble_speed' => $settings['bubble_speed'],
        'hero_background' => $settings['hero_background'],
        'background_transparency' => $settings['background_transparency'],
        'page_backgrounds' => $settings['page_backgrounds'],
        'bubble_pages' => $settings['bubble_pages'],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading visual settings: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>