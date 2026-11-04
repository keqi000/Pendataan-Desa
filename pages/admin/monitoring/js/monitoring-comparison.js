// Monitoring Comparison — Perbandingan Detail Antar Desa
(function () {
    'use strict';

    class MonitoringComparison {
        constructor() {
            if (document.querySelector('.monitoring-admin-container')) {
                this.init();
            }
        }

        init() {
            const s1 = document.getElementById('desa1Select');
            const s2 = document.getElementById('desa2Select');
            if (s1) s1.addEventListener('change', () => this.updateComparison());
            if (s2) s2.addEventListener('change', () => this.updateComparison());
        }

        updateComparison() {
            const desa1 = document.getElementById('desa1Select')?.value;
            const desa2 = document.getElementById('desa2Select')?.value;
            const result = document.getElementById('comparisonResult');
            if (!result) return;

            if (desa1 && desa2 && desa1 !== desa2) {
                result.style.display = 'block';
                this.loadComparison(desa1, desa2);
            } else {
                result.style.display = 'none';
            }
        }

        async loadComparison(id1, id2) {
            const base = window.MonitoringUtils?.getBaseUrl() ?? '';
            try {
                const [d1, d2] = await Promise.all([
                    this.fetchDesa(base, id1),
                    this.fetchDesa(base, id2)
                ]);
                this.render(d1, d2);
            } catch (e) {
                console.error('Gagal memuat data perbandingan:', e);
            }
        }

        async fetchDesa(base, id) {
            const get = (type) => fetch(
                `${base}pages/admin/monitoring/monitoring.php?endpoint=all-desa-data&type=${type}&desa_id=${id}`,
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
            ).then(r => r.json());

            const [desa, penduduk, fasilitas, infrastruktur, umkm, mataPencaharian] = await Promise.all([
                fetch(`${base}pages/admin/monitoring/monitoring.php?endpoint=desa-detail&id=${id}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()),
                get('penduduk'),
                get('pendidikan'),
                get('infrastruktur'),
                get('umkm'),
                get('ekonomi'),
            ]);

            const rows  = (r) => r?.success ? (r.data ?? []) : (Array.isArray(r) ? r : []);
            const infra = infrastruktur?.success ? (infrastruktur.data ?? {}) : (infrastruktur ?? {});

            return {
                desa:            desa?.success ? (desa.data?.desa_info ?? desa.data) : {},
                penduduk:        rows(penduduk),
                fasilitas:       rows(fasilitas),
                jalan:           infra.jalan    ?? [],
                jembatan:        infra.jembatan ?? [],
                umkm:            rows(umkm),
                mataPencaharian: rows(mataPencaharian),
            };
        }

        render(d1, d2) {
            const nama1 = d1.desa?.nama_desa ?? 'Desa 1';
            const nama2 = d2.desa?.nama_desa ?? 'Desa 2';
            this.renderUmum(d1, d2, nama1, nama2);
            this.renderDemografi(d1, d2, nama1, nama2);
            this.renderPendidikan(d1, d2, nama1, nama2);
            this.renderEkonomi(d1, d2, nama1, nama2);
            this.renderInfrastruktur(d1, d2, nama1, nama2);
        }

        // Subheading dengan font sama besar dengan th tabel
        subheading(text) {
            return `<div style="font-size:1rem;font-weight:600;margin:16px 0 6px 0">${text}</div>`;
        }

        tbl(headers, rows) {
            const th = headers.map(h => `<th>${h}</th>`).join('');
            const tr = rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('') ||
                `<tr><td colspan="${headers.length}" class="text-center text-muted">Tidak ada data</td></tr>`;
            return `<div class="table-responsive"><table class="table table-sm"><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></div>`;
        }

        pct(n, total) { return total > 0 ? ` (${((n / total) * 100).toFixed(1)}%)` : ''; }

        renderUmum(d1, d2, n1, n2) {
            const kk1 = new Set(d1.penduduk.map(p => p.id_keluarga)).size;
            const kk2 = new Set(d2.penduduk.map(p => p.id_keluarga)).size;
            const lk1 = d1.penduduk.filter(p => p.jenis_kelamin === 'L').length;
            const lk2 = d2.penduduk.filter(p => p.jenis_kelamin === 'L').length;
            const pr1 = d1.penduduk.filter(p => p.jenis_kelamin === 'P').length;
            const pr2 = d2.penduduk.filter(p => p.jenis_kelamin === 'P').length;

            const rows = [
                ['Luas Wilayah (km²)', d1.desa?.luas_wilayah ?? '-', d2.desa?.luas_wilayah ?? '-'],
                ['Jumlah Dusun',       d1.desa?.jumlah_dusun ?? '-', d2.desa?.jumlah_dusun ?? '-'],
                ['Total Penduduk',     `${d1.penduduk.length} jiwa`, `${d2.penduduk.length} jiwa`],
                ['Total KK',           `${kk1} KK`, `${kk2} KK`],
                ['Rata-rata per KK',   kk1 > 0 ? (d1.penduduk.length / kk1).toFixed(1) : '-', kk2 > 0 ? (d2.penduduk.length / kk2).toFixed(1) : '-'],
                ['Laki-laki',          `${lk1}${this.pct(lk1, d1.penduduk.length)}`, `${lk2}${this.pct(lk2, d2.penduduk.length)}`],
                ['Perempuan',          `${pr1}${this.pct(pr1, d1.penduduk.length)}`, `${pr2}${this.pct(pr2, d2.penduduk.length)}`],
            ];
            const el = document.getElementById('dataUmumContent');
            if (el) el.innerHTML = this.tbl(['Indikator', n1, n2], rows);
        }

        renderDemografi(d1, d2, n1, n2) {
            const usia = (d, min, max) => d.penduduk.filter(p => p.usia >= min && p.usia <= max).length;
            const usiaRows = [
                ['0–14 tahun',  `${usia(d1,0,14)}${this.pct(usia(d1,0,14),d1.penduduk.length)}`,    `${usia(d2,0,14)}${this.pct(usia(d2,0,14),d2.penduduk.length)}`],
                ['15–64 tahun', `${usia(d1,15,64)}${this.pct(usia(d1,15,64),d1.penduduk.length)}`,  `${usia(d2,15,64)}${this.pct(usia(d2,15,64),d2.penduduk.length)}`],
                ['65+ tahun',   `${usia(d1,65,120)}${this.pct(usia(d1,65,120),d1.penduduk.length)}`,`${usia(d2,65,120)}${this.pct(usia(d2,65,120),d2.penduduk.length)}`],
            ];

            const agamaSet = [...new Set([
                ...d1.penduduk.map(p => p.agama),
                ...d2.penduduk.map(p => p.agama)
            ].filter(Boolean))].sort();
            const agamaRows = agamaSet.map(a => {
                const c1 = d1.penduduk.filter(p => p.agama === a).length;
                const c2 = d2.penduduk.filter(p => p.agama === a).length;
                return [a, `${c1}${this.pct(c1,d1.penduduk.length)}`, `${c2}${this.pct(c2,d2.penduduk.length)}`];
            });

            const statusSet = [...new Set([
                ...d1.penduduk.map(p => p.status_pernikahan),
                ...d2.penduduk.map(p => p.status_pernikahan)
            ].filter(Boolean))].sort();
            const statusRows = statusSet.map(s => {
                const c1 = d1.penduduk.filter(p => p.status_pernikahan === s).length;
                const c2 = d2.penduduk.filter(p => p.status_pernikahan === s).length;
                return [s, `${c1}${this.pct(c1,d1.penduduk.length)}`, `${c2}${this.pct(c2,d2.penduduk.length)}`];
            });

            const el = document.getElementById('demografisContent');
            if (el) el.innerHTML =
                this.subheading('Struktur Usia')     + this.tbl(['Kelompok Usia', n1, n2], usiaRows) +
                this.subheading('Agama')             + this.tbl(['Agama', n1, n2], agamaRows) +
                this.subheading('Status Pernikahan') + this.tbl(['Status', n1, n2], statusRows);
        }

        renderPendidikan(d1, d2, n1, n2) {
            const order = ['Tidak Sekolah','SD','SMP','SMA','D3','S1','S2','S3'];
            const pdkSet = [...new Set([
                ...d1.penduduk.map(p => p.pendidikan_terakhir),
                ...d2.penduduk.map(p => p.pendidikan_terakhir)
            ].filter(Boolean))].sort((a,b) => order.indexOf(a) - order.indexOf(b));

            const pdkRows = pdkSet.map(t => {
                const c1 = d1.penduduk.filter(p => p.pendidikan_terakhir === t).length;
                const c2 = d2.penduduk.filter(p => p.pendidikan_terakhir === t).length;
                return [t, `${c1}${this.pct(c1,d1.penduduk.length)}`, `${c2}${this.pct(c2,d2.penduduk.length)}`];
            });

            const fasSet = [...new Set([
                ...d1.fasilitas.map(f => f.jenis_pendidikan),
                ...d2.fasilitas.map(f => f.jenis_pendidikan)
            ].filter(Boolean))].sort();
            const fasRows = fasSet.map(j => [
                j,
                `${d1.fasilitas.filter(f => f.jenis_pendidikan === j).length} unit`,
                `${d2.fasilitas.filter(f => f.jenis_pendidikan === j).length} unit`,
            ]);

            const el = document.getElementById('pendidikanContent');
            if (el) el.innerHTML =
                this.subheading('Tingkat Pendidikan Penduduk') + this.tbl(['Tingkat Pendidikan', n1, n2], pdkRows) +
                this.subheading('Fasilitas Pendidikan')        + this.tbl(['Jenis', n1, n2], fasRows);
        }

        renderEkonomi(d1, d2, n1, n2) {
            const pkjSet = [...new Set([
                ...d1.penduduk.map(p => p.pekerjaan),
                ...d2.penduduk.map(p => p.pekerjaan)
            ].filter(p => p && p !== 'undefined'))].sort();

            const pkjRows = pkjSet.map(pk => {
                const c1 = d1.penduduk.filter(p => p.pekerjaan === pk).length;
                const c2 = d2.penduduk.filter(p => p.pekerjaan === pk).length;
                if (!c1 && !c2) return null;
                return [pk, `${c1}${this.pct(c1,d1.penduduk.length)}`, `${c2}${this.pct(c2,d2.penduduk.length)}`];
            }).filter(Boolean);

            const potensiRows = [
                ['UMKM',            `${d1.umkm.length} unit`,            `${d2.umkm.length} unit`],
                ['Mata Pencaharian', `${d1.mataPencaharian.length} data`, `${d2.mataPencaharian.length} data`],
            ];

            const el = document.getElementById('ekonomiContent');
            if (el) el.innerHTML =
                this.subheading('Pekerjaan Penduduk') + this.tbl(['Pekerjaan', n1, n2], pkjRows) +
                this.subheading('Potensi Ekonomi')    + this.tbl(['Indikator', n1, n2], potensiRows);
        }

        renderInfrastruktur(d1, d2, n1, n2) {
            const kondisiJalan = (d, k) => d.jalan.filter(j => j.kondisi_jalan === k).length;
            const kondisiJmbt  = (d, k) => d.jembatan.filter(j => j.kondisi_jembatan === k).length;

            const jalanRows = [
                ['Baik',   `${kondisiJalan(d1,'baik')}${this.pct(kondisiJalan(d1,'baik'),d1.jalan.length)}`,    `${kondisiJalan(d2,'baik')}${this.pct(kondisiJalan(d2,'baik'),d2.jalan.length)}`],
                ['Sedang', `${kondisiJalan(d1,'sedang')}${this.pct(kondisiJalan(d1,'sedang'),d1.jalan.length)}`, `${kondisiJalan(d2,'sedang')}${this.pct(kondisiJalan(d2,'sedang'),d2.jalan.length)}`],
                ['Rusak',  `${kondisiJalan(d1,'rusak')}${this.pct(kondisiJalan(d1,'rusak'),d1.jalan.length)}`,   `${kondisiJalan(d2,'rusak')}${this.pct(kondisiJalan(d2,'rusak'),d2.jalan.length)}`],
                ['Total',  `${d1.jalan.length} unit`, `${d2.jalan.length} unit`],
            ];
            const jmbtRows = [
                ['Baik',   `${kondisiJmbt(d1,'baik')} unit`,   `${kondisiJmbt(d2,'baik')} unit`],
                ['Sedang', `${kondisiJmbt(d1,'sedang')} unit`, `${kondisiJmbt(d2,'sedang')} unit`],
                ['Rusak',  `${kondisiJmbt(d1,'rusak')} unit`,  `${kondisiJmbt(d2,'rusak')} unit`],
                ['Total',  `${d1.jembatan.length} unit`, `${d2.jembatan.length} unit`],
            ];

            const el = document.getElementById('infrastrukturContent');
            if (el) el.innerHTML =
                this.subheading('Kondisi Jalan')    + this.tbl(['Kondisi', n1, n2], jalanRows) +
                this.subheading('Kondisi Jembatan') + this.tbl(['Kondisi', n1, n2], jmbtRows);
        }
    }

    window.updateComparison = function () {
        window.MonitoringComparison?.updateComparison();
    };

    window.showComparisonTab = function (tabName) {
        document.querySelectorAll('#comparisonResult .tab-pane').forEach(p => p.style.display = 'none');
        document.querySelectorAll('#comparisonResult .monitoring-admin-tab-btn').forEach(b => b.classList.remove('active'));
        const pane = document.getElementById(tabName);
        if (pane) pane.style.display = 'block';
        if (event?.target) event.target.classList.add('active');
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.MonitoringComparison = new MonitoringComparison();
    });

    if (document.readyState !== 'loading') {
        window.MonitoringComparison = new MonitoringComparison();
    }
})();
