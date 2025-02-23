<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// **Tambah Jadwal**
if (isset($_POST['add_jadwal'])) {
    $tanggal = $_POST['tanggal'];
    $sesi_id = $_POST['sesi_id'];
    $nama_tema = $_POST['nama_tema'];

    $stmt = $conn->prepare("INSERT INTO ujian_jadwal (tanggal, sesi_id, nama_tema) VALUES (?, ?, ?)");
    $stmt->execute([$tanggal, $sesi_id, $nama_tema]);

    header("Location: jadwal.php?status=added");
    exit();
}

// **Update Status Jadwal**
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $jadwal = $conn->prepare("SELECT status FROM ujian_jadwal WHERE id = ?");
    $jadwal->execute([$id]);
    $currentStatus = $jadwal->fetchColumn();
    
    $newStatus = ($currentStatus === 'Aktif') ? 'Nonaktif' : 'Aktif';
    $stmt = $conn->prepare("UPDATE ujian_jadwal SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);

    header("Location: jadwal.php?status=updated");
    exit();
}

// **Hapus Jadwal**
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM ujian_jadwal WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: jadwal.php?status=deleted");
    exit();
}

// **Ambil daftar sesi**
$sesiList = $conn->query("SELECT * FROM ujian_sesi ORDER BY jam_mulai ASC")->fetchAll(PDO::FETCH_ASSOC);

// **Ambil daftar jadwal**
$jadwalList = $conn->query("SELECT j.*, s.nama FROM ujian_jadwal j JOIN ujian_sesi s ON j.sesi_id = s.id ORDER BY j.tanggal ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Manajemen Jadwal Ujian</h2>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success">
            Data berhasil diperbarui!
        </div>
    <?php endif; ?>

    <!-- Form Tambah Jadwal -->
    <form method="POST">
        <div class="row">
            <div class="col-md-3">
                <input type="date" name="tanggal" class="form-control" required>
            </div>
            <div class="col-md-3">
                <select name="sesi_id" class="form-control" required>
                    <option value="" disabled selected>Pilih Sesi</option>
                    <?php foreach ($sesiList as $sesi): ?>
                        <option value="<?= $sesi['id'] ?>"><?= $sesi['nama'] ?> (<?= $sesi['jam_mulai'] ?> - <?= $sesi['jam_selesai'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="nama_tema" class="form-control" placeholder="Nama Tema" required>
            </div>
            <div class="col-md-3">
                <button type="submit" name="add_jadwal" class="btn btn-primary">Tambah Jadwal</button>
            </div>
        </div>
    </form>

    <hr>

    <!-- Daftar Jadwal -->
    <table class="table table-bordered table-striped" id="datatable">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Sesi</th>
                <th>Nama Tema</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jadwalList as $jadwal) : ?>
                <tr>
                    <td><?= $jadwal['tanggal'] ?></td>
                    <td><?= $jadwal['nama'] ?></td>
                    <td><?= $jadwal['nama_tema'] ?></td>
                    <td>
                        <span class="badge bg-<?= $jadwal['status'] == 'Aktif' ? 'success' : 'secondary' ?>">
                            <?= $jadwal['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="?toggle_status=<?= $jadwal['id'] ?>" class="btn btn-sm btn-warning">
                            <?= $jadwal['status'] == 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </a>
                        <a href="?delete=<?= $jadwal['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus jadwal ini?')">Hapus</a>
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
