<?php
require_once __DIR__ . '/php/monitoring-functions.php';

// Set JSON header for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
}

$monitoringFunctions = new MonitoringFunctions();
$monitoringFunctions->checkAuthentication();

// Handle AJAX requests
if ($monitoringFunctions->isAjaxRequest() && isset($_GET['endpoint'])) {
    echo $monitoringFunctions->handleAjaxRequest();
    exit();
}

// Main monitoring entry point
require_once __DIR__ . '/php/monitoring-view.php';
?>