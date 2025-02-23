<?php
require '../header.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . $site_url);
    exit();
}

// Proses Tambah Rubrik
if (isset($_POST['add_rubrik'])) {
    $nama   = trim($_POST['nama']);
    $bobot  = $_POST['bobot'];
    $parent_id = ($_POST['parent_id'] !== '') ? $_POST['parent_id'] : null;
    
    $stmt = $conn->prepare("INSERT INTO ujian_rubrik (nama, bobot, parent_id) VALUES (?, ?, ?)");
    $stmt->execute([$nama, $bobot, $parent_id]);
    
    header("Location: rubrik.php?status=added");
    exit();
}

// Proses Update Rubrik
if (isset($_POST['update_rubrik'])) {
    $id     = $_POST['id'];
    $nama   = trim($_POST['nama']);
    $bobot  = $_POST['bobot'];
    $parent_id = ($_POST['parent_id'] !== '') ? $_POST['parent_id'] : null;
    
    $stmt = $conn->prepare("UPDATE ujian_rubrik SET nama = ?, bobot = ?, parent_id = ? WHERE id = ?");
    $stmt->execute([$nama, $bobot, $parent_id, $id]);
    
    header("Location: rubrik.php?status=updated");
    exit();
}

// Proses Hapus Rubrik
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM ujian_rubrik WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: rubrik.php?status=deleted");
    exit();
}

// Fungsi rekursif untuk mengambil struktur rubrik secara hierarki
function getRubrikTree($conn, $parent_id = null, $level = 0) {
    $stmt = $conn->prepare("SELECT * FROM ujian_rubrik WHERE parent_id " . ($parent_id === null ? "IS NULL" : "= ?") . " ORDER BY nama ASC");
    if ($parent_id === null) {
        $stmt->execute();
    } else {
        $stmt->execute([$parent_id]);
    }
    $rubriks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tree = [];
    foreach ($rubriks as $rubrik) {
        $rubrik['level'] = $level;
        $tree[] = $rubrik;
        $children = getRubrikTree($conn, $rubrik['id'], $level + 1);
        $tree = array_merge($tree, $children);
    }
    return $tree;
}
$rubrikTree = getRubrikTree($conn);

// Fungsi untuk membuat opsi dropdown parent rubrik
function getRubrikOptions($conn, $exclude_id = null) {
    $tree = getRubrikTree($conn);
    $options = '<option value="">-- Tanpa Parent --</option>';
    foreach ($tree as $rubrik) {
        if ($exclude_id !== null && $rubrik['id'] == $exclude_id) continue;
        $indent = str_repeat("--", $rubrik['level']);
        $options .= '<option value="' . $rubrik['id'] . '">' . $indent . ' ' . htmlspecialchars($rubrik['nama']) . '</option>';
    }
    return $options;
}
?>

<div class="container mt-4">
    <h2>Manajemen Rubrik Penilaian</h2>
    
    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success">
            <?php 
            if ($_GET['status'] == 'added') echo "Rubrik berhasil ditambahkan!";
            elseif ($_GET['status'] == 'updated') echo "Rubrik berhasil diperbarui!";
            elseif ($_GET['status'] == 'deleted') echo "Rubrik berhasil dihapus!";
            ?>
        </div>
    <?php endif; ?>

    <!-- Form Tambah Rubrik -->
    <div class="card mb-4">
        <div class="card-header">Tambah Rubrik</div>
        <div class="card-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" name="nama" class="form-control" placeholder="Nama Rubrik" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.01" name="bobot" class="form-control" placeholder="Bobot" required>
                    </div>
                    <div class="col-md-4">
                        <select name="parent_id" class="form-control">
                            <?= getRubrikOptions($conn); ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_rubrik" class="btn btn-primary">Tambah Rubrik</button>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Rubrik -->
    <table class="table table-bordered table-striped" id="datatable">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nama Rubrik</th>
                <th>Bobot</th>
                <th>Parent</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rubrikTree as $rubrik): ?>
                <tr>
                    <td><?= $rubrik['id']; ?></td>
                    <td><?= str_repeat("--", $rubrik['level']) . " " . htmlspecialchars($rubrik['nama']); ?></td>
                    <td><?= $rubrik['bobot']; ?></td>
                    <td>
                        <?php 
                        if ($rubrik['parent_id']) {
                            $stmt = $conn->prepare("SELECT nama FROM ujian_rubrik WHERE id = ?");
                            $stmt->execute([$rubrik['parent_id']]);
                            echo htmlspecialchars($stmt->fetchColumn());
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <!-- Tombol Edit (memicu modal) -->
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $rubrik['id']; ?>">Edit</button>
                        <a href="?delete=<?= $rubrik['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus rubrik ini?')">Hapus</a>
                    </td>
                </tr>

                <!-- Modal Edit Rubrik -->
                <div class="modal fade" id="editModal<?= $rubrik['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Rubrik</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $rubrik['id']; ?>">
                                    <div class="mb-3">
                                        <label>Nama Rubrik</label>
                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($rubrik['nama']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Bobot</label>
                                        <input type="number" step="0.01" name="bobot" class="form-control" value="<?= $rubrik['bobot']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Parent Rubrik</label>
                                        <select name="parent_id" class="form-control">
                                            <?= getRubrikOptions($conn, $rubrik['id']); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update_rubrik" class="btn btn-success">Simpan Perubahan</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- DataTables & Bootstrap JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable();
    });
</script>

<?php require '../footer.php'; ?>
