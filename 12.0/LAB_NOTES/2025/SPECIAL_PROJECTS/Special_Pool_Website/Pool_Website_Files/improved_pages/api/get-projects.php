<?php
/**
 * Pool Website - Get Projects (Public API)
 * Returns all projects for the referenzen page (public access)
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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $projects_file = '../admin/projects.json';
    
    // Load existing projects
    $projects = [];
    if (file_exists($projects_file)) {
        $projects = json_decode(file_get_contents($projects_file), true) ?: [];
    }
    
    // Filter projects to only include public information
    $public_projects = [];
    foreach ($projects as $project) {
        $public_projects[] = [
            'id' => $project['id'],
            'title' => $project['title'],
            'location' => $project['location'],
            'project_type' => $project['project_type'],
            'description' => $project['description'],
            'features' => $project['features'] ?? [],
            'photos' => $project['photos'] ?? [],
            'completion_date' => $project['completion_date'],
            'pool_size' => $project['pool_size'] ?? '',
            'special_features' => $project['special_features'] ?? ''
        ];
    }
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'projects' => $public_projects,
        'count' => count($public_projects),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading projects: ' . $e->getMessage(),
        'projects' => [],
        'count' => 0
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>
