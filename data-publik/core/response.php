<?php
// Helper: set CORS + JSON header, lalu kirim response

function setCorsHeaders() {
    // Izinkan public-portal dari domain manapun membaca endpoint ini
    // Di production, ganti * dengan domain spesifik public portal
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
    header('Content-Type: application/json; charset=UTF-8');

    // Preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function jsonSuccess($data, $message = 'OK') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data'    => null
    ]);
    exit;
}
