// // =========================================================================
// // CLIENT LOGIC: ASYNCHRONOUS POLLING ENGINE & CHART REAL-TIME TRACKING
// // =========================================================================
// document.addEventListener("DOMContentLoaded", function () {
//     var chartElement = document.getElementById('macroDistributionChart');
//     if (!chartElement) return;

//     var ctx = chartElement.getContext('2d');
    
//     // Membaca data inisiasi awal yang tertanam pada atribut data HTML
//     var initialProses = parseInt(chartElement.getAttribute('data-proses')) || 0;
//     var initialTiba = parseInt(chartElement.getAttribute('data-tiba')) || 0;

//     // Inisialisasi Chart Rangkuman Distribusi Makro (Doughnut Style)
//     var distributionChart = new Chart(ctx, {
//         type: 'doughnut',
//         data: {
//             labels: ['Dalam Proses', 'Sudah Tiba'],
//             datasets: [{
//                 data: [initialProses, initialTiba],
//                 backgroundColor: ['#2563eb', '#16a34a'],
//                 borderWidth: 2,
//                 borderColor: '#ffffff'
//             }]
//         },
//         options: {
//             responsive: true,
//             maintainAspectRatio: false,
//             plugins: {
//                 legend: { display: false }
//             },
//             cutout: '75%'
//         }
//     });

//     // -------------------------------------------------------------------------
//     // ENGINE: ASYNCHRONOUS BACKGROUND POLLING SYSTEM (BACKGROUND WORKER)
//     // -------------------------------------------------------------------------
//     setInterval(function() {
//         fetch(window.location.href)
//             .then(response => {
//                 if (!response.ok) throw new Error('Network response was not ok');
//                 return response.text();
//             })
//             .then(html => {
//                 var parser = new DOMParser();
//                 var doc = parser.parseFromString(html, 'text/html');
                
//                 // 1. Sinkronisasi Data Kontainer Tabel Log Aktivitas Aktual
//                 var targetLogContainer = document.getElementById('realtime-log-container');
//                 var sourceLogContainer = doc.getElementById('realtime-log-container');
//                 if (targetLogContainer && sourceLogContainer) {
//                     targetLogContainer.innerHTML = sourceLogContainer.innerHTML;
//                 }

//                 // 2. Sinkronisasi Tulisan Angka Indikator Bawah Grafik
//                 var txtProses = document.getElementById('txt-porsi-proses');
//                 var txtTiba = document.getElementById('txt-porsi-tiba');
                
//                 var srcProses = doc.getElementById('txt-porsi-proses');
//                 var srcTiba = doc.getElementById('txt-porsi-tiba');

//                 if (txtProses && srcProses) txtProses.innerText = srcProses.innerText;
//                 if (txtTiba && srcTiba) txtTiba.innerText = srcTiba.innerText;

//                 // 3. Ekstraksi Nilai Integer Hasil Polling Untuk Update Animasi Grafik
//                 if (srcProses && srcTiba) {
//                     var valProses = parseInt(srcProses.innerText.replace(/,/g, '')) || 0;
//                     var valTiba = parseInt(srcTiba.innerText.replace(/,/g, '')) || 0;

//                     distributionChart.data.datasets[0].data = [valProses, valTiba];
//                     distributionChart.update();
//                 }
//             })
//             .catch(err => console.warn('Peringatan: Gagal mensinkronisasikan data dashboard harian secara real-time.', err));
//     }, 10000); // Polling background worker dieksekusi berkala setiap 10 detik harian
// });

