<div class="indeks-ekonomi-container">
    <div class="indeks-summary-cards">
        <div class="summary-card main-score">
            <div class="card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="card-content">
                <h3>Indeks Ketahanan Ekonomi Kecamatan</h3>
                <div class="main-score-value" style="color: <?php echo $hasil_indeks['status_kecamatan']['warna']; ?>">
                    <?php echo $hasil_indeks['rata_rata_indeks']; ?>
                </div>
                <div class="status-badge" style="background-color: <?php echo $hasil_indeks['status_kecamatan']['warna']; ?>">
                    <?php echo $hasil_indeks['status_kecamatan']['status']; ?>
                </div>
                <p class="status-desc"><?php echo $hasil_indeks['status_kecamatan']['keterangan']; ?></p>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="card-content">
                <h4>Desa Terbaik</h4>
                <div class="card-value"><?php echo $hasil_indeks['analisis_komparatif']['desa_terbaik']['nama_desa']; ?></div>
                <div class="card-subtitle">Skor: <?php echo $hasil_indeks['analisis_komparatif']['desa_terbaik']['indeks_total']; ?></div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="card-content">
                <h4>Desa Prioritas</h4>
                <div class="card-value"><?php echo $hasil_indeks['analisis_komparatif']['desa_terburuk']['nama_desa']; ?></div>
                <div class="card-subtitle">Skor: <?php echo $hasil_indeks['analisis_komparatif']['desa_terburuk']['indeks_total']; ?></div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="card-content">
                <h4>Kesenjangan</h4>
                <div class="card-value"><?php echo $hasil_indeks['analisis_komparatif']['gap_indeks']; ?></div>
                <div class="card-subtitle"><?php echo $hasil_indeks['analisis_komparatif']['kesenjangan']; ?></div>
            </div>
        </div>
    </div>
    
    <div class="indeks-charts-section">
        <div class="chart-container">
            <h4>Distribusi Status Ketahanan Ekonomi</h4>
            <div class="chart-wrapper">
                <canvas id="chartDistribusiStatus"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <span class="legend-color" style="background-color: #27AE60;"></span>
                    <span>Kuat: <?php echo $hasil_indeks['distribusi_status']['Kuat']; ?> desa</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background-color: #F39C12;"></span>
                    <span>Sedang: <?php echo $hasil_indeks['distribusi_status']['Sedang']; ?> desa</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background-color: #E74C3C;"></span>
                    <span>Lemah: <?php echo $hasil_indeks['distribusi_status']['Lemah']; ?> desa</span>
                </div>
            </div>
        </div>
        
        <div class="chart-container">
            <h4>Ranking Indeks Ketahanan Ekonomi</h4>
            <div class="chart-wrapper">
                <canvas id="chartRankingIndeks"></canvas>
            </div>
        </div>
    </div>
    
    <div class="indeks-analysis-grid">
        <div class="card">
            <div class="card-header">
                <h3>Analisis Komparatif Antar Desa</h3>
            </div>
            <div class="card-body">
                <div class="comparison-stats">
                    <div class="comparison-item best">
                        <div class="comparison-label">Desa Terbaik</div>
                        <div class="comparison-name"><?php echo $hasil_indeks['analisis_komparatif']['desa_terbaik']['nama_desa']; ?></div>
                        <div class="comparison-score"><?php echo $hasil_indeks['analisis_komparatif']['desa_terbaik']['indeks_total']; ?></div>
                        <div class="comparison-status" style="color: <?php echo $hasil_indeks['analisis_komparatif']['desa_terbaik']['status_ketahanan']['warna']; ?>">
                            <?php echo $hasil_indeks['analisis_komparatif']['desa_terbaik']['status_ketahanan']['status']; ?>
                        </div>
                    </div>
                    
                    <div class="comparison-vs">VS</div>
                    
                    <div class="comparison-item worst">
                        <div class="comparison-label">Desa Prioritas</div>
                        <div class="comparison-name"><?php echo $hasil_indeks['analisis_komparatif']['desa_terburuk']['nama_desa']; ?></div>
                        <div class="comparison-score"><?php echo $hasil_indeks['analisis_komparatif']['desa_terburuk']['indeks_total']; ?></div>
                        <div class="comparison-status" style="color: <?php echo $hasil_indeks['analisis_komparatif']['desa_terburuk']['status_ketahanan']['warna']; ?>">
                            <?php echo $hasil_indeks['analisis_komparatif']['desa_terburuk']['status_ketahanan']['status']; ?>
                        </div>
                    </div>
                </div>
                
                <div class="gap-analysis">
                    <h5>Analisis Kesenjangan</h5>
                    <div class="gap-item">
                        <span>Selisih Indeks:</span>
                        <span class="gap-value"><?php echo $hasil_indeks['analisis_komparatif']['gap_indeks']; ?> poin</span>
                    </div>
                    <div class="gap-item">
                        <span>Tingkat Kesenjangan:</span>
                        <span class="gap-level <?php echo strtolower($hasil_indeks['analisis_komparatif']['kesenjangan']); ?>">
                            <?php echo $hasil_indeks['analisis_komparatif']['kesenjangan']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Rekomendasi Strategis Kecamatan</h3>
            </div>
            <div class="card-body">
                <div class="recommendations-list">
                    <?php foreach ($hasil_indeks['rekomendasi_kecamatan'] as $index => $rekomendasi): ?>
                        <div class="recommendation-item">
                            <div class="recommendation-number"><?php echo $index + 1; ?></div>
                            <div class="recommendation-text"><?php echo $rekomendasi; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Detail Indeks Ketahanan Ekonomi Per Desa</h3>
            <div class="table-controls">
                <input type="text" id="searchIndeksEkonomi" placeholder="Cari nama desa..." class="search-input">
                <select id="filterStatus" class="filter-select">
                    <option value="">Semua Status</option>
                    <option value="Kuat">Kuat</option>
                    <option value="Sedang">Sedang</option>
                    <option value="Lemah">Lemah</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="indeks-table" id="indeksEkonomiTable">
                    <thead>
                        <tr>
                            <th>Ranking</th>
                            <th>Nama Desa</th>
                            <th>Indeks Total</th>
                            <th>Status</th>
                            <th>Keragaman Produksi</th>
                            <th>Pusat Perdagangan</th>
                            <th>Distribusi</th>
                            <th>Keuangan</th>
                            <th>Lembaga Ekonomi</th>
                            <th>Transportasi</th>
                            <th>Prioritas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hasil_indeks['hasil_per_desa'] as $id_desa => $desa_data): ?>
                        <tr class="desa-row" data-status="<?php echo $desa_data['status_ketahanan']['status']; ?>" data-desa-id="<?php echo $id_desa; ?>">
                            <td class="ranking-cell">
                                <span class="ranking-badge rank-<?php echo $desa_data['ranking']; ?>">
                                    <?php echo $desa_data['ranking']; ?>
                                </span>
                            </td>
                            <td class="desa-name"><?php echo $desa_data['nama_desa']; ?></td>
                            <td class="indeks-score">
                                <div class="score-container">
                                    <span class="score-value"><?php echo $desa_data['indeks_total']; ?></span>
                                    <div class="score-bar">
                                        <div class="score-fill" style="width: <?php echo $desa_data['indeks_total']; ?>%; background-color: <?php echo $desa_data['status_ketahanan']['warna']; ?>"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge" style="background-color: <?php echo $desa_data['status_ketahanan']['warna']; ?>">
                                    <?php echo $desa_data['status_ketahanan']['status']; ?>
                                </span>
                            </td>
                            <td class="sub-score"><?php echo $desa_data['skor_detail']['keragaman_produksi']; ?></td>
                            <td class="sub-score"><?php echo $desa_data['skor_detail']['pusat_perdagangan']; ?></td>
                            <td class="sub-score"><?php echo $desa_data['skor_detail']['distribusi']; ?></td>
                            <td class="sub-score"><?php echo $desa_data['skor_detail']['keuangan']; ?></td>
                            <td class="sub-score"><?php echo $desa_data['skor_detail']['lembaga_ekonomi']; ?></td>
                            <td class="sub-score"><?php echo $desa_data['skor_detail']['transportasi']; ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower(str_replace(' ', '-', $desa_data['prioritas'])); ?>">
                                    <?php echo $desa_data['prioritas']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-detail" data-desa-id="<?php echo $id_desa; ?>" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
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

