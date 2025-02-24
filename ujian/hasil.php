<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// Ambil semua jadwal ujian
$query = "
    SELECT 
        uj.id AS jadwal_id,
        uj.tanggal,
        uj.sesi,
        uj.nama_tema,
        COUNT(DISTINCT upd.id) AS total_peserta,
        COUNT(DISTINCT CASE WHEN up.id IS NOT NULL THEN upd.id END) AS peserta_dinilai,
        (COUNT(DISTINCT upd.id) - COUNT(DISTINCT CASE WHEN up.id IS NOT NULL THEN upd.id END)) AS peserta_belum_dinilai
    FROM ujian_jadwal uj
    LEFT JOIN ujian_peserta_detail upd ON uj.id = upd.jadwal_id
    LEFT JOIN ujian_penilaian up ON upd.id = up.peserta_detail_id
    GROUP BY uj.id, uj.tanggal, uj.sesi, uj.nama_tema
    ORDER BY uj.tanggal DESC, uj.sesi ASC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$jadwalList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Hasil Ujian</h2>
        <table id="hasilUjianTable" class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Sesi</th>
                    <th>Nama Tema</th>
                    <th>Total Peserta</th>
                    <th>Sudah Dinilai</th>
                    <th>Belum Dinilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($jadwalList)): ?>
                    <?php $no = 1; foreach ($jadwalList as $jadwal): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($jadwal['tanggal']) ?></td>
                            <td><?= htmlspecialchars($jadwal['sesi']) ?></td>
                            <td><?= htmlspecialchars($jadwal['nama_tema']) ?></td>
                            <td><?= $jadwal['total_peserta'] ?></td>
                            <td class="text-success"><?= $jadwal['peserta_dinilai'] ?></td>
                            <td class="text-danger"><?= $jadwal['peserta_belum_dinilai'] ?></td>
                            <td>
                                <a href="admin_ujian_hasil_detail.php?jadwal_id=<?= $jadwal['jadwal_id'] ?>" class="btn btn-primary btn-sm">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada data jadwal ujian</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#hasilUjianTable').DataTable();
        });
    </script>
<?php require '../footer.php'; ?>
