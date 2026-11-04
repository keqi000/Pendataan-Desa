<?php
// Main peta entry point - handle both view and API requests
require_once __DIR__ . '/php/peta-functions.php';

if (isset($_GET['endpoint'])) {
    // Handle API request
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: application/json');
    $petaFunctions = new PetaFunctions();
    echo $petaFunctions->handleApiRequest();
    exit();
} elseif (isset($_FILES['administrative_boundaries_file'])) {
    // Handle upload request
    $petaFunctions = new PetaFunctions();
    $petaFunctions->checkAuthentication();
    $petaFunctions->handleAdministrativeBoundariesUpload();
} else {
    // Handle normal page view
    require_once __DIR__ . '/php/peta-view.php';
}
?>