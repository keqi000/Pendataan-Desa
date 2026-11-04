<?php
require_once realpath(__DIR__ . '/../../../../config/config.php');
require_once realpath(__DIR__ . '/../../../../database/queries.php');

class IndeksKetahananEkonomi {
    private $queries;
    private $desa_data = [];
    
    // Bobot untuk setiap sub-dimensi (total = 100%)
    private $bobot_sub_dimensi = [
        'keragaman_produksi' => 20,  // 20%
        'pusat_perdagangan' => 25,   // 25%
        'distribusi' => 10,          // 10%
        'keuangan' => 20,            // 20%
        'lembaga_ekonomi' => 10,     // 10%
        'transportasi' => 15         // 15%
    ];
    
    // Standar penilaian ketahanan ekonomi
    private $standar_ketahanan = [
        'kuat' => 75,      // >= 75
        'sedang' => 50,    // 50-74
        'lemah' => 0       // < 50
    ];
    
    public function __construct() {
        $this->queries = new Queries();
    }
    
    public function preloadDesaData($filtered_desa) {
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $this->desa_data[$id_desa] = [
                'desa' => $desa,
                'ekonomi' => $this->queries->getDataEkonomi($id_desa),
                'umkm' => $this->queries->getUMKM($id_desa),
                'mata_pencaharian' => $this->queries->getMataPencaharianByDesa($id_desa),
                'jalan' => $this->queries->getInfrastrukturJalan($id_desa)
            ];
        }
        return $this->desa_data;
    }
    
    public function calculateIndeksKetahananEkonomi() {
        $hasil_analisis = [];
        $total_skor_semua_desa = 0;
        $jumlah_desa = count($this->desa_data);
        
        foreach ($this->desa_data as $id_desa => $data) {
            $desa = $data['desa'];
            
            // Hitung skor setiap sub-dimensi
            $skor_keragaman = $this->hitungKeragamanProduksi($data);
            $skor_perdagangan = $this->hitungPusatPerdagangan($data);
            $skor_distribusi = $this->hitungDistribusi($data);
            $skor_keuangan = $this->hitungKeuangan($data);
            $skor_lembaga = $this->hitungLembagaEkonomi($data);
            $skor_transportasi = $this->hitungTransportasi($data);
            
            // Hitung indeks total dengan pembobotan
            $indeks_total = (
                ($skor_keragaman * $this->bobot_sub_dimensi['keragaman_produksi']) +
                ($skor_perdagangan * $this->bobot_sub_dimensi['pusat_perdagangan']) +
                ($skor_distribusi * $this->bobot_sub_dimensi['distribusi']) +
                ($skor_keuangan * $this->bobot_sub_dimensi['keuangan']) +
                ($skor_lembaga * $this->bobot_sub_dimensi['lembaga_ekonomi']) +
                ($skor_transportasi * $this->bobot_sub_dimensi['transportasi'])
            ) / 100;
            
            // Tentukan status ketahanan
            $status_ketahanan = $this->tentukanStatusKetahanan($indeks_total);
            
            // Generate rekomendasi
            $rekomendasi = $this->generateRekomendasi($indeks_total, [
                'keragaman' => $skor_keragaman,
                'perdagangan' => $skor_perdagangan,
                'distribusi' => $skor_distribusi,
                'keuangan' => $skor_keuangan,
                'lembaga' => $skor_lembaga,
                'transportasi' => $skor_transportasi
            ]);
            
            $hasil_analisis[$id_desa] = [
                'nama_desa' => $desa['nama_desa'],
                'indeks_total' => round($indeks_total, 1),
                'status_ketahanan' => $status_ketahanan,
                'skor_detail' => [
                    'keragaman_produksi' => round($skor_keragaman, 1),
                    'pusat_perdagangan' => round($skor_perdagangan, 1),
                    'distribusi' => round($skor_distribusi, 1),
                    'keuangan' => round($skor_keuangan, 1),
                    'lembaga_ekonomi' => round($skor_lembaga, 1),
                    'transportasi' => round($skor_transportasi, 1)
                ],
                'rekomendasi' => $rekomendasi,
                'prioritas' => $this->tentukanPrioritas($indeks_total)
            ];
            
            $total_skor_semua_desa += $indeks_total;
        }
        
        // Hitung rata-rata dan ranking
        $rata_rata_indeks = $jumlah_desa > 0 ? round($total_skor_semua_desa / $jumlah_desa, 1) : 0;
        
        // Sort berdasarkan indeks total (tertinggi ke terendah)
        uasort($hasil_analisis, function($a, $b) {
            return $b['indeks_total'] <=> $a['indeks_total'];
        });
        
        // Tambahkan ranking
        $ranking = 1;
        foreach ($hasil_analisis as &$desa_analisis) {
            $desa_analisis['ranking'] = $ranking++;
        }
        
        return [
            'hasil_per_desa' => $hasil_analisis,
            'rata_rata_indeks' => $rata_rata_indeks,
            'status_kecamatan' => $this->tentukanStatusKetahanan($rata_rata_indeks),
            'distribusi_status' => $this->hitungDistribusiStatus($hasil_analisis),
            'analisis_komparatif' => $this->analisisKomparatif($hasil_analisis),
            'rekomendasi_kecamatan' => $this->generateRekomendasiKecamatan($hasil_analisis, $rata_rata_indeks)
        ];
    }
    
    private function hitungKeragamanProduksi($data) {
        // ID 39: Terdapat lebih dari satu jenis kegiatan ekonomi penduduk
        $jenis_ekonomi = [];
        
        // Hitung dari mata pencaharian
        foreach ($data['mata_pencaharian'] as $mp) {
            $sektor = $mp['sektor_pekerjaan'];
            if (!in_array($sektor, $jenis_ekonomi)) {
                $jenis_ekonomi[] = $sektor;
            }
        }
        
        // Hitung dari UMKM
        foreach ($data['umkm'] as $umkm) {
            $jenis = 'umkm_' . $umkm['jenis_usaha'];
            if (!in_array($jenis, $jenis_ekonomi)) {
                $jenis_ekonomi[] = $jenis;
            }
        }
        
        // Hitung dari data ekonomi
        foreach ($data['ekonomi'] as $ekonomi) {
            $jenis = 'ekonomi_' . $ekonomi['jenis_data'];
            if (!in_array($jenis, $jenis_ekonomi)) {
                $jenis_ekonomi[] = $jenis;
            }
        }
        
        $jumlah_jenis = count($jenis_ekonomi);
        
        // Skor berdasarkan keragaman
        if ($jumlah_jenis >= 5) return 100;      // Sangat beragam
        if ($jumlah_jenis >= 3) return 75;       // Beragam
        if ($jumlah_jenis >= 2) return 50;       // Cukup beragam
        return 25;                               // Kurang beragam
    }
    
    private function hitungPusatPerdagangan($data) {
        $skor = 0;
        $total_indikator = 3;
        
        // ID 40: Akses ke pusat perdagangan (simulasi berdasarkan jalan)
        $akses_perdagangan = 0;
        foreach ($data['jalan'] as $jalan) {
            if ($jalan['kondisi_jalan'] == 'baik') {
                $akses_perdagangan += 25;
            }
        }
        $skor += min($akses_perdagangan, 100);
        
        // ID 41: Sektor perdagangan di permukiman
        $ada_perdagangan = false;
        foreach ($data['ekonomi'] as $ekonomi) {
            if ($ekonomi['jenis_data'] == 'pasar') {
                $ada_perdagangan = true;
                break;
            }
        }
        $skor += $ada_perdagangan ? 100 : 0;
        
        // ID 42: Usaha makanan/hotel/penginapan
        $ada_usaha_jasa = false;
        foreach ($data['umkm'] as $umkm) {
            if (in_array(strtolower($umkm['jenis_usaha']), ['makanan', 'kuliner', 'warung', 'hotel', 'penginapan'])) {
                $ada_usaha_jasa = true;
                break;
            }
        }
        $skor += $ada_usaha_jasa ? 100 : 0;
        
        return $skor / $total_indikator;
    }
    
    private function hitungDistribusi($data) {
        // ID 43: Kantor pos/logistik (simulasi)
        $ada_pos_logistik = false;
        foreach ($data['ekonomi'] as $ekonomi) {
            if (strpos(strtolower($ekonomi['nama_tempat'] ?? ''), 'pos') !== false) {
                $ada_pos_logistik = true;
                break;
            }
        }
        
        return $ada_pos_logistik ? 100 : 25; // Minimal 25 karena ada akses distribusi informal
    }
    
    private function hitungKeuangan($data) {
        $skor = 0;
        $total_indikator = 3;
        
        // ID 44: Bank umum (simulasi)
        $ada_bank = false;
        foreach ($data['ekonomi'] as $ekonomi) {
            if (strpos(strtolower($ekonomi['nama_tempat'] ?? ''), 'bank') !== false) {
                $ada_bank = true;
                break;
            }
        }
        $skor += $ada_bank ? 100 : 0;
        
        // ID 45: BPR (simulasi)
        $ada_bpr = false;
        foreach ($data['ekonomi'] as $ekonomi) {
            if (strpos(strtolower($ekonomi['nama_tempat'] ?? ''), 'bpr') !== false) {
                $ada_bpr = true;
                break;
            }
        }
        $skor += $ada_bpr ? 100 : 0;
        
        // ID 46: Akses kredit (berdasarkan keberadaan lembaga keuangan)
        $akses_kredit = $ada_bank || $ada_bpr ? 100 : 50; // 50 untuk akses informal
        $skor += $akses_kredit;
        
        return $skor / $total_indikator;
    }
    
    private function hitungLembagaEkonomi($data) {
        // ID 47: Koperasi (simulasi berdasarkan UMKM)
        $jumlah_umkm = count($data['umkm']);
        
        if ($jumlah_umkm >= 10) return 100;      // Banyak lembaga ekonomi
        if ($jumlah_umkm >= 5) return 75;        // Cukup
        if ($jumlah_umkm >= 1) return 50;        // Sedikit
        return 25;                               // Sangat sedikit
    }
    
    private function hitungTransportasi($data) {
        $skor = 0;
        $total_indikator = 3;
        
        // ID 48: Transportasi umum (simulasi)
        $ada_transportasi_umum = count($data['jalan']) > 2 ? 75 : 25;
        $skor += $ada_transportasi_umum;
        
        // ID 49: Jalan bisa dilalui kendaraan
        $jalan_bisa_dilalui = 0;
        $total_jalan = count($data['jalan']);
        foreach ($data['jalan'] as $jalan) {
            if (in_array($jalan['kondisi_jalan'], ['baik', 'sedang'])) {
                $jalan_bisa_dilalui++;
            }
        }
        $persentase_jalan_layak = $total_jalan > 0 ? ($jalan_bisa_dilalui / $total_jalan) * 100 : 0;
        $skor += $persentase_jalan_layak;
        
        // ID 50: Kualitas jalan
        $jalan_baik = 0;
        foreach ($data['jalan'] as $jalan) {
            if ($jalan['kondisi_jalan'] == 'baik') {
                $jalan_baik++;
            }
        }
        $persentase_jalan_baik = $total_jalan > 0 ? ($jalan_baik / $total_jalan) * 100 : 0;
        $skor += $persentase_jalan_baik;
        
        return $skor / $total_indikator;
    }
    
    private function tentukanStatusKetahanan($indeks) {
        if ($indeks >= $this->standar_ketahanan['kuat']) {
            return ['status' => 'Kuat', 'warna' => '#27AE60', 'keterangan' => 'Ketahanan ekonomi sangat baik'];
        } elseif ($indeks >= $this->standar_ketahanan['sedang']) {
            return ['status' => 'Sedang', 'warna' => '#F39C12', 'keterangan' => 'Ketahanan ekonomi cukup baik'];
        } else {
            return ['status' => 'Lemah', 'warna' => '#E74C3C', 'keterangan' => 'Ketahanan ekonomi perlu diperkuat'];
        }
    }
    
    private function tentukanPrioritas($indeks) {
        if ($indeks < 40) return 'Sangat Tinggi';
        if ($indeks < 60) return 'Tinggi';
        if ($indeks < 75) return 'Sedang';
        return 'Rendah';
    }
    
    private function generateRekomendasi($indeks_total, $skor_detail) {
        $rekomendasi = [];
        
        // Analisis kelemahan terbesar
        $kelemahan = [];
        foreach ($skor_detail as $dimensi => $skor) {
            if ($skor < 50) {
                $kelemahan[$dimensi] = $skor;
            }
        }
        
        // Sort kelemahan dari yang terburuk
        asort($kelemahan);
        
        // Generate rekomendasi berdasarkan kelemahan
        foreach ($kelemahan as $dimensi => $skor) {
            switch ($dimensi) {
                case 'keragaman':
                    $rekomendasi[] = "Diversifikasi kegiatan ekonomi dengan mengembangkan sektor non-pertanian";
                    break;
                case 'perdagangan':
                    $rekomendasi[] = "Bangun pusat perdagangan dan kembangkan usaha makanan/jasa";
                    break;
                case 'distribusi':
                    $rekomendasi[] = "Tingkatkan akses distribusi dan logistik";
                    break;
                case 'keuangan':
                    $rekomendasi[] = "Fasilitasi akses perbankan dan lembaga keuangan mikro";
                    break;
                case 'lembaga':
                    $rekomendasi[] = "Perkuat koperasi dan lembaga ekonomi masyarakat";
                    break;
                case 'transportasi':
                    $rekomendasi[] = "Perbaiki infrastruktur jalan dan transportasi umum";
                    break;
            }
        }
        
        // Jika tidak ada kelemahan signifikan
        if (empty($rekomendasi)) {
            if ($indeks_total >= 75) {
                $rekomendasi[] = "Pertahankan dan tingkatkan kualitas ketahanan ekonomi yang sudah baik";
            } else {
                $rekomendasi[] = "Optimalisasi semua dimensi untuk mencapai ketahanan ekonomi yang kuat";
            }
        }
        
        return $rekomendasi;
    }
    
    private function hitungDistribusiStatus($hasil_analisis) {
        $distribusi = ['Kuat' => 0, 'Sedang' => 0, 'Lemah' => 0];
        
        foreach ($hasil_analisis as $desa) {
            $status = $desa['status_ketahanan']['status'];
            $distribusi[$status]++;
        }
        
        return $distribusi;
    }
    
    private function analisisKomparatif($hasil_analisis) {
        $desa_terbaik = reset($hasil_analisis);
        $desa_terburuk = end($hasil_analisis);
        
        $gap_indeks = $desa_terbaik['indeks_total'] - $desa_terburuk['indeks_total'];
        
        return [
            'desa_terbaik' => $desa_terbaik,
            'desa_terburuk' => $desa_terburuk,
            'gap_indeks' => round($gap_indeks, 1),
            'kesenjangan' => $gap_indeks > 30 ? 'Tinggi' : ($gap_indeks > 15 ? 'Sedang' : 'Rendah')
        ];
    }
    
    private function generateRekomendasiKecamatan($hasil_analisis, $rata_rata_indeks) {
        $rekomendasi = [];
        
        // Analisis berdasarkan rata-rata kecamatan
        if ($rata_rata_indeks < 50) {
            $rekomendasi[] = "Prioritas utama: Perkuat fondasi ekonomi di seluruh desa";
            $rekomendasi[] = "Fokus pada infrastruktur dasar dan akses keuangan";
        } elseif ($rata_rata_indeks < 75) {
            $rekomendasi[] = "Tingkatkan kualitas layanan ekonomi yang sudah ada";
            $rekomendasi[] = "Kembangkan pusat-pusat perdagangan strategis";
        } else {
            $rekomendasi[] = "Pertahankan dan optimalkan ketahanan ekonomi yang sudah kuat";
            $rekomendasi[] = "Fokus pada inovasi dan peningkatan daya saing";
        }
        
        // Analisis kesenjangan antar desa
        $komparatif = $this->analisisKomparatif($hasil_analisis);
        if ($komparatif['kesenjangan'] == 'Tinggi') {
            $rekomendasi[] = "Kurangi kesenjangan dengan fokus pembangunan di desa tertinggal";
        }
        
        return $rekomendasi;
    }
}
?>