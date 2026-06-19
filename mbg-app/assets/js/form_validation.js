// =========================================================================
// VALIDASI FORMULIR BOOTSTRAP (CLIENT-SIDE)
// =========================================================================
// Mencegah form disubmit jika ada input wajib (required) yang belum diisi
(function () {
    'use strict'
  
    // Mengambil semua form di halaman yang memiliki class 'needs-validation'
    var forms = document.querySelectorAll('.needs-validation')
  
    // Mengubah Nodelist menjadi Array dan melakukan perulangan
    Array.prototype.slice.call(forms)
      .forEach(function (form) {
        form.addEventListener('submit', function (event) {
          // Jika formulir tidak valid
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
  
          // Menambahkan class 'was-validated' dari Bootstrap untuk memunculkan pesan error merah
          form.classList.add('was-validated')
        }, false)
      })
  })()
  
// =========================================================================
// KONFIRMASI HAPUS DATA (RESTRICT DELETE GUARD)
// =========================================================================
// Gunakan fungsi ini pada event onclick di tombol hapus/delete
function konfirmasiHapus(event) {
    var konfirmasi = confirm("⚠️ Peringatan: Apakah Anda yakin ingin menghapus data ini? Data yang terelasi dengan transaksi tidak dapat dihapus.");
    
    if (!konfirmasi) {
        // Batalkan navigasi ke link hapus jika user menekan 'Cancel'
        event.preventDefault();
    }
}

// =========================================================================
// TOGGLE SIDEBAR
// =========================================================================
// Script untuk mengendalikan tombol hamburger menu di Topbar
document.addEventListener("DOMContentLoaded", function() {
    var sidebarToggler = document.querySelector('.navbar-toggler');
    var sidebar = document.getElementById('sidebar');

    if (sidebarToggler && sidebar) {
        sidebarToggler.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('collapsed');
        });
    }
});