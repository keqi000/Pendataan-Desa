<?php
// API Client - Penghubung ke Sistem Pendataan-desa
// File: public-portal/api/core/api-client.php

require_once __DIR__ . '/../../config/base-url.php';

class ApiClient {

    private $baseUrl;

    public function __construct() {
        $this->baseUrl = MAIN_SYSTEM_API_URL . 'data-publik/public/';
    }

    public function get($endpoint, array $params = []) {
        $url = $this->buildUrl($endpoint, $params);
        return $this->httpGet($url);
    }

    private function httpGet($url) {
        // Coba cURL dulu, fallback ke file_get_contents
        if (function_exists('curl_init')) {
            return $this->curlGet($url);
        }
        return $this->fileGet($url);
    }

    private function curlGet($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: SipendesaPublicPortal/1.0',
                'X-Requested-With: XMLHttpRequest',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error || $status !== 200 || !$body) {
            $log = __DIR__ . '/../../cache/debug.txt';
            file_put_contents($log, date('Y-m-d H:i:s') . " URL=$url | HTTP=$status | error=$error | body=" . mb_substr($body, 0, 300) . PHP_EOL, FILE_APPEND);
            return null;
        }

        $data = json_decode($body, true);
        return ($data && isset($data['success']) && $data['success']) ? $data['data'] : null;
    }

    private function fileGet($url) {
        $ctx  = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);

        if (!$body) return null;

        $data = json_decode($body, true);
        return ($data && isset($data['success']) && $data['success']) ? $data['data'] : null;
    }

    private function buildUrl($endpoint, array $params) {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

}
?>
