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
    
    // 🔒 Admin authentication check
    if (!isset($_SESSION['discord_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    // 🔒 Admin role verification
    $adminRoles = ['Admin', 'Moderator', 'Owner', 'Founder'];
    $userRoles = $_SESSION['roles'] ?? [];
    $isAdmin = !empty(array_intersect($adminRoles, $userRoles));

    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
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
            $logoFilename = $_POST['logo_filename'] ?? '';
            $bannerFilename = $_POST['banner_filename'] ?? '';
            $isFeatured = intval($_POST['is_featured'] ?? 0);
            $displayOrder = intval($_POST['display_order'] ?? 0);
            
            if (empty($name)) {
                throw new Exception('Partner name is required');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO tbl_partners 
                (partner_name, partner_slug, short_description, long_description, partner_type, 
                 discord_url, twitter_url, website_url, logo_filename, banner_filename, 
                 is_featured, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name, $slug, $shortDesc, $longDesc, $type,
                $discordUrl, $twitterUrl, $websiteUrl, 
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
                'partner_type', 'discord_url', 'twitter_url', 'website_url',
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
            $uploadPath = __DIR__ . '/../../public/img/partners/' . $filename;
            
            // Create directory if it doesn't exist
            $dir = dirname($uploadPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update database with new filename
                $field = $imageType === 'logo' ? 'logo_filename' : 'banner_filename';
                $stmt = $pdo->prepare("UPDATE tbl_partners SET $field = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$filename, $partnerId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => ucfirst($imageType) . ' uploaded successfully',
                    'filename' => $filename,
                    'url' => '/public/img/partners/' . $filename
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
                $filePath = __DIR__ . '/../../public/img/partners/' . $filename;
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

