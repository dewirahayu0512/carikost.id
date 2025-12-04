<?php
session_start();
include 'db.php';

// 1. Cek Login & Hak Akses
// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Logika Hapus Pesan
if (isset($_GET['hapus_id'])) {
    $database->getReference('bantuan/' . $_GET['hapus_id'])->remove();
    header("Location: admin_bantuan.php");
    exit();
}

// 3. Ambil Semua Pesan Bantuan
$messages = $database->getReference('bantuan')->getValue();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesan Masuk - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 900px; margin: 30px auto; padding: 20px; }
        .msg-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 15px; border-left: 4px solid #17a2b8; }
        .msg-meta { font-size: 0.9em; color: #777; margin-bottom: 10px; display: flex; justify-content: space-between; }
        .msg-body { background: #f9f9f9; padding: 15px; border-radius: 5px; color: #333; line-height: 1.5; margin-bottom: 10px; }
        .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85em; color: white; display: inline-block; }
        .btn-green { background: #28a745; }
        .btn-red { background: #dc3545; }
        .btn-back { color: #555; text-decoration: none; font-weight: bold; }
        .empty-state { text-align: center; padding: 50px; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="margin-top:0;"><i class="fa-solid fa-envelope"></i> Kotak Masuk Admin</h2>
        <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
        <hr style="margin-bottom: 20px;">

        <?php 
        if ($messages && is_array($messages)): 
            $messages = array_reverse($messages, true); // Urutkan dari yang terbaru
            foreach ($messages as $id => $msg):
                
                // --- PENGAMAN DATA (Mencegah Error Undefined) ---
                $nama = htmlspecialchars($msg['nama_pengirim'] ?? 'Tanpa Nama');
                
                // Cek role (support data lama 'role_pengirim' atau data baru 'role')
                $roleRaw = $msg['role'] ?? $msg['role_pengirim'] ?? 'user';
                $role = ucfirst($roleRaw);
                
                // Cek tanggal (support data lama 'tanggal_kirim' atau data baru 'tanggal')
                $tglRaw = $msg['tanggal'] ?? $msg['tanggal_kirim'] ?? date('Y-m-d');
                $tanggal = htmlspecialchars($tglRaw);
                
                $subjek = htmlspecialchars($msg['subjek'] ?? '(Tanpa Subjek)');
                $pesan = nl2br(htmlspecialchars($msg['pesan'] ?? '-'));
        ?>
            <div class="msg-card">
                <div class="msg-meta">
                    <span>
                        <i class="fa-solid fa-user"></i> <strong><?= $nama ?></strong> 
                        <span style="background:#eee; padding:2px 6px; border-radius:4px; font-size:0.9em; margin-left:5px;"><?= $role ?></span>
                    </span>
                    <span><i class="fa-regular fa-clock"></i> <?= $tanggal ?></span>
                </div>
                
                <h3 style="margin: 5px 0 10px 0; color: #007bff;"><?= $subjek ?></h3>
                
                <div class="msg-body">
                    <?= $pesan ?>
                </div>

                <div style="text-align: right;">
                    <a href="https://wa.me/?text=Halo%20<?= urlencode($nama) ?>%2C%20terkait%20pesan%20Anda..." target="_blank" class="btn btn-green">
                        <i class="fa-brands fa-whatsapp"></i> Balas WA
                    </a>
                    <a href="admin_bantuan.php?hapus_id=<?= $id ?>" class="btn btn-red" onclick="return confirm('Hapus pesan ini?')">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </a>
                </div>
            </div>
        <?php 
            endforeach;
        else:
        ?>
            <div class="empty-state">
                <i class="fa-regular fa-folder-open" style="font-size:3em; margin-bottom:10px;"></i>
                <p>Tidak ada pesan baru.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>