// Pengaturan JavaScript
class PengaturanComponent {
    constructor() {
        this.init();
    }
    
    init() {
        console.log('🔧 Initializing Pengaturan Component');
        this.bindEvents();
    }
    
    bindEvents() {
        // Change password form
        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', this.handleChangePassword.bind(this));
        }
        
        // Create user form
        const createUserForm = document.getElementById('createUserForm');
        if (createUserForm) {
            createUserForm.addEventListener('submit', this.handleCreateUser.bind(this));
        }
    }
    
    async handleChangePassword(e) {
        e.preventDefault();
        
        const oldPassword = document.getElementById('oldPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            alert('Password baru dan konfirmasi password tidak sama!');
            return;
        }
        
        if (newPassword.length < 6) {
            alert('Password baru minimal 6 karakter!');
            return;
        }
        
        try {
            const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/change-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    old_password: oldPassword,
                    new_password: newPassword
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Password berhasil diubah!');
                document.getElementById('changePasswordForm').reset();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
        }
    }
    
    async handleCreateUser(e) {
        e.preventDefault();
        // Handled by createUser() function
    }
}

// Global functions
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active to clicked button
    event.target.classList.add('active');
}

function toggleFaq(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('i');
    
    if (answer.classList.contains('show')) {
        answer.classList.remove('show');
        element.classList.remove('active');
    } else {
        // Close all other FAQs
        document.querySelectorAll('.faq-answer').forEach(ans => {
            ans.classList.remove('show');
        });
        document.querySelectorAll('.faq-question').forEach(q => {
            q.classList.remove('active');
        });
        
        // Open clicked FAQ
        answer.classList.add('show');
        element.classList.add('active');
    }
}

function showCreateUserModal() {
    document.getElementById('createUserModal').classList.add('show');
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('show');
    document.getElementById('createUserForm').reset();
}

async function createUser() {
    const namaLengkap = document.getElementById('createNamaLengkap').value;
    const username = document.getElementById('createUsername').value;
    const email = document.getElementById('createEmail').value;
    const password = document.getElementById('createPassword').value;
    const role = document.getElementById('createRole').value;
    const idDesa = document.getElementById('createDesa').value;
    
    if (!namaLengkap || !username || !email || !password || !role) {
        alert('Semua field wajib diisi!');
        return;
    }
    
    if (role === 'operator_desa' && !idDesa) {
        alert('Pilih desa untuk operator desa!');
        return;
    }
    
    try {
        const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/create-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                nama_lengkap: namaLengkap,
                username: username,
                email: email,
                password: password,
                role: role,
                id_desa: idDesa || null
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User berhasil dibuat!');
            closeCreateUserModal();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

async function editUser(userId) {
    try {
        const response = await fetch(window.BASE_URL + 'api/user-endpoints.php?endpoint=get-user&id=' + userId);
        const result = await response.json();
        
        if (result.success) {
            const user = result.data;
            document.getElementById('editUserId').value = user.id_user;
            document.getElementById('editNamaLengkap').value = user.nama_lengkap;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editPassword').value = '';
            document.getElementById('editRole').value = user.role;
            document.getElementById('editDesa').value = user.id_desa || '';
            document.getElementById('editStatus').value = user.status_aktif;
            
            document.getElementById('editUserModal').classList.add('show');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('show');
    document.getElementById('editUserForm').reset();
}

async function updateUser() {
    const userId = document.getElementById('editUserId').value;
    const namaLengkap = document.getElementById('editNamaLengkap').value;
    const username = document.getElementById('editUsername').value;
    const email = document.getElementById('editEmail').value;
    const password = document.getElementById('editPassword').value;
    const role = document.getElementById('editRole').value;
    const idDesa = document.getElementById('editDesa').value;
    const status = document.getElementById('editStatus').value;
    
    if (!namaLengkap || !username || !email || !role || !status) {
        alert('Field wajib harus diisi!');
        return;
    }
    
    if (role === 'operator_desa' && !idDesa) {
        alert('Pilih desa untuk operator desa!');
        return;
    }
    
    try {
        const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/edit-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                nama_lengkap: namaLengkap,
                username: username,
                email: email,
                password: password || null,
                role: role,
                id_desa: idDesa || null,
                status_aktif: status
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User berhasil diupdate!');
            closeEditUserModal();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

async function deleteUser(userId, userName) {
    if (!confirm(`Yakin ingin menghapus user "${userName}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/delete-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

// Village management functions
function showCreateDesaModal() {
    document.getElementById('createDesaModal').classList.add('show');
}

function closeCreateDesaModal() {
    document.getElementById('createDesaModal').classList.remove('show');
    document.getElementById('createDesaForm').reset();
}

async function createDesa() {
    const namaDesa = document.getElementById('createNamaDesa').value;
    const luasWilayah = document.getElementById('createLuasWilayah').value;
    const jumlahDusun = document.getElementById('createJumlahDusun').value;
    const jumlahRT = document.getElementById('createJumlahRT').value;
    const jumlahRW = document.getElementById('createJumlahRW').value;
    
    if (!namaDesa || !luasWilayah || !jumlahDusun || !jumlahRT || !jumlahRW) {
        alert('Field wajib harus diisi!');
        return;
    }
    
    try {
        const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/create-desa.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                nama_desa: namaDesa,
                luas_wilayah: parseFloat(luasWilayah),
                jumlah_dusun: parseInt(jumlahDusun),
                jumlah_rt: parseInt(jumlahRT),
                jumlah_rw: parseInt(jumlahRW)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Desa berhasil ditambahkan!');
            closeCreateDesaModal();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

async function deleteDesa(desaId, desaName) {
    if (!confirm(`Yakin ingin menghapus desa "${desaName}"?\n\nPerhatian: Semua data terkait desa ini akan ikut terhapus!`)) {
        return;
    }
    
    try {
        const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/delete-desa.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                desa_id: desaId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Desa berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.pengaturan-container')) {
        window.pengaturanComponent = new PengaturanComponent();
    }
});

// Also initialize if DOM is already loaded
if (document.readyState !== 'loading' && document.querySelector('.pengaturan-container')) {
    window.pengaturanComponent = new PengaturanComponent();
}