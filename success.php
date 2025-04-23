<?php
// Include koneksi database
require_once 'connection.php';

$id_pemesanan = isset($_GET['id']) ? $_GET['id'] : 0;
$pemesanan = null;

if ($id_pemesanan > 0) {
    // Mengambil data pemesanan
    $sql = "SELECT p.*, l.nama_lapangan 
            FROM pemesanan p
            JOIN lapangan l ON p.id_lapangan = l.id_lapangan
            WHERE p.id_pemesanan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pemesanan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pemesanan = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Berhasil</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h2>Pemesanan Berhasil!</h2>

        <?php if ($pemesanan): ?>
            <div class="success-message">
                <p>Terima kasih, <strong><?php echo $pemesanan['nama']; ?></strong>!</p>
                <p>Pemesanan Anda untuk lapangan <strong><?php echo $pemesanan['nama_lapangan']; ?></strong> pada tanggal <strong><?php echo $pemesanan['tanggal_pemesanan']; ?></strong> pukul <strong><?php echo $pemesanan['jam_mulai_sewa'] . ' - ' . $pemesanan['jam_selesai_sewa']; ?></strong> telah berhasil.</p>
                <p>ID Pemesanan Anda: <strong>#<?php echo $pemesanan['id_pemesanan']; ?></strong></p>
                <p>Status: <strong><?php echo $pemesanan['status_pemesanan']; ?></strong></p>

                <?php if ($pemesanan['metode_pembayaran'] == 'Transfer Bank'): ?>
                    <div class="payment-info">
                        <h3>Informasi Pembayaran:</h3>
                        <p>Silakan transfer ke rekening berikut:</p>
                        <p>Bank XYZ</p>
                        <p>No. Rekening: 1234-5678-9012</p>
                        <p>A/N: Mini Soccer Booking</p>
                        <p>Konfirmasi pembayaran ke: 081234567890</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Terjadi kesalahan. Detail pemesanan tidak ditemukan.</p>
        <?php endif; ?>

        <div class="button-container">
            <a href="index.php" class="button">Kembali ke Beranda</a>
            <a href="read.php" class="button">Lihat Semua Pemesanan</a>
        </div>
    </div>
</body>

</html>