<?php
// === THREE.JS ASSET UPLOAD ENDPOINT ===
// Allows Discord bot or authenticated requests to upload assets to /data/ (persistent storage)
// Date: January 4, 2026

// Increase PHP upload limits for large asset uploads (up to 500MB)
// Fallback if .htaccess doesn't work
@ini_set('upload_max_filesize', '512M');
@ini_set('post_max_size', '600M');
@ini_set('max_execution_time', '1800'); // 30 minutes for large uploads
@ini_set('max_input_time', '1800');
@ini_set('memory_limit', '1024M');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Function to safely output JSON and exit
function outputJson($data) {
    if (function_exists('ob_end_clean')) @ob_end_clean();
    if (function_exists('ob_clean')) @ob_clean();
    echo json_encode($data);
    exit;
}

// --- AUTHENTICATION (Same as Discord bot) ---
function get_auth_token() {
    $headers = [];
    if (function_exists('getallheaders')) $headers = getallheaders();
    foreach (['Authorization', 'authorization'] as $key) {
        if (isset($headers[$key])) return $headers[$key];
    }
    // Also check GET parameter for curl commands
    if (isset($_GET['token'])) return $_GET['token'];
    return '';
}

$auth_token = get_auth_token();
$valid_tokens = [
    $_ENV['DISCORD_BOT_SECRET'] ?? getenv('DISCORD_BOT_SECRET'),
    $_ENV['DISCORD_SECRET'] ?? getenv('DISCORD_SECRET')
];

// Remove empty values
$valid_tokens = array_filter($valid_tokens);

if (empty($valid_tokens) || !in_array($auth_token, $valid_tokens)) {
    http_response_code(401);
    outputJson(['success' => false, 'error' => 'Unauthorized - Invalid token']);
}

// --- VALIDATE FILE UPLOAD ---
if (!isset($_FILES['file'])) {
    http_response_code(400);
    outputJson(['success' => false, 'error' => 'No file uploaded - $_FILES[file] not set', 'debug' => ['files' => array_keys($_FILES), 'post' => array_keys($_POST)]]);
}

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $errorMsg = $errorMessages[$_FILES['file']['error']] ?? 'Unknown upload error: ' . $_FILES['file']['error'];
    http_response_code(400);
    outputJson(['success' => false, 'error' => $errorMsg, 'error_code' => $_FILES['file']['error']]);
}

$uploadedFile = $_FILES['file'];
$targetPath = $_POST['target_path'] ?? '';

// Validate target path (security: only allow paths under /data/public/three.js/public/)
if (empty($targetPath)) {
    http_response_code(400);
    outputJson(['success' => false, 'error' => 'target_path parameter required']);
}

// Normalize path and ensure it's under /data/public/three.js/public/
$targetPath = str_replace('\\', '/', $targetPath);
$basePath = '/data/public/three.js/public/';

// Security: Ensure path is within allowed directory
if (strpos($targetPath, $basePath) !== 0) {
    // If relative path, prepend base path
    if (strpos($targetPath, '/') !== 0) {
        $targetPath = $basePath . ltrim($targetPath, '/');
    } else {
        http_response_code(400);
        outputJson(['success' => false, 'error' => 'Invalid target path - must be under /data/public/three.js/public/']);
    }
}

// Ensure target directory exists
$targetDir = dirname($targetPath);
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        http_response_code(500);
        outputJson(['success' => false, 'error' => 'Failed to create target directory: ' . $targetDir]);
    }
}

// Set proper permissions on directory
@chmod($targetDir, 0755);
// Note: chown may not work on all systems, but chmod should be sufficient

// Move uploaded file to target location
if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
    // Set proper permissions
    @chmod($targetPath, 0644);
    // Note: chown may not work on all systems, but chmod should be sufficient
    
    outputJson([
        'success' => true,
        'message' => 'File uploaded successfully',
        'target_path' => $targetPath,
        'file_size' => filesize($targetPath),
        'upload_info' => [
            'original_name' => $uploadedFile['name'],
            'uploaded_size' => $uploadedFile['size'],
            'mime_type' => $uploadedFile['type']
        ]
    ]);
} else {
    http_response_code(500);
    outputJson(['success' => false, 'error' => 'Failed to move uploaded file to target location']);
}
?>

