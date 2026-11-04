<?php
$functions = new PengaturanFunctions();
$current_user = $functions->getCurrentUser($_SESSION['user_id']);
$all_users = $functions->getAllUsers();
$all_desa = $functions->getAllDesa();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Pengaturan Sistem';
    $current_page = 'pengaturan';
    $page_css = [BASE_URL . 'pages/admin/pengaturan/pengaturan.css'];
    $page_js = [
        BASE_URL . 'pages/admin/pengaturan/js/pengaturan-core.js',
        BASE_URL . 'pages/admin/pengaturan/js/pengaturan-faq.js', 
        BASE_URL . 'pages/admin/pengaturan/js/pengaturan-users.js',
        BASE_URL . 'pages/admin/pengaturan/js/pengaturan-desa.js',
        BASE_URL . 'pages/admin/pengaturan/js/pengaturan-pekerjaan.js',
        BASE_URL . 'pages/admin/pengaturan/pengaturan.js'
    ];
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
        <button class="tab-btn" onclick="showTab('pekerjaan')">
            <i class="fas fa-briefcase"></i> Manajemen Pekerjaan
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
                            <div class="password-input-group">
                                <input type="password" id="oldPassword" required>
                                <span class="password-toggle" onclick="togglePassword('oldPassword')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password Baru:</label>
                            <div class="password-input-group">
                                <input type="password" id="newPassword" required>
                                <span class="password-toggle" onclick="togglePassword('newPassword')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru:</label>
                            <div class="password-input-group">
                                <input type="password" id="confirmPassword" required>
                                <span class="password-toggle" onclick="togglePassword('confirmPassword')">
                                    <i class="fas fa-eye"></i>
                                </span>
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
                            Sistem informasi berbasis web untuk mengelola data desa secara terpadu, meliputi data
                            kependudukan, ekonomi, pendidikan, dan infrastruktur di Kecamatan Tibawa.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <i class="fas fa-chevron-right"></i>
                            Bagaimana cara mengupload data GeoJSON?
                        </div>
                        <div class="faq-answer">
                            Masuk ke halaman Peta, klik tombol "Upload Batas Administratif", pilih file GeoJSON yang
                            berisi FeatureCollection dengan properties kd_kecamatan, nm_kecamatan, dll.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <i class="fas fa-chevron-right"></i>
                            Bagaimana cara menambah user desa baru?
                        </div>
                        <div class="faq-answer">
                            Di tab Manajemen User, klik "Tambah User", isi form dengan lengkap, pilih desa yang sesuai,
                            dan klik simpan.
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
                            <?php foreach ($all_desa as $index => $desa): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($desa['nama_desa']); ?></td>
                                <td><?php echo number_format($desa['luas_wilayah'] ?? 0, 2); ?> ha</td>
                                <td><?php echo $desa['jumlah_dusun'] ?? 0; ?></td>
                                <td><?php echo ($desa['jumlah_rt'] ?? 0) . '/' . ($desa['jumlah_rw'] ?? 0); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editDesa(<?php echo $desa['id_desa']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
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

    <!-- Pekerjaan Tab -->
    <div id="pekerjaan-tab" class="tab-content">
        <!-- Sub tabs untuk pekerjaan -->
        <div class="pengaturan-tabs" style="margin-bottom: 20px;">
            <button class="tab-btn active" onclick="showPekerjaanSubTab('master')">
                <i class="fas fa-list"></i> Master Pekerjaan
            </button>
            <button class="tab-btn" onclick="showPekerjaanSubTab('review')">
                <i class="fas fa-clock"></i> Perlu Review
            </button>
            <button class="tab-btn" onclick="showPekerjaanSubTab('reviewed')">
                <i class="fas fa-check-circle"></i> Mapping Direview
            </button>
            <button class="tab-btn" onclick="showPekerjaanSubTab('statistik')">
                <i class="fas fa-chart-bar"></i> Statistik
            </button>
        </div>
        
        <!-- Master Pekerjaan Sub Tab -->
        <div id="master-subtab" class="pekerjaan-subtab active">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-briefcase"></i> Master Pekerjaan</h3>
                    <div>
                        <button class="btn btn-warning" onclick="scanExistingData()" style="margin-right: 8px;">
                            <i class="fas fa-search"></i> Scan Data Existing
                        </button>
                        <button class="btn btn-success" onclick="transformExistingData()" style="margin-right: 8px;">
                            <i class="fas fa-sync-alt"></i> Transform Data Existing
                        </button>
                        <button class="btn btn-primary" onclick="showCreatePekerjaanModal()">
                            <i class="fas fa-plus"></i> Tambah Pekerjaan
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pekerjaan</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pekerjaanTableBody">
                                <!-- Data akan dimuat via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Review Sub Tab -->
        <div id="review-subtab" class="pekerjaan-subtab">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Pekerjaan Perlu Review</h3>
                    <button class="btn btn-info" onclick="loadReviewData()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div id="reviewDataContainer">
                        <p class="text-center">Klik refresh untuk memuat data yang perlu direview</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviewed Sub Tab -->
        <div id="reviewed-subtab" class="pekerjaan-subtab">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-check-circle"></i> Mapping yang Sudah Direview</h3>
                    <button class="btn btn-info" onclick="loadReviewedData()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div id="reviewedDataContainer">
                        <p class="text-center">Klik refresh untuk memuat data mapping yang sudah direview</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistik Sub Tab -->
        <div id="statistik-subtab" class="pekerjaan-subtab">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Statistik Pekerjaan</h3>
                    <button class="btn btn-info" onclick="loadStatistikData()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-md-3">
                            <div class="stat-card" style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 id="totalMapping">-</h4>
                                <p>Total Mapping</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 id="approvedMapping">-</h4>
                                <p>Mapping Disetujui</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: #fff3e0; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 id="pendingMapping">-</h4>
                                <p>Perlu Review</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="background: #f3e5f5; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 id="masterPekerjaan">-</h4>
                                <p>Master Pekerjaan</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h4>Distribusi Pekerjaan di Database</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Pekerjaan</th>
                                            <th>Jumlah Penduduk</th>
                                            <th>Persentase</th>
                                        </tr>
                                    </thead>
                                    <tbody id="statistikTableBody">
                                        <tr>
                                            <td colspan="4" class="text-center">Klik refresh untuk memuat data statistik</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Pekerjaan Modal -->
<div id="createPekerjaanModal" class="kependudukan-modal">
    <div class="kependudukan-modal-content">
        <div class="kependudukan-modal-header">
            <h3>Tambah Pekerjaan Baru</h3>
            <button class="kependudukan-modal-close" onclick="closeCreatePekerjaanModal()">&times;</button>
        </div>
        <form id="createPekerjaanForm" class="kependudukan-modal-body">
            <div class="kependudukan-form-grid">
                <div class="kependudukan-form-group">
                    <label>Nama Pekerjaan *</label>
                    <input type="text" id="createNamaPekerjaan" required>
                </div>
                <div class="kependudukan-form-group">
                    <label>Kategori *</label>
                    <select id="createKategori" required>
                        <option value="">- Pilih Kategori -</option>
                        <option value="Pertanian">Pertanian</option>
                        <option value="Perdagangan">Perdagangan</option>
                        <option value="Jasa">Jasa</option>
                        <option value="Industri">Industri</option>
                        <option value="Pemerintahan">Pemerintahan</option>
                        <option value="Pendidikan">Pendidikan</option>
                        <option value="Kesehatan">Kesehatan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="kependudukan-modal-footer">
            <button type="button" class="kependudukan-btn-secondary" onclick="closeCreatePekerjaanModal()">Batal</button>
            <button type="button" class="kependudukan-btn-primary" onclick="createPekerjaan()">Simpan</button>
        </div>
    </div>
</div>

<!-- Edit Pekerjaan Modal -->
<div id="editPekerjaanModal" class="kependudukan-modal">
    <div class="kependudukan-modal-content">
        <div class="kependudukan-modal-header">
            <h3>Edit Pekerjaan</h3>
            <button class="kependudukan-modal-close" onclick="closeEditPekerjaanModal()">&times;</button>
        </div>
        <form id="editPekerjaanForm" class="kependudukan-modal-body">
            <input type="hidden" id="editPekerjaanId">
            <div class="kependudukan-form-grid">
                <div class="kependudukan-form-group">
                    <label>Nama Pekerjaan *</label>
                    <input type="text" id="editNamaPekerjaan" required>
                </div>
                <div class="kependudukan-form-group">
                    <label>Kategori *</label>
                    <select id="editKategori" required>
                        <option value="">- Pilih Kategori -</option>
                        <option value="Pertanian">Pertanian</option>
                        <option value="Perdagangan">Perdagangan</option>
                        <option value="Jasa">Jasa</option>
                        <option value="Industri">Industri</option>
                        <option value="Pemerintahan">Pemerintahan</option>
                        <option value="Pendidikan">Pendidikan</option>
                        <option value="Kesehatan">Kesehatan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="kependudukan-form-group">
                    <label>Status *</label>
                    <select id="editStatusPekerjaan" required>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non-aktif</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="kependudukan-modal-footer">
            <button type="button" class="kependudukan-btn-secondary" onclick="closeEditPekerjaanModal()">Batal</button>
            <button type="button" class="kependudukan-btn-primary" onclick="updatePekerjaan()">Update</button>
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

<!-- Edit Desa Modal -->
<div class="modal" id="editDesaModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Desa</h4>
                <button type="button" class="close" onclick="closeEditDesaModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editDesaForm">
                    <input type="hidden" id="editDesaId">
                    <div class="form-group">
                        <label>Nama Desa:</label>
                        <input type="text" id="editNamaDesa" required>
                    </div>
                    <div class="form-group">
                        <label>Luas Wilayah (ha):</label>
                        <input type="number" id="editLuasWilayah" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Dusun:</label>
                        <input type="number" id="editJumlahDusun" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah RT:</label>
                        <input type="number" id="editJumlahRT" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah RW:</label>
                        <input type="number" id="editJumlahRW" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditDesaModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="updateDesa()">Update</button>
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
                        <div class="password-input-group">
                            <input type="password" id="createPassword" required>
                            <span class="password-toggle" onclick="togglePassword('createPassword')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
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
                        <div class="password-input-group">
                            <input type="password" id="editPassword">
                            <span class="password-toggle" onclick="togglePassword('editPassword')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
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

// Password toggle function
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
}
?>