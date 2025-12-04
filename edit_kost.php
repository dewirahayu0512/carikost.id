<?php
session_start();
include 'db.php';

// 1. Cek Login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    die("Akses Ditolak.");
}

$id_kost = $_GET['id'] ?? null;
if (!$id_kost) die("ID tidak ditemukan.");

// 2. Ambil Data Lama
$kostRef = $database->getReference('kosts/' . $id_kost); 
$kostData = $kostRef->getValue();

if (!$kostData) die("Data kost tidak ada.");

// Cek apakah kost ini milik user yg login (Keamanan)
if (!isset($kostData['owner_id']) || $kostData['owner_id'] != $_SESSION['user_id']) die("Ini bukan kost Anda.");

$message = "";
$message_success_flag = false; // <<< FLAG BARU UNTUK SWEETALERT

// 3. PROSES UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil data form
    $updateData = [
        'nama_kost' => htmlspecialchars($_POST['nama_kost'] ?? $kostData['nama_kost']),
        'alamat' => htmlspecialchars($_POST['alamat'] ?? $kostData['alamat']),
        'harga' => (int)($_POST['harga'] ?? $kostData['harga']),
        'jenis_kelamin' => $_POST['jenis_kelamin'] ?? $kostData['jenis_kelamin'],
        'deskripsi' => htmlspecialchars($_POST['deskripsi'] ?? $kostData['deskripsi']),
        'fasilitas' => isset($_POST['fasilitas']) ? $_POST['fasilitas'] : []
    ];

    // Cek jika ada foto baru diupload (Proses File Handling...)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        $filename = time() . "_" . uniqid() . "." . $ext;
        
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $filename)) {
            $updateData['foto_url'] = "http://localhost:8000/" . $target_dir . $filename;
        }
    }

    // Update ke Firebase
    try {
        $kostRef->update($updateData);
        // HANYA SET FLAG, TIDAK MENAMPILKAN DIV STATIS
        $message_success_flag = true; 
        // Refresh data agar tampilan form berubah
        $kostData = array_merge($kostData, $updateData); 
    } catch (Exception $e) {
        // Tampilkan pesan error jika gagal
        $message = "Gagal update: " . $e->getMessage();
    }
}

