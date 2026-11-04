<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/php/pengaturan-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$functions = new PengaturanFunctions();

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && 
    isset($_GET['endpoint'])) {
    echo $functions->handleAjaxRequest();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode($functions->handleRequest());
    exit();
}

// Handle GET requests for specific data
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    switch ($_GET['action']) {
        case 'get_user':
            $user_id = $_GET['id'] ?? 0;
            echo json_encode(['success' => true, 'data' => $functions->getUserById($user_id)]);
            break;
        case 'get_desa':
            $desa_id = $_GET['id'] ?? 0;
            echo json_encode(['success' => true, 'data' => $functions->getDesaById($desa_id)]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action not found']);
    }
    exit();
}

// Load view
require_once __DIR__ . '/php/pengaturan-view.php';
?>