<?php
session_start();
// Pastikan file db.php sudah di-include dan berisi koneksi ke Firebase Realtime Database ($database)
include 'db.php'; 

// Cek autentikasi dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    // Ganti dengan halaman login Anda
    header("Location: login.php"); 
    exit();
}

// --- LOGIKA UPDATE STATUS & SET NOTIFIKASI ---
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_booking = $_GET['id'];
    $aksi = $_GET['aksi'];
    
    // Ambil data booking untuk mendapatkan user_id penyewa
    $bookingRef = $database->getReference('bookings/' . $id_booking);
    $bookingData = $bookingRef->getValue();

    if ($bookingData) {
        $id_penyewa = $bookingData['user_id']; // Asumsi: field user_id adalah ID penyewa

        // Tentukan status baru
        if ($aksi == 'terima') {
            $status_baru = 'Dikonfirmasi';
            $pesan_notif = "✅ Pesanan berhasil DITERIMA! Penghasilan bertambah.";
            $tipe_notif = "success";
            $notif_to_penyewa = "Pesanan kost Anda telah dikonfirmasi oleh Pemilik Kost.";
        } else {
            $status_baru = 'Ditolak';
            $pesan_notif = "❌ Pesanan telah DITOLAK.";
            $tipe_notif = "danger";
            $notif_to_penyewa = "Pesanan kost Anda ditolak oleh Pemilik Kost. Silakan cari kost lain.";
        }

        // 1. Update status di Database
        $bookingRef->update(['status' => $status_baru]);
        
        // 2. Kirim Notifikasi ke Penyewa (Opsional, jika Anda punya modul notifikasi)
        // Jika Anda menggunakan Realtime DB:
        $database->getReference('notifications/' . $id_penyewa)->push([
            'message' => $notif_to_penyewa,
            'timestamp' => time() * 1000, // Unix timestamp in milliseconds
            'read' => false
        ]);
        
        // 3. Simpan pesan ke Session (Flash Message)
        $_SESSION['notif_pesan'] = $pesan_notif;
        $_SESSION['notif_tipe'] = $tipe_notif;
    } else {
        $_SESSION['notif_pesan'] = "⚠️ ID Pesanan tidak valid.";
        $_SESSION['notif_tipe'] = "danger";
    }

    // Redirect setelah proses
    header("Location: pesanan_masuk.php");
    exit();
}

// --- AMBIL SEMUA DATA BOOKING MILIK PEMILIK YANG SEDANG LOGIN ---
try {
    $allBookings = $database->getReference('bookings')->getValue();
} catch (Exception $e) {
    // Jika koneksi Firebase gagal
    $allBookings = [];
}

