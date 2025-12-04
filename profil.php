<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$uid = $_SESSION['user_id'];
$message = "";
$msg_type = "";

// --- 1. LOGIKA UPDATE DATA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = htmlspecialchars($_POST['nama']);
    $no_hp = htmlspecialchars($_POST['no_hp']);
    
    // Data Pembayaran (Khusus Pemilik)
    $nama_bank = htmlspecialchars($_POST['nama_bank'] ?? '');
    $no_rekening = htmlspecialchars($_POST['no_rekening'] ?? '');
    $atas_nama = htmlspecialchars($_POST['atas_nama'] ?? '');
    
    // Update Password
    $password_baru = $_POST['password'];
    
    // Data dasar
    $updateData = [
        'nama_lengkap' => $nama,
        'no_hp' => $no_hp,
        'payment_info' => [ // Simpan info bank dalam array
            'nama_bank' => $nama_bank,
            'no_rekening' => $no_rekening,
            'atas_nama' => $atas_nama
        ]
    ];

    // Logika Password
    if (!empty($password_baru)) {
        if (strlen($password_baru) < 8) {
            $message = "Password minimal 8 karakter!";
            $msg_type = "red";
        } else {
            $updateData['password'] = password_hash($password_baru, PASSWORD_DEFAULT);
        }
    }

    // Logika Upload QRIS (Khusus Pemilik)
    if (isset($_FILES['qris']) && $_FILES['qris']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["qris"]["name"], PATHINFO_EXTENSION);
        $filename = "qris_" . $uid . "." . $file_ext;
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["qris"]["tmp_name"], $target_file)) {
            // Sesuaikan URL ini dengan path server Anda
            $updateData['payment_info']['qris_url'] = "http://localhost:8000/" . $target_file;
        }
    }

    if (empty($message)) {
        try {
            $database->getReference('users/' . $uid)->update($updateData);
            $_SESSION['nama'] = $nama;
            $message = "Profil berhasil diperbarui!";
            $msg_type = "green";
        } catch (Exception $e) {
            $message = "Gagal: " . $e->getMessage();
            $msg_type = "red";
        }
    }
}

// --- 2. AMBIL DATA USER ---
try {
    $user = $database->getReference('users/' . $uid)->getValue();
    $payment = $user['payment_info'] ?? [];
} catch (Exception $e) {
    $user = [];
    $payment = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA (TEAL / TOSCA) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; 
            --hover-color: #004D40; 
            --text-color: #2f3542;
            --bg-light: #f8f9fa;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--primary-gradient); 
            min-height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0; 
            padding: 40px 20px;
            box-sizing: border-box;
            color: var(--text-color);
        }

        .profile-card { 
            background: white; 
            width: 100%; 
            max-width: 700px; /* Lebar card */
            border-radius: 15px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            overflow: hidden;
            position: relative;
        }

        .card-header {
            background: #fff;
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 { margin: 0; font-size: 1.5em; color: var(--main-color); }
        .card-header .icon { font-size: 1.5em; color: var(--main-color); }

        .card-body { padding: 30px; }

        .section-title {
            font-weight: 600;
            color: #444;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0f2f1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: var(--main-color); }
        .section-title:first-child { margin-top: 0; }

        .form-group { margin-bottom: 20px; }
        
        label {
            font-weight: 500;
            display: block;
            margin-bottom: 8px;
            font-size: 0.9em;
            color: #555;
        }

        input[type="text"], 
        input[type="password"],
        input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95em;
            box-sizing: border-box;
            transition: 0.3s;
            outline: none;
        }

        input:focus {
            border-color: var(--main-color);
            background: #fdfaff;
        }

        /* Input Readonly (Email) */
        input:disabled {
            background-color: #f1f3f5;
            color: #888;
            border-color: #eee;
            cursor: not-allowed;
        }

        .btn-save {
            background: var(--main-color);
            color: white;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 1.05em;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(0, 105, 92, 0.3);
        }
        .btn-save:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 105, 92, 0.4);
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #777;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9em;
            transition: 0.3s;
        }
        .btn-back:hover { color: var(--main-color); }

        /* QRIS Styles */
        .qris-preview {
            width: 100%;
            max-width: 200px;
            border-radius: 10px;
            border: 2px dashed #ddd;
            padding: 5px;
            margin-bottom: 10px;
        }
        .file-hint { font-size: 0.8em; color: #888; margin-top: 5px; display: block; }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }
        .alert-green { background: #e0f2f1; color: #00695c; border: 1px solid #b2dfdb; }
        .alert-red { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

    </style>
</head>
<body>

    <div class="profile-card">
        <div class="card-header">
            <h2>Pengaturan Profil</h2>
            <i class="fa-solid fa-user-gear icon"></i>
        </div>

        <div class="card-body">
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $msg_type ?>">
                    <?= ($msg_type == 'green' ? '<i class="fa-solid fa-check-circle"></i> ' : '<i class="fa-solid fa-circle-exclamation"></i> ') . $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <div class="section-title"><i class="fa-solid fa-address-card"></i> Data Pribadi</div>
                
                <div class="form-group">
                    <label>Email (Tidak dapat diubah)</label>
                    <input type="text" value="<?= htmlspecialchars($user['email'] ?? '-') ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>No HP / WhatsApp</label>
                    <input type="text" name="no_hp" inputmode="numeric" placeholder="08..." value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                </div>

                <?php if(isset($user['role']) && $user['role'] == 'pemilik'): ?>
                    <div class="section-title"><i class="fa-solid fa-wallet"></i> Rekening Penerimaan</div>
                    
                    <div class="form-group">
                        <label>Nama Bank / E-Wallet</label>
                        <input type="text" name="nama_bank" placeholder="Contoh: BCA, BRI, DANA" value="<?= htmlspecialchars($payment['nama_bank'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Nomor Rekening</label>
                        <input type="text" name="no_rekening" inputmode="numeric" placeholder="Nomor Rekening" value="<?= htmlspecialchars($payment['no_rekening'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Atas Nama</label>
                        <input type="text" name="atas_nama" placeholder="Nama Pemilik Rekening" value="<?= htmlspecialchars($payment['atas_nama'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Kode QRIS (Opsional)</label>
                        <?php if(!empty($payment['qris_url'])): ?>
                            <img src="<?= $payment['qris_url'] ?>" class="qris-preview" alt="QRIS Anda">
                            <br>
                        <?php endif; ?>
                        <input type="file" name="qris" accept="image/*">
                        <span class="file-hint">Upload gambar QRIS baru untuk mengganti yang lama.</span>
                    </div>
                <?php endif; ?>

                <div class="section-title"><i class="fa-solid fa-shield-halved"></i> Keamanan</div>
                
                <div class="form-group">
                    <label>Ganti Password</label>
                    <input type="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengganti">
                </div>

                <button type="submit" class="btn-save">Simpan Perubahan</button>
                <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
            </form>
        </div>
    </div>

</body>
</html>