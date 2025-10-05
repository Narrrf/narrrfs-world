<?php
/**
 * Pool Website - Get Bubble Effect Settings
 * Returns the current bubble effect configuration
 * 
 * Created: September 29, 2025
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $settings_file = '../admin/settings.json';
    $settings = [];
    
    // Load settings if file exists
    if (file_exists($settings_file)) {
        $settings = json_decode(file_get_contents($settings_file), true) ?: [];
    }
    
    // Set default values if not present
    $default_settings = [
        'bubble_effect' => 'off',
        'bubble_color' => 'blue',
        'bubble_opacity' => 'medium',
        'bubble_speed' => 'medium',
        'hero_background' => 'pool1.png',
        'background_transparency' => 'full',
        'page_backgrounds' => [
            'index' => 'background1.png',
            'referenzen' => 'background2.png',
            'anfragen' => 'background3.png',
            'ueber_uns' => 'background4.png',
            'kontakt' => 'background1.png'
        ],
        'bubble_pages' => [
            'index' => true,
            'referenzen' => true,
            'anfragen' => true,
            'ueber_uns' => true,
            'kontakt' => true
        ]
    ];
    
    // Merge with defaults
    $settings = array_merge($default_settings, $settings);
    
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
        'message' => 'Error loading bubble settings: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>
