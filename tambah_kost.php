<?php
session_start();
include 'db.php';

// Cek Sesi & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    die("Akses Ditolak!");
}

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Upload Foto Utama (Cover)
    $foto_url = "https://via.placeholder.com/300?text=No+Image";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        $filename = "cover_" . time() . "_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $filename)) {
            // Sesuaikan base URL jika sudah online
            $foto_url = "http://localhost:8000/" . $target_dir . $filename;
        }
    }

    // 2. Upload Galeri (Banyak Foto)
    $gallery_urls = [];
    if (isset($_FILES['galeri'])) {
        $total_files = count($_FILES['galeri']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['galeri']['error'][$i] == 0) {
                $ext = pathinfo($_FILES["galeri"]["name"][$i], PATHINFO_EXTENSION);
                $filename = "real_" . time() . "" . uniqid() . "$i." . $ext;
                
                if (move_uploaded_file($_FILES["galeri"]["tmp_name"][$i], "uploads/" . $filename)) {
                    $gallery_urls[] = "http://localhost:8000/uploads/" . $filename;
                }
            }
        }
    }

    // 3. Simpan ke Firebase
    $data = [
        'nama_kost' => htmlspecialchars($_POST['nama_kost']),
        'alamat' => htmlspecialchars($_POST['alamat']),
        'harga' => (int)$_POST['harga'],
        'fasilitas' => isset($_POST['fasilitas']) ? $_POST['fasilitas'] : [],
        'jenis_kelamin' => $_POST['jenis_kelamin'],
        'deskripsi' => htmlspecialchars($_POST['deskripsi']),
        'foto_url' => $foto_url,
        'gallery' => $gallery_urls, 
        'owner_id' => $_SESSION['user_id'],
        'created_at' => date('Y-m-d H:i:s')
    ];

    try {
        $database->getReference('kosts')->push($data);
        $message = "Berhasil! Data kost telah ditambahkan.";
        $msg_type = "success";
    } catch (Exception $e) {
        $message = "Gagal menyimpan: " . $e->getMessage();
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kost - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- TEMA TEAL/TOSCA (KONSISTEN) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; /* Teal Utama */
            --hover-color: #004D40;
            --accent-color: #4db6ac; /* Tosca Terang (Aksen) */
            --text-color: #2f3542;
            --bg-light: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            margin: 0;
            color: var(--text-color);
            font-size: 15px; 
        }

        .container {
            background: white;
            width: 100%;
            max-width: 650px; 
            padding: 35px; 
            border-radius: 18px; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.15); 
            position: relative;
        }

        /* Header Form */
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0f2f1;
        }
        .form-header h2 {
            font-weight: 800;
            color: var(--main-color);
            margin-bottom: 5px;
            font-size: 1.8em; 
        }
        .form-header p {
            color: #777;
            font-size: 0.9em;
        }

        /* Tombol Kembali */
        .btn-back {
            position: absolute;
            top: 15px;
            left: 20px;
            text-decoration: none;
            color: #888;
            font-weight: 500;
            font-size: 0.9em;
            transition: 0.3s;
        }
        .btn-back:hover {
            color: var(--accent-color);
            transform: translateX(-3px);
        }

        /* Form Elements */
        .form-group { margin-bottom: 25px; } 
        
        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            color: #444;
            font-size: 0.95em;
        }

        input[type="text"], 
        input[type="number"], 
        textarea, 
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd; 
            border-radius: 10px; 
            font-family: 'Poppins', sans-serif;
            font-size: 0.95em;
            transition: 0.3s;
            box-sizing: border-box;
            background: var(--bg-light);
            outline: none;
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--main-color);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.2);
        }

        /* Grid Layout untuk Harga & Tipe */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Checkbox Fasilitas Custom */
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            background: #e0f2f1; /* Light Teal background */
            padding: 6px 12px; 
            border-radius: 20px;
            font-size: 0.85em;
            border: 1px solid #b2dfdb;
            transition: 0.2s;
            font-weight: 500;
            color: var(--main-color);
        }
        .checkbox-label input { 
            margin-right: 6px; 
            transform: scale(1.1);
            accent-color: var(--main-color);
        }
        .checkbox-label:hover { 
            background: #c3f1e9;
            border-color: var(--main-color);
        }

        /* Upload Box Style */
        .upload-box {
            border: 2px dashed var(--accent-color); /* Boundary dashed */
            background: #fcfefe; /* Sangat putih */
            border-radius: 10px;
            padding: 20px; 
            text-align: center;
            transition: 0.3s;
            cursor: pointer;
            position: relative;
        }
        .upload-box:hover {
            border-color: var(--main-color);
            background: #e0f2f1; /* Light teal hover */
        }
        .upload-icon {
            font-size: 1.8em; 
            color: var(--main-color);
            margin-bottom: 8px;
        }
        .upload-box input[type="file"] {
            position: absolute; width: 100%; height: 100%;
            top: 0; left: 0; opacity: 0; cursor: pointer;
        }
        .upload-text {
            color: #777;
            font-size: 0.9em;
        }
        .small-note { font-size: 0.7em; color: #aaa; display: block; margin-top: 5px; }

        /* Button Save */
        .btn-save {
            background: var(--main-color); /* Teal Button */
            color: white;
            border: none;
            padding: 14px; 
            width: 100%;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1em;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(0, 121, 107, 0.4);
            margin-top: 15px;
        }
        .btn-save:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 121, 107, 0.5);
        }

        /* Alert Messages */
        .alert {
            padding: 15px; border-radius: 12px; margin-bottom: 25px;
            font-weight: 500; text-align: center;
        }
        .alert-success { 
            background: #e6ffed; 
            color: var(--success-color); 
            border: 1px solid #b7e8c8; 
        }
        .alert-error { 
            background: #fff0f0; 
            color: var(--danger-color); 
            border: 1px solid #f9c7c7; 
        }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .container { padding: 30px 20px; }
            .form-row .form-group { margin-bottom: 15px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>

        <div class="form-header">
            <h2 class="title"><i class="fa-solid fa-house-circle-check"></i> Tambah Kost Baru</h2>
            <p>Isi detail properti Anda dengan lengkap dan pastikan data sudah benar.</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Nama Kost</label>
                <input type="text" name="nama_kost" placeholder="Contoh: Kost Griya Melati" required>
            </div>
            
            <div class="form-group">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="2" placeholder="Jalan, Nomor, Kelurahan..." required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Harga per Bulan (Rp)</label>
                    <input type="number" name="harga" placeholder="Contoh: 800000" required>
                </div>
                <div class="form-group">
                    <label>Tipe Penghuni</label>
                    <select name="jenis_kelamin">
                        <option value="Campur">Campur (Putra/Putri)</option>
                        <option value="L">Khusus Putra</option>
                        <option value="P">Khusus Putri</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Fasilitas Tersedia</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="fasilitas[]" value="WiFi"> WiFi
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="fasilitas[]" value="AC"> AC
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="fasilitas[]" value="KM Dalam"> KM Dalam
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="fasilitas[]" value="Kasur"> Kasur
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="fasilitas[]" value="Lemari"> Lemari
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="fasilitas[]" value="Parkir"> Parkir Luas
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi Tambahan</label>
                <textarea name="deskripsi" rows="4" placeholder="Jelaskan kelebihan kost Anda..."></textarea>
            </div>


            <div class="form-row">
                <div class="form-group">
                    <label>Foto Sampul (Wajib)</label>
                    <div class="upload-box" style="border-color: var(--main-color);">
                        <input type="file" name="foto" accept="image/*" required onchange="updateFileName(this, 'cover-name')">
                        <div class="upload-icon" style="color: var(--main-color);"><i class="fa-solid fa-camera"></i></div>
                        <span class="upload-text" id="cover-name">Pilih foto sampul terbaik</span>
                    </div>
                    <span class="small-note">Foto ini akan menjadi tampilan utama kost Anda.</span>
                </div>
                
                <div class="form-group">
                    <label>Galeri Foto (Opsional)</label>
                    <div class="upload-box" style="border-color: var(--accent-color);">
                        <input type="file" name="galeri[]" accept="image/*" multiple onchange="updateFileName(this, 'gallery-name')">
                        <div class="upload-icon" style="color: var(--accent-color);"><i class="fa-solid fa-images"></i></div>
                        <span class="upload-text" id="gallery-name">Pilih banyak foto kamar/fasilitas</span>
                    </div>
                    <span class="small-note">Tekan CTRL/CMD saat memilih file. Maks 5 foto.</span>
                </div>
            </div>


            <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Simpan Data Kost</button>
        </form>
    </div>

    <script>
        // Script sederhana untuk mengubah teks saat file dipilih
        function updateFileName(input, elementId) {
            const element = document.getElementById(elementId);
            const fileName = input.files.length > 1 
                ? input.files.length + " file dipilih" 
                : input.files[0].name;
            
            element.innerText = fileName;
            element.style.fontWeight = "bold";
            // Gunakan warna aksen (Tosca Terang) untuk nama file yang terpilih
            element.style.color = input.files.length > 0 ? "var(--accent-color)" : "#777";
        }
    </script>
</body>
</html>