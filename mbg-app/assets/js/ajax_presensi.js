/**
 * Title: AJAX Student Attendance Checklist (Real-time Handshake)
 * Description: Mengawal pengiriman data presensi tanpa reload, melakukan filter kelas visual,
 * serta menerapkan intersept batas kuota (jml_porsi_diterima) pada sisi depan gawai/tablet.
 */

document.addEventListener("DOMContentLoaded", function() {
    const checkboxes = document.querySelectorAll('.check-makan-siswa');
    const labelTerpakai = document.getElementById('label-porsi-terpakai');
    const labelMaks = document.getElementById('label-porsi-maks');
    const filterButtons = document.querySelectorAll('.filter-kelas');
    const cardItems = document.querySelectorAll('.card-item-siswa');

    // =========================================================================
    // 1. GAWAI/TABLET TOUCH FRIENDLY FILTER SYSTEM
    // =========================================================================
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Reset status aktif tombol filter sebelumnya
            filterButtons.forEach(b => b.classList.remove('btn-dark', 'active'));
            filterButtons.forEach(b => b.classList.add('btn-outline-secondary'));
            
            // Set status aktif pada tombol yang dipilih
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-dark', 'active');

            const kelasSelected = this.getAttribute('data-kelas');
            
            // Sembunyikan atau tampilkan elemen kartu siswa berdasarkan target kelas
            cardItems.forEach(item => {
                if (kelasSelected === 'ALL' || item.getAttribute('data-kelas-siswa') === kelasSelected) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        });
    });

    // =========================================================================
    // 2. REAL-TIME ASYNCHRONOUS CHECKBOX HANDLER
    // =========================================================================
    checkboxes.forEach(box => {
        box.addEventListener('change', function() {
            const currentBox = this;
            const nisnValue = currentBox.getAttribute('data-nisn');
            const idKirimValue = currentBox.getAttribute('data-id-kirim');
            const actionType = currentBox.checked ? 'insert' : 'delete';
            const targetCard = currentBox.closest('.card');

            // Kunci interaksi tombol sementara waktu saat request berjalan (Anti-Spam Click)
            currentBox.setAttribute('disabled', 'true');

            // Konstruksi payload URL-Encoded parameters
            const payload = new URLSearchParams();
            payload.append('id_siswa', nisnValue);
            payload.append('id_kirim', idKirimValue);
            payload.append('action', actionType);

            // Kirim data menuju backend handler presensi_siswa.php secara asinkron
            fetch('/mbg-app/actions/sekolah/presensi_siswa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP status ' + response.status);
                return response.json();
            })
            .then(data => {
                // Buka kembali proteksi interaksi tombol
                currentBox.removeAttribute('disabled');
                
                if (data.status === 'success') {
                    // Update counter visual secara real-time berdasarkan respons JSON server
                    labelTerpakai.innerText = data.current_total;

                    // Berikan feedback visual perubahan warna border/background kartu secara instan
                    if (actionType === 'insert') {
                        targetCard.classList.remove('border-start-muted', 'bg-white');
                        targetCard.classList.add('border-start-success', 'bg-success-light');
                    } else {
                        targetCard.classList.remove('border-start-success', 'bg-success-light');
                        targetCard.classList.add('border-start-muted', 'bg-white');
                    }
                } else {
                    // Batalkan perubahan check jika ditolak oleh guardrail sistem backend
                    currentBox.checked = !currentBox.checked;
                    alert("Aksi Ditolak Sistem:\n" + data.message);
                }
            })
            .catch(err => {
                // Pulihkan kontrol dan kembalikan state jika terjadi kendala koneksi hulu/down
                currentBox.removeAttribute('disabled');
                currentBox.checked = !currentBox.checked;
                alert("Gangguan Komunikasi: Gagal mengirimkan data kehadiran ke server pusat.");
            });
        });
    });
});