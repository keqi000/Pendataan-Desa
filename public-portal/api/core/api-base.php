<?php
// Base API Class - Sistem Informasi Publik
// File: public-portal/api/core/api-base.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/api-config.php';
require_once __DIR__ . '/response-handler.php';

class ApiBase {
    protected $db;
    protected $apiKey;

    public function __construct() {
        $this->apiKey = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        $this->setCorsHeaders();

        try {
            $database = new PublicDatabase();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            $this->db = null;
        }
    }

    protected function setCorsHeaders() {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    protected function getRateLimit() {
        return ApiConfig::getRateLimit($this->apiKey);
    }

    protected function isValidApiKey() {
        return $this->apiKey && ApiConfig::validateApiKey($this->apiKey);
    }

    protected function filterSensitiveFields(array $data) {
        $sensitive = ApiConfig::getSensitiveFields();
        foreach ($sensitive as $field) {
            unset($data[$field]);
        }
        return $data;
    }

    protected function getPaginationParams() {
        return [
            'page'  => max(1, (int)($_GET['page'] ?? 1)),
            'limit' => min(100, max(1, (int)($_GET['limit'] ?? 20)))
        ];
    }
}
?>
