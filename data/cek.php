<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require '../config.php'; // Koneksi database

// -------------------- EXPORT FUNCTIONALITY --------------------
if (isset($_GET['export']) && in_array($_GET['export'], ['csv', 'xlsx'])) {
    $format = $_GET['export'];
    // Ambil semua data guru
    $stmt = $conn->prepare("SELECT * FROM guru ORDER BY id ASC");
    $stmt->execute();
    $guruList = $stmt->fetchAll();

    if ($format == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=guru_data.csv');
        $output = fopen('php://output', 'w');
        // Header kolom
        fputcsv($output, ['Nomor Pegawai', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir']);
        foreach ($guruList as $guru) {
            fputcsv($output, [
                $guru['nip'], 
                $guru['nama_lengkap'], 
                $guru['jk'], 
                $guru['tempat_lahir'], 
                $guru['tanggal_lahir']
            ]);
        }
        fclose($output);
        exit();
    } elseif ($format == 'xlsx') {
        // require '../vendor/autoload.php';
        // use PhpOffice\PhpSpreadsheet\Spreadsheet;
        // use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
        
        // $spreadsheet = new Spreadsheet();
        // $sheet = $spreadsheet->getActiveSheet();
        // // Header kolom
        // $sheet->setCellValue('A1', 'Nomor Pegawai');
        // $sheet->setCellValue('B1', 'Nama Lengkap');
        // $sheet->setCellValue('C1', 'Jenis Kelamin');
        // $sheet->setCellValue('D1', 'Tempat Lahir');
        // $sheet->setCellValue('E1', 'Tanggal Lahir');
        
        // $row = 2;
        // foreach ($guruList as $guru) {
        //     $sheet->setCellValue('A'.$row, $guru['nip']);
        //     $sheet->setCellValue('B'.$row, $guru['nama_lengkap']);
        //     $sheet->setCellValue('C'.$row, $guru['jk']);
        //     $sheet->setCellValue('D'.$row, $guru['tempat_lahir']);
        //     $sheet->setCellValue('E'.$row, $guru['tanggal_lahir']);
        //     $row++;
        // }
        
        // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="guru_data.xlsx"');
        // header('Cache-Control: max-age=0');
        // $writer = new Xlsx($spreadsheet);
        // $writer->save('php://output');
        // exit();
    }
}
// -------------------- END EXPORT --------------------

// -------------------- IMPORT FUNCTIONALITY --------------------
if (isset($_POST['import_submit'])) {
    if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
        $fileName = $_FILES['import_file']['name'];
        $fileTmp = $_FILES['import_file']['tmp_name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        
        $importData = [];
        if ($fileExt == 'csv') {
            if (($handle = fopen($fileTmp, 'r')) !== false) {
                // Ambil header
                $header = fgetcsv($handle, 1000, ",");
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $importData[] = $data;
                }
                fclose($handle);
            }
        } elseif ($fileExt == 'xlsx') {
            // require '../vendor/autoload.php';
            // use PhpOffice\PhpSpreadsheet\IOFactory;
            // $spreadsheet = IOFactory::load($fileTmp);
            // $worksheet = $spreadsheet->getActiveSheet();
            // $rows = $worksheet->toArray();
            // // Hapus baris header
            // $header = array_shift($rows);
            // $importData = $rows;
        } else {
            echo "<script>alert('Format file tidak didukung!'); window.location.href='guru.php';</script>";
            exit();
        }
        
        // Asumsikan urutan kolom: nip, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir
        foreach ($importData as $rowData) {
            if(count($rowData) < 5) continue;
            $nip = $rowData[0];
            $nama_lengkap = $rowData[1];
            $jenis_kelamin = $rowData[2];
            $tempat_lahir = $rowData[3];
            $tanggal_lahir = $rowData[4];
            
            // Generate username dan password
            $username = $nip;
            $password_plain = date('dmY', strtotime($tanggal_lahir));
            $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
            
            try {
                $conn->beginTransaction();
                $sql = "INSERT INTO guru (nip, nama_lengkap, jk, tempat_lahir, tanggal_lahir)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$nip, $nama_lengkap, $jenis_kelamin, $tempat_lahir, $tanggal_lahir]);
                
                $sqlUser = "INSERT INTO users (username, password, role) VALUES (?, ?, 'guru')";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->execute([$username, $password_hashed]);
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                // Log error jika diperlukan
            }
        }
        echo "<script>alert('Import data berhasil!'); window.location.href='guru.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal mengupload file!'); window.location.href='guru.php';</script>";
        exit();
    }
}
// -------------------- END IMPORT --------------------

// -------------------- CREATE / ADD GURU --------------------
if (isset($_POST['add'])) {
    $nip = $_POST['nip'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];

    // Generate username & password
    $username = $nip;
    $password_plain = date('dmY', strtotime($tanggal_lahir)); // Format DDMMYYYY
    $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

    try {
        $conn->beginTransaction();
        $sql = "INSERT INTO guru (nip, nama_lengkap, jk, tempat_lahir, tanggal_lahir) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nip, $nama_lengkap, $jenis_kelamin, $tempat_lahir, $tanggal_lahir]);

        $sqlUser = "INSERT INTO users (username, password, role) VALUES (?, ?, 'guru')";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->execute([$username, $password_hashed]);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Error: " . $e->getMessage());
    }
    header("Location: guru.php");
    exit();
}

// -------------------- DELETE GURU --------------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $conn->beginTransaction();
        // Ambil nomor pegawai untuk menghapus akun pengguna
        $stmt = $conn->prepare("SELECT nip FROM guru WHERE id = ?");
        $stmt->execute([$id]);
        $guru = $stmt->fetch();
        $username = $guru['nip'];

        $stmt = $conn->prepare("DELETE FROM guru WHERE id = ?");
        $stmt->execute([$id]);

        $stmtUser = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmtUser->execute([$username]);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Error: " . $e->getMessage());
    }
    header("Location: guru.php");
    exit();
}

