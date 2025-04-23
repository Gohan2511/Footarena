<?php
require_once 'connection.php';

// Inisialisasi variabel
$fields = ['nama', 'email', 'nomor', 'tanggal_pemesanan', 'metode_pembayaran', 'jam_mulai_sewa', 'jam_selesai_sewa', 'lapangan'];
foreach ($fields as $field) {
    $$field = $_POST[$field] ?? "";
}

$errors = [
    'namaErr' => '',
    'emailErr' => '',
    'nomorErr' => '',
    'tanggal_pemesananErr' => '',
    'metode_pembayaranErr' => '',
    'jam_mulai_sewaErr' => '',
    'jam_selesai_sewaErr' => '',
];

// Ambil data lapangan aktif
$sql_lapangan = "SELECT id_lapangan, nama_lapangan FROM lapangan WHERE status = 'aktif'";
$result_lapangan = $conn->query($sql_lapangan);

function isValidTimeRange($start, $end)
{
    return strtotime($end) > strtotime($start);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validasi input
    if (!$nama) $errors['namaErr'] = "Nama wajib diisi";
    if (!$email) {
        $errors['emailErr'] = "Email wajib diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['emailErr'] = "Format email tidak valid";
    }
    if (!$nomor) {
        $errors['nomorErr'] = "Nomor Telepon wajib diisi";
    } elseif (!ctype_digit($nomor)) {
        $errors['nomorErr'] = "Nomor Telepon harus berupa angka";
    }
    if (!$tanggal_pemesanan) $errors['tanggal_pemesananErr'] = "Tanggal Pemesanan wajib diisi";
    if (!$metode_pembayaran) $errors['metode_pembayaranErr'] = "Metode Pembayaran wajib diisi";
    if (!$jam_mulai_sewa) $errors['jam_mulai_sewaErr'] = "Jam Mulai Sewa wajib diisi";
    if (!$jam_selesai_sewa) {
        $errors['jam_selesai_sewaErr'] = "Jam Selesai Sewa wajib diisi";
    } elseif (!isValidTimeRange($jam_mulai_sewa, $jam_selesai_sewa)) {
        $errors['jam_selesai_sewaErr'] = "Jam Selesai tidak boleh lebih awal atau sama";
    }

    // Jika validasi lulus
    if (!array_filter($errors)) {
        $stmt_check = $conn->prepare("
            SELECT 1 FROM pemesanan 
            WHERE id_lapangan = ? AND tanggal_pemesanan = ? 
              AND ((jam_mulai_sewa <= ? AND jam_selesai_sewa > ?) 
                OR (jam_mulai_sewa < ? AND jam_selesai_sewa >= ?)
                OR (jam_mulai_sewa >= ? AND jam_selesai_sewa <= ?))
              AND status_pemesanan IN ('pending', 'konfirmasi')
        ");
        $stmt_check->bind_param(
            "isssssss",
            $lapangan,
            $tanggal_pemesanan,
            $jam_selesai_sewa,
            $jam_mulai_sewa,
            $jam_selesai_sewa,
            $jam_mulai_sewa,
            $jam_mulai_sewa,
            $jam_selesai_sewa
        );
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $errors['jam_mulai_sewaErr'] = "Maaf, lapangan sudah dipesan pada waktu tersebut.";
        } elseif ($_POST["konfirmasi_pemesanan"] === "true") {
            $stmt = $conn->prepare("INSERT INTO pemesanan 
                (nama, email, nomor_telepon, tanggal_pemesanan, id_lapangan, metode_pembayaran, jam_mulai_sewa, jam_selesai_sewa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisss", $nama, $email, $nomor, $tanggal_pemesanan, $lapangan, $metode_pembayaran, $jam_mulai_sewa, $jam_selesai_sewa);
            if ($stmt->execute()) {
                header("Location: success.php?id=" . $conn->insert_id);
                exit();
            } else {
                echo "Error saat menyimpan: " . $stmt->error;
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
    <title>Form Pemesanan Lapangan Mini Soccer</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function konfirmasiPemesanan() {
            var nama = document.querySelector('#data-pemesanan td:nth-child(1)').innerText;
            var email = document.querySelector('#data-pemesanan td:nth-child(2)').innerText;
            var nomor = document.querySelector('#data-pemesanan td:nth-child(3)').innerText;
            var tanggal = document.querySelector('#data-pemesanan td:nth-child(4)').innerText;
            var lapangan = document.querySelector('#data-pemesanan td:nth-child(5)').innerText;
            var metodeBayar = document.querySelector('#data-pemesanan td:nth-child(6)').innerText;
            var jamMulai = document.querySelector('#data-pemesanan td:nth-child(7)').innerText;
            var jamSelesai = document.querySelector('#data-pemesanan td:nth-child(8)').innerText;

            var konfirmasi = confirm(
                "Konfirmasi Pemesanan:\n\n" +
                "Nama: " + nama + "\n" +
                "Email: " + email + "\n" +
                "Nomor Telepon: " + nomor + "\n" +
                "Tanggal Pemesanan: " + tanggal + "\n" +
                "Lapangan: " + lapangan + "\n" +
                "Metode Pembayaran: " + metodeBayar + "\n" +
                "Jam Mulai Sewa : " + jamMulai + "\n" +
                "Jam Selesai Sewa : " + jamSelesai + "\n\n" +
                "Apakah Anda yakin ingin menyelesaikan pemesanan ini?"
            );

            if (konfirmasi) {
                // Tambahkan input hidden untuk memicu penyimpanan
                var inputKonfirmasi = document.createElement("input");
                inputKonfirmasi.type = "hidden";
                inputKonfirmasi.name = "konfirmasi_pemesanan";
                inputKonfirmasi.value = "true";
                document.getElementById("form-pemesanan").appendChild(inputKonfirmasi);

                // Submit form
                document.getElementById("form-pemesanan").submit();
            } else {
                alert("Pemesanan dibatalkan.");
            }
        }
    </script>
</head>

<body>
    <div class="container">
        <h2>Form Pemesanan Lapangan Mini Soccer</h2>
        <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>" id="form-pemesanan">
            <div class="form-group">
                <label for="nama">Nama:</label>
                <input type="text" id="nama" name="nama" value="<?php echo $nama; ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>">

            </div>

            <div class="form-group">
                <label for="nomor">Nomor Telepon:</label>
                <input type="text" id="nomor" name="nomor" value="<?php echo $nomor; ?>" required>
            </div>

            <div class="form-group">
                <label for="tanggal_pemesanan">Tanggal Pemesanan:</label>
                <input type="date" id="tanggal_pemesanan" name="tanggal_pemesanan" value="<?php echo $tanggal_pemesanan; ?>" required>
            </div>

            <div class="form-group">
                <label for="lapangan">Pilih Lapangan Mini Soccer:</label>
                <select id="lapangan" name="lapangan" required>
                    <option value="">-- Pilih Lapangan --</option>
                    <?php while ($row = $result_lapangan->fetch_assoc()) : ?>
                        <option value="<?= htmlspecialchars($row['id_lapangan']) ?>"
                            <?= ($lapangan == $row['id_lapangan']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['nama_lapangan']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="metode_pembayaran">Metode Pembayaran:</label>
                <select id="metode_pembayaran" name="metode_pembayaran" required>
                    <option value="">-- Pilih Metode --</option>
                    <?php
                    $metodes = ['Transfer Bank', 'E-Wallet', 'Cash'];
                    foreach ($metodes as $metode) :
                    ?>
                        <option value="<?= $metode ?>" <?= ($metode_pembayaran == $metode) ? 'selected' : '' ?>>
                            <?= $metode ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group">
                <label for="jam_mulai_sewa">Jam Mulai Sewa :</label>
                <input type="time" id="jam_mulai_sewa" name="jam_mulai_sewa" value="<?php echo $jam_mulai_sewa; ?>" required>
            </div>

            <div class="form-group">
                <label for="jam_selesai_sewa">Jam Selesai Sewa :</label>
                <input type="time" id="jam_selesai_sewa" name="jam_selesai_sewa" value="<?php echo $jam_selesai_sewa; ?>" required>
            </div>

            <div class="button-container">
                <button type="submit">Pilih Lapangan</button>
            </div>
        </form>
    </div>

    <?php if ($_SERVER["REQUEST_METHOD"] == "POST") { ?>
        <div class="container">
            <h3>Data Pemesanan:</h3>
            <div class="table-container">
                <table id="data-pemesanan">
                    <thead>
                        <tr>
                            <th width="18%">Nama</th>
                            <th width="18%">Email</th>
                            <th width="14%">Nomor Telepon</th>
                            <th width="14%">Tanggal Pemesanan</th>
                            <th width="14%">Lapangan</th>
                            <th width="14%">Metode Pembayaran</th>
                            <th width="14%">Jam Mulai Sewa </th>
                            <th width="14%">Jam Selesai Sewa </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $nama; ?></td>
                            <td><?php echo $email; ?></td>
                            <td><?php echo $nomor; ?></td>
                            <td><?php echo $tanggal_pemesanan; ?></td>
                            <td><?php echo $lapangan; ?></td>
                            <td><?php echo $metode_pembayaran; ?></td>
                            <td><?php echo $jam_mulai_sewa; ?></td>
                            <td><?php echo $jam_selesai_sewa; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" onclick="konfirmasiPemesanan()">Selesaikan Pemesanan</button>

        </div>
    <?php } ?>
</body>

</html>