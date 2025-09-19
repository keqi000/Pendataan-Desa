<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$queries = new Queries();

$stmt = $queries->db->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $queries->db->prepare("
    SELECT u.*, d.nama_desa 
    FROM user u 
    LEFT JOIN desa d ON u.id_desa = d.id_desa 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $queries->db->prepare("SELECT * FROM desa ORDER BY nama_desa");
$stmt->execute();
$all_desa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get fresh desa data for management tab
$stmt = $queries->db->prepare("SELECT * FROM desa ORDER BY nama_desa");
$stmt->execute();
$desa_management = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Pengaturan Sistem';
    $current_page = 'pengaturan';
    $page_css = [BASE_URL . 'pages/admin/pengaturan/pengaturan.css'];
    $page_js = [BASE_URL . 'pages/admin/pengaturan/pengaturan.js'];
    ob_start();
}
?>

<div class="pengaturan-container">
    <div class="pengaturan-header">
        <h2><i class="fas fa-cog"></i> Pengaturan Sistem</h2>
        <p>Kelola profil, pengguna, dan informasi sistem</p>
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
        <button class="tab-btn" onclick="showTab('users')">
            <i class="fas fa-users"></i> Manajemen User
        </button>
        <button class="tab-btn" onclick="showTab('desa')">
            <i class="fas fa-map-marked-alt"></i> Manajemen Desa
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
                            <input type="password" id="oldPassword" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru:</label>
                            <input type="password" id="newPassword" required>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru:</label>
                            <input type="password" id="confirmPassword" required>
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
                            Bagaimana cara mengupload data GeoJSON?
                        </div>
                        <div class="faq-answer">
                            Masuk ke halaman Peta, klik tombol "Upload Batas Administratif", pilih file GeoJSON yang berisi FeatureCollection dengan properties kd_kecamatan, nm_kecamatan, dll.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <i class="fas fa-chevron-right"></i>
                            Bagaimana cara menambah user desa baru?
                        </div>
                        <div class="faq-answer">
                            Di tab Manajemen User, klik "Tambah User", isi form dengan lengkap, pilih desa yang sesuai, dan klik simpan.
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
                        <h4><i class="fas fa-map"></i> Mengelola Peta</h4>
                        <ol>
                            <li>Buka halaman "Peta & Geografis"</li>
                            <li>Klik "Upload Batas Administratif" untuk menambah boundary</li>
                            <li>Gunakan legend untuk show/hide layer</li>
                            <li>Klik tombol filter untuk fokus ke Kecamatan Tibawa</li>
                        </ol>
                    </div>
                    
                    <div class="guide-section">
                        <h4><i class="fas fa-users"></i> Mengelola Data Penduduk</h4>
                        <ol>
                            <li>Buka halaman "Kependudukan"</li>
                            <li>Tambah keluarga terlebih dahulu</li>
                            <li>Tambah anggota keluarga dengan NIK yang valid</li>
                            <li>Pastikan data lengkap untuk analisis yang akurat</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desa Tab -->
    <div id="desa-tab" class="tab-content">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-map-marked-alt"></i> Manajemen Desa</h3>
                <button class="btn btn-primary" onclick="showCreateDesaModal()">
                    <i class="fas fa-plus"></i> Tambah Desa
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Desa</th>
                                <th>Luas Wilayah</th>
                                <th>Jumlah Dusun</th>
                                <th>RT/RW</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($desa_management as $index => $desa): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($desa['nama_desa']); ?></td>
                                <td><?php echo number_format($desa['luas_wilayah'] ?? 0, 2); ?> ha</td>
                                <td><?php echo $desa['jumlah_dusun'] ?? 0; ?></td>
                                <td><?php echo ($desa['jumlah_rt'] ?? 0) . '/' . ($desa['jumlah_rw'] ?? 0); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDesa(<?php echo $desa['id_desa']; ?>, '<?php echo htmlspecialchars($desa['nama_desa']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Tab -->
    <div id="users-tab" class="tab-content">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Manajemen User</h3>
                <button class="btn btn-primary" onclick="showCreateUserModal()">
                    <i class="fas fa-plus"></i> Tambah User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Desa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $index => $user): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] === 'admin_kecamatan' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['nama_desa'] ?: '-'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['status_aktif'] === 'aktif' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status_aktif']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['id_user']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id_user'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id_user']; ?>, '<?php echo htmlspecialchars($user['nama_lengkap']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Desa Modal -->
<div class="modal" id="createDesaModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Desa Baru</h4>
                <button type="button" class="close" onclick="closeCreateDesaModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createDesaForm">
                    <div class="form-group">
                        <label>Nama Desa:</label>
                        <input type="text" id="createNamaDesa" required>
                    </div>
                    <div class="form-group">
                        <label>Luas Wilayah (ha):</label>
                        <input type="number" id="createLuasWilayah" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Dusun:</label>
                        <input type="number" id="createJumlahDusun" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah RT:</label>
                        <input type="number" id="createJumlahRT" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah RW:</label>
                        <input type="number" id="createJumlahRW" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateDesaModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="createDesa()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal" id="createUserModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah User Baru</h4>
                <button type="button" class="close" onclick="closeCreateUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createUserForm">
                    <div class="form-group">
                        <label>Nama Lengkap:</label>
                        <input type="text" id="createNamaLengkap" required>
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" id="createUsername" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="createEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" id="createPassword" required>
                    </div>
                    <div class="form-group">
                        <label>Role:</label>
                        <select id="createRole" required>
                            <option value="operator_desa">Operator Desa</option>
                            <option value="admin_kecamatan">Admin Kecamatan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Desa:</label>
                        <select id="createDesa">
                            <option value="">- Pilih Desa -</option>
                            <?php foreach ($all_desa as $desa): ?>
                            <option value="<?php echo $desa['id_desa']; ?>"><?php echo htmlspecialchars($desa['nama_desa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="createUser()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="editUserModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit User</h4>
                <button type="button" class="close" onclick="closeEditUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId">
                    <div class="form-group">
                        <label>Nama Lengkap:</label>
                        <input type="text" id="editNamaLengkap" required>
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" id="editUsername" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="editEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru (kosongkan jika tidak diubah):</label>
                        <input type="password" id="editPassword">
                    </div>
                    <div class="form-group">
                        <label>Role:</label>
                        <select id="editRole" required>
                            <option value="operator_desa">Operator Desa</option>
                            <option value="admin_kecamatan">Admin Kecamatan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Desa:</label>
                        <select id="editDesa">
                            <option value="">- Pilih Desa -</option>
                            <?php foreach ($all_desa as $desa): ?>
                            <option value="<?php echo $desa['id_desa']; ?>"><?php echo htmlspecialchars($desa['nama_desa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select id="editStatus" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Non-aktif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="updateUser()">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
// Pass BASE_URL to JavaScript
window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>