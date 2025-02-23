<?php
include('../header.php');
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

require_once '../tools/import_export.php';

// Menambahkan jurusan
if (isset($_POST['add'])) {
    $kode = $_POST['kode'];
    $nama = $_POST['nama'];

    // Insert ke tabel jurusan
    $sql = "INSERT INTO jurusan (kode, nama) 
            VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$kode, $nama]);

    header("Location: jurusan.php");
    exit();
}

// Menghapus jurusan
if (isset($_GET['delete'])) {
    $sql = "DELETE FROM jurusan WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header("Location: jurusan.php");
    exit();
}

// Mengupdate jurusan
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $kode = $_POST['kode'];
    $nama = $_POST['nama'];

    $sql = "UPDATE jurusan SET kode=?, nama=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$$kode, $nama, $id]);
    header("Location: jurusan.php");
    exit();
}

// Ambil semua data jurusan
$sql = "SELECT * FROM jurusan ORDER BY nama ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$jurusanList = $stmt->fetchAll();

// Eksport Data
if (isset($_GET['export'])) {
    // Ambil data dari database (contoh)
    $headers = ['Kode', 'Nama jurusan'];
    $data = $jurusanList; // Isi data dari query ke database
    // Misalnya:
    // $data[] = [$row['nomor_pegawai'], $row['nama'], $row['jenis'], $row['kapasitas'], $row['tanggal_lahir']];

    // Panggil fungsi export
    exportData($headers, $data, 'csv', 'data_jurusan');
}
?>

<div class="container mt-4">
    <h2>Manajemen Jurusan</h2>
    <!-- Tombol Import & Export -->
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import Data</button>
        <a href="jurusan.php?export=csv" class="btn btn-info">Export CSV</a>
    </div>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Jurusan</button>

    <table id="jurusanTable" class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Jurusan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jurusanList as $index => $jurusan): ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= htmlspecialchars($jurusan['kode']); ?></td>
                    <td><?= htmlspecialchars($jurusan['nama']); ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $jurusan['id']; ?>">Edit</button>
                        <a href="jurusan.php?delete=<?= $jurusan['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?');">Hapus</a>
                    </td>
                </tr>

                <!-- Modal Edit -->
                <div class="modal fade" id="editModal<?= $jurusan['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Jurusan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $jurusan['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Kode</label>
                                        <input type="text" name="kode" class="form-control" value="<?= $jurusan['kode']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nama Jurusan</label>
                                        <input type="text" name="nama" class="form-control" value="<?= $jurusan['nama']; ?>" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update" class="btn btn-success">Update</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modal Tambah -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jurusan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Jurusan</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add" class="btn btn-success">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#jurusanTable').DataTable();
});
</script>
<?php include('../footer.php')?>
