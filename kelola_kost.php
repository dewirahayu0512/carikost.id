<?php
session_start();
include 'db.php';

// 1. Cek Akses Pemilik
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pemilik') {
    die("Akses Ditolak.");
}

// 2. Logika Hapus (Dipanggil setelah konfirmasi SweetAlert)
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    $database->getReference('kosts/' . $id_hapus)->remove();
    
    // Set session untuk notifikasi sukses (opsional)
    // $_SESSION['alert'] = "Data berhasil dihapus!";
    
    header("Location: kelola_kost.php");
    exit();
}

// 3. Logika Ubah Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id_kost = $_GET['id'];
    $status_saat_ini = $_GET['toggle_status'];
    $status_baru = ($status_saat_ini == 'Tersedia') ? 'Penuh' : 'Tersedia';
    $database->getReference('kosts/' . $id_kost)->update(['status_ketersediaan' => $status_baru]);
    header("Location: kelola_kost.php");
    exit();
}

// 4. Ambil Data
$allKosts = $database->getReference('kosts')->getValue();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kost - Carikost.id</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7fc; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; }
        .header h2 { margin: 0; color: #333; font-size: 1.8em; }
        .btn-add { background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,123,255,0.2); }
        .btn-add:hover { background: #0056b3; transform: translateY(-2px); }
        .btn-back { color: #666; text-decoration: none; font-weight: 500; font-size: 0.95em; transition: 0.3s; }
        .btn-back:hover { color: #007bff; }

        table { width: 100%; border-collapse: separate; border-spacing: 0 15px; margin-top: 10px; }
        thead th { color: #888; font-weight: 600; text-transform: uppercase; font-size: 0.85em; padding: 0 15px 10px; text-align: left; letter-spacing: 0.5px; }
        
        tbody tr { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: transform 0.2s; }
        tbody tr:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        
        td { padding: 20px; vertical-align: middle; border-top: 1px solid #f9f9f9; border-bottom: 1px solid #f9f9f9; }
        td:first-child { border-left: 1px solid #f9f9f9; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        td:last-child { border-right: 1px solid #f9f9f9; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .kost-img { width: 140px; height: 90px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: block; }
        .kost-name { font-size: 1.1em; font-weight: bold; color: #222; margin-bottom: 5px; display: block; }
        .kost-addr { font-size: 0.9em; color: #777; display: flex; align-items: start; gap: 5px; }
        .kost-addr i { margin-top: 3px; color: #dc3545; }
        
        .price-tag { font-weight: bold; color: #28a745; font-size: 1.1em; }
        .price-unit { font-size: 0.8em; color: #888; font-weight: normal; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75em; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .bg-L { background: #e3f2fd; color: #0d47a1; border: 1px solid #b3e5fc; }
        .bg-P { background: #fce4ec; color: #c2185b; border: 1px solid #f8bbd0; }
        .bg-Campur { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }

        .status-badge { padding: 5px 10px; border-radius: 6px; font-size: 0.8em; font-weight: bold; cursor: pointer; display: inline-block; text-align: center; width: 80px; transition:0.3s; text-decoration:none; }
        .status-Tersedia { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-Penuh { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-badge:hover { transform: scale(1.05); }

        .desc-text { font-size: 0.9em; color: #666; line-height: 1.4; max-width: 200px; }

        .action-box { display: flex; gap: 8px; }
        .btn-action { width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; border: none; cursor: pointer; }
        .btn-edit { background: #fff3cd; color: #856404; }
        .btn-edit:hover { background: #ffecb5; }
        .btn-delete { background: #f8d7da; color: #721c24; }
        .btn-delete:hover { background: #f5c6cb; }

        @media (max-width: 900px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { margin-bottom: 20px; border: 1px solid #eee; border-radius: 10px; padding: 15px; }
            td { padding: 10px 0; border: none; position: relative; }
            .kost-img { width: 100%; height: 200px; margin-bottom: 10px; }
            .action-box { justify-content: flex-end; margin-top: 10px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div>
                <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <h2>Manajemen Kost</h2>
            </div>
            <a href="tambah_kost.php" class="btn-add"><i class="fa-solid fa-plus"></i> Tambah Kost</a>
        </div>

        <?php 
        $adaData = false;
        $myKosts = [];
        if ($allKosts && is_array($allKosts)) {
            foreach ($allKosts as $id => $k) {
                if (isset($k['owner_id']) && $k['owner_id'] == $_SESSION['user_id']) {
                    $myKosts[$id] = $k;
                }
            }
        }
        
        if (!empty($myKosts)): 
        ?>
            <table>
                <thead>
                    <tr>
                        <th width="150">Foto Kost</th>
                        <th>Informasi Kost</th>
                        <th>Status</th>
                        <th>Tipe & Harga</th>
                        <th>Deskripsi</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myKosts as $id => $k): 
                        $desc = $k['deskripsi'] ?? '-';
                        if (strlen($desc) > 50) $desc = substr($desc, 0, 50) . "...";
                        
                        $gender = $k['jenis_kelamin'] ?? 'Campur';
                        $genderLabel = ($gender == 'L') ? 'Putra' : (($gender == 'P') ? 'Putri' : 'Campur');
                        $status = $k['status_ketersediaan'] ?? 'Tersedia';
                    ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($k['foto_url'] ?? '') ?>" class="kost-img" onerror="this.src='https://via.placeholder.com/150x100?text=No+Image'">
                        </td>
                        <td>
                            <span class="kost-name"><?= htmlspecialchars($k['nama_kost']) ?></span>
                            <div class="kost-addr">
                                <i class="fa-solid fa-location-dot"></i>
                                <span><?= htmlspecialchars($k['alamat']) ?></span>
                            </div>
                            <div style="margin-top:5px; font-size:0.85em; color:#888;">
                                <i class="fa-solid fa-list-check"></i> <?= isset($k['fasilitas']) ? implode(", ", $k['fasilitas']) : '-' ?>
                            </div>
                        </td>
                        <td>
                            <a href="kelola_kost.php?id=<?= $id ?>&toggle_status=<?= $status ?>" 
                               class="status-badge status-<?= $status ?>" 
                               title="Klik untuk ubah status">
                                <?= $status ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-<?= $gender ?>"><?= $genderLabel ?></span>
                            <br><br>
                            <span class="price-tag">Rp <?= number_format($k['harga']) ?></span>
                            <span class="price-unit">/bln</span>
                        </td>
                        <td>
                            <div class="desc-text"><?= htmlspecialchars($desc) ?></div>
                        </td>
                        <td>
                            <div class="action-box">
                                <a href="edit_kost.php?id=<?= $id ?>" class="btn-action btn-edit" title="Edit Data">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                
                                <a href="#" 
                                   class="btn-action btn-delete" 
                                   onclick="konfirmasiHapus('<?= $id ?>', '<?= htmlspecialchars($k['nama_kost']) ?>')"
                                   title="Hapus Data">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <?php else: ?>
            <div style="text-align: center; padding: 60px; color: #999;">
                <i class="fa-solid fa-folder-open" style="font-size: 4em; margin-bottom: 15px; color: #e0e0e0;"></i>
                <h3>Belum ada data kost</h3>
                <p>Mulai tambahkan kost Anda untuk menjangkau penyewa.</p>
                <br>
                <a href="tambah_kost.php" class="btn-add">Tambah Sekarang</a>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function konfirmasiHapus(id, namaKost) {
            Swal.fire({
                title: 'Yakin hapus kost ini?',
                text: "Anda akan menghapus: " + namaKost,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika user klik Ya, arahkan ke link hapus PHP
                    window.location.href = "kelola_kost.php?hapus_id=" + id;
                }
            })
        }
    </script>

</body>
</html>