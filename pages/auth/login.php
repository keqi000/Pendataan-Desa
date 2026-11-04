<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../database/queries.php';

// Initialize session dengan session manager
initSession();

// Redirect jika sudah login dan session valid
if (validateSession() && isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin_kecamatan') {
        header('Location: ' . BASE_URL . 'pages/admin/dashboard/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'pages/user/dashboard/dashboard.php');
    }
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password harus diisi';
    } else {
        $queries = new Queries();
        $user = $queries->getUserByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            // Destroy any existing session first
            destroySession();
            
            // Start fresh session
            initSession();
            
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_name'] = $user['nama_lengkap'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_desa'] = $user['id_desa'];
            $_SESSION['desa_name'] = $user['nama_desa'];
            $_SESSION['login_time'] = time();
            
            $queries->updateLastLogin($user['id_user']);
            
            if ($user['role'] === 'admin_kecamatan') {
                header('Location: ' . BASE_URL . 'pages/admin/dashboard/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . 'pages/user/dashboard/dashboard.php');
            }
            exit();
        } else {
            $error_message = 'Username atau password salah';
        }
    }
}

$page_title = 'Login - Sistem Pendataan Desa';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>global.css">
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-page">
    <!-- Page Loader -->
    <div class="page-loader">
        <div class="loader-spinner"></div>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-map-marked-alt"></i>
                    <h1>Sistem Pendataan Desa</h1>
                </div>
                <p class="subtitle">Kecamatan Tibawa, Kabupaten Gorontalo</p>
            </div>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form validate-form">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Masukkan username"
                        value="<?php echo htmlspecialchars($username ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Masukkan password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Masuk
                </button>
            </form>

            <div style="text-align:center; margin-top:16px;">
                <a href="<?php echo PUBLIC_PORTAL_URL; ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
                    <i class="fas fa-globe"></i>
                    Lihat Portal Publik
                </a>
            </div>

        </div>
        
        <div class="login-bg">
            <div class="bg-overlay"></div>
            <div class="bg-content">
                <h2>Sistem Informasi Analisis dan Pelaporan Data Desa Terintegrasi</h2>
                <p>Mendukung kebijakan pemerintah pusat melalui data yang akurat dan terintegrasi</p>
                <div class="features">
                    <div class="feature-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analisis Data Real-time</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Pelaporan Otomatis</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-sync-alt"></i>
                        <span>Integrasi Desa-Kecamatan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?php echo JS_URL; ?>global.js"></script>
    <script src="login.js"></script>
</body>
</html>