<?php include('header.php') ?>
<!-- Konten Dashboard -->
<div class="container mt-4">
  <h2>Dashboard</h2>
  <p>Selamat datang di sistem manajemen sekolah.</p>
  <p>Anda login sebagai <strong><?= htmlspecialchars($role); ?></strong>.</p>

  <?php if($role == 'admin'): ?>
    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header bg-info text-white">Kelola Data Siswa</div>
          <div class="card-body">
            <p>Tambah, ubah, dan hapus data siswa.</p>
            <a href="#" class="btn btn-primary btn-sm">Kelola Siswa</a>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header bg-info text-white">Kelola Data Guru</div>
          <div class="card-body">
            <p>Tambah, ubah, dan hapus data guru.</p>
            <a href="data/guru.php" class="btn btn-primary btn-sm">Kelola Guru</a>
          </div>
        </div>
      </div>
      <!-- Tambahan card untuk Kelas dan Ruangan -->
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header bg-info text-white">Kelola Kelas & Ruangan</div>
          <div class="card-body">
            <p>Atur kelas dan ruangan pembelajaran.</p>
            <a href="#" class="btn btn-primary btn-sm">Kelola Kelas & Ruangan</a>
          </div>
        </div>
      </div>
      <!-- Card untuk menu Ujian Admin -->
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header bg-info text-white">Kelola Ujian</div>
          <div class="card-body">
            <p>Atur daftar ujian, peserta, jadwal, pengawas, ruang, dan rubrik.</p>
            <a href="#" class="btn btn-primary btn-sm">Kelola Ujian</a>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info">
      <strong>Info:</strong> Menu ujian tersedia untuk mengakses jadwal dan pelaksanaan ujian.
    </div>
    <!-- Konten tambahan khusus Guru/Siswa -->
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-secondary text-white">Informasi Ujian</div>
          <div class="card-body">
            <p>Berikut adalah informasi terkait ujian yang dapat diikuti.</p>
            <a href="<?php echo $site_url ?>ujian/guru_ujian.php" class="btn btn-primary btn-sm">Lihat Ujian</a>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php include('footer.php') ?>