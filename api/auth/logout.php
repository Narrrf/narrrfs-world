<?php
header('Content-Type: application/json');
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

echo json_encode(['success' => true]); 