<!-- Modal Detail Desa -->
<div id="desaDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalDesaName">Detail Desa</h3>
            <span class="close" onclick="closeDesaDetail()">&times;</span>
        </div>
        <div class="modal-body" id="modalDesaContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<script>
// Data untuk charts
window.indeksEkonomiData = {
    distribusiStatus: {
        labels: ['Kuat', 'Sedang', 'Lemah'],
        data: [<?php echo $hasil_indeks['distribusi_status']['Kuat']; ?>, <?php echo $hasil_indeks['distribusi_status']['Sedang']; ?>, <?php echo $hasil_indeks['distribusi_status']['Lemah']; ?>],
        colors: ['#27AE60', '#F39C12', '#E74C3C']
    },
    rankingData: {
        labels: [<?php foreach ($hasil_indeks['hasil_per_desa'] as $desa): echo '"' . $desa['nama_desa'] . '",'; endforeach; ?>],
        data: [<?php foreach ($hasil_indeks['hasil_per_desa'] as $desa): echo $desa['indeks_total'] . ','; endforeach; ?>],
        colors: [<?php foreach ($hasil_indeks['hasil_per_desa'] as $desa): echo '"' . $desa['status_ketahanan']['warna'] . '",'; endforeach; ?>]
    },
    desaDetails: <?php echo json_encode($hasil_indeks['hasil_per_desa']); ?>
};

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initIndeksEkonomiCharts();
    initIndeksEkonomiTable();
});

