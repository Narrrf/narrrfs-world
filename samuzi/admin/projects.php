<?php
/**
 * Samuzi Admin - Project Management
 * Handles CRUD operations for Samuzi project/showcase entries.
 */

// Suppress all output and warnings to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
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

    $projects = loadProjects($projects_file);

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
            ob_clean();
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ], JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            break;
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

function loadProjects(string $projects_file): array
{
    if (!file_exists($projects_file)) {
        return [];
    }

    $decoded_projects = json_decode(file_get_contents($projects_file), true);

    return is_array($decoded_projects) ? $decoded_projects : [];
}

function saveProjects(string $projects_file, array $projects): void
{
    $saved = file_put_contents(
        $projects_file,
        json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    if ($saved === false) {
        throw new Exception('Could not save projects file');
    }
}

function getJsonInput(): array
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new Exception('Invalid JSON input');
    }

    return $input;
}

function sanitizeText($value): string
{
    return trim((string)($value ?? ''));
}

function sanitizeArray($value): array
{
    return is_array($value) ? array_values($value) : [];
}

function validateRequiredFields(array $input): void
{
    $required_fields = ['title', 'location', 'project_type', 'description'];

    foreach ($required_fields as $field) {
        if (sanitizeText($input[$field] ?? '') === '') {
            throw new Exception("Missing required field: {$field}");
        }
    }
}

function getAdminUsername(): string
{
    return $_SESSION['admin_username'] ?? 'admin';
}

function writeAdminLog(string $message): void
{
    $log_entry = date('Y-m-d H:i:s') . ' - ' . $message . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
}

function handleGetProjects(array $projects): void
{
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

function handleCreateProject(array &$projects, string $projects_file): void
{
    $input = getJsonInput();
    validateRequiredFields($input);

    $admin_user = getAdminUsername();
    $now = date('Y-m-d H:i:s');

    $project = [
        'id' => 'project_' . date('Ymd_His') . '_' . uniqid(),
        'title' => sanitizeText($input['title']),
        'location' => sanitizeText($input['location']),
        'project_type' => sanitizeText($input['project_type']),
        'description' => sanitizeText($input['description']),
        'features' => sanitizeArray($input['features'] ?? []),
        'photos' => sanitizeArray($input['photos'] ?? []),

        // Internal legacy key kept for compatibility with existing admin/public JS.
        // Display label is "Supply / Scope".
        'pool_size' => sanitizeText($input['pool_size'] ?? ''),

        'completion_date' => sanitizeText($input['completion_date'] ?? date('Y-m-d')),
        'special_features' => sanitizeText($input['special_features'] ?? ''),
        'created_at' => $now,
        'updated_at' => $now,
        'created_by' => $admin_user
    ];

    $projects[] = $project;
    saveProjects($projects_file, $projects);

    writeAdminLog("Project created: {$project['title']} ({$project['id']}) by {$admin_user}");

    ob_clean();
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Project created successfully',
        'project' => $project
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function handleUpdateProject(array &$projects, string $projects_file): void
{
    $input = getJsonInput();

    if (sanitizeText($input['id'] ?? '') === '') {
        throw new Exception('Missing project ID');
    }

    validateRequiredFields($input);

    $project_id = sanitizeText($input['id']);
    $project_index = findProjectIndex($projects, $project_id);

    if ($project_index === -1) {
        throw new Exception('Project not found');
    }

    $admin_user = getAdminUsername();

    $projects[$project_index]['title'] = sanitizeText($input['title']);
    $projects[$project_index]['location'] = sanitizeText($input['location']);
    $projects[$project_index]['project_type'] = sanitizeText($input['project_type']);
    $projects[$project_index]['description'] = sanitizeText($input['description']);
    $projects[$project_index]['features'] = sanitizeArray($input['features'] ?? []);
    $projects[$project_index]['photos'] = sanitizeArray($input['photos'] ?? []);
    $projects[$project_index]['pool_size'] = sanitizeText($input['pool_size'] ?? '');
    $projects[$project_index]['completion_date'] = sanitizeText($input['completion_date'] ?? '');
    $projects[$project_index]['special_features'] = sanitizeText($input['special_features'] ?? '');
    $projects[$project_index]['updated_at'] = date('Y-m-d H:i:s');
    $projects[$project_index]['updated_by'] = $admin_user;

    saveProjects($projects_file, $projects);

    writeAdminLog("Project updated: {$projects[$project_index]['title']} ({$project_id}) by {$admin_user}");

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Project updated successfully',
        'project' => $projects[$project_index]
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function handleDeleteProject(array &$projects, string $projects_file): void
{
    $input = getJsonInput();

    if (sanitizeText($input['id'] ?? '') === '') {
        throw new Exception('Missing project ID');
    }

    $project_id = sanitizeText($input['id']);
    $project_index = findProjectIndex($projects, $project_id);

    if ($project_index === -1) {
        throw new Exception('Project not found');
    }

    $deleted_project = $projects[$project_index];

    array_splice($projects, $project_index, 1);
    saveProjects($projects_file, $projects);

    $admin_user = getAdminUsername();
    $deleted_title = sanitizeText($deleted_project['title'] ?? 'Untitled project');

    writeAdminLog("Project deleted: {$deleted_title} ({$project_id}) by {$admin_user}");

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Project deleted successfully',
        'deleted_project' => $deleted_project
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function findProjectIndex(array $projects, string $project_id): int
{
    foreach ($projects as $index => $project) {
        if (($project['id'] ?? '') === $project_id) {
            return $index;
        }
    }

    return -1;
}
?>