<?php
// 🤝 GET PARTNERS API
// Created: October 28, 2025
// Purpose: Fetch active partners for frontend display

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Database connection
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
$dbPath = $isProduction ? '/var/www/html/db/narrrf_world.sqlite' : __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get only active partners, ordered by featured status and display order
    $stmt = $pdo->prepare("
        SELECT 
            id,
            partner_name,
            partner_slug,
            logo_filename,
            banner_filename,
            short_description,
            long_description,
            partner_type,
            discord_url,
            twitter_url,
            website_url,
            youtube_url,
            additional_info,
            gallery_images,
            is_featured,
            display_order
        FROM tbl_partners 
        WHERE is_active = 1
        ORDER BY is_featured DESC, display_order ASC, created_at DESC
    ");
    
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate featured and regular partners
    $featured = array_filter($partners, fn($p) => $p['is_featured'] == 1);
    $regular = array_filter($partners, fn($p) => $p['is_featured'] == 0);
    
    echo json_encode([
        'success' => true,
        'partners' => [
            'featured' => array_values($featured),
            'all' => $partners,
            'regular' => array_values($regular)
        ],
        'total' => count($partners),
        'featured_count' => count($featured)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch partners: ' . $e->getMessage()
    ]);
}
?>

