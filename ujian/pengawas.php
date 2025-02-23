<?php
include('../header.php');
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// **Tambah Pengawas**
if (isset($_POST['add_pengawas'])) {
    $guru_id = $_POST['guru_id'];

    // Tambahkan ke tabel pengawas
    $stmt = $conn->prepare("INSERT INTO ujian_pengawas (guru_id) VALUES (?)");
    $stmt->execute([$guru_id]);

    // Update role guru agar bisa mengakses menu ujian
    // $updateRole = $conn->prepare("UPDATE guru SET role = 'pengawas' WHERE id = ?");
    // $updateRole->execute([$guru_id]);

    header("Location: pengawas.php?status=added");
    exit();
}

// **Hapus Pengawas**
if (isset($_GET['remove']) && isset($_GET['id'])) {
    $pengawas_id = $_GET['id'];

    // Ambil guru_id sebelum menghapus
    $stmt = $conn->prepare("SELECT guru_id FROM ujian_pengawas WHERE id = ?");
    $stmt->execute([$pengawas_id]);
    $guru_id = $stmt->fetchColumn();

    // Hapus dari tabel pengawas
    $stmt = $conn->prepare("DELETE FROM ujian_pengawas WHERE id = ?");
    $stmt->execute([$pengawas_id]);

    // Cek apakah guru masih menjadi pengawas
    $check = $conn->prepare("SELECT COUNT(*) FROM ujian_pengawas WHERE guru_id = ?");
    $check->execute([$guru_id]);
    if ($check->fetchColumn() == 0) {
        $updateRole = $conn->prepare("UPDATE guru SET role = 'guru' WHERE id = ?");
        $updateRole->execute([$guru_id]);
    }

    header("Location: pengawas.php?status=removed");
    exit();
}

// **Ambil daftar pengawas ujian**
$sql = "SELECT p.id, g.kode, g.nip, g.nama_lengkap 
        FROM ujian_pengawas p 
        JOIN guru g ON p.guru_id = g.id";
$pengawasList = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// **Ambil daftar guru yang belum menjadi pengawas**
$guruList = $conn->query("SELECT * FROM guru WHERE id NOT IN (SELECT guru_id FROM ujian_pengawas)")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Manajemen Pengawas Ujian</h2>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?= $_GET['status'] == 'added' ? 'success' : 'danger' ?>">
            <?= $_GET['status'] == 'added' ? 'Pengawas berhasil ditambahkan!' : 'Pengawas berhasil dihapus!' ?>
        </div>
    <?php endif; ?>

    <!-- Form Tambah Pengawas -->
    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <select name="guru_id" class="form-control" required>
                    <option value="">Pilih Guru</option>
                    <?php foreach ($guruList as $guru) : ?>
                        <option value="<?= $guru['id'] ?>"><?= $guru['kode'] ?> - <?= $guru['nip'] ?> - <?= $guru['nama_lengkap'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" name="add_pengawas" class="btn btn-primary">Tambah Pengawas</button>
            </div>
        </div>
    </form>

    <hr>

    <!-- Daftar Pengawas -->
    <table class="table table-bordered table-striped" id="datatable">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Guru</th>
                <th>NIP</th>
                <th>Nama Pengawas</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pengawasList as $index => $pengawas) : ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= $pengawas['kode'] ?></td>
                    <td><?= $pengawas['nip'] ?></td>
                    <td><?= $pengawas['nama_lengkap'] ?></td>
                    <td>
                        <a href="pengawas.php?remove=1&id=<?= $pengawas['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus pengawas ini?')">Hapus</a>
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

<?php include('../footer.php')?>
