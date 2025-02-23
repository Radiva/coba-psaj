<?php
include('../header.php');
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

require_once '../tools/import_export.php';

// Menambahkan Guru
if (isset($_POST['add'])) {
    $nip = $_POST['nip'];
    $kode = $_POST['kode'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];

   // Generate username & password
   $username = $kode;
   $password_plain = date('dmY', strtotime($tanggal_lahir)); // Format DDMMYYYY
   $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

    try {
        $conn->beginTransaction();

        // Insert ke tabel guru
        $sql = "INSERT INTO guru (nip, kode, nama_lengkap, jk, tempat_lahir, tanggal_lahir) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nip, $kode, $nama_lengkap, $jenis_kelamin, $tempat_lahir, $tanggal_lahir]);

        // Insert ke tabel users
        $sqlUser = "INSERT INTO users (username, password, role, id_profil) VALUES (?, ?, 'guru', ?)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->execute([$username, $password_hashed, $conn->lastInsertId()]);

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Error: " . $e->getMessage());
    }

    header("Location: guru.php");
    exit();
}

// Menghapus Guru
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sqlUser = "DELETE FROM users WHERE id_profil = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->execute([$id]);

    $sql = "DELETE FROM guru WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header("Location: guru.php");
    exit();
}

// Mengupdate Guru
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $nip = $_POST['nip'];
    $kode = $_POST['kode'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];

    $sql = "UPDATE guru SET nip=?, kode=?, nama_lengkap=?, jk=?, tempat_lahir=?, tanggal_lahir=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nip, $kode, $nama_lengkap, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $id]);
    header("Location: guru.php");
    exit();
}

// Ambil semua data guru
$sql = "SELECT * FROM guru ORDER BY nama_lengkap ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$guruList = $stmt->fetchAll();

// Eksport Data
if (isset($_GET['export'])) {
    // Ambil data dari database (contoh)
    $headers = ['no','NIP', 'Kode Guru', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir'];
    $data = $guruList; // Isi data dari query ke database
    // Misalnya:
    // $data[] = [$row['nomor_pegawai'], $row['nama_lengkap'], $row['jenis_kelamin'], $row['tempat_lahir'], $row['tanggal_lahir']];

    // Panggil fungsi export
    exportData($headers, $data, 'csv', 'data_guru');
}

// Import Data
if (isset($_POST['import_submit'])) {
    try {
        $importedData = importData($_FILES['import_file'], 6); // Misalnya diharapkan 5 kolom
        // Proses $importedData sesuai kebutuhan, seperti memasukkan ke database
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    
}
?>

<div class="container mt-4">
    <h2>Manajemen Guru</h2>
    <!-- Tombol Import & Export -->
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import Data</button>
        <a href="guru.php?export=csv" class="btn btn-info">Export CSV</a>
    </div>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Guru</button>

    <table id="guruTable" class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>NIP</th>
                <th>Kode Guru</th>
                <th>Nama Lengkap</th>
                <th>Jenis Kelamin</th>
                <th>Tempat Lahir</th>
                <th>Tanggal Lahir</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($guruList as $index => $guru): ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= $guru['nip']; ?></td>
                    <td><?= htmlspecialchars($guru['kode']); ?></td>
                    <td><?= htmlspecialchars($guru['nama_lengkap']); ?></td>
                    <td><?= htmlspecialchars($guru['jk']); ?></td>
                    <td><?= htmlspecialchars($guru['tempat_lahir']); ?></td>
                    <td><?= htmlspecialchars($guru['tanggal_lahir']); ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $guru['id']; ?>">Edit</button>
                        <a href="guru.php?delete=<?= $guru['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?');">Hapus</a>
                    </td>
                </tr>

                <!-- Modal Edit -->
                <div class="modal fade" id="editModal<?= $guru['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Guru</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $guru['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">NIP</label>
                                        <input type="text" name="nip" class="form-control" value="<?= $guru['nip']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kode Guru</label>
                                        <input type="text" name="kode" class="form-control" value="<?= $guru['kode']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" class="form-control" value="<?= $guru['nama_lengkap']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <select name="jenis_kelamin" class="form-control">
                                            <option value="Laki-laki" <?= ($guru['jk'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                            <option value="Perempuan" <?= ($guru['jk'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tempat Lahir</label>
                                        <input type="text" name="tempat_lahir" class="form-control" value="<?= $guru['tempat_lahir']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Lahir</label>
                                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= $guru['tanggal_lahir']; ?>" required>
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

    <!-- Modal Import Data -->
    <div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Import Data Guru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Pilih file (CSV atau XLSX)</label>
                <input type="file" name="import_file" class="form-control" required>
            </div>
            </div>
            <div class="modal-footer">
            <button type="submit" name="import_submit" class="btn btn-success">Import</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
        </div>
    </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">NIP</label>
                            <input type="text" name="nip" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Guru</label>
                            <input type="text" name="kode" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" required>
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
    $('#guruTable').DataTable();
});
</script>
<?php include('../footer.php')?>