// -------------------- AMBIL DATA GURU --------------------
$sql = "SELECT * FROM guru ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$guruList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Guru</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css"/>
</head>
<body>
<div class="container mt-5">
  <h2>Manajemen Guru</h2>
  
  <!-- Tombol Import & Export -->
  <div class="mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import Data</button>
    <a href="guru.php?export=csv" class="btn btn-info">Export CSV</a>
    <a href="guru.php?export=xlsx" class="btn btn-info">Export XLSX</a>
  </div>
  
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Guru</button>
  
  <table id="guruTable" class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>No</th>
        <th>Nomor Pegawai</th>
        <th>Nama Lengkap</th>
        <th>Jenis Kelamin</th>
        <th>Tempat Lahir</th>
        <th>Tanggal Lahir</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($guruList as $index => $guru): ?>
        <tr>
          <td><?= $index + 1; ?></td>
          <td><?= htmlspecialchars($guru['nip']); ?></td>
          <td><?= htmlspecialchars($guru['nama_lengkap']); ?></td>
          <td><?= htmlspecialchars($guru['jk']); ?></td>
          <td><?= htmlspecialchars($guru['tempat_lahir']); ?></td>
          <td><?= htmlspecialchars($guru['tanggal_lahir']); ?></td>
          <td>
            <a href="guru.php?delete=<?= $guru['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus?');">Hapus</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal Import Data -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Data Guru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pilih file (CSV atau XLSX)</label>
            <input type="file" name="import_file" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="import_submit" class="btn btn-success">Import</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tambah Guru -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Guru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nomor Pegawai</label>
            <input type="text" name="nip" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-control">
              <option value="Laki-laki">Laki-laki</option>
              <option value="Perempuan">Perempuan</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Tempat Lahir</label>
            <input type="text" name="tempat_lahir" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn btn-success">Tambah</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery, Bootstrap, DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#guruTable').DataTable();
});
</script>
</body>
</html>
