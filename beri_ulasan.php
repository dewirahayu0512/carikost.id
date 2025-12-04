<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$booking_id = $_GET['id'] ?? '';
if (!$booking_id) { 
    // Jika tidak ada ID, redirect ke riwayat booking
    header("Location: riwayat_booking.php");
    exit(); 
}

// Ambil data booking untuk verifikasi dan detail kost
$bookingRef = $database->getReference('bookings/' . $booking_id)->getValue();

// Validasi: Apakah booking ini milik user yang login?
if (!$bookingRef || $bookingRef['penyewa_id'] != $_SESSION['user_id']) { 
    header("Location: riwayat_booking.php"); 
    exit(); 
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pastikan rating dikirim
    $rating = (int)($_POST['rating'] ?? 0);
    $komentar = htmlspecialchars($_POST['komentar'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $message = "Rating tidak valid.";
    } else {
        // Data Ulasan
        $reviewData = [
            'kost_id' => $bookingRef['kost_id'] ?? '', // Penting: Relasi ke kost
            'user_id' => $_SESSION['user_id'],
            'nama_user' => $_SESSION['nama'] ?? 'Pengguna',
            'rating' => $rating,
            'komentar' => $komentar,
            'tanggal' => date('Y-m-d H:i:s')
        ];

        try {
            // 1. Simpan Ulasan ke tabel 'reviews'
            $database->getReference('reviews')->push($reviewData);
            
            // 2. Tandai booking ini sudah diulas (agar tidak ulasan ganda)
            $database->getReference('bookings/' . $booking_id)->update(['is_reviewed' => true]);
            
            // Redirect kembali dengan pesan sukses (opsional, bisa diganti redirect langsung)
            $_SESSION['review_success'] = true;
            header("Location: riwayat_booking.php");
            exit();
        } catch (Exception $e) {
            $message = "Gagal mengirim ulasan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Ulasan - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA (TEAL MODERN) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; 
            --hover-color: #004D40; 
            --bg-light: #f5f7fa;
            --text-dark: #2f3542;
            --text-muted: #888;
            --color-yellow: #FFC107; /* Warna Bintang */
        }

        /* Base Styles */
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            margin: 0; 
            padding: 0; 
            color: var(--text-dark);
        }
        
        a { text-decoration: none; color: var(--main-color); }
        .full-page-container { 
            max-width: 600px; 
            margin: 0 auto; 
            min-height: 100vh; 
            background: white; 
            box-shadow: 0 0 30px rgba(0,0,0,0.08);
            display: flex; 
            flex-direction: column; 
        }

        /* Header Teal (Konsisten dengan chat_list.php) */
        .header { 
            background: var(--primary-gradient); 
            color: white; 
            padding: 20px 25px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            box-shadow: 0 4px 10px rgba(0, 105, 92, 0.25);
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }
        .header h2 { margin: 0; font-size: 1.3em; flex: 1; font-weight: 600; }
        .btn-back { /* Gaya tombol kembali */
            color: white; font-size: 1.2em; transition: 0.3s; background: rgba(255,255,255,0.2);
            width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; flex-shrink: 0;
        }

        /* Form Card */
        .review-card { 
            padding: 30px 25px; 
            flex-grow: 1;
            text-align: center;
        }
        
        .review-card h2 {
            color: var(--main-color);
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .review-card p {
            color: var(--text-muted);
            font-size: 0.9em;
            margin-bottom: 20px;
        }

        /* Rating Stars (Fieldset radio button) */
        .rating-stars {
            display: flex;
            justify-content: center;
            flex-direction: row-reverse; /* Agar 5 bintang ada di kanan */
            margin-bottom: 25px;
        }

        .rating-stars input[type="radio"] {
            display: none;
        }

        .rating-stars label {
            font-size: 3em;
            color: #ddd; /* Warna bintang default/kosong */
            cursor: pointer;
            padding: 0 5px;
            transition: color 0.3s;
        }

        /* Hover Effect: bintang yang di-hover dan bintang di kirinya menyala */
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: var(--color-yellow);
        }

        /* Checked Effect: bintang yang dipilih dan bintang di kirinya menyala */
        .rating-stars input:checked ~ label {
            color: var(--color-yellow);
        }

        /* Form Elements */
        label {
            display: block;
            font-weight: 600;
            font-size: 0.95em;
            color: #444;
            margin-bottom: 8px;
            text-align: left;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        textarea:focus {
            border-color: var(--main-color);
            outline: none;
        }

        /* BUTTON */
        .btn-submit {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: none;
            border-radius: 8px;
            background: var(--main-color);
            color: white;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-submit:hover {
            background: var(--hover-color);
        }
        .btn-batal {
            display: inline-block;
            margin-top: 15px;
            color: var(--text-muted);
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .alert-error {
            color: #d32f2f;
            background: #ffebee;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

    </style>
</head>
<body>

    <div class="full-page-container">
        
        <div class="header">
            <a href="riwayat_booking.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
            <h2>Beri Ulasan</h2>
        </div>

        <div class="review-card">
            <h2>⭐ Beri Penilaian</h2>
            <p>Bagaimana pengalamanmu ngekost di <strong><?= htmlspecialchars($bookingRef['nama_kost'] ?? 'Kost Ini') ?></strong>?</p>
            
            <?php if($message): ?>
                <div class="alert-error"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <label style="text-align: center;">Rating (Bintang):</label>
                
                <div class="rating-stars">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5" title="Sangat Puas"><i class="fa-solid fa-star"></i></label>
                    
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4" title="Bagus"><i class="fa-solid fa-star"></i></label>
                    
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3" title="Cukup"><i class="fa-solid fa-star"></i></label>
                    
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2" title="Kurang"><i class="fa-solid fa-star"></i></label>
                    
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1" title="Buruk"><i class="fa-solid fa-star"></i></label>
                </div>
                
                <label for="komentar">Komentar:</label>
                <textarea id="komentar" name="komentar" rows="4" placeholder="Ceritakan pengalamanmu..."></textarea>
                
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Ulasan
                </button>
                
                <a href="riwayat_booking.php" class="btn-batal">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>