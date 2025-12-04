<?php
session_start();
include 'db.php';

// 1. Cek Akses Pemilik
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    die("Akses Ditolak.");
}

$uid = $_SESSION['user_id'];

// 2. Ambil Data
try {
    $bookings = $database->getReference('bookings')->getValue();
    $users = $database->getReference('users')->getValue();
} catch (Exception $e) {
    $bookings = [];
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Penyewa - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA (TEAL / TOSCA) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; 
            --hover-color: #004D40; 
            --bg-light: #f4f6f8;
            --text-color: #2f3542;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-color); margin: 0; padding: 20px; }
        
        .container { 
            max-width: 1200px; /* Lebar sedikit ditambah agar lega */
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        }
        
        /* HEADER */
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #e0f2f1; 
            padding-bottom: 20px; 
        }
        .header h2 { margin: 0; color: #333; font-size: 1.6em; font-weight: 700; }
        
        .btn-back { 
            color: #777; 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 0.95em; 
            transition: 0.3s; 
            display: flex; 
            align-items: center; 
            gap: 5px;
            margin-bottom: 5px;
        }
        .btn-back:hover { color: var(--main-color); }

        /* TABEL MODERN */
        table { width: 100%; border-collapse: separate; border-spacing: 0 15px; margin-top: 10px; }
        
        thead th { 
            color: #888; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.85em; 
            /* PERBAIKAN: Padding kiri disamakan dengan td (20px) agar lurus */
            padding: 0 20px 10px; 
            text-align: left; 
            letter-spacing: 0.5px;
        }
        
        tbody tr { 
            background: white; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.03); 
            transition: transform 0.2s; 
            border-radius: 12px;
        }
        tbody tr:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        
        td { padding: 20px; vertical-align: middle; border-top: 1px solid #f9f9f9; border-bottom: 1px solid #f9f9f9; }
        td:first-child { border-left: 1px solid #f9f9f9; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-right: 1px solid #f9f9f9; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

        /* User Info */
        .user-box { display: flex; align-items: center; gap: 15px; }
        .user-avatar { 
            width: 45px; height: 45px; 
            background: #e0f2f1; 
            color: var(--main-color); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.2em; 
            flex-shrink: 0;
        }
        .user-name { font-weight: 700; color: #333; display: block; font-size: 1em; margin-bottom: 2px; }
        .user-contact { font-size: 0.85em; color: #888; }

        /* Status Badge */
        .badge { padding: 8px 15px; border-radius: 30px; font-size: 0.75em; font-weight: 700; text-transform: uppercase; display: inline-block; letter-spacing: 0.5px; }
        .bg-Dikonfirmasi { background: #e8f5e9; color: #2e7d32; }
        .bg-menunggu_pembayaran { background: #fff8e1; color: #ff8f00; }
        .bg-menunggu_verifikasi { background: #e3f2fd; color: #1565c0; }
        .bg-Ditolak { background: #ffebee; color: #c62828; }

        /* Tombol WA */
        .btn-wa { 
            background: #25D366; 
            color: white; 
            text-decoration: none; 
            padding: 8px 16px; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 0.9em; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: 0.3s; 
            box-shadow: 0 4px 10px rgba(37,211,102,0.2);
        }
        .btn-wa:hover { background: #128C7E; transform: translateY(-2px); }

        /* Empty State */
        .empty-state { text-align: center; padding: 80px; color: #999; }
        .empty-state i { font-size: 4em; margin-bottom: 15px; color: #e0e0e0; }
        .empty-state h3 { margin: 0; font-weight: 600; color: #555; }

        /* --- PERBAIKAN TANGGAL & DURASI --- */
        .date-info { font-size: 0.9em; color: #555; display: flex; flex-direction: column; gap: 6px; }
        .date-row { display: flex; align-items: center; }
        
        /* Icon Fixed Width: Agar semua teks di sebelah kanan ikon lurus rata vertikal */
        .icon-fixed { 
            width: 20px; 
            text-align: center; 
            margin-right: 10px; 
            display: inline-block;
        }
        
        .date-label { font-size: 0.85em; color: #888; margin-right: 5px; min-width: 50px; display: inline-block; }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .container { padding: 20px; }
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { margin-bottom: 20px; border: 1px solid #eee; border-radius: 15px; padding: 20px; background: white; }
            td { padding: 10px 0; border: none; position: relative; border-bottom: 1px solid #f9f9f9; }
            td:last-child { border-bottom: none; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div>
                <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <h2>Data Penyewa</h2>
            </div>
            <div style="color: #666; font-weight: 500;">
                <i class="fa-solid fa-users" style="color: var(--main-color);"></i> Daftar Pelanggan
            </div>
        </div>

        <?php 
        $adaData = false;
        if ($bookings && is_array($bookings)):
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Nama Penyewa</th>
                        <th>Info Kost</th>
                        <th>Periode Sewa</th>
                        <th>Status</th>
                        <th>Kontak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Urutkan dari yang terbaru
                    $bookings = array_reverse($bookings);
                    
                    foreach ($bookings as $b):
                        // Filter: Hanya tampilkan booking milik Owner yang sedang login
                        if (isset($b['owner_id']) && $b['owner_id'] == $uid):
                            $adaData = true;
                            
                            // Ambil data detail user
                            $penyewaId = $b['penyewa_id'];
                            $detailPenyewa = $users[$penyewaId] ?? [];
                            $noHp = $detailPenyewa['no_hp'] ?? '-';
                            
                            // --- HITUNG TANGGAL KELUAR ---
                            $tglMasuk = $b['tanggal_masuk']; // Format: YYYY-MM-DD
                            $durasi = (int)$b['durasi_bulan'];
                            
                            $dateObj = new DateTime($tglMasuk);
                            $tglMasukIndo = $dateObj->format('d M Y');
                            
                            $dateObj->modify("+$durasi months");
                            $tglKeluarIndo = $dateObj->format('d M Y');
                            
                            // Format Link WA
                            $waLink = "#";
                            if ($noHp != '-') {
                                $hpFormat = preg_replace('/^0/', '62', $noHp);
                                $waLink = "https://wa.me/$hpFormat?text=Halo%20" . urlencode($b['nama_penyewa']) . "%2C%20terkait%20kost%20Anda...";
                            }
                    ?>
                        <tr>
                            <td>
                                <div class="user-box">
                                    <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                                    <div>
                                        <span class="user-name"><?= htmlspecialchars($b['nama_penyewa']) ?></span>
                                        <span class="user-contact"><?= htmlspecialchars($detailPenyewa['email'] ?? '') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #333; margin-bottom: 5px; font-size: 1.05em;">
                                    <?= htmlspecialchars($b['nama_kost']) ?>
                                </div>
                                <div style="font-size: 0.9em; color: var(--main-color); font-weight: 600; background: #e0f2f1; display: inline-block; padding: 4px 10px; border-radius: 6px;">
                                    Rp <?= number_format($b['total_bayar']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="date-info">
                                    <div class="date-row">
                                        <i class="fa-solid fa-right-to-bracket icon-fixed" style="color: var(--main-color);"></i> 
                                        <span class="date-label">Masuk:</span> 
                                        <strong><?= $tglMasukIndo ?></strong>
                                    </div>
                                    <div class="date-row">
                                        <i class="fa-solid fa-right-from-bracket icon-fixed" style="color: #ef5350;"></i> 
                                        <span class="date-label">Keluar:</span> 
                                        <strong><?= $tglKeluarIndo ?></strong>
                                    </div>
                                    <div class="date-row" style="color: #777;">
                                        <i class="fa-regular fa-clock icon-fixed"></i> 
                                        <span><?= $durasi ?> Bulan</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $b['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $b['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($noHp != '-'): ?>
                                    <a href="<?= $waLink ?>" target="_blank" class="btn-wa">
                                        <i class="fa-brands fa-whatsapp"></i> Hubungi
                                    </a>
                                <?php else: ?>
                                    <span style="color:#aaa; font-size:0.8em; font-style:italic;">No HP Tidak Ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!$adaData): ?>
            <div class="empty-state">
                <i class="fa-solid fa-user-xmark"></i>
                <h3>Belum ada penyewa</h3>
                <p>Saat pesanan masuk, data penyewa akan muncul di sini.</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>