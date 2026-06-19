// =========================================================================
// INTERFACE HANDLER: BULK IMPORT EXTENSION VALIDATOR & ANIMATED PROGRESS BAR
// =========================================================================
document.addEventListener("DOMContentLoaded", function () {
    var formImport = document.getElementById('form-import-siswa');
    var fileInput = document.getElementById('file_excel');
    var progressContainer = document.getElementById('import-progress-container');
    var progressBar = document.getElementById('import-progress-bar');

    if (formImport && fileInput) {
        formImport.addEventListener('submit', function (event) {
            var filePath = fileInput.value;
            var allowedExtensions = /(\.csv)$/i;

            // Validasi format berkas hulu di sisi klien
            if (!allowedExtensions.exec(filePath)) {
                alert('Format file salah! Sistem hanya mengizinkan dokumen dengan ekstensi .csv');
                fileInput.value = '';
                event.preventDefault();
                return false;
            }

            // Memunculkan efek visual progress bar simulasi proses unggah data massal
            progressContainer.classList.remove('d-none');
            var width = 0;
            var interval = setInterval(function () {
                if (width >= 90) {
                    clearInterval(interval);
                } else {
                    width += 15;
                    progressBar.style.width = width + '%';
                    progressBar.setAttribute('aria-valuenow', width);
                }
            }, 100);
        });
    }
});