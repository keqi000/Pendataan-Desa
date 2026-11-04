// Pengaturan Users Tab Functions
(function() {
    'use strict';
    
    class PengaturanUsers {
        constructor() {
            this.init();
        }
        
        init() {
            this.setupUserModals();
            this.setupUserFunctions();
        }
        
        setupUserModals() {
            // Global user modal functions
            window.showCreateUserModal = () => {
                document.getElementById('createUserModal').classList.add('show');
            };
            
            window.closeCreateUserModal = () => {
                document.getElementById('createUserModal').classList.remove('show');
                document.getElementById('createUserForm').reset();
            };
            
            window.closeEditUserModal = () => {
                document.getElementById('editUserModal').classList.remove('show');
                document.getElementById('editUserForm').reset();
            };
        }
        
        setupUserFunctions() {
            window.createUser = async () => {
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
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'create_user',
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
                        window.closeCreateUserModal();
                        await this.reloadUsersTab();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan: ' + error.message);
                }
            };
            
            window.editUser = async (userId) => {
                try {
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php?action=get_user&id=' + userId);
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
            };
            
            window.updateUser = async () => {
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
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_user',
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
                        window.closeEditUserModal();
                        await this.reloadUsersTab();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan: ' + error.message);
                }
            };
            
            window.deleteUser = async (userId, userName) => {
                if (!confirm(`Yakin ingin menghapus user "${userName}"?`)) {
                    return;
                }
                
                try {
                    const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_user',
                            user_id: userId
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('User berhasil dihapus!');
                        await this.reloadUsersTab();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan: ' + error.message);
                }
            };
        }
        
        async reloadUsersTab() {
            try {
                const response = await fetch(window.PengaturanUtils.getBaseUrl() + 'pages/admin/pengaturan/pengaturan.php', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newUsersTab = doc.querySelector('#users-tab');
                
                if (newUsersTab) {
                    document.querySelector('#users-tab').innerHTML = newUsersTab.innerHTML;
                }
            } catch (error) {
                location.reload();
            }
        }
    }
    
    // Initialize Users functionality
    window.PengaturanUsers = new PengaturanUsers();
})();