function initIndeksEkonomiCharts() {
    // Chart Distribusi Status
    const ctxDistribusi = document.getElementById('chartDistribusiStatus');
    if (ctxDistribusi && typeof Chart !== 'undefined') {
        new Chart(ctxDistribusi, {
            type: 'doughnut',
            data: {
                labels: window.indeksEkonomiData.distribusiStatus.labels,
                datasets: [{
                    data: window.indeksEkonomiData.distribusiStatus.data,
                    backgroundColor: window.indeksEkonomiData.distribusiStatus.colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Chart Ranking
    const ctxRanking = document.getElementById('chartRankingIndeks');
    if (ctxRanking && typeof Chart !== 'undefined') {
        new Chart(ctxRanking, {
            type: 'bar',
            data: {
                labels: window.indeksEkonomiData.rankingData.labels,
                datasets: [{
                    label: 'Indeks Ketahanan Ekonomi',
                    data: window.indeksEkonomiData.rankingData.data,
                    backgroundColor: window.indeksEkonomiData.rankingData.colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

function initIndeksEkonomiTable() {
    const searchInput = document.getElementById('searchIndeksEkonomi');
    const filterStatus = document.getElementById('filterStatus');
    const table = document.getElementById('indeksEkonomiTable');
    
    if (searchInput && filterStatus && table) {
        searchInput.addEventListener('input', filterTable);
        filterStatus.addEventListener('change', filterTable);
    }
}

function filterTable() {
    const searchTerm = document.getElementById('searchIndeksEkonomi').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('.desa-row');
    
    rows.forEach(row => {
        const desaName = row.querySelector('.desa-name').textContent.toLowerCase();
        const status = row.dataset.status;
        
        const matchesSearch = desaName.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
}

function showDesaDetail(idDesa) {
    const desaData = window.indeksEkonomiData.desaDetails[idDesa];
    if (!desaData) return;
    
    document.getElementById('modalDesaName').textContent = `Detail Analisis - ${desaData.nama_desa}`;
    
    const content = `
        <div class="desa-detail-content">
            <div class="detail-summary">
                <div class="detail-score">
                    <h4>Indeks Total</h4>
                    <div class="score-display" style="color: ${desaData.status_ketahanan.warna}">
                        ${desaData.indeks_total}
                    </div>
                    <div class="status-display" style="background-color: ${desaData.status_ketahanan.warna}">
                        ${desaData.status_ketahanan.status}
                    </div>
                </div>
                <div class="detail-ranking">
                    <h4>Ranking</h4>
                    <div class="ranking-display">#${desaData.ranking}</div>
                    <div class="priority-display">Prioritas: ${desaData.prioritas}</div>
                </div>
            </div>
            
            <div class="detail-dimensions">
                <h4>Skor Per Dimensi</h4>
                <div class="dimensions-grid">
                    <div class="dimension-item">
                        <span>Keragaman Produksi</span>
                        <div class="dimension-bar">
                            <div class="dimension-fill" style="width: ${desaData.skor_detail.keragaman_produksi}%"></div>
                        </div>
                        <span>${desaData.skor_detail.keragaman_produksi}</span>
                    </div>
                    <div class="dimension-item">
                        <span>Pusat Perdagangan</span>
                        <div class="dimension-bar">
                            <div class="dimension-fill" style="width: ${desaData.skor_detail.pusat_perdagangan}%"></div>
                        </div>
                        <span>${desaData.skor_detail.pusat_perdagangan}</span>
                    </div>
                    <div class="dimension-item">
                        <span>Distribusi</span>
                        <div class="dimension-bar">
                            <div class="dimension-fill" style="width: ${desaData.skor_detail.distribusi}%"></div>
                        </div>
                        <span>${desaData.skor_detail.distribusi}</span>
                    </div>
                    <div class="dimension-item">
                        <span>Keuangan</span>
                        <div class="dimension-bar">
                            <div class="dimension-fill" style="width: ${desaData.skor_detail.keuangan}%"></div>
                        </div>
                        <span>${desaData.skor_detail.keuangan}</span>
                    </div>
                    <div class="dimension-item">
                        <span>Lembaga Ekonomi</span>
                        <div class="dimension-bar">
                            <div class="dimension-fill" style="width: ${desaData.skor_detail.lembaga_ekonomi}%"></div>
                        </div>
                        <span>${desaData.skor_detail.lembaga_ekonomi}</span>
                    </div>
                    <div class="dimension-item">
                        <span>Transportasi</span>
                        <div class="dimension-bar">
                            <div class="dimension-fill" style="width: ${desaData.skor_detail.transportasi}%"></div>
                        </div>
                        <span>${desaData.skor_detail.transportasi}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-recommendations">
                <h4>Rekomendasi Khusus</h4>
                <ul class="recommendations-list">
                    ${desaData.rekomendasi.map(rec => `<li>${rec}</li>`).join('')}
                </ul>
            </div>
        </div>
    `;
    
    document.getElementById('modalDesaContent').innerHTML = content;
    document.getElementById('desaDetailModal').style.display = 'block';
}

function closeDesaDetail() {
    document.getElementById('desaDetailModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('desaDetailModal');
    if (event.target === modal) {
        closeDesaDetail();
    }
});
</script>