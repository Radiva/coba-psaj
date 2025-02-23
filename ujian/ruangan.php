<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// **Tambah Ruang Ujian dengan Nama Lain**
if (isset($_POST['add_ruang_ujian'])) {
    $ruangan_id = $_POST['ruangan_id'];
    $nama_lain = trim($_POST['nama_lain']);

    // Jika nama lain kosong, gunakan nama asli dari tabel ruangan
    if (empty($nama_lain)) {
        $stmt = $conn->prepare("SELECT nama FROM ruangan WHERE id = ?");
        $stmt->execute([$ruangan_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nama_lain = $row['nama'];
    }

    // Tambahkan ke tabel ujian_ruangan
    $stmt = $conn->prepare("INSERT INTO ujian_ruangan (ruangan_id, nama) VALUES (?, ?)");
    $stmt->execute([$ruangan_id, $nama_lain]);

    header("Location: ruangan.php?status=added");
    exit();
}

// **Hapus Ruang Ujian**
if (isset($_GET['remove']) && isset($_GET['id'])) {
    $ruang_ujian_id = $_GET['id'];

    // Hapus dari tabel ujian_ruangan
    $stmt = $conn->prepare("DELETE FROM ujian_ruangan WHERE id = ?");
    $stmt->execute([$ruang_ujian_id]);

    header("Location: ruangan.php?status=removed");
    exit();
}

// **Ambil daftar ruang ujian**
$sql = "SELECT u.id, r.nama, u.nama as nama_lain 
        FROM ujian_ruangan u 
        JOIN ruangan r ON u.ruangan_id = r.id";
$ruangUjianList = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// **Ambil daftar ruangan yang belum menjadi ruang ujian**
$ruanganList = $conn->query("SELECT * FROM ruangan WHERE id NOT IN (SELECT ruangan_id FROM ujian_ruangan)")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Manajemen Ruang Ujian</h2>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?= $_GET['status'] == 'added' ? 'success' : 'danger' ?>">
            <?= $_GET['status'] == 'added' ? 'Ruang Ujian berhasil ditambahkan!' : 'Ruang Ujian berhasil dihapus!' ?>
        </div>
    <?php endif; ?>

    <!-- Form Tambah Ruang Ujian -->
    <form method="POST">
        <div class="row">
            <div class="col-md-4">
                <select name="ruangan_id" class="form-control" required>
                    <option value="">Pilih Ruangan</option>
                    <?php foreach ($ruanganList as $ruangan) : ?>
                        <option value="<?= $ruangan['id'] ?>"><?= $ruangan['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="nama_lain" class="form-control" placeholder="Nama Lain (Opsional)">
            </div>
            <div class="col-md-4">
                <button type="submit" name="add_ruang_ujian" class="btn btn-primary">Tambah Ruang Ujian</button>
            </div>
        </div>
    </form>

    <hr>

    <!-- Daftar Ruang Ujian -->
    <table class="table table-bordered table-striped" id="datatable">
        <thead>
            <tr>
                <th>Nama Ruangan</th>
                <th>Nama Lain</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ruangUjianList as $ruangUjian) : ?>
                <tr>
                    <td><?= $ruangUjian['nama'] ?></td>
                    <td><?= $ruangUjian['nama_lain'] ?></td>
                    <td>
                        <a href="ruangan.php?remove=1&id=<?= $ruangUjian['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus ruang ujian ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#datatable').DataTable();
    });
</script>

<?php require '../footer.php'; ?>
