<?php
session_start();
// Tidak perlu include db.php jika hanya menampilkan ID dari URL
// include 'db.php'; 

if (!isset($_GET['id'])) { header("Location: dashboard.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Berhasil</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            color: var(--text-color);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card { 
            background: white; 
            padding: 50px 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0, 105, 92, 0.1); 
            text-align: center;
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        /* Hiasan atas */
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: var(--primary-gradient);
        }

        /* Icon Animasi */
        .icon-box {
            width: 100px;
            height: 100px;
            background: #e0f2f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--main-color);
            font-size: 3.5em;
            animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        h2 { margin: 0 0 10px; color: #333; font-weight: 700; }
        p { color: #666; margin: 0 0 20px; font-size: 0.95em; line-height: 1.5; }

        /* Booking Code Box */
        .code-box {
            background: #f9f9f9;
            border: 2px dashed #b2dfdb;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0 30px;
        }
        .code-label { font-size: 0.8em; text-transform: uppercase; color: #888; letter-spacing: 1px; display: block; margin-bottom: 5px; }
        .code-val { font-size: 1.4em; font-weight: 700; color: var(--main-color); letter-spacing: 1px; }

        .btn-home {
            display: inline-block;
            width: 100%;
            padding: 15px;
            background: var(--main-color);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(0, 105, 92, 0.2);
            box-sizing: border-box;
        }
        .btn-home:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 105, 92, 0.3);
        }

        .btn-secondary {
            display: inline-block;
            margin-top: 15px;
            color: #777;
            font-size: 0.9em;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-secondary:hover { color: var(--main-color); }

    </style>
</head>
<body>

    <div class="card">
        <div class="icon-box">
            <i class="fa-solid fa-check"></i>
        </div>
        
        <h2>Permintaan Terkirim!</h2>
        <p>Booking Anda telah berhasil dibuat. Pemilik kost akan segera meninjau permintaan ini.</p>
        
        <div class="code-box">
            <span class="code-label">Kode Booking</span>
            <span class="code-val"><?= htmlspecialchars($_GET['id']) ?></span>
        </div>

        <a href="dashboard.php" class="btn-home">Kembali ke Dashboard</a>
        <a href="riwayat_booking.php" class="btn-secondary">Lihat Riwayat Pesanan</a>
    </div>

</body>
</html>