// Helper untuk cek fasilitas tercentang
$fasilitasLama = $kostData['fasilitas'] ?? [];
$nama_kost = $kostData['nama_kost'] ?? 'Nama Kost Tidak Tersedia'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kost - Carikost.id</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Definisi Warna Hijau Teal dari Dashboard */
        :root {
            --primary-color: #388e8e; /* Warna Hijau Teal Utama */
            --primary-dark: #2c7272;
            --light-bg: #f0f2f5;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--light-bg); 
            padding: 30px; 
        }
        .container { 
            max-width: 650px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        }
        h2 {
            color: var(--primary-color); 
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-group { 
            margin-bottom: 25px; 
        }
        label { 
            font-weight: 600; 
            display: block; 
            margin-bottom: 8px; 
            color: #333; 
            font-size: 0.95em;
        }
        input[type="text"], input[type="number"], textarea, select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ced4da; 
            border-radius: 8px; 
            box-sizing: border-box; 
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, input[type="number"]:focus, textarea:focus, select:focus {
            border-color: var(--primary-color); 
            outline: none;
        }
        
        .checkbox-group { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px; 
            padding: 15px; 
            border: 1px solid #e9ecef; 
            border-radius: 8px; 
            background: #f8f9fa;
        }
        .checkbox-group label {
            font-weight: normal;
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-bottom: 0;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            accent-color: var(--primary-color); 
        }
        
        .btn-save { 
            background: var(--primary-color); 
            color: white; 
            border: none; 
            padding: 14px; 
            width: 100%; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 1.1em; 
            letter-spacing: 0.5px;
            transition: background 0.3s ease;
        }
        .btn-save:hover { 
            background: var(--primary-dark); 
        }
        .img-preview { 
            width: 120px; 
            height: 90px; 
            object-fit: cover; 
            margin-top: 5px; 
            margin-bottom: 10px;
            border-radius: 8px; 
            border: 2px solid #ddd; 
        }
        .alert-error {
            /* Gaya untuk pesan error statis */
            background:#f8d7da; 
            color:#721c24; 
            padding:12px; 
            border-radius:8px; 
            margin-bottom:25px; 
            font-weight:bold;
            border: 1px solid #f5c6cb;
        }
        .back-link {
             text-decoration:none; 
             color: var(--primary-dark); 
             font-weight:600;
        }
        .back-link:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>

    <div class="container">
        <h2 style="margin-top:0;"><i class="fas fa-edit"></i> Edit Kost: <?= htmlspecialchars($nama_kost) ?></h2>
        <a href="kelola_kost.php" class="back-link"><i class="fas fa-arrow-left"></i> Batal & Kembali ke Daftar Kost</a>
        <hr style="margin-top:20px; margin-bottom:30px; border: 0; border-top: 1px solid #eee;">

        <?php if($message && !$message_success_flag): // Hanya tampilkan jika ada ERROR, bukan sukses ?>
            <div class="alert-error">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="nama_kost">Nama Kost</label>
                <input type="text" id="nama_kost" name="nama_kost" value="<?= htmlspecialchars($kostData['nama_kost'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat</label>
                <input type="text" id="alamat" name="alamat" value="<?= htmlspecialchars($kostData['alamat'] ?? '') ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="harga">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" value="<?= $kostData['harga'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="jenis_kelamin">Tipe</label>
                    <select id="jenis_kelamin" name="jenis_kelamin">
                        <option value="L" <?= ($kostData['jenis_kelamin'] ?? '')=='L'?'selected':'' ?>>Putra</option>
                        <option value="P" <?= ($kostData['jenis_kelamin'] ?? '')=='P'?'selected':'' ?>>Putri</option>
                        <option value="Campur" <?= ($kostData['jenis_kelamin'] ?? '')=='Campur'?'selected':'' ?>>Campur</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Fasilitas</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="fasilitas[]" value="WiFi" <?= in_array('WiFi', $fasilitasLama)?'checked':'' ?>> WiFi</label>
                    <label><input type="checkbox" name="fasilitas[]" value="AC" <?= in_array('AC', $fasilitasLama)?'checked':'' ?>> AC</label>
                    <label><input type="checkbox" name="fasilitas[]" value="KM Dalam" <?= in_array('KM Dalam', $fasilitasLama)?'checked':'' ?>> KM Dalam</label>
                    <label><input type="checkbox" name="fasilitas[]" value="Kasur" <?= in_array('Kasur', $fasilitasLama)?'checked':'' ?>> Kasur</label>
                    <label><input type="checkbox" name="fasilitas[]" value="Dapur" <?= in_array('Dapur', $fasilitasLama)?'checked':'' ?>> Dapur</label>
                    <label><input type="checkbox" name="fasilitas[]" value="Parkir" <?= in_array('Parkir', $fasilitasLama)?'checked':'' ?>> Parkir</label>
                </div>
            </div>

            <div class="form-group">
                <label>Foto Kost (Biarkan kosong jika tidak diganti)</label>
                <?php if(!empty($kostData['foto_url'])): ?>
                    <img src="<?= htmlspecialchars($kostData['foto_url']) ?>" class="img-preview" alt="Foto Kost Saat Ini">
                <?php endif; ?>
                <input type="file" name="foto" accept="image/*" style="margin-top:5px; padding: 0;">
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="5"><?= htmlspecialchars($kostData['deskripsi'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-save">Simpan Perubahan</button>
        </form>
    </div>

    <?php if ($message_success_flag): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data kost telah berhasil diperbarui.',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: 'var(--primary-color)' // Menggunakan warna hijau teal
                }).then((result) => {
                    // Opsional: Redirect atau refresh halaman setelah pengguna menekan OK
                    if (result.isConfirmed) {
                        // Agar tidak muncul lagi saat refresh
                        window.location.href = window.location.href; 
                    }
                });
            });
        </script>
    <?php endif; ?>

</body>
</html>