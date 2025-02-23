<?php
include('../header.php');
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

require_once '../tools/import_export.php';

// Menambahkan Ruangan
if (isset($_POST['add'])) {
    $kode = $_POST['kode'];
    $nama = $_POST['nama'];
    $jenis = $_POST['jenis'];
    $kapasitas = $_POST['kapasitas'];

    // Insert ke tabel ruangan
    $sql = "INSERT INTO ruangan (kode, nama, jenis, kapasitas) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$kode, $nama, $jenis, $kapasitas]);

    header("Location: ruangan.php");
    exit();
}

// Menghapus Ruangan
if (isset($_GET['delete'])) {
    $sql = "DELETE FROM ruangan WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header("Location: ruangan.php");
    exit();
}

// Mengupdate Ruangan
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $kode = $_POST['kode'];
    $nama = $_POST['nama'];
    $jenis = $_POST['jenis'];
    $kapasitas = $_POST['kapasitas'];

    $sql = "UPDATE ruangan SET kode=?, nama=?, jenis=?, kapasitas=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$kode, $nama, $jenis, $kapasitas, $id]);
    header("Location: ruangan.php");
    exit();
}

// Ambil semua data ruangan
$sql = "SELECT * FROM ruangan ORDER BY nama ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$ruanganList = $stmt->fetchAll();

// Eksport Data
if (isset($_GET['export'])) {
    // Ambil data dari database (contoh)
    $headers = ['Kode', 'Nama Ruangan', 'Jenis Ruangan', 'Kapasitas'];
    $data = $ruanganList; // Isi data dari query ke database
    // Misalnya:
    // $data[] = [$row['nomor_pegawai'], $row['nama'], $row['jenis'], $row['kapasitas'], $row['tanggal_lahir']];

    // Panggil fungsi export
    exportData($headers, $data, 'csv', 'data_ruangan');
}
?>

<div class="container mt-4">
    <h2>Manajemen Ruangan</h2>
    <!-- Tombol Import & Export -->
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import Data</button>
        <a href="ruangan.php?export=csv" class="btn btn-info">Export CSV</a>
    </div>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Ruangan</button>

    <table id="ruanganTable" class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Ruangan</th>
                <th>Jenis</th>
                <th>Kapasitas</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ruanganList as $index => $ruangan): ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= htmlspecialchars($ruangan['kode']); ?></td>
                    <td><?= htmlspecialchars($ruangan['nama']); ?></td>
                    <td><?= htmlspecialchars($ruangan['jenis']); ?></td>
                    <td><?= htmlspecialchars($ruangan['kapasitas']); ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $ruangan['id']; ?>">Edit</button>
                        <a href="ruangan.php?delete=<?= $ruangan['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?');">Hapus</a>
                    </td>
                </tr>

                <!-- Modal Edit -->
                <div class="modal fade" id="editModal<?= $ruangan['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Ruangan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $ruangan['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Kode</label>
                                        <input type="text" name="kode" class="form-control" value="<?= $ruangan['kode']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nama Ruangan</label>
                                        <input type="text" name="nama" class="form-control" value="<?= $ruangan['nama']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jenis</label>
                                        <select name="jenis" class="form-control">
                                            <option value="Laboratorium" <?= ($ruangan['jenis'] == 'Laboratorium') ? 'selected' : ''; ?>>Laboratorium</option>
                                            <option value="Teori" <?= ($ruangan['jenis'] == 'Teori') ? 'selected' : ''; ?>>Teori</option>
                                            <option value="Lainnya" <?= ($ruangan['jenis'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kapasitas</label>
                                        <input type="number" name="kapasitas" class="form-control" value="<?= $ruangan['kapasitas']; ?>" required>
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
                    <h5 class="modal-title">Tambah Ruangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Ruangan</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis</label>
                            <select name="jenis" class="form-control">
                                <option value="Laboratorium">Laboratorium</option>
                                <option value="Teori">Teori</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kapasitas</label>
                            <input type="number" name="kapasitas" class="form-control" required>
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
    $('#ruanganTable').DataTable();
});
</script>
<?php include('../footer.php')?>
