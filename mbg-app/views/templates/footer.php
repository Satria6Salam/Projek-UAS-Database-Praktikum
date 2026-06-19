<footer class="mt-5 py-3 border-top text-center text-muted bg-white rounded shadow-sm">
                <div class="container-fluid">
                    <span class="small">
                        &copy; <?= date('Y'); ?> 
                        <img src="/mbg-app/assets/images/logo.jfif" alt="" style="max-height: 18px; width: auto;" onerror="this.style.display='none';"> 
                        <strong>MBG-APP</strong> (Makan Bergizi Gratis). Hak Cipta Dilindungi.
                    </span>
                    <br>
                    <span class="small text-secondary" style="font-size: 0.8rem;">
                        <i class="bi bi-shield-check text-success"></i> Sistem Informasi Terintegrasi Distribusi & Presensi Pangan
                    </span>
                </div>
            </footer>
            
        </main> 
    </div> <?php 
$current_path = $_SERVER['PHP_SELF']; 
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/mbg-app/assets/js/form_validation.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sidebar = document.getElementById('sidebar');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        
        if(sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function () {
                sidebar.classList.toggle('active');
            });
        }
        
        // Menutup sidebar jika klik di luar area saat mode mobile overlay aktif
        document.addEventListener('click', function (event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickInsideButton = sidebarCollapse ? sidebarCollapse.contains(event.target) : false;
            
            if (!isClickInsideSidebar && !isClickInsideButton && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    });
</script>

<?php if (strpos($current_path, 'sekolah/presensi.php') !== false): ?>
    <script src="/mbg-app/assets/js/ajax_presensi.js"></script>
<?php endif; ?>

<?php if (strpos($current_path, 'sekolah/verifikasi.php') !== false): ?>
    <script src="/mbg-app/assets/js/form_validation_sekolah.js"></script>
<?php endif; ?>

<?php if (strpos($current_path, 'dapur/menu.php') !== false): ?>
    <script src="/mbg-app/assets/js/dynamic_menu.js"></script>
<?php endif; ?>

<?php if (strpos($current_path, 'dapur/distribusi.php') !== false): ?>
    <script src="/mbg-app/assets/js/guard_distribusi.js"></script>
<?php endif; ?>

<?php if (strpos($current_path, 'admin/master/pengguna.php') !== false): ?>
    <script src="/mbg-app/assets/js/dynamic_role.js"></script>
<?php endif; ?>

<?php if (strpos($current_path, 'admin/dashboard.php') !== false || strpos($current_path, 'dapur/dashboard.php') !== false): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/mbg-app/assets/js/realtime_dashboard.js"></script>
<?php endif; ?>

<?php if (strpos($current_path, 'admin/master/siswa.php') !== false): ?>
    <script src="/mbg-app/assets/js/import_handler.js"></script>
<?php endif; ?>

</body>
</html>