<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

if (!isset($_GET['jadwal_id'])) {
    die("Jadwal tidak ditemukan.");
}

$jadwal_id = $_GET['jadwal_id'];

// Query untuk mengambil detail peserta ujian beserta nilai akhir
$query = "
    SELECT 
        upd.id AS peserta_id,
        s.nama_lengkap AS nama_siswa,
        s.nis,
        urg.nama AS nama_ruang,
        GROUP_CONCAT(DISTINCT k.nama ORDER BY k.nama ASC SEPARATOR ', ') AS kelas_siswa,
        upe.nomor AS nomor_ujian,
        COALESCE(GROUP_CONCAT(DISTINCT g.nama_lengkap ORDER BY g.nama_lengkap ASC SEPARATOR ' & '), '-') AS penguji,
        COALESCE(SUM((up.nilai * ur.bobot) / 100) / NULLIF(COUNT(DISTINCT up.pengawas_id), 0), 0) AS nilai_akhir
    FROM ujian_peserta_detail upd
    JOIN ujian_peserta upe ON upe.id = upd.peserta_id
    LEFT JOIN ujian_ruangan urg ON urg.id = upd.ruang_id
    LEFT JOIN siswa s ON upe.siswa_id = s.id
    LEFT JOIN siswa_kelas sk ON s.id = sk.siswa_id
    LEFT JOIN kelas k ON sk.kelas_id = k.id
    LEFT JOIN ujian_penilaian up ON upd.id = up.peserta_detail_id
    LEFT JOIN ujian_rubrik ur ON up.rubrik_id = ur.id
    LEFT JOIN guru g ON up.pengawas_id = g.id
    WHERE upd.jadwal_id = :jadwal_id
    GROUP BY upd.id, s.nama_lengkap, upe.nomor
    ORDER BY upd.nomor_urut ASC
";

$stmt = $conn->prepare($query);
$stmt->bindParam(':jadwal_id', $jadwal_id, PDO::PARAM_INT);
$stmt->execute();
$pesertaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] == "csv") {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hasil_ujian_' . $jadwal_id . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Nomor Ujian', 'NIS', 'Kelas', 'Nama Siswa', 'Penguji', 'Nilai Akhir']);

    $no = 1;
    foreach ($pesertaList as $peserta) {
        fputcsv($output, [
            $no++,
            $peserta['nomor_ujian'],
            $peserta['nis'],
            $peserta['kelas_siswa'],
            $peserta['nama_siswa'],
            $peserta['penguji'],
            number_format($peserta['nilai_akhir'], 2)
        ]);
    }

    fclose($output);
    exit;
}
?>
    <div class="container mt-5">
        <h2 class="mb-4">Detail Hasil Ujian</h2>
        <a href="?jadwal_id=<?= $jadwal_id ?>&export=csv" class="btn btn-success mb-3">
            Export ke CSV
        </a>
        <table id="detailHasilTable" class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nomor Ujian</th>
                    <th>NIS</th>
                    <th>Kelas</th>
                    <th>Ruang Ujian</th>
                    <th>Nama Siswa</th>
                    <th>Penguji</th>
                    <th>Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pesertaList)): ?>
                    <?php $no = 1; foreach ($pesertaList as $peserta): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($peserta['nomor_ujian']) ?></td>
                            <td><?= htmlspecialchars($peserta['nis']) ?></td>
                            <td><?= htmlspecialchars($peserta['kelas_siswa']) ?></td>
                            <td><?= htmlspecialchars($peserta['nama_ruang']) ?></td>
                            <td><?= htmlspecialchars($peserta['nama_siswa']) ?></td>
                            <td><?= htmlspecialchars($peserta['penguji']) ?></td>
                            <td class="text-center"><strong><?= number_format($peserta['nilai_akhir'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data peserta ujian</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="admin_ujian_hasil.php" class="btn btn-secondary mt-3">Kembali</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#detailHasilTable').DataTable();
        });
    </script>
<?php require '../footer.php'; ?>
