<?php
// Include koneksi database
require_once 'connection.php';

// Inisialisasi variabel
$id_pemesanan = $nama = $email = $nomor = $tanggal_pemesanan = $lapangan = $jam_mulai_sewa = $jam_selesai_sewa = $metode_pembayaran = $status_pemesanan = "";
$namaErr = $emailErr = $nomorErr = $tanggal_pemesananErr = $lapanganErr = $jam_mulai_sewaErr = $jam_selesai_sewaErr = $metode_pembayaranErr = $status_pemesananErr = "";

// Mendapatkan data lapangan dari database
$sql_lapangan = "SELECT id_lapangan, nama_lapangan FROM lapangan WHERE status = 'aktif'";
$result_lapangan = $conn->query($sql_lapangan);

// Mendapatkan ID pemesanan dari URL
if (isset($_GET['id'])) {
    $id_pemesanan = $_GET['id'];

    // Mengambil data pemesanan berdasarkan ID
    $sql = "SELECT * FROM pemesanan WHERE id_pemesanan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pemesanan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nama = $row['nama'];
        $email = $row['email'];
        $nomor = $row['nomor_telepon'];
        $tanggal_pemesanan = $row['tanggal_pemesanan'];
        $lapangan = $row['id_lapangan'];
        $jam_mulai_sewa = $row['jam_mulai_sewa'];
        $jam_selesai_sewa = $row['jam_selesai_sewa'];
        $metode_pembayaran = $row['metode_pembayaran'];
        $status_pemesanan = $row['status_pemesanan'];
    } else {
        echo "Pemesanan tidak ditemukan.";
        exit;
    }
    $stmt->close();
}

