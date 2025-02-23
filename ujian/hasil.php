<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}


// Query untuk mengambil seluruh jadwal ujian
$query = "
    SELECT 
        j.id, 
        j.nama_tema, 
        j.tanggal, 
        j.status,
        -- Hitung jumlah peserta yang sudah dinilai pada jadwal ini
        (SELECT COUNT(*) 
         FROM ujian_peserta_detail pd 
         JOIN ujian_penilaian up ON pd.id = up.peserta_detail_id 
         WHERE pd.jadwal_id = j.id) AS scored_count,
        -- Hitung total peserta pada jadwal ini
        (SELECT COUNT(*) 
         FROM ujian_peserta_detail pd 
         WHERE pd.jadwal_id = j.id) AS total_count
    FROM ujian_jadwal j
    ORDER BY j.tanggal DESC
";
$stmt = $conn->query($query);
$jadwalList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Hasil Ujian</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Tema Ujian</th>
                <th>Tanggal</th>
                <th>Status Peserta</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jadwalList as $index => $jadwal): 
                $scored = (int)$jadwal['scored_count'];
                $total  = (int)$jadwal['total_count'];
                $not_scored = $total - $scored;
            ?>
            <tr>
                <td><?= $index + 1; ?></td>
                <td><?= htmlspecialchars($jadwal['nama_tema']); ?></td>
                <td><?= date('d-m-Y', strtotime($jadwal['tanggal'])); ?></td>
                <td>
                    <span class="badge bg-success">Sudah Dinilai: <?= $scored; ?></span>
                    <span class="badge bg-warning">Belum Dinilai: <?= $not_scored; ?></span>
                </td>
                <td>
                    <a href="admin_ujian_hasil_detail.php?jadwal_id=<?= $jadwal['id']; ?>" class="btn btn-primary btn-sm">
                        Detail
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require '../footer.php'; ?>
