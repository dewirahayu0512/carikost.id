<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$message = "";

// --- 1. PROSES UPLOAD BUKTI BAYAR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['bukti'])) {
    $booking_id = $_POST['booking_id'];
    
    // Upload File
    $target_dir = "uploads/bukti/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_ext = pathinfo($_FILES["bukti"]["name"], PATHINFO_EXTENSION);
    $filename = "bukti_" . $booking_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($_FILES["bukti"]["tmp_name"], $target_file)) {
        $bukti_url = "http://localhost:8000/" . $target_file;
        
        // Update Database: Simpan URL Bukti & Ubah Status jadi 'menunggu_verifikasi'
        $database->getReference('bookings/' . $booking_id)->update([
            'bukti_bayar' => $bukti_url,
            'status' => 'menunggu_verifikasi' // Status berubah agar pemilik cek
        ]);
        
        $message = "✅ Bukti pembayaran berhasil diupload! Mohon tunggu verifikasi pemilik.";
    } else {
        $message = "❌ Gagal mengupload gambar.";
    }
}

// --- 2. AMBIL DATA BOOKING SAYA ---
try {
    $allBookings = $database->getReference('bookings')->getValue();
} catch (Exception $e) {
    $allBookings = [];
}

// Filter hanya booking milik saya
$myBookings = [];
if ($allBookings && is_array($allBookings)) {
    foreach ($allBookings as $id => $b) {
        if (isset($b['penyewa_id']) && $b['penyewa_id'] == $uid) {
            $myBookings[$id] = $b;
        }
    }
    // Urutkan dari yang terbaru
    $myBookings = array_reverse($myBookings, true);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA (TEAL / TOSCA) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; 
            --hover-color: #004D40; 
            --text-color: #2f3542;
            --bg-light: #f4f6f8;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-color); margin: 0; padding: 20px; }
        
        .container { max-width: 800px; margin: 0 auto; }

        /* HEADER */
        .header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e0f2f1;
        }
        .header h2 { margin: 0; color: #333; font-size: 1.5em; font-weight: 700; }
        .btn-back { color: #555; text-decoration: none; font-weight: 500; transition: 0.3s; }
        .btn-back:hover { color: var(--main-color); }

        /* NOTIFIKASI */
        .alert { 
            background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; 
            margin-bottom: 20px; border: 1px solid #c3e6cb; font-weight: 500;
        }

        /* CARD BOOKING */
        .booking-card { 
            background: white; padding: 25px; border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.03); margin-bottom: 20px; 
            border: 1px solid #eee; position: relative; overflow: hidden;
        }
        .booking-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px;
            background: var(--main-color);
        }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .kost-name { font-size: 1.3em; font-weight: 700; color: #333; margin: 0; }
        .booking-date { font-size: 0.85em; color: #888; display: block; margin-top: 5px; }
        
        /* STATUS BADGE */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75em; font-weight: 700; text-transform: uppercase; }
        .bg-menunggu_pembayaran { background: #fff3cd; color: #856404; }
        .bg-menunggu_verifikasi { background: #d1ecf1; color: #0c5460; }
        .bg-Dikonfirmasi { background: #d4edda; color: #155724; }
        .bg-Ditolak { background: #f8d7da; color: #721c24; }

        /* INFO GRID */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-item label { font-size: 0.8em; color: #888; display: block; }
        .info-item span { font-weight: 600; color: #444; }
        .total-price { color: var(--main-color) !important; font-size: 1.1em !important; }

        /* PAYMENT BOX */
        .payment-box { 
            background: #fcfcfc; border: 1px dashed #ccc; padding: 15px; border-radius: 10px; margin-top: 10px;
        }
        .payment-title { font-weight: 700; color: #555; margin-bottom: 10px; font-size: 0.95em; }
        .bank-info { 
            background: #e0f2f1; color: #00695c; padding: 10px; border-radius: 8px; 
            font-family: monospace; font-size: 1.1em; font-weight: bold; text-align: center;
            margin-bottom: 15px;
        }
        .qris-img { width: 150px; display: block; margin: 10px auto; border-radius: 8px; border: 1px solid #ddd; }

        /* FORM UPLOAD */
        .upload-area { display: flex; gap: 10px; align-items: center; }
        input[type="file"] { font-size: 0.9em; width: 100%; }
        
        .btn-upload { 
            background: var(--main-color); color: white; border: none; padding: 10px 20px; 
            border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s;
        }
        .btn-upload:hover { background: var(--hover-color); }

        .btn-chat { 
            text-decoration: none; color: var(--main-color); font-weight: 600; font-size: 0.9em;
            display: inline-flex; align-items: center; gap: 5px; margin-top: 10px;
        }

        @media (max-width: 600px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div>
                <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <h2>Riwayat Booking</h2>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <?php if (!empty($myBookings)): ?>
            <?php foreach ($myBookings as $id => $b): 
                // Ambil data pemilik untuk info pembayaran
                try {
                    $ownerData = $database->getReference('users/' . $b['owner_id'])->getValue();
                    $paymentInfo = $ownerData['payment_info'] ?? [];
                } catch(Exception $e) { $paymentInfo = []; }
            ?>
                <div class="booking-card">
                    <div class="card-header">
                        <div>
                            <h3 class="kost-name"><?= htmlspecialchars($b['nama_kost']) ?></h3>
                            <span class="booking-date"><i class="fa-regular fa-clock"></i> Dipesan: <?= date('d M Y', strtotime($b['tanggal_booking'])) ?></span>
                        </div>
                        <span class="badge bg-<?= $b['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $b['status'])) ?>
                        </span>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Tanggal Masuk</label>
                            <span><?= date('d M Y', strtotime($b['tanggal_masuk'])) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Durasi Sewa</label>
                            <span><?= $b['durasi_bulan'] ?> Bulan</span>
                        </div>
                        <div class="info-item">
                            <label>Metode Pembayaran</label>
                            <span><?= $b['metode_bayar'] ?></span>
                        </div>
                        <div class="info-item">
                            <label>Total Tagihan</label>
                            <span class="total-price">Rp <?= number_format($b['total_bayar']) ?></span>
                        </div>
                    </div>

                    <?php if ($b['status'] == 'menunggu_pembayaran'): ?>
                        <div class="payment-box">
                            <div class="payment-title">📢 Segera Lakukan Pembayaran</div>
                            
                            <?php if ($b['metode_bayar'] == 'Transfer Bank'): ?>
                                <p style="font-size:0.9em; margin-top:0;">Silakan transfer ke rekening pemilik:</p>
                                <div class="bank-info">
                                    <?= htmlspecialchars($paymentInfo['nama_bank'] ?? 'Bank') ?> <br>
                                    <?= htmlspecialchars($paymentInfo['no_rekening'] ?? '-') ?> <br>
                                    <small style="font-weight:normal;">a.n <?= htmlspecialchars($paymentInfo['atas_nama'] ?? 'Pemilik') ?></small>
                                </div>
                            
                            <?php elseif ($b['metode_bayar'] == 'QRIS'): ?>
                                <p style="font-size:0.9em; margin-top:0; text-align:center;">Scan QRIS di bawah ini:</p>
                                <?php if(!empty($paymentInfo['qris_url'])): ?>
                                    <img src="<?= $paymentInfo['qris_url'] ?>" class="qris-img" alt="QRIS Pemilik">
                                <?php else: ?>
                                    <div class="bank-info">QRIS Belum Diupload Pemilik</div>
                                <?php endif; ?>
                            
                            <?php elseif ($b['metode_bayar'] == 'Tunai'): ?>
                                <div class="bank-info" style="background:#fff3e0; color:#e65100;">
                                    Silakan bayar tunai di lokasi kost.
                                </div>
                            <?php endif; ?>

                            <?php if ($b['metode_bayar'] != 'Tunai'): ?>
                                <div style="margin-top:15px; border-top:1px solid #ddd; padding-top:10px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9em;">Upload Bukti Transfer:</label>
                                    <form method="POST" enctype="multipart/form-data" class="upload-area">
                                        <input type="hidden" name="booking_id" value="<?= $id ?>">
                                        <input type="file" name="bukti" accept="image/*" required>
                                        <button type="submit" class="btn-upload"><i class="fa-solid fa-upload"></i> Kirim</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div style="text-align:center; margin-top:10px;">
                                    <small>Tunjukkan bukti booking ini kepada pemilik saat pembayaran.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($b['status'] == 'menunggu_verifikasi'): ?>
                        <div style="background:#e3f2fd; color:#0d47a1; padding:10px; border-radius:8px; margin-top:10px; font-size:0.9em;">
                            <i class="fa-solid fa-clock"></i> Bukti pembayaran telah dikirim. Menunggu verifikasi pemilik.
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:15px;">
                        <a href="chat.php?lawan_id=<?= $b['owner_id'] ?>" class="btn-chat">
                            <i class="fa-regular fa-comment-dots"></i> Hubungi Pemilik
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        
        <?php else: ?>
            <div style="text-align:center; padding:60px; color:#999;">
                <i class="fa-solid fa-receipt" style="font-size:4em; margin-bottom:15px; color:#e0e0e0;"></i>
                <h3>Belum ada riwayat pesanan</h3>
                <p>Cari kost impianmu dan lakukan pemesanan sekarang!</p>
                <br>
                <a href="cari_kost.php" style="background:var(--main-color); color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600;">Cari Kost</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>