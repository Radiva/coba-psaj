<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// **Tambah Peserta ke Jadwal dan Ruangan**
if (isset($_POST['add_peserta_jadwal'])) {
    $peserta_id = $_POST['peserta_id'];
    $jadwal_id = $_POST['jadwal_id'];
    $ruang_id = $_POST['ruang_id'];
    $nomor_urut = $_POST['nomor_urut']; // Nomor urut diinput oleh admin

    // Cek apakah nomor urut sudah digunakan dalam ruangan dan jadwal yang sama
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ujian_peserta_detail WHERE jadwal_id = ? AND ruang_id = ? AND nomor_urut = ?");
    $stmt->execute([$jadwal_id, $ruang_id, $nomor_urut]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        header("Location: peserta_jadwal.php?status=error&msg=Nomor urut sudah digunakan!");
        exit();
    }

    // Tambahkan peserta ke dalam jadwal dan ruangan dengan nomor urut
    $stmt = $conn->prepare("INSERT INTO ujian_peserta_detail (peserta_id, jadwal_id, ruang_id, nomor_urut) VALUES (?, ?, ?, ?)");
    $stmt->execute([$peserta_id, $jadwal_id, $ruang_id, $nomor_urut]);

    header("Location: peserta_jadwal.php?status=added");
    exit();
}

// **Hapus Peserta dari Jadwal**
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Hapus peserta dari tabel
    $stmt = $conn->prepare("DELETE FROM ujian_peserta_detail WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: peserta_jadwal.php?status=deleted");
    exit();
}

// **Ambil Data**
$pesertaList = $conn->query("
    SELECT p.id, s.nama_lengkap, p.nomor 
    FROM ujian_peserta p
    JOIN siswa s ON p.siswa_id = s.id
")->fetchAll(PDO::FETCH_ASSOC);

$jadwalList = $conn->query("SELECT id, nama_tema, tanggal FROM ujian_jadwal ORDER BY tanggal ASC")->fetchAll(PDO::FETCH_ASSOC);

$ruangList = $conn->query("SELECT id, nama FROM ujian_ruangan ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

$pesertaJadwalList = $conn->query("
    SELECT pd.id, pd.nomor_urut, p.nomor, s.nama_lengkap AS siswa, j.nama_tema, j.tanggal, r.nama, 
           se.nama as nama_sesi, se.jam_mulai, se.jam_selesai
    FROM ujian_peserta_detail pd
    JOIN ujian_peserta p ON pd.peserta_id = p.id
    JOIN siswa s ON p.siswa_id = s.id
    JOIN ujian_jadwal j ON pd.jadwal_id = j.id
    JOIN ujian_ruangan r ON pd.ruang_id = r.id
    JOIN ujian_sesi se ON j.sesi_id = se.id
    ORDER BY j.tanggal ASC, se.jam_mulai ASC, r.nama ASC, pd.nomor_urut ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Manajemen Peserta Ujian per Jadwal</h2>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success">
            Data berhasil diperbarui!
        </div>
    <?php elseif (isset($_GET['msg'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Form Tambah Peserta ke Jadwal -->
    <form method="POST">
        <div class="row">
            <div class="col-md-3">
                <select name="peserta_id" class="form-control" required>
                    <option value="" disabled selected>Pilih Peserta</option>
                    <?php foreach ($pesertaList as $peserta): ?>
                        <option value="<?= $peserta['id'] ?>"><?= $peserta['nomor'] ?> - <?= $peserta['nama_lengkap'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="jadwal_id" class="form-control" required>
                    <option value="" disabled selected>Pilih Jadwal</option>
                    <?php foreach ($jadwalList as $jadwal): ?>
                        <option value="<?= $jadwal['id'] ?>"><?= $jadwal['nama_tema'] ?> (<?= $jadwal['tanggal'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="ruang_id" class="form-control" required>
                    <option value="" disabled selected>Pilih Ruangan</option>
                    <?php foreach ($ruangList as $ruang): ?>
                        <option value="<?= $ruang['id'] ?>"><?= $ruang['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="nomor_urut" class="form-control" placeholder="Nomor Urut" required>
            </div>
        </div>
        <br>
        <button type="submit" name="add_peserta_jadwal" class="btn btn-primary">Tambahkan</button>
    </form>

    <hr>

    <!-- Daftar Peserta di Jadwal -->
    <table class="table table-bordered table-striped" id="datatable">
        <thead>
            <tr>
                <th>Nomor Urut</th>
                <th>Nomor Ujian</th>
                <th>Nama Siswa</th>
                <th>Jadwal</th>
                <th>Tanggal</th>
                <th>Sesi</th>
                <th>Jam</th>
                <th>Ruang Ujian</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pesertaJadwalList as $data) : ?>
                <tr>
                    <td><?= $data['nomor_urut'] ?></td>
                    <td><?= $data['nomor'] ?></td>
                    <td><?= $data['siswa'] ?></td>
                    <td><?= $data['nama_tema'] ?></td>
                    <td><?= $data['tanggal'] ?></td>
                    <td><?= $data['nama_sesi'] ?></td>
                    <td><?= $data['jam_mulai'] . " - " . $data['jam_selesai'] ?></td>
                    <td><?= $data['nama'] ?></td>
                    <td>
                        <a href="?delete=<?= $data['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus peserta ini dari jadwal?')">Hapus</a>
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
