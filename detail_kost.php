<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$id_kost = $_GET['id'] ?? null;
if (!$id_kost) { die("ID Kost tidak ditemukan."); }

// Ambil data kost
$kostRef = $database->getReference('kosts/' . $id_kost);
$kost = $kostRef->getValue();
if (!$kost) { die("Data kost tidak ditemukan."); }

// Ambil status (Default: Tersedia)
$status_kost = $kost['status_ketersediaan'] ?? 'Tersedia';

// Ambil data pemilik
$ownerData = $database->getReference('users/' . $kost['owner_id'])->getValue();
$namaOwner = $ownerData['nama_lengkap'] ?? 'Pemilik';

// PROSES BOOKING
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($status_kost == 'Penuh') {
        die("Maaf, kost ini sudah penuh.");
    }

    $durasi = (int)$_POST['durasi'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $total_bayar = $durasi * $kost['harga'];

    $bookingData = [
        'kost_id' => $id_kost,
        'nama_kost' => $kost['nama_kost'],
        'penyewa_id' => $_SESSION['user_id'],
        'nama_penyewa' => $_SESSION['nama'],
        'owner_id' => $kost['owner_id'],
        'tanggal_booking' => date('Y-m-d H:i:s'),
        'tanggal_masuk' => $tanggal_masuk,
        'durasi_bulan' => $durasi,
        'total_bayar' => $total_bayar,
        'status' => 'menunggu_pembayaran',
        'metode_bayar' => $_POST['metode_bayar']
    ];

    $newBookingRef = $database->getReference('bookings')->push($bookingData);
    header("Location: booking_sukses.php?id=" . $newBookingRef->getKey());
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kost['nama_kost']) ?> - Detail Kost</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --main-color: #00796B; --bg-light: #f4f6f8; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); margin: 0; }
        
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .brand { font-size: 1.4em; font-weight: 700; color: var(--main-color); text-decoration: none; }
        .nav-link { color: #555; text-decoration: none; font-weight: 500; }

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 2fr 1.1fr; gap: 30px; align-items: start; }
        
        .main-img { width: 100%; height: 450px; object-fit: cover; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid #eee; }
        
        /* STATUS BADGE */
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.8em; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; }
        .st-Tersedia { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .st-Penuh { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .booking-box { position: sticky; top: 90px; border-top: 6px solid var(--main-color); }
        .price { font-size: 2.2em; font-weight: 700; color: var(--main-color); }
        
        input, select { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; outline: none; }
        
        .btn-book { width: 100%; background: var(--main-color); color: white; border: none; padding: 14px; font-weight: 600; font-size: 1.1em; border-radius: 10px; cursor: pointer; transition: 0.3s; }
        .btn-book:hover { background: #004D40; transform: translateY(-2px); }
        
        .btn-disabled { background: #ccc !important; cursor: not-allowed !important; transform: none !important; }

        .btn-chat { display: flex; justify-content: center; align-items: center; gap: 8px; width: 100%; margin-top: 15px; color: var(--main-color); border: 2px solid var(--main-color); padding: 12px; border-radius: 10px; font-weight: 600; text-decoration: none; box-sizing: border-box; transition: 0.3s; }
        .btn-chat:hover { background: #e0f2f1; }

        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 10px; margin-top: 15px; }
        .gallery-item { width: 100%; height: 80px; object-fit: cover; border-radius: 10px; cursor: pointer; opacity: 0.8; }
        .gallery-item:hover { opacity: 1; }

        /* ULASAN STYLE */
        .review-section-title { font-size: 1.2em; font-weight: 700; color: #333; margin-bottom: 15px; border-bottom: 2px solid #e0f2f1; padding-bottom: 10px; }
        .review-box { border-bottom: 1px solid #f0f0f0; padding: 15px 0; }
        .reviewer-name { font-weight: 700; color: #333; font-size: 0.95em; }
        .rating-star { color: #ffc107; font-size: 0.9em; margin-left: 5px; }
        .review-text { color: #666; font-size: 0.95em; margin-top: 5px; line-height: 1.5; font-style: italic; }

        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
    </style>
    
    <script>
        function gantiGambar(src) { document.getElementById('mainImage').src = src; }
        function hitungTotal() {
            var harga = <?= $kost['harga'] ?>;
            var durasi = document.getElementById('durasi').value;
            var total = harga * durasi;
            document.getElementById('total_tampil').innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total);
        }
    </script>
</head>
<body>

    <nav class="navbar">
        <a href="cari_kost.php" class="brand"><i class="fa-solid fa-house-chimney"></i> Carikost.id</a>
        <a href="cari_kost.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </nav>

    <div class="container">
        
        <div>
            <img id="mainImage" src="<?= htmlspecialchars($kost['foto_url']) ?>" class="main-img" onerror="this.src='https://via.placeholder.com/800x450?text=No+Image'">
            
            <?php if (isset($kost['gallery']) && is_array($kost['gallery'])): ?>
                <div class="gallery-grid">
                    <img src="<?= htmlspecialchars($kost['foto_url']) ?>" class="gallery-item" onclick="gantiGambar(this.src)">
                    <?php foreach($kost['gallery'] as $foto): ?>
                        <img src="<?= htmlspecialchars($foto) ?>" class="gallery-item" onclick="gantiGambar(this.src)">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-top:25px;">
                <span class="status-badge st-<?= $status_kost ?>">
                    <?= ($status_kost == 'Tersedia') ? '<i class="fa-solid fa-check"></i> Kamar Tersedia' : '<i class="fa-solid fa-xmark"></i> Kamar Penuh' ?>
                </span>

                <h1><?= htmlspecialchars($kost['nama_kost']) ?></h1>
                <p style="color:#666;"><i class="fa-solid fa-location-dot" style="color: #d32f2f;"></i> <?= htmlspecialchars($kost['alamat']) ?></p>
                
                <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                
                <h3>Fasilitas</h3>
                <p><?= isset($kost['fasilitas']) ? implode(" • ", $kost['fasilitas']) : '-' ?></p>
                
                <h3>Deskripsi</h3>
                <p style="line-height:1.7; color:#555;"><?= nl2br(htmlspecialchars($kost['deskripsi'])) ?></p>
            </div>

            <div class="card">
                <div class="review-section-title"><i class="fa-solid fa-star" style="color:#ffc107;"></i> Ulasan Penghuni</div>
                <?php
                // Ambil ulasan dari database
                try {
                    $reviews = $database->getReference('reviews')->getValue();
                } catch(Exception $e) { $reviews = []; }
                
                $adaUlasan = false;
                if($reviews && is_array($reviews)){
                    // Balik urutan agar ulasan terbaru di atas
                    foreach(array_reverse($reviews) as $r){
                        // Filter ulasan hanya untuk kost ini
                        if(isset($r['kost_id']) && $r['kost_id'] == $id_kost){
                            $adaUlasan = true;
                            // Tampilkan Ulasan
                            echo "<div class='review-box'>
                                    <div class='reviewer-name'>
                                        <i class='fa-solid fa-user-circle' style='color:#ccc;'></i> " . htmlspecialchars($r['nama_user']) . "
                                        <span class='rating-star'>" . str_repeat('★', $r['rating']) . "</span>
                                    </div>
                                    <div class='review-text'>\"" . htmlspecialchars($r['komentar']) . "\"</div>
                                  </div>";
                        }
                    }
                }
                
                if(!$adaUlasan) {
                    echo "<div style='text-align:center; padding:30px; color:#999;'>
                            <i class='fa-regular fa-comment-dots' style='font-size:2em; margin-bottom:10px;'></i><br>
                            Belum ada ulasan untuk kost ini.
                          </div>";
                }
                ?>
            </div>
        </div>

        <div>
            <div class="card booking-box">
                <div style="font-size:0.9em; color:#777;">Harga Sewa</div>
                <div class="price">
                    Rp <?= number_format($kost['harga']) ?><small>/bulan</small>
                </div>
                <hr style="margin: 15px 0; border:0; border-top:1px solid #eee;">
                
                <?php if ($status_kost == 'Tersedia'): ?>
                    <form method="POST">
                        <label>Mulai Kost Tanggal</label>
                        <input type="date" name="tanggal_masuk" required>
                        
                        <label>Durasi Sewa (Bulan)</label>
                        <input type="number" id="durasi" name="durasi" value="1" min="1" oninput="hitungTotal()" required>
                        
                        <label>Metode Pembayaran</label>
                        <select name="metode_bayar">
                            <option value="QRIS">QRIS (OVO/GoPay/Dana)</option>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Tunai">Tunai (Bayar di Tempat)</option>
                        </select>

                        <div style="background:#f9f9f9; padding:15px; border-radius:10px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:600; color:#555;">Total Bayar:</span>
                            <span id="total_tampil" style="font-weight:700; color:var(--main-color); font-size:1.2em;">Rp <?= number_format($kost['harga']) ?></span>
                        </div>
                        
                        <button type="submit" class="btn-book">Ajukan Sewa <i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                <?php else: ?>
                    <div style="text-align:center; padding:20px; background:#fff5f5; border-radius:10px; border:1px solid #feb2b2; color:#c53030;">
                        <i class="fa-solid fa-ban" style="font-size:2em; margin-bottom:10px;"></i>
                        <p style="margin:0; font-weight:bold;">Maaf, kost ini sedang penuh.</p>
                    </div>
                    <button class="btn-book btn-disabled" disabled>Tidak Bisa Dipesan</button>
                <?php endif; ?>
                
                <a href="chat.php?lawan_id=<?= $kost['owner_id'] ?>" class="btn-chat">
                    <i class="fa-solid fa-comment-dots"></i> Chat Pemilik (<?= htmlspecialchars(explode(' ', $namaOwner)[0]) ?>)
                </a>
            </div>
        </div>

    </div>

</body>
</html>