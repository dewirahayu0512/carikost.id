<?php
session_start();
include 'db.php';

// Jika user sudah login, lempar langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$msg_type = ""; 

if (isset($_SESSION['sukses'])) {
    $message = $_SESSION['sukses'];
    $msg_type = "green";
    unset($_SESSION['sukses']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $users = $database->getReference('users')->getValue();
    } catch (Exception $e) {
        $users = null;
    }

    $userFound = false;

    if ($users && is_array($users)) {
        foreach ($users as $userId => $userData) {
            $dbEmail = $userData['email'] ?? '';
            $dbPass  = $userData['password'] ?? '';

            if (strcasecmp($dbEmail, $email) == 0) {
                if (password_verify($password, $dbPass)) {
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['nama'] = $userData['nama_lengkap'] ?? 'User';
                    $_SESSION['role'] = $userData['role'] ?? 'penyewa';
                    
                    $userFound = true;
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $message = "Kata sandi yang Anda masukkan salah.";
                    $msg_type = "red";
                    $userFound = true; 
                    break; 
                }
            }
        }
    }

    if (!$userFound && empty($message)) {
        $message = "Email tidak terdaftar. Silakan daftar dulu.";
        $msg_type = "red";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA BARU (TEAL / TOSCA) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; /* Teal solid */
            --hover-color: #004D40; /* Teal lebih gelap */
            --text-color: #2f3542;
        }

        /* PERBAIKAN LAYOUT SCROLLING */
        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--primary-gradient); 
            
            /* Gunakan min-height, bukan height fix */
            min-height: 100vh; 
            
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0; 
            
            /* Tambahkan padding agar tidak mepet atas/bawah saat di HP */
            padding: 40px 20px; 
            box-sizing: border-box;
            
            color: var(--text-color);
        }
        
        .login-card { 
            background: white; 
            padding: 40px 30px; 
            border-radius: 15px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            width: 100%; 
            max-width: 380px; 
            text-align: center;
            position: relative;
            overflow: hidden;
            border-top: 6px solid var(--main-color);
            
            /* Pastikan card tidak terlalu mepet ke browser */
            margin: auto; 
        }

        .brand-icon {
            font-size: 3.5em;
            margin-bottom: 10px;
            color: var(--main-color);
        }

        h2 { 
            color: #333; 
            margin: 0 0 5px 0; 
            font-weight: 700; 
        }
        
        p.subtitle { 
            color: #666; 
            font-size: 0.9em; 
            margin-bottom: 30px; 
            font-weight: 400; 
        }

        .form-group { text-align: left; margin-bottom: 20px; }
        
        label { 
            font-weight: 600; 
            color: #444; 
            display: block; 
            margin-bottom: 8px; 
            font-size: 0.9em; 
        }
        
        input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #ddd; 
            border-radius: 8px; 
            font-size: 0.95em; 
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box; 
            transition: 0.3s;
            outline: none;
            background: #fafafa;
        }
        
        input:focus { 
            border-color: var(--main-color); 
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1);
        }

        .btn-login { 
            width: 100%; 
            background: var(--main-color); 
            color: white; 
            padding: 14px; 
            border: none; 
            border-radius: 8px; 
            font-size: 1em; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s; 
            box-shadow: 0 4px 10px rgba(0, 105, 92, 0.3);
            margin-top: 10px;
        }
        
        .btn-login:hover { 
            background: var(--hover-color); 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(0, 105, 92, 0.4);
        }

        /* Alert Styles */
        .alert { 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 0.9em; 
            font-weight: 500;
        }
        .alert-red { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .alert-green { background: #e0f2f1; color: #00695c; border: 1px solid #80cbc4; }

        .footer-link { margin-top: 25px; font-size: 0.9em; color: #666; }
        .footer-link a { color: var(--main-color); text-decoration: none; font-weight: 600; transition: 0.3s; }
        .footer-link a:hover { color: var(--hover-color); text-decoration: underline; }

        .forgot-pass { font-size: 0.8em; color: var(--main-color); text-decoration: none; font-weight: 500; }
        .forgot-pass:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-icon"><i class="fa-solid fa-house-chimney"></i></div>
        <h2>Selamat Datang</h2>
        <p class="subtitle">Masuk ke Sistem Informasi Kost</p>
        
        <?php if($message): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?php if($msg_type == 'red') echo '<i class="fa-solid fa-circle-exclamation"></i> '; ?>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="nama@email.com" required>
            </div>
            
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label style="margin:0;">Password</label>
                    <a href="lupa_password.php" class="forgot-pass">Lupa Sandi?</a>
                </div>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-login">Masuk Sekarang</button>
        </form>

        <div class="footer-link">
            Belum punya akun? <a href="register.php">Daftar disini</a>
        </div>
    </div>

</body>
</html>