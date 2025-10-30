<?php
// 🤝 PARTNER PORTAL MANAGEMENT API
// Created: October 28, 2025
// Purpose: Admin CRUD operations for partner management

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// 🧪 LOCAL TESTING BYPASS
$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
               strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;

if ($isLocalhost) {
    // ✅ LOCAL TESTING MODE - Bypass authentication
    error_log('🧪 LOCAL TESTING MODE: Partner management API - Authentication bypassed');
} else {
    // 🔒 PRODUCTION MODE - Require authentication
    
    // 🔒 Check if already authenticated via admin panel
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        // ✅ Already authenticated via admin panel
        error_log('✅ Admin authenticated via admin panel');
    } else if (!isset($_SESSION['discord_id'])) {
        // ❌ Not authenticated at all
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    } else {
        // 🔒 Check Discord roles for admin access
        $adminRoles = ['Admin', 'Moderator', 'Owner', 'Founder'];
        $userRoles = $_SESSION['roles'] ?? [];
        $isAdmin = !empty(array_intersect($adminRoles, $userRoles));

        if (!$isAdmin) {
            // Check for "narrrf" (you) - hardcoded owner access
            $discordName = $_SESSION['discord_name'] ?? '';
            if (strtolower($discordName) !== 'narrrf') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin access required']);
                exit;
            }
        }
    }
}

// 🔧 HELPER FUNCTIONS

/**
 * Extract YouTube video ID from various URL formats
 * Supports: youtube.com/watch?v=, youtu.be/, youtube.com/embed/
 */
