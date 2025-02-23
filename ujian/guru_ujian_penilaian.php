<?php
require '../header.php';
if ($_SESSION['role'] !== 'guru') {
    header("Location: " . $site_url);
    exit();
}

// Pastikan parameter jadwal_id dan ruang_id dikirim melalui URL
if (!isset($_GET['jadwal_id']) || !isset($_GET['ruang_id'])) {
    echo "Parameter jadwal_id dan ruang_id diperlukan.";
    exit();
}

$jadwal_id = $_GET['jadwal_id'];
$ruang_id  = $_GET['ruang_id'];

// Ambil detail jadwal (tema, tanggal, status) beserta informasi sesi dan ruangan
$stmt = $conn->prepare("
    SELECT j.nama_tema, j.tanggal, j.status, 
           se.nama as nama_sesi, se.jam_mulai, se.jam_selesai, 
           r.nama as nama_ruang 
    FROM ujian_jadwal j
    JOIN ujian_sesi se ON j.sesi_id = se.id
    JOIN ujian_ruangan r ON r.id = ?
    WHERE j.id = ?
");
$stmt->execute([$ruang_id, $jadwal_id]);
$scheduleDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$scheduleDetails) {
    echo "Jadwal atau ruang tidak ditemukan.";
    exit();
}

$scheduleStatus = $scheduleDetails['status']; // "Aktif" atau "Nonaktif"

// Ambil daftar peserta dengan data nilai (jika sudah dinilai)
// Asumsi: Tabel ujian_penilaian menyimpan nilai peserta dengan kolom nilai_total,
// dan dihubungkan dengan ujian_peserta_detail melalui peserta_detail_id.
$stmt = $conn->prepare("
    SELECT 
        p.id AS peserta_id, 
        p.nomor, 
        s.nama_lengkap, 
        pd.id AS peserta_detail_id,
        pd.nomor_urut,
        COALESCE(MAX(up.id), 0) AS status_penilaian_id,
        CASE 
            WHEN MAX(up.id) IS NOT NULL THEN 'Sudah Dinilai' 
            ELSE 'Belum Dinilai' 
        END AS status_penilaian
    FROM ujian_peserta_detail pd
    JOIN ujian_peserta p ON pd.peserta_id = p.id
    JOIN siswa s ON p.siswa_id = s.id
    LEFT JOIN ujian_penilaian up ON pd.id = up.peserta_detail_id
    WHERE pd.jadwal_id = ? AND pd.ruang_id = ?
    GROUP BY p.id, p.nomor, s.nama_lengkap, pd.id, pd.nomor_urut
    ORDER BY pd.nomor_urut ASC;
");
$stmt->execute([$jadwal_id, $ruang_id]);
$pesertaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Penilaian Ujian</h2>
    
    <!-- Detail Jadwal Ujian -->
    <div class="mb-3">
        <h4>Detail Jadwal Ujian</h4>
        <p>
            <strong>Tema Ujian:</strong> <?= htmlspecialchars($scheduleDetails['nama_tema']); ?><br>
            <strong>Tanggal:</strong> <?= date('d-m-Y', strtotime($scheduleDetails['tanggal'])); ?><br>
            <strong>Sesi:</strong> <?= htmlspecialchars($scheduleDetails['nama_sesi']); ?> 
            (<?= substr($scheduleDetails['jam_mulai'], 0, 5); ?> - <?= substr($scheduleDetails['jam_selesai'], 0, 5); ?>)<br>
            <strong>Ruang Ujian:</strong> <?= htmlspecialchars($scheduleDetails['nama_ruang']); ?><br>
            <strong>Status Jadwal:</strong> <?= htmlspecialchars($scheduleDetails['status']); ?>
        </p>
    </div>
    
    <!-- Daftar Peserta Ujian -->
    <h3>Daftar Peserta Ujian</h3>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Nomor Urut</th>
                <th>Data Siswa</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($pesertaList)): ?>
                <?php foreach($pesertaList as $peserta): ?>
                <tr>
                    <td><?= htmlspecialchars($peserta['nomor_urut']); ?></td>
                    <td><?= htmlspecialchars($peserta['nomor']); ?><br/>
                    <?= htmlspecialchars($peserta['nama_lengkap']); ?></td>
                    <td>
                        <?php if ($scheduleStatus == 'Nonaktif'): ?>
                            <button class="btn btn-secondary btn-sm" disabled>
                                <?= ($peserta['status_penilaian'] === "Belum Dinilai") ? "Nilai" : "Edit" ?>
                            </button>
                        <?php else: ?>
                            <?php if ($peserta['status_penilaian'] === "Belum Dinilai"): ?>
                                <a href="guru_nilai.php?peserta_detail_id=<?= $peserta['peserta_detail_id']; ?>&jadwal_id=<?= $jadwal_id; ?>&ruang_id=<?= $ruang_id; ?>" class="btn btn-primary btn-sm">
                                    Nilai
                                </a>
                            <?php else: ?>
                                <a href="guru_nilai.php?peserta_detail_id=<?= $peserta['peserta_detail_id']; ?>&jadwal_id=<?= $jadwal_id; ?>&ruang_id=<?= $ruang_id; ?>" class="btn btn-warning btn-sm">
                                    Edit
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">Tidak ada peserta ujian</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="mt-3">
        <a href="guru_ujian.php" class="btn btn-outline-secondary ms-2">Kembali</a>
    </div>
</div>

<?php require '../footer.php'; ?>
