<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// **Tambah Sesi Ujian**
if (isset($_POST['add_sesi'])) {
    $nama_sesi = $_POST['nama_sesi'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // Hitung durasi dalam menit
    $start = strtotime($jam_mulai);
    $end = strtotime($jam_selesai);
    $durasi = ($end - $start) / 60;

    if ($durasi <= 0) {
        header("Location: sesi.php?status=error");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO ujian_sesi (nama, jam_mulai, jam_selesai, durasi) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nama_sesi, $jam_mulai, $jam_selesai, $durasi]);

    header("Location: sesi.php?status=added");
    exit();
}

// **Ambil daftar sesi**
$sesiList = $conn->query("SELECT * FROM ujian_sesi ORDER BY jam_mulai ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Manajemen Sesi Ujian</h2>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?= $_GET['status'] == 'error' ? 'danger' : 'success' ?>">
            <?= $_GET['status'] == 'error' ? 'Jam mulai harus lebih awal dari jam selesai!' : 'Data berhasil diperbarui!' ?>
        </div>
    <?php endif; ?>

    <!-- Form Tambah Sesi -->
    <form method="POST">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="nama_sesi" class="form-control" placeholder="Nama Sesi" required>
            </div>
            <div class="col-md-3">
                <input type="time" name="jam_mulai" class="form-control" required>
            </div>
            <div class="col-md-3">
                <input type="time" name="jam_selesai" class="form-control" required>
            </div>
            <div class="col-md-3">
                <button type="submit" name="add_sesi" class="btn btn-primary">Tambah Sesi</button>
            </div>
        </div>
    </form>

    <hr>

    <!-- Daftar Sesi -->
    <table class="table table-bordered table-striped" id="datatable">
        <thead>
            <tr>
                <th>Nama Sesi</th>
                <th>Jam Mulai</th>
                <th>Jam Selesai</th>
                <th>Durasi (menit)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sesiList as $sesi) : ?>
                <tr>
                    <td><?= $sesi['nama'] ?></td>
                    <td><?= $sesi['jam_mulai'] ?></td>
                    <td><?= $sesi['jam_selesai'] ?></td>
                    <td><?= $sesi['durasi'] ?></td>
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
