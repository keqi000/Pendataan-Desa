<?php
require_once __DIR__ . '/pengaturan-functions.php';

$pengaturanFunctions = new PengaturanFunctions();
$pengaturanFunctions->checkAuthentication();
$pengaturanFunctions->handleChangePassword();

$current_user = $pengaturanFunctions->getCurrentUser();
$is_ajax = $pengaturanFunctions->isAjaxRequest();

if (!$is_ajax) {
    $page_title = 'Pengaturan';
    $current_page = 'pengaturan';
    $page_css = [BASE_URL . 'pages/user/pengaturan/pengaturan.css'];
    $page_js = [BASE_URL . 'pages/user/pengaturan/pengaturan.js'];
    ob_start();
}
?>

<div class="pengaturan-container">
    <div class="pengaturan-header">
        <h2><i class="fas fa-cog"></i> Pengaturan</h2>
        <p>Kelola profil dan informasi akun Anda</p>
    </div>

    <div class="pengaturan-tabs">
        <button class="tab-btn active" onclick="showTab('profil')">
            <i class="fas fa-user"></i> Profil Pengguna
        </button>
        <button class="tab-btn" onclick="showTab('faq')">
            <i class="fas fa-question-circle"></i> FAQ
        </button>
        <button class="tab-btn" onclick="showTab('panduan')">
            <i class="fas fa-book"></i> Panduan
        </button>
    </div>

    <!-- Profil Tab -->
    <div id="profil-tab" class="tab-content active">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-circle"></i> Informasi Profil</h3>
            </div>
            <div class="card-body">
                <div class="profile-info">
                    <div class="profile-item">
                        <label>Nama Lengkap:</label>
                        <span><?php echo htmlspecialchars($current_user['nama_lengkap']); ?></span>
                    </div>
                    <div class="profile-item">
                        <label>Username:</label>
                        <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                    </div>
                    <div class="profile-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($current_user['email']); ?></span>
                    </div>
                    <div class="profile-item">
                        <label>Role:</label>
                        <span class="badge badge-primary"><?php echo ucfirst(str_replace('_', ' ', $current_user['role'])); ?></span>
                    </div>
                    <div class="profile-item">
                        <label>Login Terakhir:</label>
                        <span><?php echo $current_user['last_login'] ? date('d/m/Y H:i', strtotime($current_user['last_login'])) : 'Belum pernah'; ?></span>
                    </div>
                </div>
                
                <div class="change-password-section">
                    <h4><i class="fas fa-key"></i> Ubah Password</h4>
                    <form id="changePasswordForm">
                        <div class="form-group">
                            <label>Password Lama:</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="oldPassword" required>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('oldPassword')"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password Baru:</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="newPassword" required>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('newPassword')"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru:</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirmPassword" required>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirmPassword')"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Tab -->
    <div id="faq-tab" class="tab-content">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-question-circle"></i> FAQ</h3>
            </div>
            <div class="card-body">
                <div class="faq-list">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <i class="fas fa-chevron-right"></i>
                            Apa itu Sistem Informasi Pendataan Desa Terintegrasi?
                        </div>
                        <div class="faq-answer">
                            Sistem informasi berbasis web untuk mengelola data desa secara terpadu, meliputi data kependudukan, ekonomi, pendidikan, dan infrastruktur di Kecamatan Tibawa.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <i class="fas fa-chevron-right"></i>
                            Bagaimana cara menambah data penduduk?
                        </div>
                        <div class="faq-answer">
                            Masuk ke halaman Kependudukan, tambah keluarga terlebih dahulu, kemudian tambah anggota keluarga dengan NIK yang valid. Pastikan data lengkap untuk analisis yang akurat.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <i class="fas fa-chevron-right"></i>
                            Bagaimana cara mengubah password?
                        </div>
                        <div class="faq-answer">
                            Di tab Profil Pengguna, scroll ke bawah ke bagian "Ubah Password", masukkan password lama dan password baru, lalu klik "Ubah Password".
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <i class="fas fa-chevron-right"></i>
                            Bagaimana cara mengelola data ekonomi desa?
                        </div>
                        <div class="faq-answer">
                            Buka halaman Ekonomi, Anda dapat menambah data UMKM, mata pencaharian penduduk, dan data bantuan sosial. Pastikan data yang dimasukkan akurat dan terkini.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan Tab -->
    <div id="panduan-tab" class="tab-content">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book"></i> Panduan Penggunaan</h3>
            </div>
            <div class="card-body">
                <div class="guide-sections">
                    <div class="guide-section">
                        <h4><i class="fas fa-users"></i> Mengelola Data Penduduk</h4>
                        <ol>
                            <li>Buka halaman "Kependudukan"</li>
                            <li>Tambah keluarga terlebih dahulu dengan mengisi data kepala keluarga</li>
                            <li>Tambah anggota keluarga dengan NIK yang valid</li>
                            <li>Pastikan data lengkap untuk analisis yang akurat</li>
                            <li>Gunakan fitur filter untuk mencari data dengan mudah</li>
                        </ol>
                    </div>
                    
                    <div class="guide-section">
                        <h4><i class="fas fa-chart-line"></i> Mengelola Data Ekonomi</h4>
                        <ol>
                            <li>Buka halaman "Ekonomi"</li>
                            <li>Pilih tab sesuai jenis data (UMKM, Mata Pencaharian, Bantuan Sosial)</li>
                            <li>Klik tombol "Tambah" untuk menambah data baru</li>
                            <li>Isi form dengan lengkap dan akurat</li>
                            <li>Gunakan fitur edit/hapus untuk mengelola data yang sudah ada</li>
                        </ol>
                    </div>
                    
                    <div class="guide-section">
                        <h4><i class="fas fa-graduation-cap"></i> Mengelola Data Pendidikan</h4>
                        <ol>
                            <li>Buka halaman "Pendidikan"</li>
                            <li>Tambah data fasilitas pendidikan di desa</li>
                            <li>Masukkan informasi lengkap tentang sekolah/lembaga pendidikan</li>
                            <li>Update data secara berkala untuk menjaga akurasi</li>
                        </ol>
                    </div>
                    
                    <div class="guide-section">
                        <h4><i class="fas fa-road"></i> Mengelola Data Infrastruktur</h4>
                        <ol>
                            <li>Buka halaman "Infrastruktur"</li>
                            <li>Pilih tab Jalan atau Jembatan sesuai kebutuhan</li>
                            <li>Tambah data infrastruktur dengan kondisi terkini</li>
                            <li>Sertakan informasi detail seperti panjang, lebar, dan kondisi</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
}
?>