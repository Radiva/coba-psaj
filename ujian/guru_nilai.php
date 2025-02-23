<?php
require '../header.php';
if ($_SESSION['role'] !== 'guru') {
    header("Location: " . $site_url);
    exit();
}

// Pastikan parameter peserta_detail_id dikirim melalui URL
if (!isset($_GET['peserta_detail_id'])) {
    echo "Parameter peserta_detail_id diperlukan.";
    exit();
}

$jadwal_id = $_GET['jadwal_id'];
$ruang_id  = $_GET['ruang_id'];
$peserta_detail_id = $_GET['peserta_detail_id'];

// Ambil data siswa lengkap (nomor ujian, nama, kelas)
$stmt = $conn->prepare("
    SELECT p.nomor as nomor_ujian, s.nama_lengkap as nama
    FROM ujian_peserta_detail pd
    JOIN ujian_peserta p ON pd.peserta_id = p.id
    JOIN siswa s ON p.siswa_id = s.id
    WHERE pd.id = ?
");
$stmt->execute([$peserta_detail_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    echo "Data peserta tidak ditemukan.";
    exit();
}

// Ambil seluruh rubrik leaf (rubrik yang tidak memiliki sub rubrik)
$stmt = $conn->query("
    SELECT * FROM ujian_rubrik r 
    WHERE NOT EXISTS (SELECT 1 FROM ujian_rubrik r2 WHERE r2.parent_id = r.id) 
    ORDER BY id ASC
");
$rubrikList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jika form dikirim, proses simpan nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['nilai'] as $rubrik_id => $nilai) {
        $nilai = floatval($nilai);
        if ($nilai > 100 || $nilai < 0) {
            echo "<script>alert('Nilai harus antara 0 hingga 100');</script>";
            continue;
        }
        // Cek apakah nilai untuk rubrik ini sudah ada
        $stmt = $conn->prepare("SELECT id FROM ujian_penilaian WHERE peserta_detail_id = ? AND rubrik_id = ?");
        $stmt->execute([$peserta_detail_id, $rubrik_id]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            // Update nilai
            $stmtUpdate = $conn->prepare("UPDATE ujian_penilaian SET nilai = ? WHERE id = ?");
            $stmtUpdate->execute([$nilai, $existing]);
        } else {
            // Insert nilai
            $stmtInsert = $conn->prepare("INSERT INTO ujian_penilaian (peserta_detail_id, rubrik_id, nilai) VALUES (?, ?, ?)");
            $stmtInsert->execute([$peserta_detail_id, $rubrik_id, $nilai]);
        }
    }
    header("Location: guru_nilai.php?peserta_detail_id=".$peserta_detail_id."&jadwal_id=".$jadwal_id."&ruang_id=".$ruang_id."&status=success");
    exit();
}

// Ambil nilai yang sudah ada (jika ada) untuk rubrik-rubrik ini
$existingScores = [];
$stmt = $conn->prepare("SELECT rubrik_id, nilai FROM ujian_penilaian WHERE peserta_detail_id = ?");
$stmt->execute([$peserta_detail_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingScores[$row['rubrik_id']] = $row['nilai'];
}
?>

<div class="container mt-4">
    <h2>Penilaian Siswa</h2>
    
    <!-- Tampilkan Data Peserta -->
    <div class="mb-3">
        <h4>Data Peserta</h4>
        <p>
            <strong>Nomor Ujian:</strong> <?= htmlspecialchars($student['nomor_ujian']); ?><br>
            <strong>Nama:</strong> <?= htmlspecialchars($student['nama']); ?><br>
            <!-- <strong>Kelas:</strong> <?= htmlspecialchars($student['kelas']); ?> -->
        </p>
    </div>
    
    <!-- Form Input Nilai Rubrik -->
    <form method="POST">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Rubrik Penilaian</th>
                    <th>Nilai (Maks. 100)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rubrikList as $rubrik): ?>
                    <tr>
                        <td><?= htmlspecialchars($rubrik['nama']); ?></td>
                        <td>
                            <input type="number" step="0.01" name="nilai[<?= $rubrik['id']; ?>]" class="form-control"
                                value="<?= isset($existingScores[$rubrik['id']]) ? htmlspecialchars($existingScores[$rubrik['id']]) : ''; ?>" 
                                min="0" max="100" required>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-success">Simpan Penilaian</button>
        <a href="guru_ujian_penilaian.php?jadwal_id=<?= $jadwal_id; ?>&ruang_id=<?= $ruang_id; ?>" class="btn btn-secondary">Kembali</a>
    </form>
    
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success mt-3">Penilaian berhasil disimpan!</div>
    <?php endif; ?>
</div>

<?php require '../footer.php'; ?>
