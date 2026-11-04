// Desa Profile Charts - Sistem Informasi Publik
// File: public-portal/pages/desa-detail/js/desa-profile.js

(function() {
    'use strict';

    async function loadChartJS() {
        if (typeof Chart !== 'undefined') return;
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    async function initCharts() {
        const ageCanvas = document.getElementById('usiaChart');
        const genderCanvas = document.getElementById('genderChart');
        if (!ageCanvas && !genderCanvas) return;

        try {
            await loadChartJS();

            if (ageCanvas) {
                const labels = JSON.parse(ageCanvas.dataset.labels || '[]');
                const values = JSON.parse(ageCanvas.dataset.values || '[]').map(Number);

                if (labels.length && labels.length === values.length) {
                    new Chart(ageCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{
                                data: values,
                                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => `${ctx.label}: ${ctx.parsed.toLocaleString('id-ID')} jiwa`
                                    }
                                }
                            },
                            cutout: '60%'
                        }
                    });
                }
            }

            if (genderCanvas) {
                const statCards = document.querySelectorAll('.desa-stat-card');
                let laki = 0, perempuan = 0;

                statCards.forEach(card => {
                    const label = card.querySelector('.desa-stat-label')?.textContent;
                    const value = parseInt(card.querySelector('.desa-stat-value')?.textContent.replace(/\./g, '')) || 0;
                    if (label === 'Laki-laki') laki = value;
                    if (label === 'Perempuan') perempuan = value;
                });

                new Chart(genderCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Laki-laki', 'Perempuan'],
                        datasets: [{
                            data: [laki, perempuan],
                            backgroundColor: ['#3F72AF', '#FF6384'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.parsed.toLocaleString('id-ID')} jiwa`
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }
        } catch (error) {
            console.error('Gagal memuat grafik profil desa:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', initCharts);
})();
