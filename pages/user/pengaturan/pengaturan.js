// User Pengaturan JavaScript
class UserPengaturanComponent {
    constructor() {
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        // Change password form
        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', this.handleChangePassword.bind(this));
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
            const response = await fetch(window.BASE_URL + 'pages/user/pengaturan/pengaturan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
}

// Global functions
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
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

// Make showTab globally available
window.showTab = showTab;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.pengaturan-container')) {
        window.userPengaturanComponent = new UserPengaturanComponent();
    }
});

if (document.readyState !== 'loading' && document.querySelector('.pengaturan-container')) {
    window.userPengaturanComponent = new UserPengaturanComponent();
}