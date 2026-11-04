<?php
// Dashboard View untuk Sistem Informasi Publik
// File: public-portal/pages/dashboard/php/dashboard-view.php

require_once __DIR__ . '/dashboard-functions.php';

$fn           = new PublicDashboardFunctions();
$overview     = $fn->getOverviewStatistics();
$quickStats   = $fn->getQuickStats();
$topDesa      = $fn->getTopDesaByPopulation();
$umkmCategories = $fn->getPopularUMKMCategories();
$infrastructure = $fn->getInfrastructureStatus();
$facilities   = $fn->getFacilitiesByType();
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Selamat Datang di Portal Data Terbuka</h1>
                <h2>Kecamatan Tibawa</h2>
                <p>Akses informasi publik dan statistik terkini dari <?php echo $overview['total_desa'] ?? 16; ?> desa di Kecamatan Tibawa, Kabupaten Gorontalo Utara. Data transparan untuk masyarakat yang lebih baik.</p>
                <div class="hero-buttons">
                    <a href="<?php echo BASE_URL_PUBLIC; ?>pages/fasilitas/fasilitas.php" class="btn btn-primary">
                        <i class="fas fa-building"></i> Lihat Fasilitas
                    </a>
                    <a href="<?php echo BASE_URL_PUBLIC; ?>pages/umkm/umkm.php" class="btn btn-secondary">
                        <i class="fas fa-store"></i> Direktori UMKM
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Stats Section -->
<section class="quick-stats-section">
    <div class="container">
        <div class="row">
            <?php foreach ($quickStats as $stat): ?>
                <div class="col-md-3">
                    <div class="stat-card stat-card-<?php echo $stat['color']; ?>">
                        <div class="stat-icon">
                            <i class="<?php echo $stat['icon']; ?>"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $stat['value']; ?></div>
                            <div class="stat-title"><?php echo $stat['title']; ?></div>
                            <div class="stat-subtitle"><?php echo $stat['subtitle']; ?></div>
                            <div class="stat-trend">
                                <span class="trend-indicator"><?php echo $stat['trend']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Main Dashboard Content -->
<section class="dashboard-content">
    <div class="container">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Top Desa Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-trophy"></i>
                            5 Desa Terbesar
                        </h3>
                        <p>Berdasarkan jumlah penduduk</p>
                    </div>
                    <div class="card-body">
                        <div class="top-desa-list">
                            <?php foreach ($topDesa as $index => $desa): ?>
                                <div class="top-desa-item">
                                    <div class="desa-rank">
                                        <span class="rank-number"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="desa-info">
                                        <h4><?php echo $desa['nama_desa']; ?></h4>
                                        <div class="desa-stats">
                                            <span class="penduduk">
                                                <i class="fas fa-users"></i>
                                                <?php echo number_format($desa['penduduk']); ?> jiwa
                                            </span>
                                            <span class="kk">
                                                <i class="fas fa-home"></i>
                                                <?php echo number_format($desa['kk']); ?> KK
                                            </span>
                                        </div>
                                    </div>
                                    <div class="desa-action">
                                        <a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=<?php echo strtolower(str_replace(' ', '_', $desa['nama_desa'])); ?>" class="btn btn-sm btn-primary">
                                            Detail
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-pie"></i>
                                    Distribusi Usia
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="ageChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-graduation-cap"></i>
                                    Tingkat Pendidikan
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="educationChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- UMKM Categories -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-store"></i>
                            Kategori UMKM Populer
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="umkm-categories">
                            <?php foreach ($umkmCategories as $category): ?>
                                <div class="category-item">
                                    <div class="category-info">
                                        <span class="category-name"><?php echo $category['kategori']; ?></span>
                                        <span class="category-count"><?php echo $category['jumlah']; ?> usaha</span>
                                    </div>
                                    <div class="category-progress">
                                        <div class="progress-bar" style="width: <?php echo $category['persen']; ?>%"></div>
                                    </div>
                                    <div class="category-percent"><?php echo $category['persen']; ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Infrastructure Status -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-road"></i>
                            Status Infrastruktur
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="infrastructure-stats">
                            <div class="infra-item">
                                <h4>Jalan</h4>
                                <div class="infra-details">
                                    <div class="infra-stat">
                                        <span class="label">Total:</span>
                                        <span class="value"><?php echo $infrastructure['jalan']['total']; ?> unit</span>
                                    </div>
                                    <div class="infra-stat">
                                        <span class="label">Baik:</span>
                                        <span class="value text-success"><?php echo $infrastructure['jalan']['baik']; ?></span>
                                    </div>
                                    <div class="infra-stat">
                                        <span class="label">Sedang:</span>
                                        <span class="value text-warning"><?php echo $infrastructure['jalan']['sedang']; ?></span>
                                    </div>
                                    <div class="infra-stat">
                                        <span class="label">Rusak:</span>
                                        <span class="value text-danger"><?php echo $infrastructure['jalan']['rusak']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="infra-item">
                                <h4>Jembatan</h4>
                                <div class="infra-details">
                                    <div class="infra-stat">
                                        <span class="label">Total:</span>
                                        <span class="value"><?php echo $infrastructure['jembatan']['total']; ?> unit</span>
                                    </div>
                                    <div class="infra-stat">
                                        <span class="label">Baik:</span>
                                        <span class="value text-success"><?php echo $infrastructure['jembatan']['baik']; ?></span>
                                    </div>
                                    <div class="infra-stat">
                                        <span class="label">Sedang:</span>
                                        <span class="value text-warning"><?php echo $infrastructure['jembatan']['sedang']; ?></span>
                                    </div>
                                    <div class="infra-stat">
                                        <span class="label">Rusak:</span>
                                        <span class="value text-danger"><?php echo $infrastructure['jembatan']['rusak']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facilities Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-building"></i>
                            Fasilitas Publik
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="facilities-list">
                            <?php foreach ($facilities as $facility): ?>
                                <div class="facility-item">
                                    <div class="facility-info">
                                        <span class="facility-type"><?php echo $facility['jenis']; ?></span>
                                        <span class="facility-count"><?php echo $facility['jumlah']; ?> unit</span>
                                    </div>
                                    <div class="facility-capacity">
                                        <small>Kapasitas: <?php echo number_format($facility['kapasitas']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="<?php echo BASE_URL_PUBLIC; ?>pages/fasilitas/fasilitas.php" class="btn btn-primary btn-sm">
                                Lihat Semua Fasilitas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Jelajahi Data Lebih Lanjut</h2>
            <p>Temukan informasi detail tentang setiap desa, fasilitas publik, dan potensi ekonomi di Kecamatan Tibawa</p>
            <div class="cta-buttons">
                <a href="<?php echo BASE_URL_PUBLIC; ?>pages/fasilitas/fasilitas.php" class="btn btn-primary">
                    <i class="fas fa-building"></i> Fasilitas Publik
                </a>
                <a href="<?php echo BASE_URL_PUBLIC; ?>pages/umkm/umkm.php" class="btn btn-secondary">
                    <i class="fas fa-store"></i> Direktori UMKM
                </a>
                <a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php" class="btn btn-info">
                    <i class="fas fa-map-marked-alt"></i> Profil Desa
                </a>
            </div>
        </div>
    </div>
</section>