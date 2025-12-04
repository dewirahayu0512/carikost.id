<?php
session_start();
include 'db.php';

// Cek Login (Opsional, agar publik bisa lihat juga)
// if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

// --- 1. AMBIL PARAMETER FILTER ---
$keyword = $_GET['keyword'] ?? '';
$gender = $_GET['gender'] ?? '';
$min_harga = $_GET['min_harga'] ?? '';
$max_harga = $_GET['max_harga'] ?? '';
$fasilitas_pilih = $_GET['fasilitas'] ?? [];

// --- 2. AMBIL DATA DARI FIREBASE ---
try {
    $allKosts = $database->getReference('kosts')->getValue();
} catch (Exception $e) {
    $allKosts = [];
}
$results = [];

// --- 3. LOGIKA FILTER PHP ---
if ($allKosts && is_array($allKosts)) {
    foreach ($allKosts as $id => $data) {
        $lolos = true;

        // Filter Keyword
        if (!empty($keyword)) {
            $nama = strtolower($data['nama_kost'] ?? '');
            $alamat = strtolower($data['alamat'] ?? '');
            $cari = strtolower($keyword);
            if (strpos($nama, $cari) === false && strpos($alamat, $cari) === false) $lolos = false;
        }

        // Filter Gender
        if ($lolos && !empty($gender) && $gender != 'Semua') {
            if (($data['jenis_kelamin'] ?? '') != $gender) $lolos = false;
        }

        // Filter Harga
        $harga_kost = (int)($data['harga'] ?? 0);
        if ($lolos && !empty($min_harga) && $harga_kost < (int)$min_harga) $lolos = false;
        if ($lolos && !empty($max_harga) && $harga_kost > (int)$max_harga) $lolos = false;

        // Filter Fasilitas
        if ($lolos && !empty($fasilitas_pilih)) {
            $fasilitas_kost = $data['fasilitas'] ?? [];
            foreach ($fasilitas_pilih as $f) {
                if (!in_array($f, $fasilitas_kost)) { $lolos = false; break; }
            }
        }

        if ($lolos) $results[$id] = $data;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Kost - Carikost.id</title>
    
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

        /* BASE STYLE */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-color); }
        
        /* NAVBAR */
        .navbar { 
            background: white; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        .brand { 
            font-size: 1.4em; 
            font-weight: 700; 
            color: var(--main-color); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .nav-link { 
            color: #555; 
            text-decoration: none; 
            font-weight: 500; 
            transition: 0.3s; 
            font-size: 0.9em;
        }
        .nav-link:hover { color: var(--main-color); }

        /* LAYOUT UTAMA */
        .main-container { 
            max-width: 1200px; 
            margin: 30px auto; 
            padding: 0 20px; 
            display: flex; 
            gap: 30px; 
            align-items: flex-start; 
        }
        
        /* SIDEBAR FILTER */
        .filter-sidebar { 
            flex: 0 0 280px; 
            background: white; 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            position: sticky; 
            top: 90px; 
        }
        .filter-header { 
            font-size: 1.1em; 
            font-weight: 700; 
            margin-bottom: 20px; 
            padding-bottom: 15px; 
            border-bottom: 2px solid #e0f2f1; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            color: var(--main-color);
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9em; color: #444; }
        
        .form-control { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #eee; 
            border-radius: 10px; 
            font-size: 0.9em; 
            font-family: 'Poppins', sans-serif;
            transition: 0.3s; 
            outline: none;
        }
        .form-control:focus { border-color: var(--main-color); background: #fdfaff; }
        
        .check-item { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin-bottom: 10px; 
            cursor: pointer; 
            font-size: 0.9em; 
            color: #555; 
        }
        .check-item input { accent-color: var(--main-color); width: 18px; height: 18px; }

        .btn-filter { 
            width: 100%; 
            background: var(--main-color); 
            color: white; 
            padding: 12px; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s; 
            box-shadow: 0 4px 10px rgba(0, 105, 92, 0.2);
        }
        .btn-filter:hover { background: var(--hover-color); transform: translateY(-2px); }
        
        .btn-reset { 
            width: 100%; 
            background: #fff; 
            color: #777; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            margin-top: 10px; 
            cursor: pointer; 
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-reset:hover { border-color: var(--main-color); color: var(--main-color); }

        /* HASIL PENCARIAN */
        .results-area { flex: 1; }
        .results-header { margin-bottom: 20px; font-size: 1.2em; font-weight: 600; color: #333; }
        
        /* CARD KOST DESIGN */
        .kost-card { 
            background: white; 
            border-radius: 15px; 
            overflow: hidden; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.03); 
            display: flex; 
            margin-bottom: 25px; 
            transition: transform 0.3s, box-shadow 0.3s; 
            border: 1px solid #f0f0f0; 
        }
        .kost-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.08); 
            border-color: #b2dfdb; 
        }
        
        .card-img-wrapper { 
            width: 280px; 
            height: 210px; 
            position: relative; 
            background: #eee; 
            flex-shrink: 0; 
        }
        .card-img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Label Gender */
        .badge-gender { 
            position: absolute; 
            top: 15px; left: 15px; 
            padding: 6px 12px; 
            border-radius: 30px; 
            color: white; 
            font-size: 0.75em; 
            font-weight: 600; 
            text-transform: uppercase; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.2); 
            letter-spacing: 0.5px;
        }
        .bg-L { background: #0288d1; } /* Biru */
        .bg-P { background: #e91e63; } /* Pink */
        .bg-Campur { background: var(--main-color); } /* Teal */

        .card-content { 
            padding: 25px; 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
        }
        
        .card-title { font-size: 1.4em; font-weight: 700; margin-bottom: 8px; color: #222; }
        .card-loc { color: #666; font-size: 0.9em; display: flex; align-items: center; gap: 8px; margin-bottom: 15px; }
        
        .card-facilities { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
        .facility-tag { 
            font-size: 0.8em; 
            background: #e0f2f1; 
            color: #00695c; 
            padding: 5px 10px; 
            border-radius: 6px; 
            display: flex; 
            align-items: center; 
            gap: 5px; 
        }
        
        .card-footer { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-top: auto; 
            border-top: 1px solid #f0f0f0; 
            padding-top: 20px; 
        }
        .price-tag { font-size: 1.3em; font-weight: 700; color: var(--main-color); }
        .price-period { font-size: 0.75em; color: #888; font-weight: 400; }
        
        .btn-detail { 
            background: var(--main-color); 
            color: white; 
            text-decoration: none; 
            padding: 10px 20px; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 0.95em; 
            transition: 0.3s; 
            box-shadow: 0 4px 10px rgba(0, 105, 92, 0.2);
        }
        .btn-detail:hover { background: var(--hover-color); transform: translateY(-2px); }

        /* EMPTY STATE */
        .empty-state { 
            text-align: center; 
            padding: 60px 20px; 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }
        .empty-state i { font-size: 5em; color: #e0e0e0; margin-bottom: 20px; }
        .empty-state h3 { color: #555; margin-bottom: 5px; font-weight: 600; }
        .empty-state p { color: #888; }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .main-container { flex-direction: column; }
            .filter-sidebar { width: 100%; position: static; margin-bottom: 30px; }
            .kost-card { flex-direction: column; }
            .card-img-wrapper { width: 100%; height: 200px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="dashboard.php" class="brand">
            <i class="fa-solid fa-house-chimney"></i> Carikost.id
        </a>
        <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
    </nav>

    <div class="main-container">
        
        <form class="filter-sidebar" method="GET">
            <div class="filter-header">
                <span>Filter Pencarian</span>
                <i class="fa-solid fa-sliders"></i>
            </div>

            <div class="form-group">
                <label class="form-label">Lokasi / Nama</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 14px; color: #aaa;"></i>
                    <input type="text" name="keyword" class="form-control" placeholder="Cth: Mawar, Telanai..." value="<?= htmlspecialchars($keyword) ?>" style="padding-left: 40px;">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Tipe Kost</label>
                <select name="gender" class="form-control">
                    <option value="">Semua Tipe</option>
                    <option value="L" <?= $gender == 'L' ? 'selected' : '' ?>>Khusus Putra</option>
                    <option value="P" <?= $gender == 'P' ? 'selected' : '' ?>>Khusus Putri</option>
                    <option value="Campur" <?= $gender == 'Campur' ? 'selected' : '' ?>>Campur</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Rentang Harga</label>
                <input type="number" name="min_harga" class="form-control" placeholder="Min (Rp)" value="<?= htmlspecialchars($min_harga) ?>" style="margin-bottom: 10px;">
                <input type="number" name="max_harga" class="form-control" placeholder="Max (Rp)" value="<?= htmlspecialchars($max_harga) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Fasilitas Wajib</label>
                <label class="check-item"><input type="checkbox" name="fasilitas[]" value="WiFi" <?= in_array('WiFi', $fasilitas_pilih) ? 'checked' : '' ?>> WiFi</label>
                <label class="check-item"><input type="checkbox" name="fasilitas[]" value="AC" <?= in_array('AC', $fasilitas_pilih) ? 'checked' : '' ?>> AC</label>
                <label class="check-item"><input type="checkbox" name="fasilitas[]" value="KM Dalam" <?= in_array('KM Dalam', $fasilitas_pilih) ? 'checked' : '' ?>> KM Dalam</label>
                <label class="check-item"><input type="checkbox" name="fasilitas[]" value="Kasur" <?= in_array('Kasur', $fasilitas_pilih) ? 'checked' : '' ?>> Kasur</label>
            </div>

            <button type="submit" class="btn-filter">Terapkan Filter</button>
            <a href="cari_kost.php"><button type="button" class="btn-reset">Reset Filter</button></a>
        </form>

        <div class="results-area">
            <div class="results-header">
                Menampilkan <?= count($results) ?> Kost Tersedia
            </div>

            <?php if (!empty($results)): ?>
                <?php foreach ($results as $id => $kost): ?>
                    <div class="kost-card">
                        <div class="card-img-wrapper">
                            <span class="badge-gender bg-<?= $kost['jenis_kelamin'] ?>">
                                <?= $kost['jenis_kelamin'] == 'L' ? 'Putra' : ($kost['jenis_kelamin'] == 'P' ? 'Putri' : 'Campur') ?>
                            </span>
                            <img src="<?= htmlspecialchars($kost['foto_url'] ?? '') ?>" class="card-img" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                        </div>

                        <div class="card-content">
                            <div>
                                <h3 class="card-title"><?= htmlspecialchars($kost['nama_kost']) ?></h3>
                                <div class="card-loc">
                                    <i class="fa-solid fa-location-dot" style="color: #ef5350;"></i> 
                                    <?= htmlspecialchars($kost['alamat']) ?>
                                </div>
                                
                                <div class="card-facilities">
                                    <?php if(isset($kost['fasilitas'])): ?>
                                        <?php foreach($kost['fasilitas'] as $f): ?>
                                            <span class="facility-tag"><i class="fa-solid fa-check"></i> <?= $f ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div>
                                    <span class="price-tag">Rp <?= number_format($kost['harga']) ?></span>
                                    <span class="price-period">/ bulan</span>
                                </div>
                                <a href="detail_kost.php?id=<?= $id ?>" class="btn-detail">Lihat & Pesan <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-magnifying-glass-location"></i>
                    <h3>Kost tidak ditemukan</h3>
                    <p>Coba kurangi filter atau cari dengan kata kunci lain.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>