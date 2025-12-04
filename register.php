<?php
session_start();
include 'db.php';

$message = "";
$msg_type = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $no_hp = htmlspecialchars($_POST['no_hp']); // Ambil data No HP
    $password = $_POST['password'];
    $role = $_POST['role'];

    // --- VALIDASI INPUT ---
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
        $msg_type = "red";
    } 
    elseif (strlen($password) < 8) {
        $message = "Password minimal 8 karakter!";
        $msg_type = "red";
    } 
    else {
        // --- CEK DUPLIKASI EMAIL ---
        try {
            $users = $database->getReference('users')->getValue();
        } catch (Exception $e) {
            $users = null;
        }

        $emailSudahAda = false;

        if ($users && is_array($users)) {
            foreach ($users as $u) {
                if (isset($u['email']) && strcasecmp($u['email'], $email) == 0) {
                    $emailSudahAda = true;
                    break; 
                }
            }
        }

        if ($emailSudahAda) {
            $message = "Email sudah terdaftar! Silakan login.";
            $msg_type = "red";
        } else {
            // --- PROSES PENDAFTARAN ---
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $newUser = [
                'nama_lengkap' => $nama,
                'email' => $email,
                'no_hp' => $no_hp, // Simpan No HP sebagai string/text
                'password' => $hashedPassword,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s')
            ];

            try {
                $database->getReference('users')->push($newUser);
                
                $_SESSION['sukses'] = "Pendaftaran berhasil! Silakan login.";
                header("Location: login.php");
                exit();
            } catch (Exception $e) {
                $message = "Terjadi kesalahan sistem: " . $e->getMessage();
                $msg_type = "red";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA WARNA (TEAL / TOSCA) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #00695c 0%, #4db6ac 100%);
            --main-color: #00796B; 
            --hover-color: #004D40; 
            --text-color: #2f3542;
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
        
        .register-card { 
            background: white; 
            padding: 40px 35px; 
            border-radius: 15px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            width: 100%; 
            max-width: 450px;
            position: relative;
            overflow: hidden;
            border-top: 6px solid var(--main-color);
            margin: auto;
        }

        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { margin: 0; color: #333; font-weight: 700; }
        .header p { color: #666; margin-top: 5px; font-size: 0.9em; }

        .form-group { margin-bottom: 15px; }
        
        label { 
            font-weight: 600; 
            color: #444; 
            display: block; 
            margin-bottom: 8px; 
            font-size: 0.9em; 
        }
        
        input, select { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #ddd; 
            border-radius: 8px; 
            font-size: 0.95em; 
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box; 
            transition: 0.3s;
            background-color: #fafafa;
            outline: none;
        }

        input:focus, select:focus { 
            border-color: var(--main-color); 
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1);
        }

        /* Hilangkan spinner pada input number jika browser memaksanya */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .btn-register { 
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
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(0, 105, 92, 0.3);
        }
        
        .btn-register:hover { 
            background: var(--hover-color); 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(0, 105, 92, 0.4);
        }

        .alert { 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 0.9em; 
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-red { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        .footer-link { margin-top: 25px; text-align: center; font-size: 0.9em; color: #666; }
        .footer-link a { color: var(--main-color); text-decoration: none; font-weight: 600; }
        .footer-link a:hover { color: var(--hover-color); text-decoration: underline; }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="header">
            <h2>Buat Akun Baru</h2>
            <p>Lengkapi data diri Anda untuk bergabung</p>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
            </div>

            <div class="form-group">
                <label>Nomor HP / WhatsApp</label>
                <input type="text" name="no_hp" inputmode="numeric" pattern="[0-9]*" placeholder="08123456789" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Contoh: user@email.com" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Minimal 8 karakter" required>
            </div>

            <div class="form-group">
                <label>Daftar Sebagai</label>
                <select name="role" required>
                    <option value="penyewa">Pencari Kost (Penyewa)</option>
                    <option value="pemilik">Pemilik Kost</option>
                </select>
            </div>
            
            <button type="submit" class="btn-register">Daftar Sekarang</button>
        </form>

        <div class="footer-link">
            Sudah punya akun? <a href="login.php">Login disini</a>
        </div>
    </div>

</body>
</html>