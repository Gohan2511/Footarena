<?php
// Include koneksi database
require_once 'connection.php';

// Query untuk mendapatkan semua data pemesanan
$sql = "SELECT p.*, l.nama_lapangan 
        FROM pemesanan p
        JOIN lapangan l ON p.id_lapangan = l.id_lapangan
        ORDER BY p.tanggal_pemesanan DESC, p.jam_mulai_sewa ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pemesanan Lapangan Mini Soccer</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h2>Daftar Pemesanan Lapangan Mini Soccer</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Nomor Telepon</th>
                        <th>Tanggal</th>
                        <th>Lapangan</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Metode Pembayaran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id_pemesanan"] . "</td>";
                            echo "<td>" . $row["nama"] . "</td>";
                            echo "<td>" . $row["email"] . "</td>";
                            echo "<td>" . $row["nomor_telepon"] . "</td>";
                            echo "<td>" . $row["tanggal_pemesanan"] . "</td>";
                            echo "<td>" . $row["nama_lapangan"] . "</td>";
                            echo "<td>" . $row["jam_mulai_sewa"] . "</td>";
                            echo "<td>" . $row["jam_selesai_sewa"] . "</td>";
                            echo "<td>" . $row["metode_pembayaran"] . "</td>";
                            echo "<td>" . $row["status_pemesanan"] . "</td>";
                            echo "<td>
                                    <a href='update.php?id=" . $row["id_pemesanan"] . "'>Edit</a> | 
                                    <a href='delete.php?id=" . $row["id_pemesanan"] . "' onclick='return confirm(\"Apakah Anda yakin ingin menghapus data ini?\")'>Hapus</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11'>Tidak ada data pemesanan</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="button-container">
            <a href="create.php" class="button">Tambah Pemesanan Baru</a>
        </div>
    </div>
</body>

</html>