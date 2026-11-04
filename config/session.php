<?php
// Session Configuration untuk Sistem Pendataan Desa
class SessionManager {
    private static $instance = null;
    private $sessionTimeout = 7200; // 2 jam dalam detik
    private $warningTime = 300; // 5 menit sebelum timeout
    
    private function __construct() {
        $this->configureSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function configureSession() {
        // Only set session parameters if session is not active
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', $this->sessionTimeout);
            ini_set('session.cookie_lifetime', $this->sessionTimeout);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');
        }
        
        // Set session save path (optional)
        $sessionPath = __DIR__ . '/../sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }
        // session_save_path($sessionPath); // Comment out untuk gunakan default
    }
    
    public function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Regenerate session ID untuk security
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
            
            // Set session start time
            if (!isset($_SESSION['session_start'])) {
                $_SESSION['session_start'] = time();
            }
            
            // Update last activity
            $_SESSION['last_activity'] = time();
        }
        
        return $this->validateSession();
    }
    
    public function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $currentTime = time();
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = $currentTime - $_SESSION['last_activity'];
            
            if ($inactiveTime > $this->sessionTimeout) {
                $this->destroySession();
                return false;
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = $currentTime;
        
        return true;
    }
    
    public function refreshSession() {
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_activity'] = time();
            
            // Regenerate session ID setiap 30 menit untuk security
            if (!isset($_SESSION['last_regeneration']) || 
                (time() - $_SESSION['last_regeneration']) > 1800) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
            
            return true;
        }
        return false;
    }
    
    public function getSessionInfo() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $currentTime = time();
        $lastActivity = $_SESSION['last_activity'] ?? $currentTime;
        $timeLeft = $this->sessionTimeout - ($currentTime - $lastActivity);
        
        return [
            'user_id' => $_SESSION['user_id'],
            'user_name' => $_SESSION['user_name'],
            'user_role' => $_SESSION['user_role'],
            'time_left' => max(0, $timeLeft),
            'warning_threshold' => $this->warningTime,
            'is_warning' => $timeLeft <= $this->warningTime && $timeLeft > 0
        ];
    }
    
    public function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear all session data
            $_SESSION = array();
            
            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destroy session
            session_destroy();
            
            // Start new empty session to prevent issues
            session_start();
            session_regenerate_id(true);
        }
    }
    
    public function extendSession() {
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }
}

// Helper functions
function initSession() {
    return SessionManager::getInstance()->startSession();
}

function validateSession() {
    return SessionManager::getInstance()->validateSession();
}

function refreshSession() {
    return SessionManager::getInstance()->refreshSession();
}

function getSessionInfo() {
    return SessionManager::getInstance()->getSessionInfo();
}

function destroySession() {
    SessionManager::getInstance()->destroySession();
}

function extendSession() {
    return SessionManager::getInstance()->extendSession();
}
?>