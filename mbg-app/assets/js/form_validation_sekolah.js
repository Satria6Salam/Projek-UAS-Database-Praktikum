/**
 * Title: Form Validation & Real-time Calculation for School Verification
 * Description: Mengamankan input porsi rusak, menghitung porsi diterima secara dinamis, 
 * menyuntikkan data manifes ke dalam modal, serta memvalidasi data sebelum submit.
 */

document.addEventListener("DOMContentLoaded", function () {
    const modalElement = document.getElementById('modalVerifikasi');
    if (!modalElement) return; 

    document.body.appendChild(modalElement);
    const inputRusak = document.getElementById('input-rusak');
    const porsiAwalHidden = document.getElementById('modal-porsi-awal');
    const displayPorsiBersih = document.getElementById('display-porsi-bounds'); // Dialihkan ke id dinamis
    const textPorsiBersih = document.getElementById('display-porsi-bersih');
    const inputPorsiDiterimaHidden = document.getElementById('input-porsi-diterima');
    const btnSubmitVerifikasi = document.getElementById('btn-submit-verifikasi');
    const errorValidationMsg = document.getElementById('error-validation-msg');
    const formVerifikasi = modalElement.querySelector('form');

    // Element tambahan di dalam modal untuk disuntikkan data dari tombol tabel
    const modalIdKirim = document.getElementById('modal-id-kirim');
    const modalNamaMenu = document.getElementById('modal-nama-menu');
    const modalDisplayPorsi = document.getElementById('modal-display-porsi');

    // =========================================================================
    // BRIDGE LOGIC: OPER DATA DARI TOMBOL TABEL KE DALAM MODAL
    // =========================================================================
    
    /**
     * Kejadian dipicu sesaat sebelum Modal Bootstrap ditampilkan ke layar.
     * Digunakan untuk membaca atribut data- milik tombol pengaktif yang diklik.
     */
    modalElement.addEventListener('show.bs.modal', function (event) {
        // Tombol yang memicu munculnya modal
        const button = event.relatedTarget;
        
        // Ekstraksi nilai parameter data fisik dari baris tabel
        const idKirim = button.getAttribute('data-id');
        const porsiAwal = button.getAttribute('data-porsi');
        const namaMenu = button.getAttribute('data-menu');

        // Suntikkan nilai ke dalam elemen UI di dalam Form Modal
        modalIdKirim.value = idKirim;
        modalNamaMenu.innerText = namaMenu;
        modalDisplayPorsi.value = formatRibuan(porsiAwal) + " Boks";
        
        // Set nilai acuan perhitungan internal (hidden input)
        porsiAwalHidden.value = porsiAwal;
        inputPorsiDiterimaHidden.value = porsiAwal;
        
        // Reset form input status setiap kali modal dibuka kembali
        inputRusak.value = 0;
        textPorsiBersih.innerHTML = `${formatRibuan(porsiAwal)} <span class="fs-6 text-muted fw-normal">Boks</span>`;
        errorValidationMsg.classList.add('d-none');
        btnSubmitVerifikasi.removeAttribute('disabled');
    });

    // =========================================================================
    // EVENT LISTENERS & REAL-TIME CALCULATION ENGINE
    // =========================================================================

    inputRusak.addEventListener('input', function () {
        const porsiAwal = parseInt(porsiAwalHidden.value) || 0;
        let porsiRusak = this.value;

        if (porsiRusak === '') {
            porsiRusak = 0;
        } else {
            porsiRusak = parseInt(porsiRusak);
        }

        // Guardrail batasan volume logika porsi rusak
        if (porsiRusak > porsiAwal || porsiRusak < 0 || isNaN(porsiRusak)) {
            errorValidationMsg.classList.remove('d-none');
            btnSubmitVerifikasi.setAttribute('disabled', 'true');
            textPorsiBersih.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Error</span>`;
            inputPorsiDiterimaHidden.value = 0;
        } else {
            errorValidationMsg.classList.add('d-none');
            btnSubmitVerifikasi.removeAttribute('disabled');
            
            const porsiBersih = porsiAwal - porsiRusak;
            
            textPorsiBersih.innerHTML = `${formatRibuan(porsiBersih)} <span class="fs-6 text-muted fw-normal">Boks</span>`;
            inputPorsiDiterimaHidden.value = porsiBersih;
        }
    });

    inputRusak.addEventListener('keydown', function (e) {
        const invalidChars = ["e", "E", "+", "-", "."];
        if (invalidChars.includes(e.key)) {
            e.preventDefault();
        }
    });

    // =========================================================================
    // SUBMIT INTERCEPTION & DIALOG CONFIRMATION
    // =========================================================================

    formVerifikasi.addEventListener('submit', function (e) {
        e.preventDefault(); 

        const porsiAwal = parseInt(porsiAwalHidden.value) || 0;
        const porsiRusak = parseInt(inputRusak.value) || 0;
        const porsiDiterima = parseInt(inputPorsiDiterimaHidden.value) || 0;

        if (porsiRusak > porsiAwal || porsiRusak < 0) {
            alert("Gagal Memproses: Kuantitas porsi rusak anomali dan melanggar batas.");
            return false;
        }

        if ((porsiDiterima + porsiRusak) !== porsiAwal) {
            alert("Gagal Memproses: Sinkronisasi kalkulasi jumlah boks tidak cocok.");
            return false;
        }

        const namaMenu = modalNamaMenu.innerText;
        const konfirmasiText = `Apakah Anda yakin ingin menyimpan data log penerimaan ini?\n\n` +
                               `Menu: ${namaMenu}\n` +
                               `Porsi Aman Diterima: ${formatRibuan(porsiDiterima)} Boks\n` +
                               `Porsi Rusak/Tumpah: ${formatRibuan(porsiRusak)} Boks\n\n` +
                               `Setelah disimpan, data terkunci secara permanen untuk keperluan audit.`;

        if (confirm(konfirmasiText)) {
            btnSubmitVerifikasi.setAttribute('disabled', 'true');
            btnSubmitVerifikasi.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...`;
            formVerifikasi.submit();
        }
    });

    function formatRibuan(angka) {
        return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
});