<?php
require 'config.php'; // Koneksi database
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ".$site_url."login.php");
    exit();
}
$role = $_SESSION['role'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Sistem Sekolah</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css"/>
  <style>
    body {
      padding-top: 56px; /* Menyesuaikan ruang untuk navbar fixed */
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Sistem Sekolah</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" 
              aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavDropdown">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <!-- Link Dashboard -->
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="<?php echo $site_url ?>dashboard.php">Dashboard</a>
          </li>
          <?php if($role == 'admin'): ?>
            <!-- Menu Data untuk Admin -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="dataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Data
              </a>
              <ul class="dropdown-menu" aria-labelledby="dataDropdown">
                <li><a class="dropdown-item" href="<?php echo $site_url ?>data/siswa.php">Siswa</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>data/guru.php">Guru</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>data/jurusan.php">Jurusan </a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>data/kelas.php">Kelas</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>data/ruangan.php">Ruangan</a></li>
              </ul>
            </li>
            <!-- Menu Ujian untuk Admin -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="ujianDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Ujian
              </a>
              <ul class="dropdown-menu" aria-labelledby="ujianDropdown">
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/peserta.php">Peserta</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/pengawas.php">Pengawas</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/ruangan.php">Ruangan</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/sesi.php">Sesi</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/jadwal.php">Jadwal</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/peserta_jadwal.php">Jadwal Peserta</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/rubrik.php">Rubrik</a></li>
                <li><a class="dropdown-item" href="<?php echo $site_url ?>ujian/hasil.php">Hasil</a></li>
              </ul>
            </li>
            <!-- Menu Tambahan untuk Admin -->
            <li class="nav-item">
              <a class="nav-link" href="#">Laporan</a>
            </li>
          <?php else: ?>
            <!-- Menu Ujian untuk Guru dan Siswa -->
            <li class="nav-item">
              <a class="nav-link" href="<?php echo $site_url ?>ujian/guru_ujian.php">Ujian</a>
            </li>
            <!-- Menu Profil untuk Guru dan Siswa -->
            <li class="nav-item">
              <a class="nav-link" href="#">Profil</a>
            </li>
          <?php endif; ?>
        </ul>
        <ul class="navbar-nav">
          <li class="nav-item">
            <span class="navbar-text">
              Halo, <?= htmlspecialchars($username); ?> (<?= $role; ?>)
            </span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>