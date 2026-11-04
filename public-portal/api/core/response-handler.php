<?php
// Response Handler - Sistem Informasi Publik
// File: public-portal/api/core/response-handler.php

class ResponseHandler {

    public static function success($data, $message = 'OK', $meta = []) {
        $response = [
            'success'   => true,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date('c')
        ];
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    public static function error($message, $code = 400, $details = null) {
        http_response_code($code);
        $response = [
            'success'   => false,
            'message'   => $message,
            'timestamp' => date('c')
        ];
        if ($details) {
            $response['details'] = $details;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function paginated($data, $total, $page, $limit) {
        self::success($data, 'OK', [
            'total'        => (int)$total,
            'page'         => (int)$page,
            'limit'        => (int)$limit,
            'total_pages'  => (int)ceil($total / $limit)
        ]);
    }
}
?>
