<?php
session_start();

// Jika sudah login, langsung redirect ke dashboard masing-masing
if (isset($_SESSION['role'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
?>