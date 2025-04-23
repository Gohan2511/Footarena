<?php
// Include koneksi database
require_once 'connection.php';

// Mendapatkan ID pemesanan dari URL
if (isset($_GET['id'])) {
    $id_pemesanan = $_GET['id'];

    // Query untuk menghapus data
    $sql = "DELETE FROM pemesanan WHERE id_pemesanan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pemesanan);

    if ($stmt->execute()) {
        // Redirect ke halaman daftar pemesanan
        header("Location: read.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "ID pemesanan tidak diberikan.";
}

$conn->close();
