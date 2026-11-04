<?php
// Footer Component untuk Sistem Informasi Publik
// File: public-portal/layout/footer/footer.php
?>

<footer class="public-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Portal Data Terbuka</h4>
                <p>Kecamatan Tibawa</p>
                <p>Kabupaten Gorontalo Utara</p>
                <p>Provinsi Gorontalo</p>
                
                <div class="footer-stats">
                    <div class="stat-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>16 Desa</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span>43,336 Jiwa</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Navigasi</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL_PUBLIC; ?>">Beranda</a></li>
                    <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/fasilitas/fasilitas.php">Fasilitas</a></li>
                    <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/umkm/umkm.php">UMKM</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Data & API</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL_PUBLIC; ?>api/docs.php" target="_blank">Dokumentasi API</a></li>
                    <li><a href="<?php echo BASE_URL_PUBLIC; ?>api/public/desa" target="_blank">Data Desa (JSON)</a></li>
                    <li><a href="<?php echo BASE_URL_PUBLIC; ?>api/public/statistik" target="_blank">Statistik (JSON)</a></li>
                    <li><a href="#" onclick="PublicUtils.copyToClipboard('<?php echo BASE_URL_PUBLIC; ?>api/')">Copy API URL</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Kontak</h4>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>kecamatan.tibawa@gorontaloutara.go.id</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>(0435) 123-456</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-globe"></i>
                        <a href="https://gorontaloutara.go.id" target="_blank">gorontaloutara.go.id</a>
                    </div>
                </div>
                
                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> Pemerintah Kecamatan Tibawa. Semua hak dilindungi.</p>
                    <p>Data diperbarui: <span id="lastUpdate"><?php echo date('d F Y, H:i'); ?> WIT</span></p>
                </div>
                
                <div class="footer-badges">
                    <div class="badge-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Data Terverifikasi</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-lock"></i>
                        <span>Aman & Terpercaya</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-sync-alt"></i>
                        <span>Real-time Update</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top" onclick="scrollToTop()" title="Kembali ke atas">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- Global JavaScript -->
<script src="<?php echo BASE_URL_PUBLIC; ?>assets/js/global.js"></script>
<script src="<?php echo BASE_URL_PUBLIC; ?>layout/navbar/navbar.js"></script>
<script src="<?php echo BASE_URL_PUBLIC; ?>layout/footer/footer.js"></script>

<!-- Page specific JavaScript -->
<?php if (isset($page_js) && is_array($page_js)): ?>
    <?php foreach ($page_js as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>