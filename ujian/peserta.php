<?php
include('../header.php');
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// -------------------- TAMBAH PESERTA UJIAN --------------------
if (isset($_POST['add'])) {
    $siswa_id = $_POST['siswa_id'];
    // Generate nomor peserta ujian:
    $stmtSeq = $conn->query("SELECT MAX(id) as max_id FROM ujian_peserta");
    $max_id = $stmtSeq->fetchColumn();
    $nextSequence = $max_id + 1;
    $nomor = "PS-" . date('Ymd') . '-' . sprintf('%04d', $nextSequence);
    
    try {
        $conn->beginTransaction();
        $sql = "INSERT INTO ujian_peserta (siswa_id, nomor) VALUES (?, ?)";
        $stmtInsert = $conn->prepare($sql);
        $stmtInsert->execute([$siswa_id, $nomor]);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Error: " . $e->getMessage());
    }
    header("Location: peserta.php");
    exit();
}
// -------------------- END TAMBAH --------------------

// -------------------- DELETE PESERTA UJIAN --------------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM ujian_peserta WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
    header("Location: peserta.php");
    exit();
}
// -------------------- END DELETE --------------------

// Ambil data peserta dengan join ke tabel siswa
$sql = "SELECT pu.*, s.nama_lengkap FROM ujian_peserta pu JOIN siswa s ON pu.siswa_id = s.id ORDER BY pu.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pesertaList = $stmt->fetchAll();

// Ambil data siswa untuk dropdown form penambahan
$stmtSiswa = $conn->prepare("SELECT * FROM siswa ORDER BY nama_lengkap ASC");
$stmtSiswa->execute();
$siswaList = $stmtSiswa->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Peserta Ujian</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css"/>
</head>
<body>
<div class="container mt-5">
  <h2>Manajemen Peserta Ujian</h2>
  <!-- Tombol Import & Export -->
  <div class="mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import Data</button>
    <a href="peserta.php?export=csv" class="btn btn-info">Export CSV</a>
    <a href="peserta.php?export=xlsx" class="btn btn-info">Export XLSX</a>
  </div>
  
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Peserta Ujian</button>
  
  <table id="pesertaTable" class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>No</th>
        <th>Nomor Peserta</th>
        <th>Siswa ID</th>
        <th>Nama Siswa</th>
        <th>Tanggal Pendaftaran</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pesertaList as $index => $peserta): ?>
        <tr>
          <td><?= $index + 1; ?></td>
          <td><?= htmlspecialchars($peserta['nomor']); ?></td>
          <td><?= htmlspecialchars($peserta['siswa_id']); ?></td>
          <td><?= htmlspecialchars($peserta['nama_lengkap']); ?></td>
          <td><?= htmlspecialchars($peserta['created_at']); ?></td>
          <td>
            <a href="peserta.php?delete=<?= $peserta['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?');">Hapus</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal Import Data -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Data Peserta Ujian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pilih file (CSV atau XLSX)</label>
            <input type="file" name="import_file" class="form-control" required>
          </div>
          <small class="text-muted">Pastikan file hanya memiliki satu kolom: Siswa ID</small>
        </div>
        <div class="modal-footer">
          <button type="submit" name="import_submit" class="btn btn-success">Import</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tambah Peserta Ujian -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Peserta Ujian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pilih Siswa</label>
            <select name="siswa_id" class="form-select" required>
              <option value="">-- Pilih Siswa --</option>
              <?php foreach ($siswaList as $siswa): ?>
                <option value="<?= $siswa['id']; ?>"><?= htmlspecialchars($siswa['nama_lengkap']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Nomor peserta akan digenerate secara otomatis -->
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn btn-success">Tambah</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery, Bootstrap, DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#pesertaTable').DataTable();
});
</script>
<?php include('../footer.php')?>
