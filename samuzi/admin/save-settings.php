<?php
/**
 * Pool Website Admin - Save Settings
 * Saves website and email settings from admin dashboard
 * 
 * Created: September 28, 2025
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Check if user is logged in
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $settings_file = 'settings.json';
    $current_settings = [];
    
    // Load existing settings if file exists
    if (file_exists($settings_file)) {
        $current_settings = json_decode(file_get_contents($settings_file), true) ?: [];
    }
    
    // Update settings
    if (isset($input['email_address'])) {
        $current_settings['email_address'] = $input['email_address'];
        
        // Update the email address in send-email.php
        updateEmailInSendEmail($input['email_address']);
    }
    
    // SMTP Settings REMOVED - Only using Resend.com API now
    // SMTP settings are no longer used or saved
    
    if (isset($input['from_email'])) {
        $current_settings['from_email'] = $input['from_email'];
    }
    
    if (isset($input['from_name'])) {
        $current_settings['from_name'] = $input['from_name'];
    }
    
    if (isset($input['company_name'])) {
        $current_settings['company_name'] = $input['company_name'];
    }
    
    if (isset($input['phone_number'])) {
        $current_settings['phone_number'] = $input['phone_number'];
    }
    
    if (isset($input['slider_speed'])) {
        $current_settings['slider_speed'] = intval($input['slider_speed']);
        
        // Update slider speed in index.html
        updateSliderSpeed($input['slider_speed']);
    }
    
    if (isset($input['hero_background'])) {
        $current_settings['hero_background'] = $input['hero_background'];
        
        // Update page background in index.html
        updatePageBackground($input['hero_background']);
    }
    
    if (isset($input['background_transparency'])) {
        $current_settings['background_transparency'] = $input['background_transparency'];
        
        // Update background transparency in index.html
        updateBackgroundTransparency($input['background_transparency']);
    }
    
    if (isset($input['page_backgrounds'])) {
        $current_settings['page_backgrounds'] = $input['page_backgrounds'];
        
        // Update backgrounds for all pages
        updateAllPageBackgrounds($input['page_backgrounds']);
    }
    
    if (isset($input['bubble_effect'])) {
        $current_settings['bubble_effect'] = $input['bubble_effect'];
    }
    
    if (isset($input['bubble_color'])) {
        $current_settings['bubble_color'] = $input['bubble_color'];
    }
    
    if (isset($input['bubble_opacity'])) {
        $current_settings['bubble_opacity'] = $input['bubble_opacity'];
    }
    
    if (isset($input['bubble_speed'])) {
        $current_settings['bubble_speed'] = $input['bubble_speed'];
    }
    
    if (isset($input['bubble_pages'])) {
        $current_settings['bubble_pages'] = $input['bubble_pages'];
    }
    
    // Save settings to file
    $current_settings['last_updated'] = date('Y-m-d H:i:s');
    $current_settings['updated_by'] = $_SESSION['admin_username'];
    
    file_put_contents($settings_file, json_encode($current_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log the settings update
    $log_entry = date('Y-m-d H:i:s') . " - Settings updated by " . $_SESSION['admin_username'] . ": " . json_encode($input) . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully',
        'settings' => $current_settings
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving settings: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function updateEmailInSendEmail($new_email) {
    $send_email_file = '../api/send-email.php';
    
    if (file_exists($send_email_file)) {
        $content = file_get_contents($send_email_file);
        
        // Replace the email address in the PHP file
        $content = preg_replace(
            '/\$to_email\s*=\s*"[^"]*";/',
            '$to_email = "' . $new_email . '";',
            $content
        );
        
        file_put_contents($send_email_file, $content);
    }
}

function updateSliderSpeed($speed) {
    $index_file = '../index.html';
    
    if (file_exists($index_file)) {
        $content = file_get_contents($index_file);
        
        // Replace slider speed in JavaScript
        $content = preg_replace(
            '/setInterval\(function\(\)\s*\{[^}]*\},[^)]*\);/',
            'setInterval(function() { changeSlide(1); }, ' . ($speed * 1000) . ');',
            $content
        );
        
        file_put_contents($index_file, $content);
    }
}

function updatePageBackground($background) {
    $index_file = '../index.html';
    
    if (file_exists($index_file)) {
        $content = file_get_contents($index_file);
        
        // Determine the correct path for the background
        if (in_array($background, ['background1.png', 'background2.png', 'background3.png', 'background4.png', 'akamai-samurai-phase-bg.webp'])) {
            $background_path = './assets/background/' . $background;
        } else {
            // Default to slider-photos for other images
            $background_path = './slider-photos/' . $background;
        }
        
        // Update the loadPageBackground function to use the new background
        $content = preg_replace(
            '/backgroundPath = \'[^\']*\';/',
            'backgroundPath = \'' . $background_path . '\';',
            $content
        );
        
        file_put_contents($index_file, $content);
    }
}

function updateBackgroundTransparency($transparency) {
    $index_file = '../index.html';
    
    if (file_exists($index_file)) {
        $content = file_get_contents($index_file);
        
        // Update the loadPageBackground function to apply transparency settings
        $transparency_js = '';
        switch ($transparency) {
            case 'full':
                $transparency_js = '// Full transparency - remove overlay
                        const existingOverlay = body.querySelector(\'.background-overlay\');
                        if (existingOverlay) {
                            existingOverlay.remove();
                        }';
                break;
            case 'hero-only':
                $transparency_js = '// Hero only - add blue overlay to hero
                        const hero = document.querySelector(\'.hero\');
                        if (hero) {
                            hero.style.background = `linear-gradient(rgba(0,102,204,0.85), rgba(0,68,153,0.85)), url(\'${backgroundPath}\')`;
                            hero.style.backgroundSize = \'cover\';
                            hero.style.backgroundPosition = \'center\';
                        }';
                break;
                    case 'subtle':
                        $transparency_js = '// Subtle - add light overlay
                                if (!body.querySelector(\'.background-overlay\')) {
                                    const overlay = document.createElement(\'div\');
                                    overlay.className = \'background-overlay\';
                                    overlay.style.cssText = `
                                        position: fixed;
                                        top: 0;
                                        left: 0;
                                        width: 100%;
                                        height: 100%;
                                        background: rgba(0, 0, 0, 0.1);
                                        z-index: -1;
                                        pointer-events: none;
                                    `;
                                    body.appendChild(overlay);
                                }';
                        break;
                    case 'complete':
                        $transparency_js = '// Complete background - remove all overlays and make content completely opaque
                                const existingOverlay = body.querySelector(\'.background-overlay\');
                                if (existingOverlay) {
                                    existingOverlay.remove();
                                }
                                const mainContent = document.querySelector(\'.main-content\');
                                if (mainContent) {
                                    mainContent.classList.remove(\'full-transparency\');
                                    mainContent.style.background = \'rgba(26, 26, 26, 0.95)\';
                                    mainContent.style.backdropFilter = \'blur(20px)\';
                                }
                                // Make all content cards completely opaque
                                const contentCards = document.querySelectorAll(\'.content-text, .contact-section, .service-item, .contact-item, .contact-form\');
                                contentCards.forEach(card => {
                                    card.style.background = \'rgba(255, 255, 255, 0.95)\';
                                    card.style.backdropFilter = \'blur(10px)\';
                                    card.style.color = \'#333\';
                                    card.querySelectorAll(\'h3, h4, h5, p, strong, label\').forEach(text => {
                                        text.style.color = \'#333\';
                                        text.style.textShadow = \'none\';
                                    });
                                });';
                        break;
        }
        
        // Replace the transparency logic in the loadPageBackground function
        $content = preg_replace(
            '/\/\/ Add overlay for text readability if needed[\s\S]*?\}\);/',
            $transparency_js,
            $content
        );
        
        file_put_contents($index_file, $content);
    }
}

function updateAllPageBackgrounds($page_backgrounds) {
    $pages = [
        'index' => '../index.html',
        'projects' => '../projects.html',
        'about' => '../about.html',
        'contact' => '../contact.html',
        'legal' => '../legal.html'
    ];
    
    foreach ($page_backgrounds as $page => $background) {
        if (isset($pages[$page])) {
            $file_path = $pages[$page];
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                // Determine the correct path for the background
                if (in_array($background, ['background1.png', 'background2.png', 'background3.png', 'background4.png'])) {
                    $background_path = './assets/background/' . $background;
                } elseif (strpos($background, 'custom_') === 0) {
                    $background_path = './assets/custom-backgrounds/' . $background;
                } else {
                    $background_path = './slider-photos/' . $background;
                }
                
                // Update the loadPageBackground function to use the new background
                $content = preg_replace(
                    '/backgroundPath = \'[^\']*\';/',
                    'backgroundPath = \'' . $background_path . '\';',
                    $content
                );
                
                file_put_contents($file_path, $content);
            }
        }
    }
}
?>
