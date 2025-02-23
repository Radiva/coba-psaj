<?php
include('../header.php');
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// PROSES TAMBAH KELAS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $jurusan_id = $_POST['jurusan_id'];
    $nama_kelas = $_POST['nama_kelas'];
    $tingkat = $_POST['tingkat'];
    $stmt = $conn->prepare("INSERT INTO kelas (jurusan_id, nama, tingkat) VALUES (?, ?, ?)");
    $stmt->execute([$jurusan_id, $nama_kelas, $tingkat]);
    header("Location: kelas.php");
    exit();
}

// PROSES ASSIGN SISWA KE KELAS (insert ke tabel siswa_kelas)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_student'])) {
    $kelas_id = $_POST['kelas_id'];
    $siswa_ids = isset($_POST['siswa_ids']) ? $_POST['siswa_ids'] : [];
    if (!empty($siswa_ids)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO siswa_kelas (kelas_id, siswa_id) VALUES (?, ?)");
        foreach ($siswa_ids as $siswa_id) {
            $stmt->execute([$kelas_id, $siswa_id]);
        }
    }
    header("Location: kelas.php");
    exit();
}

// Ambil data jurusan untuk dropdown
$stmtJurusan = $conn->query("SELECT * FROM jurusan ORDER BY nama ASC");
$jurusanList = $stmtJurusan->fetchAll();

// Ambil data kelas beserta nama jurusan dan jumlah siswanya
$sql = "SELECT k.id, k.jurusan_id, k.nama as nama_kelas, k.tingkat, j.nama as nama_jurusan, 
        (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id = k.id) AS jumlah_siswa 
        FROM kelas k
        JOIN jurusan j ON k.jurusan_id = j.id
        ORDER BY k.id DESC";
$stmtKelas = $conn->prepare($sql);
$stmtKelas->execute();
$kelasList = $stmtKelas->fetchAll();

?>
<div class="container mt-5">
  <h2>Manajemen Kelas</h2>
  
  <!-- Form Tambah Kelas -->
  <div class="card mb-4">
    <div class="card-header">Tambah Kelas</div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Jurusan</label>
          <select name="jurusan_id" class="form-select" required>
            <option value="">-- Pilih Jurusan --</option>
            <?php foreach ($jurusanList as $jurusan): ?>
              <option value="<?= $jurusan['id']; ?>"><?= htmlspecialchars($jurusan['nama']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Tingkat</label>
          <input type="number" name="tingkat" class="form-control" placeholder="Contoh: 10" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nama Kelas</label>
          <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: XI TO 1" required>
        </div>
        <button type="submit" name="add_class" class="btn btn-success">Tambah Kelas</button>
      </form>
    </div>
  </div>
  
  <!-- Tabel Daftar Kelas -->
  <table id="kelasTable" class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>No</th>
        <th>Jurusan</th>
        <th>Nama Kelas</th>
        <th>Jumlah Siswa</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($kelasList as $index => $kelas): ?>
        <tr>
          <td><?= $index + 1; ?></td>
          <td><?= htmlspecialchars($kelas['nama_jurusan']); ?></td>
          <td><?= htmlspecialchars($kelas['nama_kelas']); ?></td>
          <td><?= htmlspecialchars($kelas['jumlah_siswa']); ?></td>
          <td>
            <!-- Tombol untuk membuka modal assign siswa -->
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal<?= $kelas['id']; ?>">Tambah Siswa</button>
          </td>
        </tr>
        
        <!-- Modal Assign Siswa ke Kelas -->
        <div class="modal fade" id="assignModal<?= $kelas['id']; ?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Tambah Siswa ke Kelas <?= htmlspecialchars($kelas['nama_kelas']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form method="POST">
                <input type="hidden" name="kelas_id" value="<?= $kelas['id']; ?>">
                <div class="modal-body">
                  <?php
                  // Ambil data siswa yang belum terdaftar di kelas ini (meskipun mungkin sudah di kelas lain)
                  $stmtSiswa = $conn->prepare("SELECT * FROM siswa WHERE id NOT IN (SELECT siswa_id FROM siswa_kelas WHERE kelas_id = ? ) ORDER BY nama_lengkap ASC");
                  $stmtSiswa->execute([$kelas['id']]);
                  $siswaList = $stmtSiswa->fetchAll();
                  ?>
                  <table id="siswaTable<?= $kelas['id']; ?>" class="table table-bordered">
                    <thead class="table-secondary">
                      <tr>
                        <th><input type="checkbox" class="selectAll" data-target="#siswaTable<?= $kelas['id']; ?>"></th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>NISN</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($siswaList as $siswa): ?>
                        <tr>
                          <td><input type="checkbox" name="siswa_ids[]" value="<?= $siswa['id']; ?>"></td>
                          <td><?= htmlspecialchars($siswa['nama_lengkap']); ?></td>
                          <td><?= htmlspecialchars($siswa['nis']); ?></td>
                          <td><?= htmlspecialchars($siswa['nisn']); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php if(empty($siswaList)): ?>
                    <div class="alert alert-info">Semua siswa sudah terdaftar di kelas ini.</div>
                  <?php endif; ?>
                </div>
                <div class="modal-footer">
                  <button type="submit" name="assign_student" class="btn btn-success">Simpan</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- jQuery, Bootstrap, DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#kelasTable').DataTable();
    // Inisialisasi DataTables untuk setiap modal tabel assign siswa
    $('table[id^="siswaTable"]').each(function(){
        $(this).DataTable();
    });
    // Fungsi select/deselect semua checkbox pada tabel assign siswa
    $('.selectAll').click(function(){
        var target = $(this).data('target');
        $(target).find('tbody input[type="checkbox"]').prop('checked', this.checked);
    });
});
</script>
<?php include('../footer.php')?>
