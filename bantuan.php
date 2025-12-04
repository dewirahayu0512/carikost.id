<?php
session_start();
include 'db.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Inisialisasi variabel pesan
$message = "";
$msg_type = "";

// --- LOGIKA PENGIRIMAN BANTUAN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subjek = htmlspecialchars($_POST['subjek']);
    $isi = htmlspecialchars($_POST['pesan']);

    $data = [
        'user_id' => $_SESSION['user_id'],
        'nama_pengirim' => $_SESSION['nama'],
        'role' => $_SESSION['role'],
        'subjek' => $subjek,
        'pesan' => $isi,
        'tanggal' => date('Y-m-d H:i:s'),
        'status' => 'unread'
    ];

    try {
        // Asumsi $database adalah koneksi ke Firebase Realtime Database atau Firestore
        $database->getReference('bantuan')->push($data);
        $message = "Pesan terkirim! Admin akan segera merespon.";
        $msg_type = "green";
    } catch (Exception $e) {
        $message = "Gagal kirim: " . $e->getMessage();
        $msg_type = "red";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan & Dukungan - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA (TEAL / TOSCA) dari profile-edit.php --- */
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

        .help-card { 
            background: white; 
            width: 100%; 
            max-width: 600px; /* Sedikit lebih kecil dari profil */
            border-radius: 15px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            overflow: hidden;
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

        /* --- Form Elements Consistency --- */
        label {
            font-weight: 500;
            display: block;
            margin-bottom: 8px;
            font-size: 0.9em;
            color: #555;
        }

        input[type="text"], 
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95em;
            box-sizing: border-box;
            transition: 0.3s;
            outline: none;
            margin-bottom: 20px; /* Tambahkan margin di sini */
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }

        input:focus, textarea:focus {
            border-color: var(--main-color);
            background: #fdfaff;
        }

        /* --- Alert Styles Consistency --- */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }
        .alert-green { background: #e0f2f1; color: #00695c; border: 1px solid #b2dfdb; }
        .alert-red { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        /* --- Button Styles Consistency (btn-save) --- */
        .btn-submit {
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
        .btn-submit:hover {
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

        .whatsapp-info {
            margin-top: 30px; 
            padding: 15px; 
            background: #e0f2f1; /* Menggunakan warna light dari tema Anda */
            border-radius: 10px; 
            text-align: center; 
            border: 1px solid #b2dfdb;
        }
        .whatsapp-info p { margin: 0 0 8px 0; color: #555; font-weight: 500; }
        .whatsapp-info a { 
            color: #25D366; /* Warna WhatsApp */
            font-weight: 700; 
            text-decoration: none; 
            font-size: 1.1em; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .whatsapp-info a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="help-card">
        <div class="card-header">
            <h2>Pusat Bantuan</h2>
            <i class="fa-solid fa-headset icon"></i>
        </div>

        <div class="card-body">
            
            <?php if($message): ?>
                <div class="alert <?= $msg_type == 'green' ? 'alert-green' : 'alert-red' ?>">
                    <?= ($msg_type == 'green' ? '<i class="fa-solid fa-check-circle"></i> ' : '<i class="fa-solid fa-circle-exclamation"></i> ') . $message ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label for="subjek">Perihal / Subjek:</label>
                <input type="text" id="subjek" name="subjek" placeholder="Contoh: Kendala Pembayaran atau Laporan Bug" required>

                <label for="pesan">Pesan Anda:</label>
                <textarea id="pesan" name="pesan" rows="5" placeholder="Jelaskan masalah Anda secara rinci, termasuk langkah-langkah untuk mereplikasi masalah jika ini adalah bug." required></textarea>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Pesan Bantuan
                </button>
                
                <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
            </form>

            <div class="whatsapp-info">
                <p>Butuh respon yang lebih cepat?</p>
                <a href="https://wa.me/628123456789" target="_blank">
                    <i class="fa-brands fa-whatsapp"></i> Hubungi Admin via WhatsApp
                </a>
            </div>
        </div>
    </div>

</body>
</html>