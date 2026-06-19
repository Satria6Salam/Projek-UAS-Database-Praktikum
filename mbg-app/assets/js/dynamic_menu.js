// =========================================================================
// CLIENT-SIDE LOGIC: INTERACTIVE FORM ARRAY MULTIPLE ROW CONTROLLER
// =========================================================================
document.addEventListener("DOMContentLoaded", function () {
    
    // Mengambil manifest data master katalog bahan baku dari penampung blueprint HTML
    var blueprintElement = document.getElementById('blueprint-data-container');
    if (!blueprintElement) return;
    
    var masterBahan = JSON.parse(blueprintElement.getAttribute('data-opsi')) || [];
    var tabelKomposisi = document.getElementById('tabel-komposisi').getElementsByTagName('tbody')[0];
    var btnTambahBaris = document.getElementById('btn-tambah-baris');

    // -------------------------------------------------------------------------
    // FUNCTION: DETECT & ASSIGN MEASUREMENT UNIT NAME
    // -------------------------------------------------------------------------
    function attachUnitSelectorListener(row) {
        var selector = row.querySelector('.select-bahan');
        var labelUnit = row.querySelector('.label-satuan');

        selector.addEventListener('change', function () {
            var selectedOption = selector.options[selector.selectedIndex];
            var satuanName = selectedOption.getAttribute('data-satuan') || 'Unit';
            labelUnit.innerText = satuanName;
        });
    }

    // Menempelkan fungsi pelacak nama satuan bawaan untuk baris pertama (default row)
    var barisPertama = document.querySelector('.baris-bahan');
    if (barisPertama) {
        attachUnitSelectorListener(barisPertama);
    }

    // -------------------------------------------------------------------------
    // EVENT: APPEND DYNAMIC COMPOSITION INPUT ROW
    // -------------------------------------------------------------------------
    btnTambahBaris.addEventListener('click', function () {
        var newRow = document.createElement('tr');
        newRow.className = 'baris-bahan';

        // Merender skema element HTML baris masukan baru
        var htmlContent = `
            <td class="ps-4">
                <select class="form-select select-bahan" name="id_bahan[]" required>
                    <option value="" disabled selected>-- Pilih Bahan Baku --</option>`;
        
        masterBahan.forEach(function (b) {
            htmlContent += `<option value="${b.id_bahan}" data-satuan="${b.satuan}">${b.nama_bahan} (${b.satuan})</option>`;
        });

        htmlContent += `
                </select>
                <div class="invalid-feedback text-xs">Pilih salah satu item katalog.</div>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" step="0.01" min="0.01" class="form-control input-takaran" name="jumlah_takaran[]" placeholder="0.00" required>
                    <span class="input-group-text bg-light text-muted label-satuan" style="min-width: 65px; font-size: 0.8rem;">Unit</span>
                    <div class="invalid-feedback text-xs">Nilai desimal harus > 0.00.</div>
                </div>
            </td>
            <td class="text-center pe-4">
                <button type="button" class="btn btn-sm btn-link text-danger btn-hapus-baris" title="Hapus komponen racikan">
                    <i class="bi bi-trash3-fill fs-5"></i>
                </button>
            </td>`;

        newRow.innerHTML = htmlContent;
        tabelKomposisi.appendChild(newRow);

        // Pasangkan Event Listener unit pengukuran dan tombol eliminasi baris komponen terkait
        attachUnitSelectorListener(newRow);
        checkRowLimitationConstraint();
    });

    // -------------------------------------------------------------------------
    // EVENT: REMOVE CHOSEN RECIPE ROW COMPONENTS
    // -------------------------------------------------------------------------
    tabelKomposisi.addEventListener('click', function (e) {
        var targetBtn = e.target.closest('.btn-hapus-baris');
        if (!targetBtn || targetBtn.classList.contains('disabled')) return;

        var rowToEliminate = targetBtn.closest('.baris-bahan');
        if (rowToEliminate) {
            rowToEliminate.remove();
            checkRowLimitationConstraint();
        }
    });

    // -------------------------------------------------------------------------
    // HELPER: MANDATORY LEAST ONE ROW ENFORCEMENT
    // -------------------------------------------------------------------------
    function checkRowLimitationConstraint() {
        var seluruhBaris = tabelKomposisi.querySelectorAll('.baris-bahan');
        var btnHapusPertama = seluruhBaris[0].querySelector('.btn-hapus-baris');

        if (seluruhBaris.length === 1) {
            btnHapusPertama.classList.add('disabled');
            btnHapusPertama.setAttribute('title', 'Minimal menyisakan 1 baris komposisi');
        } else {
            seluruhBaris.forEach(function (row) {
                var btn = row.querySelector('.btn-hapus-baris');
                btn.classList.remove('disabled');
                btn.removeAttribute('title');
            });
        }
    }

    // -------------------------------------------------------------------------
    // VALIDATOR: BOOTSTRAP FORM SUBMISSION PREVENTER CLIENT-SIDE
    // -------------------------------------------------------------------------
    var formRacik = document.getElementById('form-racik-menu');
    formRacik.addEventListener('submit', function (event) {
        if (!formRacik.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        formRacik.classList.add('was-validated');
    }, false);
});