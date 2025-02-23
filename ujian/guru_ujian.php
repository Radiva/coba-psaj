<?php
require '../header.php';
if ($_SESSION['role'] !== 'guru') {
    header("Location: " . $site_url);
    exit();
}

// Ambil jadwal ujian yang aktif dengan detail sesi dan tema
$sqlJadwal = "SELECT j.id, j.nama_tema, j.tanggal, j.status, s.nama as nama_sesi, s.jam_mulai, s.jam_selesai 
              FROM ujian_jadwal j 
              JOIN ujian_sesi s ON j.sesi_id = s.id 
              WHERE j.status = 'Aktif'
              ORDER BY j.tanggal ASC";
$stmtJadwal = $conn->query($sqlJadwal);
$jadwalList = $stmtJadwal->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar ruang ujian
$sqlRuang = "SELECT id, nama as nama_ruang FROM ujian_ruangan ORDER BY nama_ruang ASC";
$stmtRuang = $conn->query($sqlRuang);
$ruangList = $stmtRuang->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Menu Ujian</h2>
    <p>Pilih jadwal ujian dan ruang ujian yang akan digunakan.</p>
    
    <form id="examForm">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="jadwalSelect" class="form-label">Pilih Jadwal Ujian</label>
                <select id="jadwalSelect" name="jadwal_id" class="form-select" required>
                    <option value="" disabled selected>-- Pilih Jadwal Ujian --</option>
                    <?php foreach ($jadwalList as $jadwal): ?>
                        <option value="<?= $jadwal['id'] ?>">
                            <?= htmlspecialchars($jadwal['nama_tema']) ?> | <?= date('d-m-Y', strtotime($jadwal['tanggal'])) ?> |
                            Sesi: <?= htmlspecialchars($jadwal['nama_sesi']) ?> (<?= substr($jadwal['jam_mulai'], 0, 5) ?> - <?= substr($jadwal['jam_selesai'], 0, 5) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="ruangSelect" class="form-label">Pilih Ruang Ujian</label>
                <select id="ruangSelect" name="ruang_id" class="form-select" required>
                    <option value="" disabled selected>-- Pilih Ruang Ujian --</option>
                    <?php foreach ($ruangList as $ruang): ?>
                        <option value="<?= $ruang['id'] ?>">
                            <?= htmlspecialchars($ruang['nama_ruang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="button" id="btnLanjut" class="btn btn-primary">Lanjut</button>
    </form>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"> 
        <h5 class="modal-title" id="confirmModalLabel">Konfirmasi Pilihan Ujian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="modalDetail"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" id="btnConfirm" class="btn btn-primary">Konfirmasi</button>
      </div>
    </div>
  </div>
</div>

<!-- jQuery & Bootstrap 5 JS (pastikan sudah include bootstrap.bundle.min.js agar modal berfungsi) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables (jika diperlukan di halaman ini, misalnya untuk tabel, tapi di sini tidak ada tabel) -->
<script>
$(document).ready(function(){
    // Saat tombol Lanjut diklik, tampilkan modal konfirmasi
    $('#btnLanjut').click(function(){
        var jadwalId = $('#jadwalSelect').val();
        var ruangId = $('#ruangSelect').val();
        
        if(!jadwalId || !ruangId) {
            alert("Silakan pilih jadwal dan ruang ujian terlebih dahulu!");
            return;
        }
        
        // Ambil teks yang dipilih untuk ditampilkan pada modal
        var jadwalText = $('#jadwalSelect option:selected').text();
        var ruangText = $('#ruangSelect option:selected').text();
        
        var detail = "<strong>Jadwal Ujian:</strong> " + jadwalText + "<br>" +
                     "<strong>Ruang Ujian:</strong> " + ruangText;
        
        $('#modalDetail').html(detail);
        
        // Tampilkan modal konfirmasi
        var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        confirmModal.show();
    });
    
    // Saat tombol Konfirmasi pada modal diklik, lanjutkan ke halaman berikutnya
    $('#btnConfirm').click(function(){
        var jadwalId = $('#jadwalSelect').val();
        var ruangId = $('#ruangSelect').val();
        // Redirect ke halaman berikutnya (misalnya, halaman penilaian ujian)
        window.location.href = "guru_ujian_penilaian.php?jadwal_id=" + jadwalId + "&ruang_id=" + ruangId;
    });
});
</script>

<?php require '../footer.php'; ?>
