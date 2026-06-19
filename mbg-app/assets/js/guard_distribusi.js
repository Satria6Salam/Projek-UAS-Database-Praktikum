// =========================================================================
// JAVASCRIPT: GUARDRAIL DISTRIBUTION VERIFIER & INTERFACES MANAGEMENT
// =========================================================================

document.addEventListener("DOMContentLoaded", function() {
    const selectSekolah = document.getElementById('selectSekolah');
    const inputPorsi = document.getElementById('inputPorsi');
    const infoKuotaBatas = document.getElementById('infoKuotaBatas');
    const maxQuotaLabel = document.getElementById('maxQuotaLabel');
    const formManifestKirim = document.getElementById('formManifestKirim');

    if (selectSekolah && inputPorsi) {
        // Mengunci Aturan Bisnis Hilir: Sinkronisasi ambang batas kuota sekolah tujuan
        selectSekolah.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const kuotaSiswa = parseInt(selectedOption.getAttribute('data-kuota')) || 0;
            
            if (kuotaSiswa > 0) {
                maxQuotaLabel.textContent = kuotaSiswa;
                inputPorsi.setAttribute('max', kuotaSiswa);
                infoKuotaBatas.classList.remove('d-none');
            } else {
                infoKuotaBatas.classList.add('d-none');
                inputPorsi.removeAttribute('max');
            }
        });

        // Interseptor Validasi Formulir Sebelum Dikirim ke Backend Actions
        formManifestKirim.addEventListener('submit', function(e) {
            const selectedOption = selectSekolah.options[selectSekolah.selectedIndex];
            const kuotaSiswa = parseInt(selectedOption.getAttribute('data-kuota')) || 0;
            const porsiInput = parseInt(inputPorsi.value) || 0;

            if (porsiInput > kuotaSiswa) {
                e.preventDefault();
                alert("Operasional Dibatalkan: Jumlah porsi boks yang dimasukkan melampaui batasan jumlah siswa aktif (kuota harian) di sekolah tujuan!");
            }
        });
    }
});