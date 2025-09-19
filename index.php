<?php
require_once 'config/config.php';
session_start();

// Redirect ke dashboard jika sudah login
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin_kecamatan') {
        header('Location: ' . BASE_URL . 'pages/admin/dashboard/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'pages/user/dashboard/dashboard.php');
    }
    exit();
}

// Redirect ke login jika belum login
header('Location: ' . BASE_URL . 'pages/auth/login.php');
exit();
?>