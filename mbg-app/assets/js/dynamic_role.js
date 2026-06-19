// =========================================================================
// CLIENT LOGIC: DYNAMIC DROPDOWN CONTROL FOR SINGLE ROLE RESTRICTION
// =========================================================================
document.addEventListener("DOMContentLoaded", function () {
    var selectRole = document.getElementById("select-role");
    
    var containerSekolah = document.getElementById("mapping-sekolah-container");
    var containerDapur = document.getElementById("mapping-dapur-container");
    var containerSupplier = document.getElementById("mapping-supplier-container");

    var inputSekolah = document.getElementById("id_sekolah");
    var inputStaf = document.getElementById("id_staf");
    var inputSupplier = document.getElementById("id_supplier");

    function adjustMappingFields() {
        var selectedRole = selectRole.value;

        // Reset state awal: sembunyikan semua kontainer relasi fisik
        containerSekolah.classList.add("d-none");
        containerDapur.classList.add("d-none");
        containerSupplier.classList.add("d-none");

        // Hapus penanda required validasi bawaan html5
        inputSekolah.removeAttribute("required");
        inputStaf.removeAttribute("required");
        inputSupplier.removeAttribute("required");

        // Evaluasi kondisional kemunculan objek kontrol berdasarkan tipe role pilihan
        if (selectedRole === "Sekolah") {
            containerSekolah.classList.remove("d-none");
            inputSekolah.setAttribute("required", "required");
        } else if (selectedRole === "Dapur") {
            containerDapur.classList.remove("d-none");
            inputStaf.setAttribute("required", "required");
        } else if (selectedRole === "Supplier") {
            containerSupplier.classList.remove("d-none");
            inputSupplier.setAttribute("required", "required");
        }
    }

    if (selectRole) {
        selectRole.addEventListener("change", adjustMappingFields);
        // Jalankan fungsi saat render inisiasi awal untuk memfasilitasi modul mode edit data
        adjustMappingFields();
    }
});