$myBookings = [];
if ($allBookings && is_array($allBookings)) {
    // Membalik urutan agar pesanan terbaru di atas
    $allBookings = array_reverse($allBookings, true);
    foreach ($allBookings as $id => $b) {
        // Filter hanya pesanan milik pemilik yang sedang login
        if (isset($b['owner_id']) && $b['owner_id'] == $_SESSION['user_id']) {
            $myBookings[$id] = $b;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesanan Masuk - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            max-width: 1100px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        }
        
        /* HEADER */
        .header-top { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #e0f2f1; 
            padding-bottom: 20px; 
        }
        .header-top h2 { margin: 0; color: #333; font-size: 1.6em; font-weight: 700; }
        
        .btn-back { 
            color: #777; 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 0.95em; 
            transition: 0.3s; 
            display: flex; 
            align-items: center; 
            gap: 5px;
        }
        .btn-back:hover { color: var(--main-color); }

        /* ALERT NOTIFIKASI */
        .alert { 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-weight: 500; 
            animation: fadeIn 0.5s; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background-color: #e0f2f1; color: #00695c; border: 1px solid #b2dfdb; }
        .alert-danger { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* TABLE STYLE */
        table { width: 100%; border-collapse: separate; border-spacing: 0 15px; margin-top: 10px; }
        
        thead th { 
            color: #888; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.8em; 
            padding: 0 15px 10px; 
            text-align: left; 
            letter-spacing: 0.5px; 
        }
        
        tbody tr { 
            background: white; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.03); 
            transition: transform 0.2s; 
            border-radius: 10px;
        }
        tbody tr:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        
        td { padding: 20px; vertical-align: middle; border-top: 1px solid #f9f9f9; border-bottom: 1px solid #f9f9f9; }
        td:first-child { border-left: 1px solid #f9f9f9; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-right: 1px solid #f9f9f9; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        
        /* Badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75em; font-weight: 600; display: inline-block; letter-spacing: 0.5px; text-transform: uppercase; }
        .bg-menunggu_pembayaran { background: #fff8e1; color: #ff8f00; }
        .bg-menunggu_verifikasi { background: #e3f2fd; color: #1565c0; }
        .bg-Dikonfirmasi { background: #e8f5e9; color: #2e7d32; }
        .bg-Ditolak { background: #ffebee; color: #c62828; }
        
        /* Buttons */
        .btn-bukti { 
            background: #e0f7fa; 
            color: #006064; 
            padding: 6px 12px; 
            text-decoration: none; 
            border-radius: 6px; 
            font-size: 0.85em; 
            font-weight: 500;
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
            transition: 0.3s;
        }
        .btn-bukti:hover { background: #b2ebf2; }

        .btn-action-group {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .btn-action { 
            padding: 8px 12px; 
            text-decoration: none; 
            border-radius: 8px; 
            font-size: 0.9em; 
            font-weight: 500;
            border: none; 
            cursor: pointer; 
            transition: 0.3s;
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
        }
        
        .btn-accept { background: #25D366; color: white; }
        .btn-reject { background: #ffebee; color: #c62828; }

        .btn-accept:hover { background: #128C7E; box-shadow: 0 4px 10px rgba(37,211,102,0.2); }
        .btn-reject:hover { background: #ffcdd2; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px; color: #999; }
        .empty-state i { font-size: 4em; margin-bottom: 15px; color: #e0e0e0; }
        
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
        
        <div class="header-top">
            <div>
                <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <h2>Kelola Pesanan Masuk</h2>
            </div>
            <div style="color: #666; font-weight: 500;">
                <i class="fa-solid fa-inbox" style="color: var(--main-color);"></i> Order Baru
            </div>
        </div>

        <?php if (isset($_SESSION['notif_pesan'])): ?>
            <div class="alert alert-<?= $_SESSION['notif_tipe'] ?>">
                <?= $_SESSION['notif_tipe'] == 'success' ? '<i class="fa-solid fa-check-circle"></i>' : '<i class="fa-solid fa-circle-exclamation"></i>' ?>
                <?= $_SESSION['notif_pesan'] ?>
            </div>
            <?php unset($_SESSION['notif_pesan']); unset($_SESSION['notif_tipe']); ?>
        <?php endif; ?>
        
        <?php if (!empty($myBookings)): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Penyewa</th>
                        <th style="width: 25%;">Kost & Harga</th>
                        <th style="width: 10%;">Metode</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 15%;">Bukti Bayar</th>
                        <th style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myBookings as $id => $b): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #333; margin-bottom: 3px;">
                                <?= htmlspecialchars($b['nama_penyewa'] ?? 'N/A') ?>
                            </div>
                            <div style="font-size: 0.85em; color: #777;">
                                <i class="fa-regular fa-calendar" style="margin-right: 5px;"></i> 
                                Masuk: <?= htmlspecialchars($b['tanggal_masuk'] ?? 'N/A') ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; margin-bottom: 3px;">
                                <?= htmlspecialchars($b['nama_kost'] ?? 'N/A') ?>
                            </div>
                            <div style="color: #2e7d32; font-weight: 700;">
                                Rp <?= number_format($b['total_bayar'] ?? 0) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($b['metode_bayar'] ?? 'N/A') ?></td>
                        <td>
                            <?php 
                                $status_key = htmlspecialchars($b['status'] ?? 'N/A');
                                $status_display = ucfirst(str_replace('_', ' ', $status_key));
                            ?>
                            <span class="badge bg-<?= $status_key ?>">
                                <?= $status_display ?>
                            </span>
                        </td>
                        
                        <td>
                            <?php if (isset($b['bukti_bayar']) && !empty($b['bukti_bayar'])): ?>
                                <a href="<?= htmlspecialchars($b['bukti_bayar']) ?>" target="_blank" class="btn-bukti">
                                    <i class="fa-solid fa-image"></i> Lihat Foto
                                </a>
                            <?php elseif (($b['metode_bayar'] ?? '') == 'Tunai'): ?>
                                <span style="color:#888; font-size:0.9em; font-style:italic;">Bayar di tempat</span>
                            <?php else: ?>
                                <span style="color:#ef5350; font-size:0.9em; font-style:italic;">Belum upload</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if(isset($b['status']) && $b['status'] != 'Dikonfirmasi' && $b['status'] != 'Ditolak'): ?>
                                <div class="btn-action-group">
                                    <button 
                                        class="btn-action btn-accept" 
                                        data-id="<?= $id ?>" 
                                        data-aksi="terima"
                                    >
                                        <i class="fa-solid fa-check"></i> Terima
                                    </button>
                                    
                                    <button 
                                        class="btn-action btn-reject" 
                                        data-id="<?= $id ?>" 
                                        data-aksi="tolak"
                                    >
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                </div>
                            <?php else: ?>
                                <span style="color:#aaa; font-size:0.9em; display:flex; align-items:center; gap:5px;">
                                    <i class="fa-solid fa-lock"></i> Selesai
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <h3>Belum ada pesanan masuk</h3>
                <p>Pesanan baru dari penyewa akan muncul di sini.</p>
            </div>
        <?php endif; ?>
        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil warna dari CSS
            const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--main-color') || '#00796B';
            const dangerColor = '#c62828';
            
            document.querySelectorAll('.btn-action').forEach(button => {
                button.addEventListener('click', function() {
                    const id_booking = this.getAttribute('data-id');
                    const aksi = this.getAttribute('data-aksi');
                    let title = '';
                    let text = '';
                    let icon = '';
                    let confirmButtonText = '';
                    let confirmButtonColor = '';

                    if (aksi === 'terima') {
                        title = 'Konfirmasi Penerimaan';
                        text = 'Anda yakin ingin menerima pesanan ini? Pastikan Anda sudah menerima pembayaran penuh.';
                        icon = 'question';
                        confirmButtonText = 'Ya, Terima Pesanan';
                        confirmButtonColor = primaryColor;
                    } else if (aksi === 'tolak') {
                        title = 'Konfirmasi Penolakan';
                        text = 'Pesanan akan dibatalkan. Tindakan ini tidak dapat dibatalkan.';
                        icon = 'warning';
                        confirmButtonText = 'Ya, Tolak Pesanan';
                        confirmButtonColor = dangerColor;
                    }
                    
                    // Tampilkan SweetAlert
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: icon,
                        showCancelButton: true,
                        confirmButtonColor: confirmButtonColor,
                        cancelButtonColor: '#aaa',
                        confirmButtonText: confirmButtonText,
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        // Jika pengguna mengklik 'Ya'/'OK'
                        if (result.isConfirmed) {
                            // Redirect ke PHP untuk memproses aksi
                            window.location.href = `pesanan_masuk.php?id=${id_booking}&aksi=${aksi}`;
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>