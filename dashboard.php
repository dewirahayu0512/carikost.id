<?php
session_start();
include 'db.php';

// 1. Cek Login & Redirect
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$nama = $_SESSION['nama'] ?? 'Pengguna'; // Ubah default ke 'Pengguna'
$role = $_SESSION['role'] ?? 'penyewa';

// --- CONFIG SUPER ADMIN ---
$email_super_admin = 'admin@gmail.com'; 

// Mendapatkan email user saat ini
try {
    // Diasumsikan $database adalah instance Firebase Realtime Database
    $currentUser = $database->getReference('users/' . $uid)->getValue();
    $userEmail = $currentUser['email'] ?? '';
} catch (Exception $e) {
    // Log error or handle gracefully
    $userEmail = '';
}

$is_admin = ($userEmail === $email_super_admin);

// --- LOGIKA HITUNG STATISTIK (Server Side) ---
$stats = ['total_kost' => 0, 'pesanan_pending' => 0, 'pendapatan' => 0, 'total_pemesanan' => 0];

// Fungsi format rupiah
function formatRupiah($angka){
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

if ($role == 'pemilik' && !$is_admin) {
    try {
        // Hitung Kost
        $kosts = $database->getReference('kosts')->getValue();
        if ($kosts && is_array($kosts)) {
            foreach ($kosts as $k) {
                if (isset($k['owner_id']) && $k['owner_id'] == $uid) $stats['total_kost']++;
            }
        }
        // Hitung Transaksi
        $bookings = $database->getReference('bookings')->getValue();
        if ($bookings && is_array($bookings)) {
            foreach ($bookings as $b) {
                if (isset($b['owner_id']) && $b['owner_id'] == $uid) {
                    $stats['total_pemesanan']++;
                    
                    // Status yang perlu konfirmasi
                    if (in_array($b['status'], ['menunggu_pembayaran', 'pending'])) {
                        $stats['pesanan_pending']++;
                    }
                    // Status pendapatan
                    if ($b['status'] == 'Dikonfirmasi' && isset($b['total_bayar'])) {
                        $stats['pendapatan'] += (int)$b['total_bayar'];
                    }
                }
            }
        }
    } catch (Exception $e) { 
        // Handle database or connection error
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- THEME CONFIGURATION (TEAL MODERN) --- */
        :root {
            --color-primary: #00796B; /* Teal Dark */
            --color-primary-light: #e0f2f1;
            --color-primary-dark: #00695c;
            --color-secondary: #0288d1; /* Blue for accent/actions */
            --color-text: #2f3542;
            --color-text-light: #666;
            --color-warning: #ff9800; /* Orange for alert/pending */
            --color-danger: #d32f2f; /* Red for logout/error */
            --bg-color-main: #f4f6f8; /* Abu-abu sangat muda bersih */
            --bg-color-card: white;
            
            --primary-gradient: linear-gradient(135deg, var(--color-primary-dark) 0%, #4db6ac 100%);
            --dark-gradient: linear-gradient(135deg, #263238 0%, #37474f 100%);
        }

        /* BASE STYLE */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-color-main); 
            color: var(--color-text); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        a { text-decoration: none; transition: 0.3s; }

        /* NAVBAR */
        .navbar { 
            background: var(--bg-color-card); 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.04); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        
        .brand { 
            font-size: 1.4em; 
            font-weight: 700; 
            color: var(--color-primary);
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .user-info { display: flex; align-items: center; gap: 15px; }
        
        .badge-role { 
            padding: 6px 15px; 
            border-radius: 6px; 
            font-size: 0.75em; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            white-space: nowrap;
        }
        
        .role-pemilik { background: #e0f2f1; color: var(--color-primary-dark); border: 1px solid #b2dfdb; }
        .role-penyewa { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
        .role-admin { background: #37474f; color: #fff; }

        .btn-logout { 
            color: var(--color-danger); 
            font-weight: 600; 
            font-size: 0.9em; 
            padding: 8px 15px; 
            border: 1px solid var(--color-danger); 
            border-radius: 8px; 
            background: #ffebee;
            transition: all 0.3s; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-logout:hover { 
            background: var(--color-danger); 
            color: var(--bg-color-card); 
            border-color: var(--color-danger);
        }

        /* HERO SECTION */
        .hero { 
            background: var(--primary-gradient);
            color: white; 
            padding: 50px 20px 90px; 
            text-align: center; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 20px rgba(0, 105, 92, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        /* MENGHILANGKAN EFEK BINTANG-BINTANG */
        .hero::before {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background-image: none; /* Dihapus */
        }

        .hero.admin-hero { background: var(--dark-gradient); }
        
        .hero h1 { font-size: 2.2em; margin: 0 0 10px; font-weight: 700; position: relative; }
        .hero p { opacity: 0.9; font-size: 1em; font-weight: 400; position: relative; }

        /* MAIN CONTENT */
        main { flex-grow: 1; }

        /* CONTAINER */
        .container { 
            max-width: 1200px;
            margin: -60px auto 50px; 
            padding: 0 20px; 
            position: relative; 
            z-index: 10; 
        }

        /* FADE IN ANIMATION */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.5s ease-out forwards;
        }
        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        /* STATS GRID */
        .stats-grid { 
            display: grid; 
            /* Mengurangi minmax agar lebih kecil di desktop */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; /* Mengurangi gap */
            margin-bottom: 30px; 
        }
        
        .stat-card { 
            background: var(--bg-color-card); 
            padding: 20px; /* Padding dikurangi dari 25px */
            border-radius: 10px; /* Radius dikurangi */
            box-shadow: 0 3px 10px rgba(0,0,0,0.05); /* Shadow dikurangi */
            display: flex; 
            align-items: center; 
            gap: 15px; /* Gap dikurangi */
            transition: all 0.3s ease;
            border-left: 4px solid var(--color-primary); /* Aksen garis kiri dikurangi */
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        
        .icon-box { 
            width: 45px; height: 45px; /* Icon lebih kecil dari 55px */
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.4em; /* Font icon lebih kecil dari 1.6em */
            flex-shrink: 0; 
        }
        
        /* Warna Icon Professional */
        .icon-green { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;} 
        .icon-yellow { background: #fff3e0; color: var(--color-warning); border: 1px solid #ffe0b2;} 
        .icon-purple { background: var(--color-primary-light); color: var(--color-primary); border: 1px solid #b2dfdb; } 
        .icon-blue { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; } 

        .stat-info h3 { margin: 0; font-size: 1.4em; /* Ukuran font H3 dikurangi dari 1.8em */ font-weight: 700; color: var(--color-text); }
        .stat-info p { margin: 3px 0 0; color: var(--color-text-light); font-size: 0.8em; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

        /* MENU SECTION */
        .menu-section-title { 
            font-size: 1.1em; /* Ukuran font H3 dikurangi */
            font-weight: 700; 
            color: #455a64; 
            margin-bottom: 15px; /* Margin dikurangi */
            display: flex; 
            align-items: center; 
            gap: 10px; 
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-left: 5px;
        }
        .menu-section-title i { font-size: 1.1em; color: var(--color-primary); }

        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); /* Minmax dikurangi */ gap: 15px; }
        
        .menu-item { 
            background: var(--bg-color-card); 
            border-radius: 10px; /* Radius dikurangi */
            padding: 20px 15px; /* Padding dikurangi */
            text-align: center; 
            box-shadow: 0 3px 8px rgba(0,0,0,0.03); 
            transition: all 0.3s; 
            border: 1px solid #f0f0f0; 
            color: #555; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            height: 150px; /* Tinggi kotak dikurangi dari 180px */
            text-decoration: none;
            position: relative;
        }
        
        .menu-item:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 20px rgba(0, 105, 92, 0.08); 
            border-color: var(--color-primary); 
        }
        
        .menu-item i { 
            font-size: 2.2em; /* Icon lebih kecil dari 2.8em */
            margin-bottom: 10px; /* Margin dikurangi */
            color: var(--color-primary);
            transition: 0.3s; 
        }
        
        .menu-item:hover i { transform: scale(1.05); color: var(--color-primary-dark); }
        
        .menu-item span { font-weight: 600; font-size: 1em; /* Font Span dikurangi dari 1.1em */ color: var(--color-text); margin-bottom: 3px; display: block; }
        .menu-item small { font-size: 0.8em; color: var(--color-text-light); font-weight: 400; }

        /* Khusus tombol laporan PDF */
        .menu-item.pdf-menu i { color: var(--color-danger); }
        .menu-item.pdf-menu:hover { border-color: var(--color-danger); }

        /* Badge Notifikasi */
        .notification-badge {
            position: absolute;
            top: 5px; /* Disesuaikan */
            right: 5px; /* Disesuaikan */
            background-color: var(--color-warning);
            color: white;
            padding: 3px 7px; /* Disesuaikan */
            border-radius: 50px;
            font-size: 0.65em; /* Disesuaikan */
            font-weight: 700;
            line-height: 1;
        }

        /* Animation Pulse untuk Notif */
        @keyframes pulse-warning { 
            0% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4); } 
            70% { box-shadow: 0 0 0 8px rgba(255, 152, 0, 0); } /* Disesuaikan */
            100% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0); } 
        }
        .live-update-pending { animation: pulse-warning 1s ease-out; }
        .live-update-total { animation: pulse-teal 1s ease-out; }

        /* FOOTER (Opsional) */
        .footer {
            padding: 15px; /* Padding dikurangi */
            text-align: center;
            font-size: 0.8em; /* Font dikurangi */
            color: #999;
            margin-top: 30px; /* Margin dikurangi */
            border-top: 1px solid #eee;
        }

        /* MEDIA QUERIES */
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; }
            .hero { padding-bottom: 70px; }
            .hero h1 { font-size: 1.8em; }
            .container { margin-top: -50px; }
            .stat-card { padding: 15px; } /* Disesuaikan untuk HP */
            .stat-info h3 { font-size: 1.2em; } /* Disesuaikan untuk HP */
            .stat-info p { font-size: 0.75em; } /* Disesuaikan untuk HP */
            .badge-role { display: none; }
            .btn-logout span { display: none; }
            .btn-logout { padding: 8px 10px; }
            .menu-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); } /* Disesuaikan untuk HP */
            .menu-item { height: 140px; /* Disesuaikan untuk HP */ }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand"><i class="fa-solid fa-house-chimney"></i> Carikost.id</div>
        <div class="user-info">
            <?php if($is_admin): ?>
                <span class="badge-role role-admin">SUPER ADMIN</span>
            <?php else: ?>
                <span class="badge-role role-<?= $role ?>"><?= ucfirst($role) ?></span>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Keluar</span>
            </a>
        </div>
    </nav>

    <div class="hero <?= $is_admin ? 'admin-hero' : '' ?>">
        <h1>Selamat Datang, <?= htmlspecialchars($nama) ?>!</h1>
        <p>
            <?php 
            if ($is_admin) echo "Panel Kontrol Utama Administrator Sistem";
            elseif ($role == 'pemilik') echo "Kelola bisnis kost Anda dengan mudah dan profesional";
            else echo "Temukan dan kelola tempat tinggal impianmu di sini";
            ?>
        </p>
    </div>

    <main>
        <div class="container fade-in" style="animation-delay: 0.1s;">

            <?php if ($is_admin): ?>
                
                <div class="menu-section-title"><i class="fa-solid fa-screwdriver-wrench"></i> ADMINISTRATOR</div>
                <div class="menu-grid">
                    <a href="admin_bantuan.php" class="menu-item">
                        <i class="fa-solid fa-headset"></i>
                        <span>Pesan Bantuan</span>
                        <small>Cek keluhan pengguna</small>
                    </a>
                    <a href="admin_kelola_user.php" class="menu-item">
                        <i class="fa-solid fa-users-gear"></i>
                        <span>Kelola Pengguna</span>
                        <small>Lihat data penyewa/pemilik</small>
                    </a>
                    </div>

            <?php elseif ($role == 'pemilik'): ?>
                
                <div class="stats-grid fade-in" style="animation-delay: 0.3s;">
                    <div class="stat-card">
                        <div class="icon-box icon-green"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div class="stat-info">
                            <h3><?= formatRupiah($stats['pendapatan']) ?></h3>
                            <p>Total Pendapatan</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon-box icon-purple"><i class="fa-solid fa-receipt"></i></div>
                        <div class="stat-info">
                            <h3 id="live-total"><?= $stats['total_pemesanan'] ?></h3>
                            <p>Semua Pesanan</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon-box icon-yellow"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="stat-info">
                            <h3 id="live-pending" class="<?= $stats['pesanan_pending'] > 0 ? 'live-update-pending' : '' ?>"><?= $stats['pesanan_pending'] ?></h3>
                            <p>Perlu Konfirmasi</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon-box icon-blue"><i class="fa-solid fa-building-user"></i></div>
                        <div class="stat-info">
                            <h3><?= $stats['total_kost'] ?></h3>
                            <p>Unit Kost Aktif</p>
                        </div>
                    </div>
                </div>

                <div class="menu-section-title"><i class="fa-solid fa-compass"></i> NAVIGASI CEPAT</div>
                <div class="menu-grid fade-in" style="animation-delay: 0.5s;">
                    <a href="tambah_kost.php" class="menu-item">
                        <i class="fa-solid fa-house-medical"></i>
                        <span>Tambah Kost</span>
                        <small>Input data properti baru</small>
                    </a>
                    <a href="kelola_kost.php" class="menu-item">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Kelola Kost</span>
                        <small>Edit & hapus data properti</small>
                    </a>
                    <a href="pesanan_masuk.php" class="menu-item">
                        <i class="fa-solid fa-inbox"></i>
                        <span>Pesanan Masuk</span>
                        <small>Konfirmasi order penyewa</small>
                        <?php if ($stats['pesanan_pending'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pesanan_pending'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="chat_list.php" class="menu-item">
                        <i class="fa-solid fa-comments"></i>
                        <span>Live Chat</span>
                        <small>Respon pesan dari penyewa</small>
                    </a>
                    <a href="kelola_penyewa.php" class="menu-item">
                        <i class="fa-solid fa-users"></i>
                        <span>Data Penyewa</span>
                        <small>Kelola kontak penghuni</small>
                    </a>
                    
                    <a href="laporan.php" class="menu-item pdf-menu">
                        <i class="fa-solid fa-file-export"></i>
                        <span>Unduh Laporan</span>
                        <small>Laporan PDF Bulanan</small>
                    </a>

                    <a href="profil.php" class="menu-item">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Pengaturan Akun</span>
                        <small>Profil, Bank, & keamanan</small>
                    </a>

                    <a href="bantuan_pemilik.php" class="menu-item">
                        <i class="fa-solid fa-headset"></i>
                        <span>Pusat Bantuan</span>
                        <small>Dukungan & FAQ</small>
                    </a>

                </div>

            <?php else: /* Role Penyewa */ ?>
                
                <div class="stats-grid fade-in" style="animation-delay: 0.3s;">
                    <div class="stat-card">
                        <div class="icon-box icon-blue"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($nama) ?></h3>
                            <p>Akun Aktif</p>
                        </div>
                    </div>
                    </div>

                <div class="menu-section-title"><i class="fa-solid fa-magnifying-glass"></i> JELAJAHI & KELOLA</div>
                <div class="menu-grid fade-in" style="animation-delay: 0.5s;">
                    <a href="cari_kost.php" class="menu-item">
                        <i class="fa-solid fa-magnifying-glass-location"></i>
                        <span>Cari Kost</span>
                        <small>Temukan kost impian</small>
                    </a>
                    <a href="riwayat_booking.php" class="menu-item">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Riwayat Booking</span>
                        <small>Cek status & pembayaran</small>
                    </a>
                    <a href="chat_list.php" class="menu-item">
                        <i class="fa-solid fa-message"></i>
                        <span>Live Chat</span>
                        <small>Tanya pemilik kost</small>
                    </a>
                    <a href="profil.php" class="menu-item">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Profil Saya</span>
                        <small>Ubah data diri</small>
                    </a>
                    <a href="bantuan.php" class="menu-item">
                        <i class="fa-solid fa-headset"></i>
                        <span>Pusat Bantuan</span>
                        <small>Hubungi Admin</small>
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <footer class="footer">
        &copy; <?= date('Y') ?> Carikost.id. All rights reserved.
    </footer>

    <?php if ($role == 'pemilik' && !$is_admin): ?>
    <script type="module">
        // Pastikan konfigurasi Firebase ada dan benar
        const firebaseConfig = {
            apiKey: "AIzaSyC_DglM3bl4NiamVIbs2lj9IgrUpCF1oIg",
            authDomain: "carikost-id-f46dc.firebaseapp.com",
            projectId: "carikost-id-f46dc",
            storageBucket: "carikost-id-f46dc.firebasestorage.app",
            messagingSenderId: "396631809884",
            appId: "1:396631809884:web:093fc32ee8e348ccceab99",
            measurementId: "G-LVCRCVCXLB"
        };
        
        // Memuat Firebase SDK dari CDN
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
        import { getDatabase, ref, onValue } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-database.js";

        const app = initializeApp(firebaseConfig);
        const db = getDatabase(app);
        const ownerId = "<?= $uid ?>";

        const bookingsRef = ref(db, 'bookings');
        onValue(bookingsRef, (snapshot) => {
            const data = snapshot.val();
            let pendingCount = 0;
            let totalCount = 0;

            if (data) {
                Object.values(data).forEach(booking => {
                    if (booking.owner_id === ownerId) {
                        totalCount++;
                        if (booking.status === 'menunggu_pembayaran' || booking.status === 'pending') {
                            pendingCount++;
                        }
                    }
                });
            }

            const elPending = document.getElementById('live-pending');
            const elTotal = document.getElementById('live-total');
            // Menghilangkan pencarian badge yang kompleks, badge kini dimasukkan langsung ke tag <a>
            const targetMenuItem = document.querySelector('.menu-grid a[href="pesanan_masuk.php"]');
            let elBadge = targetMenuItem ? targetMenuItem.querySelector('.notification-badge') : null;

            // Update Total Pesanan (Live)
            if(elTotal && parseInt(elTotal.innerText) !== totalCount) { 
                elTotal.innerText = totalCount; 
                elTotal.classList.add('live-update-total');
            }

            // Update Perlu Konfirmasi (Live)
            if(elPending && parseInt(elPending.innerText) !== pendingCount) { 
                elPending.innerText = pendingCount;
                elPending.classList.add('live-update-pending'); 
                
                // Update Badge Notifikasi di Menu
                if (targetMenuItem) {
                    if (pendingCount > 0) {
                        // Pastikan ada badge di elemen menu item
                        if (!elBadge) {
                            targetMenuItem.insertAdjacentHTML('beforeend', <span class="notification-badge">${pendingCount}</span>);
                        } else {
                            elBadge.innerText = pendingCount;
                        }
                    } else if (elBadge) {
                        elBadge.remove();
                    }
                }
            }

            // Hapus animasi setelah 1 detik
            setTimeout(() => {
                if(elPending) elPending.classList.remove('live-update-pending');
                if(elTotal) elTotal.classList.remove('live-update-total');
            }, 1000);
        });
    </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach(el => el.style.opacity = 1);
        });
    </script>

</body>
</html>