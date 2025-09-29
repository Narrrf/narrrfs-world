<?php
/**
 * Pool Website Admin - Project Management
 * Handles CRUD operations for pool projects (Referenzen)
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $projects_file = 'projects.json';
    
    // Load existing projects
    $projects = [];
    if (file_exists($projects_file)) {
        $projects = json_decode(file_get_contents($projects_file), true) ?: [];
    }
    
    switch ($method) {
        case 'GET':
            handleGetProjects($projects);
            break;
        case 'POST':
            handleCreateProject($projects, $projects_file);
            break;
        case 'PUT':
            handleUpdateProject($projects, $projects_file);
            break;
        case 'DELETE':
            handleDeleteProject($projects, $projects_file);
            break;
        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function handleGetProjects($projects) {
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'count' => count($projects),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function handleCreateProject(&$projects, $projects_file) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required_fields = ['title', 'location', 'project_type', 'description'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Generate new project ID
    $new_id = 'project_' . date('Ymd_His') . '_' . uniqid();
    
    // Create project object
    $project = [
        'id' => $new_id,
        'title' => trim($input['title']),
        'location' => trim($input['location']),
        'project_type' => trim($input['project_type']),
        'description' => trim($input['description']),
        'features' => $input['features'] ?? [],
        'photos' => $input['photos'] ?? [],
        'completion_date' => $input['completion_date'] ?? date('Y-m-d'),
        'pool_size' => $input['pool_size'] ?? '',
        'special_features' => $input['special_features'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'created_by' => $_SESSION['admin_username']
    ];
    
    // Add to projects array
    $projects[] = $project;
    
    // Save to file
    file_put_contents($projects_file, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log creation
    $log_entry = date('Y-m-d H:i:s') . " - Project created: " . $project['title'] . " (" . $new_id . ") by " . $_SESSION['admin_username'] . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    ob_clean();
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Project created successfully',
        'project' => $project
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function handleUpdateProject(&$projects, $projects_file) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        throw new Exception('Invalid input or missing project ID');
    }
    
    $project_id = $input['id'];
    $project_index = -1;
    
    // Find project
    foreach ($projects as $index => $project) {
        if ($project['id'] === $project_id) {
            $project_index = $index;
            break;
        }
    }
    
    if ($project_index === -1) {
        throw new Exception('Project not found');
    }
    
    // Update project
    $projects[$project_index]['title'] = trim($input['title'] ?? $projects[$project_index]['title']);
    $projects[$project_index]['location'] = trim($input['location'] ?? $projects[$project_index]['location']);
    $projects[$project_index]['project_type'] = trim($input['project_type'] ?? $projects[$project_index]['project_type']);
    $projects[$project_index]['description'] = trim($input['description'] ?? $projects[$project_index]['description']);
    $projects[$project_index]['features'] = $input['features'] ?? $projects[$project_index]['features'];
    $projects[$project_index]['photos'] = $input['photos'] ?? $projects[$project_index]['photos'];
    $projects[$project_index]['completion_date'] = $input['completion_date'] ?? $projects[$project_index]['completion_date'];
    $projects[$project_index]['pool_size'] = $input['pool_size'] ?? $projects[$project_index]['pool_size'];
    $projects[$project_index]['special_features'] = $input['special_features'] ?? $projects[$project_index]['special_features'];
    $projects[$project_index]['updated_at'] = date('Y-m-d H:i:s');
    $projects[$project_index]['updated_by'] = $_SESSION['admin_username'];
    
    // Save to file
    file_put_contents($projects_file, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log update
    $log_entry = date('Y-m-d H:i:s') . " - Project updated: " . $projects[$project_index]['title'] . " (" . $project_id . ") by " . $_SESSION['admin_username'] . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Project updated successfully',
        'project' => $projects[$project_index]
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function handleDeleteProject(&$projects, $projects_file) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        throw new Exception('Invalid input or missing project ID');
    }
    
    $project_id = $input['id'];
    $project_index = -1;
    $deleted_project = null;
    
    // Find and remove project
    foreach ($projects as $index => $project) {
        if ($project['id'] === $project_id) {
            $project_index = $index;
            $deleted_project = $project;
            break;
        }
    }
    
    if ($project_index === -1) {
        throw new Exception('Project not found');
    }
    
    // Remove project
    array_splice($projects, $project_index, 1);
    
    // Save to file
    file_put_contents($projects_file, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log deletion
    $log_entry = date('Y-m-d H:i:s') . " - Project deleted: " . $deleted_project['title'] . " (" . $project_id . ") by " . $_SESSION['admin_username'] . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Project deleted successfully',
        'deleted_project' => $deleted_project
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
?>