// Memproses form update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi Nama
    $nama = $_POST["nama"];
    if (empty($nama)) {
        $namaErr = "Nama wajib diisi";
    }

    // Validasi Email
    $email = $_POST["email"];
    if (empty($email)) {
        $emailErr = "Email wajib diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Format email tidak valid";
    }

    // Validasi Nomor Telepon
    $nomor = $_POST["nomor"];
    if (empty($nomor)) {
        $nomorErr = "Nomor Telepon wajib diisi";
    } elseif (!ctype_digit($nomor)) {
        $nomorErr = "Nomor Telepon harus berupa angka";
    }

    // Validasi Tanggal Pemesanan
    $tanggal_pemesanan = $_POST["tanggal_pemesanan"];
    if (empty($tanggal_pemesanan)) {
        $tanggal_pemesananErr = "Tanggal Pemesanan wajib diisi";
    }

    // Validasi Lapangan
    $lapangan = $_POST["lapangan"];
    if (empty($lapangan)) {
        $lapanganErr = "Lapangan wajib dipilih";
    }

    // Validasi Jam Mulai Sewa
    $jam_mulai_sewa = $_POST["jam_mulai_sewa"];
    if (empty($jam_mulai_sewa)) {
        $jam_mulai_sewaErr = "Jam Mulai Sewa wajib diisi";
    }

    // Validasi Jam Selesai Sewa
    $jam_selesai_sewa = $_POST["jam_selesai_sewa"];
    if (empty($jam_selesai_sewa)) {
        $jam_selesai_sewaErr = "Jam Selesai Sewa wajib diisi";
    }

    // Validasi Metode Pembayaran
    $metode_pembayaran = $_POST["metode_pembayaran"];
    if (empty($metode_pembayaran)) {
        $metode_pembayaranErr = "Metode Pembayaran wajib diisi";
    }

    // Validasi Status Pemesanan
    $status_pemesanan = $_POST["status_pemesanan"];
    if (empty($status_pemesanan)) {
        $status_pemesananErr = "Status Pemesanan wajib diisi";
    }

    // Validasi tambahan: Jam selesai tidak boleh lebih awal dari jam mulai
    if (!empty($jam_mulai_sewa) && !empty($jam_selesai_sewa) && strtotime($jam_selesai_sewa) <= strtotime($jam_mulai_sewa)) {
        $jam_selesai_sewaErr = "Jam Selesai Sewa tidak boleh lebih awal atau sama dengan Jam Mulai Sewa";
    }

    // Jika tidak ada error, update data
    if (
        empty($namaErr) && empty($emailErr) && empty($nomorErr) && empty($tanggal_pemesananErr) &&
        empty($lapanganErr) && empty($jam_mulai_sewaErr) && empty($jam_selesai_sewaErr) &&
        empty($metode_pembayaranErr) && empty($status_pemesananErr)
    ) {

        // Memeriksa ketersediaan lapangan untuk pemesanan yang diedit
        $check_availability = "SELECT * FROM pemesanan 
                              WHERE id_lapangan = ? 
                              AND tanggal_pemesanan = ? 
                              AND ((jam_mulai_sewa <= ? AND jam_selesai_sewa > ?) 
                                  OR (jam_mulai_sewa < ? AND jam_selesai_sewa >= ?)
                                  OR (jam_mulai_sewa >= ? AND jam_selesai_sewa <= ?))
                              AND status_pemesanan IN ('pending', 'konfirmasi')
                              AND id_pemesanan != ?";

        $stmt_check = $conn->prepare($check_availability);
        $stmt_check->bind_param(
            "isssssssi",
            $lapangan,
            $tanggal_pemesanan,
            $jam_selesai_sewa,
            $jam_mulai_sewa,
            $jam_selesai_sewa,
            $jam_mulai_sewa,
            $jam_mulai_sewa,
            $jam_selesai_sewa,
            $id_pemesanan
        );
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Lapangan sudah dipesan pada waktu tersebut
            $jam_mulai_sewaErr = "Maaf, lapangan sudah dipesan pada waktu tersebut.";
        } else {
            // Update data pemesanan
            $sql_update = "UPDATE pemesanan SET 
                          nama = ?, 
                          email = ?, 
                          nomor_telepon = ?, 
                          tanggal_pemesanan = ?, 
                          id_lapangan = ?, 
                          jam_mulai_sewa = ?, 
                          jam_selesai_sewa = ?, 
                          metode_pembayaran = ?, 
                          status_pemesanan = ? 
                          WHERE id_pemesanan = ?";

            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param(
                "sssssssssi",
                $nama,
                $email,
                $nomor,
                $tanggal_pemesanan,
                $lapangan,
                $jam_mulai_sewa,
                $jam_selesai_sewa,
                $metode_pembayaran,
                $status_pemesanan,
                $id_pemesanan
            );

            if ($stmt->execute()) {
                // Redirect ke halaman daftar pemesanan
                header("Location: read.php");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pemesanan Lapangan Mini Soccer</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h2>Edit Pemesanan Lapangan Mini Soccer</h2>

        <form method="POST" action="<?php echo $_SERVER["PHP_SELF"] . "?id=" . $id_pemesanan; ?>">
            <!-- Form Fields dengan nilai yang sudah diisi -->
            <div class="form-group">
                <label for="nama">Nama:</label>
                <input type="text" id="nama" name="nama" value="<?php echo $nama; ?>" required>
                <span class="error"><?php echo $namaErr ? "* $namaErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                <span class="error"><?php echo $emailErr ? "* $emailErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="nomor">Nomor Telepon:</label>
                <input type="text" id="nomor" name="nomor" value="<?php echo $nomor; ?>" required>
                <span class="error"><?php echo $nomorErr ? "* $nomorErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="tanggal_pemesanan">Tanggal Pemesanan:</label>
                <input type="date" id="tanggal_pemesanan" name="tanggal_pemesanan" value="<?php echo $tanggal_pemesanan; ?>" required>
                <span class="error"><?php echo $tanggal_pemesananErr ? "* $tanggal_pemesananErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="lapangan">Pilih Lapangan Mini Soccer:</label>
                <select id="lapangan" name="lapangan">
                    <?php
                    if ($result_lapangan->num_rows > 0) {
                        while ($row = $result_lapangan->fetch_assoc()) {
                            $selected = ($lapangan == $row["id_lapangan"]) ? "selected" : "";
                            echo "<option value='" . $row["id_lapangan"] . "' $selected>" . $row["nama_lapangan"] . "</option>";
                        }
                    }
                    ?>
                </select>
                <span class="error"><?php echo $lapanganErr ? "* $lapanganErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="jam_mulai_sewa">Jam Mulai Sewa:</label>
                <input type="time" id="jam_mulai_sewa" name="jam_mulai_sewa" value="<?php echo $jam_mulai_sewa; ?>" required>
                <span class="error"><?php echo $jam_mulai_sewaErr ? "* $jam_mulai_sewaErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="jam_selesai_sewa">Jam Selesai Sewa:</label>
                <input type="time" id="jam_selesai_sewa" name="jam_selesai_sewa" value="<?php echo $jam_selesai_sewa; ?>" required>
                <span class="error"><?php echo $jam_selesai_sewaErr ? "* $jam_selesai_sewaErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="metode_pembayaran">Metode Pembayaran:</label>
                <select id="metode_pembayaran" name="metode_pembayaran" required>
                    <option value="Transfer Bank" <?php echo ($metode_pembayaran == "Transfer Bank") ? "selected" : ""; ?>>Transfer Bank</option>
                    <option value="E-Wallet" <?php echo ($metode_pembayaran == "E-Wallet") ? "selected" : ""; ?>>E-Wallet</option>
                    <option value="Cash" <?php echo ($metode_pembayaran == "Cash") ? "selected" : ""; ?>>Cash</option>
                </select>
                <span class="error"><?php echo $metode_pembayaranErr ? "* $metode_pembayaranErr" : ""; ?></span>
            </div>

            <div class="form-group">
                <label for="status_pemesanan">Status Pemesanan:</label>
                <select id="status_pemesanan" name="status_pemesanan">
                    <option value="pending" <?php echo ($status_pemesanan == "pending") ? "selected" : ""; ?>>Pending</option>
                    <option value="konfirmasi" <?php echo ($status_pemesanan == "konfirmasi") ? "selected" : ""; ?>>Konfirmasi</option>
                    <option value="selesai" <?php echo ($status_pemesanan == "selesai") ? "selected" : ""; ?>>Selesai</option>
                    <option value="batal" <?php echo ($status_pemesanan == "batal") ? "selected" : ""; ?>>Batal</option>
                </select>
                <span class="error"><?php echo $status_pemesananErr ? "* $status_pemesananErr" : ""; ?></span>
            </div>

            <div class="button-container">
                <button type="submit">Update Pemesanan</button>
                <a href="read.php" class="button">Batal</a>
            </div>
        </form>
    </div>
</body>

</html>