function extractYouTubeVideoId($url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/i',
        '/youtu\.be\/([a-zA-Z0-9_-]+)/i',
        '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/i',
        '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/i',
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/i'  // YouTube Shorts support
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Normalize gallery format from old (strings) to new (objects)
 * Supports backward compatibility
 */
function normalizeGalleryFormat($galleryImages) {
    if (empty($galleryImages)) {
        return [];
    }
    
    $normalized = [];
    foreach ($galleryImages as $item) {
        if (is_string($item)) {
            // Old format: convert string to object
            $normalized[] = [
                'type' => 'file',
                'filename' => $item
            ];
        } else if (is_array($item) && isset($item['type'])) {
            // New format: already normalized
            $normalized[] = $item;
        }
    }
    
    return $normalized;
}

// Database connection
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
$dbPath = $isProduction ? '/var/www/html/db/narrrf_world.sqlite' : __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // 📋 GET ALL PARTNERS
        case 'get_all':
            $stmt = $pdo->prepare("
                SELECT * FROM tbl_partners 
                ORDER BY is_featured DESC, display_order ASC, created_at DESC
            ");
            $stmt->execute();
            $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'partners' => $partners,
                'total' => count($partners)
            ]);
            break;
            
        // ➕ ADD NEW PARTNER
        case 'add':
            $name = $_POST['partner_name'] ?? '';
            $slug = $_POST['partner_slug'] ?? strtolower(str_replace(' ', '-', $name));
            $shortDesc = $_POST['short_description'] ?? '';
            $longDesc = $_POST['long_description'] ?? '';
            $type = $_POST['partner_type'] ?? 'Community Partner';
            $discordUrl = $_POST['discord_url'] ?? '';
            $twitterUrl = $_POST['twitter_url'] ?? '';
            $websiteUrl = $_POST['website_url'] ?? '';
            $youtubeUrl = $_POST['youtube_url'] ?? '';
            $logoFilename = $_POST['logo_filename'] ?? '';
            $bannerFilename = $_POST['banner_filename'] ?? '';
            $isFeatured = intval($_POST['is_featured'] ?? 0);
            $displayOrder = intval($_POST['display_order'] ?? 0);
            
            if (empty($name)) {
                throw new Exception('Partner name is required');
            }
            
            $additionalInfo = $_POST['additional_info'] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO tbl_partners 
                (partner_name, partner_slug, short_description, long_description, partner_type, 
                 discord_url, twitter_url, website_url, youtube_url, additional_info, logo_filename, banner_filename, 
                 is_featured, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name, $slug, $shortDesc, $longDesc, $type,
                $discordUrl, $twitterUrl, $websiteUrl, $youtubeUrl, $additionalInfo,
                $logoFilename, $bannerFilename,
                $isFeatured, $displayOrder
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Partner added successfully',
                'partner_id' => $pdo->lastInsertId()
            ]);
            break;
            
        // ✏️ UPDATE PARTNER
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Invalid partner ID');
            }
            
            // 🚨 CRITICAL FIX: Get current partner data to check what actually changed
            $currentStmt = $pdo->prepare("SELECT * FROM tbl_partners WHERE id = ?");
            $currentStmt->execute([$id]);
            $currentData = $currentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentData) {
                throw new Exception('Partner not found');
            }
            
            // 🚨 CRITICAL FIX: Only check slug if it's DIFFERENT from current
            if (isset($_POST['partner_slug']) && $_POST['partner_slug'] !== $currentData['partner_slug']) {
                $checkSlugStmt = $pdo->prepare("SELECT id FROM tbl_partners WHERE partner_slug = ? AND id != ?");
                $checkSlugStmt->execute([$_POST['partner_slug'], $id]);
                $conflictingPartner = $checkSlugStmt->fetch();
                
                if ($conflictingPartner) {
                    throw new Exception('Partner slug already exists. Please use a different name.');
                }
            }
            
            $updates = [];
            $params = [];
            
            $fields = [
                'partner_name', 'partner_slug', 'short_description', 'long_description',
                'partner_type', 'discord_url', 'twitter_url', 'website_url', 'youtube_url', 'additional_info',
                'logo_filename', 'banner_filename', 'is_featured', 'is_active', 'display_order'
            ];
            
            // 🚨 CRITICAL FIX: Only update fields that actually changed
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $oldValue = $currentData[$field] ?? null;
                    $newValue = $_POST[$field];
                    
                    // Special handling for checkboxes (convert to int for comparison)
                    if (in_array($field, ['is_featured', 'is_active', 'display_order'])) {
                        $oldValue = intval($oldValue);
                        $newValue = intval($newValue);
                    }
                    
                    // Only add to UPDATE if value actually changed
                    if ($newValue !== $oldValue) {
                        $updates[] = "$field = ?";
                        $params[] = $_POST[$field];
                        error_log("📝 Field changed: $field = '{$oldValue}' → '{$newValue}'");
                    } else {
                        error_log("⏭️ Field unchanged: $field = '{$oldValue}'");
                    }
                }
            }
            
            // 🚨 FIX: If no fields changed, just update timestamp (allows image-only updates)
            if (empty($updates)) {
                error_log("ℹ️ No fields changed - updating timestamp only");
                $updates[] = "updated_at = CURRENT_TIMESTAMP";
            } else {
                // Add updated timestamp
                $updates[] = "updated_at = CURRENT_TIMESTAMP";
            }
            
            $params[] = $id;
            
            $sql = "UPDATE tbl_partners SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Partner updated successfully'
            ]);
            break;
            
        // 🗑️ DELETE PARTNER
        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Invalid partner ID');
            }
            
            // Get partner info before deleting (for logging)
            $stmt = $pdo->prepare("SELECT partner_name, logo_filename, banner_filename FROM tbl_partners WHERE id = ?");
            $stmt->execute([$id]);
            $partner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM tbl_partners WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Partner deleted successfully',
                'deleted_partner' => $partner['partner_name'] ?? 'Unknown'
            ]);
            break;
            
        // 🔄 REORDER PARTNERS
        case 'reorder':
            $partnerId = intval($_POST['partner_id'] ?? 0);
            $newOrder = intval($_POST['new_order'] ?? 0);
            
            if ($partnerId <= 0) {
                throw new Exception('Invalid partner ID');
            }
            
            $stmt = $pdo->prepare("UPDATE tbl_partners SET display_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newOrder, $partnerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Partner order updated'
            ]);
            break;
            
        // 🖼️ UPLOAD LOGO/BANNER
        case 'upload_image':
            $partnerId = intval($_POST['partner_id'] ?? 0);
            $imageType = $_POST['image_type'] ?? 'logo'; // 'logo' or 'banner'
            
            if ($partnerId <= 0) {
                throw new Exception('Invalid partner ID');
            }
            
            if (!isset($_FILES['image'])) {
                throw new Exception('No image file provided');
            }
            
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, WEBP');
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception('File too large. Maximum 5MB');
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $partnerId . '_' . $imageType . '_' . time() . '.' . $extension;
            
            // 🚨 CRITICAL FIX: Different paths for local vs production
            $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
            if ($isProduction) {
                // Production: use persistent storage in /data and serve via symlink /var/www/html/img/partners
                $persistentDir = '/data/img/partners/';
                if (!is_dir($persistentDir)) {
                    @mkdir($persistentDir, 0775, true);
                    @chown($persistentDir, 'www-data');
                    @chgrp($persistentDir, 'www-data');
                }
                $uploadPath = $persistentDir . $filename;
            } else {
                // Local: public/img/partners/
                $uploadPath = __DIR__ . '/../../public/img/partners/' . $filename;
            }
            
            error_log("📁 Upload path determined: $uploadPath (Production: " . ($isProduction ? 'YES' : 'NO') . ")");
            
            // Create directory if it doesn't exist
            $dir = dirname($uploadPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
                @chown($dir, 'www-data');
                @chgrp($dir, 'www-data');
                error_log("📁 Created directory: $dir");
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update database with new filename
                $field = $imageType === 'logo' ? 'logo_filename' : 'banner_filename';
                $stmt = $pdo->prepare("UPDATE tbl_partners SET $field = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$filename, $partnerId]);
                
                $publicUrl = $isProduction ? ('/img/partners/' . $filename) : ('/public/img/partners/' . $filename);
                echo json_encode([
                    'success' => true,
                    'message' => ucfirst($imageType) . ' uploaded successfully',
                    'filename' => $filename,
                    'url' => $publicUrl
                ]);
            } else {
                throw new Exception('Failed to upload file');
            }
            break;
            
        // 🗑️ DELETE IMAGE
        case 'delete_image':
            $partnerId = intval($_POST['partner_id'] ?? 0);
            $imageType = $_POST['image_type'] ?? ''; // 'logo' or 'banner'
            
            if ($partnerId <= 0) {
                throw new Exception('Invalid partner ID');
            }
            
            if (!in_array($imageType, ['logo', 'banner'])) {
                throw new Exception('Invalid image type');
            }
            
            // Get current partner data
            $stmt = $pdo->prepare("SELECT logo_filename, banner_filename FROM tbl_partners WHERE id = ?");
            $stmt->execute([$partnerId]);
            $partner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$partner) {
                throw new Exception('Partner not found');
            }
            
            // Get filename to delete
            $fieldName = $imageType === 'logo' ? 'logo_filename' : 'banner_filename';
            $filename = $partner[$fieldName];
            
            if ($filename) {
                // Delete physical file
                // 🚨 CRITICAL FIX: Different paths for local vs production
                $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
                if ($isProduction) {
                    $filePath = '/data/img/partners/' . $filename;
                } else {
                    $filePath = __DIR__ . '/../../public/img/partners/' . $filename;
                }
                
                if (file_exists($filePath)) {
                    unlink($filePath);
                    error_log("🗑️ Deleted image file: $filePath");
                }
            }
            
            // Update database to remove filename
            $stmt = $pdo->prepare("UPDATE tbl_partners SET $fieldName = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$partnerId]);
            
            echo json_encode([
                'success' => true,
                'message' => ucfirst($imageType) . ' deleted successfully'
            ]);
            break;
            
        // 📸 UPLOAD GALLERY IMAGE
        case 'upload_gallery':
            $partnerId = intval($_POST['partner_id'] ?? 0);
            $galleryIndex = intval($_POST['gallery_index'] ?? 0);
            
            if ($partnerId <= 0) {
                throw new Exception('Invalid partner ID');
            }
            
            if (!isset($_FILES['gallery_image']) || $_FILES['gallery_image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }
            
            $file = $_FILES['gallery_image'];
            
            // Validate file type (images and videos)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, GIF, WEBP, MP4, WEBM allowed');
            }
            
            // Validate file size (2MB for images, 25MB for videos)
            $isVideo = strpos($file['type'], 'video/') === 0;
            $maxSize = $isVideo ? (25 * 1024 * 1024) : (2 * 1024 * 1024);
            if ($file['size'] > $maxSize) {
                $maxSizeMB = $isVideo ? '25MB' : '2MB';
                throw new Exception("File too large. Maximum {$maxSizeMB} per " . ($isVideo ? 'video' : 'image'));
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $partnerId . '_gallery_' . ($galleryIndex + 1) . '_' . time() . '.' . $extension;
            
            // 🚨 CRITICAL: Environment-aware path (no /public/ on production!)
            $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
            if ($isProduction) {
                $persistentDir = '/data/img/partners/';
                if (!is_dir($persistentDir)) { @mkdir($persistentDir, 0775, true); @chown($persistentDir, 'www-data'); @chgrp($persistentDir, 'www-data'); }
                $uploadPath = $persistentDir . $filename;
            } else {
                $uploadPath = __DIR__ . '/../../public/img/partners/' . $filename;
            }
            
            error_log("📸 Gallery upload path: $uploadPath (Production: " . ($isProduction ? 'YES' : 'NO') . ")");
            
            // Create directory if it doesn't exist
            $dir = dirname($uploadPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
                @chown($dir, 'www-data');
                @chgrp($dir, 'www-data');
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Get current gallery images
                $stmt = $pdo->prepare("SELECT gallery_images FROM tbl_partners WHERE id = ?");
                $stmt->execute([$partnerId]);
                $partner = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $galleryImages = $partner['gallery_images'] ? json_decode($partner['gallery_images'], true) : [];
                if (!is_array($galleryImages)) $galleryImages = [];
                
                // Normalize old format to new format if needed
                $galleryImages = normalizeGalleryFormat($galleryImages);
                
                // Add new filename to gallery (new format)
                $galleryImages[] = [
                    'type' => 'file',
                    'filename' => $filename
                ];
                
                // Limit to 7 items for files (images + videos), YouTube is unlimited
                // Count only file items (not YouTube)
                $fileCount = 0;
                foreach ($galleryImages as $item) {
                    $itemFilename = is_string($item) ? $item : (isset($item['type']) && $item['type'] === 'file' ? $item['filename'] : null);
                    if ($itemFilename && preg_match('/\.(jpg|jpeg|png|gif|webp|mp4|webm)$/i', $itemFilename)) {
                        $fileCount++;
                    }
                }
                
                // Only trim files if total files > 7
                if ($fileCount > 7) {
                    // Remove oldest file items until we're at 7
                    $trimmed = [];
                    $fileCount = 0;
                    foreach ($galleryImages as $item) {
                        $itemFilename = is_string($item) ? $item : (isset($item['type']) && $item['type'] === 'file' ? $item['filename'] : null);
                        $isFile = $itemFilename && preg_match('/\.(jpg|jpeg|png|gif|webp|mp4|webm)$/i', $itemFilename);
                        
                        if ($isFile) {
                            if ($fileCount < 7) {
                                $trimmed[] = $item;
                                $fileCount++;
                            }
                        } else {
                            // Always keep YouTube videos
                            $trimmed[] = $item;
                        }
                    }
                    $galleryImages = $trimmed;
                }
                
                // Update database
                $stmt = $pdo->prepare("UPDATE tbl_partners SET gallery_images = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([json_encode($galleryImages), $partnerId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Gallery item uploaded successfully',
                    'filename' => $filename,
                    'gallery_count' => count($galleryImages),
                    'is_video' => $isVideo
                ]);
            } else {
                throw new Exception('Failed to upload gallery image');
            }
            break;
            
        // 🗑️ DELETE GALLERY IMAGE
        case 'delete_gallery_image':
            $partnerId = intval($_POST['partner_id'] ?? 0);
            $filename = $_POST['filename'] ?? '';
            
            if ($partnerId <= 0 || empty($filename)) {
                throw new Exception('Invalid partner ID or filename');
            }
            
            // Get current gallery images
            $stmt = $pdo->prepare("SELECT gallery_images FROM tbl_partners WHERE id = ?");
            $stmt->execute([$partnerId]);
            $partner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$partner) {
                throw new Exception('Partner not found');
            }
            
            $galleryImages = $partner['gallery_images'] ? json_decode($partner['gallery_images'], true) : [];
            if (!is_array($galleryImages)) $galleryImages = [];
            
            // Normalize old format to new format if needed
            $galleryImages = normalizeGalleryFormat($galleryImages);
            
            // Remove item from array (support both old string format and new object format)
            $galleryImages = array_filter($galleryImages, function($item) use ($filename) {
                if (is_string($item)) {
                    return $item !== $filename; // Old format
                } else if (is_array($item)) {
                    // New format: check if it's a file with matching filename or YouTube video
                    if (isset($item['type']) && $item['type'] === 'file') {
                        return $item['filename'] !== $filename;
                    }
                    // For YouTube videos, check if deleting by video_id
                    if (isset($item['type']) && $item['type'] === 'youtube') {
                        return isset($item['video_id']) && $item['video_id'] !== $filename;
                    }
                }
                return true;
            });
            $galleryImages = array_values($galleryImages); // Re-index array
            
            // Delete physical file (only for file type, not YouTube)
            // Check if it's a file or YouTube by checking if filename contains extension
            if (preg_match('/\.(jpg|jpeg|png|gif|webp|mp4|webm)$/i', $filename)) {
                $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
                if ($isProduction) {
                    $filePath = '/data/img/partners/' . $filename;
                } else {
                    $filePath = __DIR__ . '/../../public/img/partners/' . $filename;
                }
                
                if (file_exists($filePath)) {
                    unlink($filePath);
                    $isVideo = preg_match('/\.(mp4|webm)$/i', $filename);
                    error_log("🗑️ Deleted gallery " . ($isVideo ? "video" : "image") . ": $filePath");
                }
            } else {
                // YouTube video deletion (no file to delete)
                error_log("🗑️ Deleted YouTube video: $filename");
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE tbl_partners SET gallery_images = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([json_encode($galleryImages), $partnerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Gallery item deleted successfully',
                'remaining_count' => count($galleryImages)
            ]);
            break;
            
        // ▶️ ADD YOUTUBE VIDEO
        case 'add_youtube_video':
            $partnerId = intval($_POST['partner_id'] ?? 0);
            $youtubeUrl = trim($_POST['youtube_url'] ?? '');
            
            if ($partnerId <= 0 || empty($youtubeUrl)) {
                throw new Exception('Invalid partner ID or YouTube URL');
            }
            
            // Extract YouTube video ID
            $videoId = extractYouTubeVideoId($youtubeUrl);
            if (!$videoId) {
                throw new Exception('Invalid YouTube URL format');
            }
            
            // Get current gallery images
            $stmt = $pdo->prepare("SELECT gallery_images FROM tbl_partners WHERE id = ?");
            $stmt->execute([$partnerId]);
            $partner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$partner) {
                throw new Exception('Partner not found');
            }
            
            $galleryImages = $partner['gallery_images'] ? json_decode($partner['gallery_images'], true) : [];
            if (!is_array($galleryImages)) $galleryImages = [];
            
            // Normalize old format to new format if needed
            $galleryImages = normalizeGalleryFormat($galleryImages);
            
            // Add YouTube video object
            $galleryImages[] = [
                'type' => 'youtube',
                'video_id' => $videoId,
                'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg"
            ];
            
            // Limit to 7 items for files (images + videos), YouTube is unlimited
            // Count only file items (not YouTube)
            $fileCount = 0;
            foreach ($galleryImages as $item) {
                $itemFilename = is_string($item) ? $item : (isset($item['type']) && $item['type'] === 'file' ? $item['filename'] : null);
                if ($itemFilename && preg_match('/\.(jpg|jpeg|png|gif|webp|mp4|webm)$/i', $itemFilename)) {
                    $fileCount++;
                }
            }
            
            // Only trim files if total files > 7 (YouTube videos don't count)
            if ($fileCount > 7) {
                // Remove oldest file items until we're at 7
                $trimmed = [];
                $fileCount = 0;
                foreach ($galleryImages as $item) {
                    $itemFilename = is_string($item) ? $item : (isset($item['type']) && $item['type'] === 'file' ? $item['filename'] : null);
                    $isFile = $itemFilename && preg_match('/\.(jpg|jpeg|png|gif|webp|mp4|webm)$/i', $itemFilename);
                    
                    if ($isFile) {
                        if ($fileCount < 7) {
                            $trimmed[] = $item;
                            $fileCount++;
                        }
                    } else {
                        // Always keep YouTube videos (and other non-file items)
                        $trimmed[] = $item;
                    }
                }
                $galleryImages = $trimmed;
            }
            
            // Update database
            $jsonData = json_encode($galleryImages);
            error_log("📺 Saving YouTube video to gallery: Partner ID $partnerId, Video ID: $videoId, Gallery count: " . count($galleryImages));
            error_log("📺 Gallery JSON: " . $jsonData);
            
            $stmt = $pdo->prepare("UPDATE tbl_partners SET gallery_images = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$jsonData, $partnerId]);
            
            // Verify update
            $verify = $pdo->prepare("SELECT gallery_images FROM tbl_partners WHERE id = ?");
            $verify->execute([$partnerId]);
            $verified = $verify->fetch(PDO::FETCH_ASSOC);
            error_log("📺 Verified saved gallery: " . ($verified['gallery_images'] ?? 'NULL'));
            
            echo json_encode([
                'success' => true,
                'message' => 'YouTube video added successfully',
                'video_id' => $videoId,
                'gallery_count' => count($galleryImages),
                'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
                'item' => [
                    'type' => 'youtube',
                    'video_id' => $videoId,
                    'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg"
                ]
            ]);
            break;
            
        // 🗑️ DELETE YOUTUBE VIDEO (uses same delete_gallery_image action, but by video_id)
            
        // ❌ INVALID ACTION
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