// =========================================================================
// CLIENT LOGIC: ASYNCHRONOUS POLLING ENGINE & CHART REAL-TIME TRACKING
// =========================================================================
document.addEventListener("DOMContentLoaded", function () {
    
    // -------------------------------------------------------------------------
    // INDEPENDENT INITIALIZATION: GRAPH COMPONENT (DAPUR / ADMIN SECTOR Only)
    // -------------------------------------------------------------------------
    var chartElement = document.getElementById('macroDistributionChart');
    var distributionChart = null;

    // Logika grafik hanya diinisialisasi jika elemen kanvasnya eksis di halaman aktif
    if (chartElement) {
        var ctx = chartElement.getContext('2d');
        
        var initialProses = parseInt(chartElement.getAttribute('data-proses')) || 0;
        var initialTiba = parseInt(chartElement.getAttribute('data-tiba')) || 0;

        distributionChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Dalam Proses', 'Sudah Tiba'],
                datasets: [{
                    data: [initialProses, initialTiba],
                    backgroundColor: ['#2563eb', '#16a34a'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '75%'
            }
        });
    }

    // -------------------------------------------------------------------------
    // ENGINE: ASYNCHRONOUS BACKGROUND POLLING SYSTEM (GLOBAL BACKGROUND WORKER)
    // -------------------------------------------------------------------------
    setInterval(function() {
        fetch(window.location.href)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                // =========================================================================
                // SEKTOR 1: AKTOR DAPUR / ADMIN / SEKOLAH (LOGIKAL LAMA - TIDAK DIUBAH)
                // =========================================================================
                
                // 1. Sinkronisasi Data Kontainer Tabel Log Aktivitas Aktual
                var targetLogContainer = document.getElementById('realtime-log-container');
                var sourceLogContainer = doc.getElementById('realtime-log-container');
                if (targetLogContainer && sourceLogContainer) {
                    targetLogContainer.innerHTML = sourceLogContainer.innerHTML;
                }

                // 2. Sinkronisasi Tulisan Angka Indikator Bawah Grafik
                var txtProses = document.getElementById('txt-porsi-proses');
                var txtTiba = document.getElementById('txt-porsi-tiba');
                
                var srcProses = doc.getElementById('txt-porsi-proses');
                var srcTiba = doc.getElementById('txt-porsi-tiba');

                if (txtProses && srcProses) txtProses.innerText = srcProses.innerText;
                if (txtTiba && srcTiba) txtTiba.innerText = srcTiba.innerText;

                // 3. Ekstraksi Nilai Integer Hasil Polling Untuk Update Animasi Grafik (Jika Grafik Eksis)
                if (distributionChart && srcProses && srcTiba) {
                    var valProses = parseInt(srcProses.innerText.replace(/,/g, '')) || 0;
                    var valTiba = parseInt(srcTiba.innerText.replace(/,/g, '')) || 0;

                    distributionChart.data.datasets[0].data = [valProses, valTiba];
                    distributionChart.update();
                }

                // =========================================================================
                // SEKTOR 2: AKTOR SUPPLIER LOKAL (LOGIKAL BARU - ISOLASI MANDIRI)
                // =========================================================================
                
                // 1. Sinkronisasi Tabel Riwayat Log Records Transaksi Pasokan Historis Vendor
                var targetSupplierContainer = document.getElementById('realtime-supplier-container');
                var sourceSupplierContainer = doc.getElementById('realtime-supplier-container');
                if (targetSupplierContainer && sourceSupplierContainer) {
                    targetSupplierContainer.innerHTML = sourceSupplierContainer.innerHTML;
                }

                // 2. Sinkronisasi Widget Ringkasan Angka Nominal Pasokan Supplier
                var targetWidgetSupplier = document.getElementById('widget-total-pasokan');
                var sourceWidgetSupplier = doc.getElementById('widget-total-pasokan');
                if (targetWidgetSupplier && sourceWidgetSupplier) {
                    targetWidgetSupplier.innerText = sourceWidgetSupplier.innerText;
                }
            })
            .catch(err => console.warn('Peringatan: Gagal mensinkronisasikan data dashboard harian secara real-time.', err));
    }, 10000); // Polling background worker dieksekusi berkala setiap 10 detik harian
});