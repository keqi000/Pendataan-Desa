<?php
// API Authentication - Sistem Informasi Publik
// File: public-portal/api/auth/api-auth.php

require_once __DIR__ . '/../core/api-base.php';

class ApiAuth extends ApiBase {

    public function validateKey() {
        if (!$this->apiKey) {
            ResponseHandler::error('API key required', 401);
        }

        if (!$this->isValidApiKey()) {
            ResponseHandler::error('Invalid API key', 403);
        }

        $info = ApiConfig::getApiKeyInfo($this->apiKey);
        ResponseHandler::success([
            'valid'       => true,
            'name'        => $info['name'],
            'rate_limit'  => $info['rate_limit'],
            'permissions' => $info['permissions']
        ], 'API key valid');
    }
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $auth = new ApiAuth();
    $auth->validateKey();
}
?>
