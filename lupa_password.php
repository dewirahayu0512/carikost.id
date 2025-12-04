<?php
session_start();
include 'db.php';

$message = "";
$msg_type = ""; // 'green' atau 'red'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $no_hp = htmlspecialchars($_POST['no_hp']);
    $password_baru = $_POST['password_baru'];

    if (strlen($password_baru) < 8) {
        $message = "Password baru minimal 8 karakter.";
        $msg_type = "red";
    } else {
        try {
            $users = $database->getReference('users')->getValue();
            $userFound = false;
            $targetUid = "";
            $oldHash = ""; 

            if ($users && is_array($users)) {
                foreach ($users as $uid => $data) {
                    $dbEmail = $data['email'] ?? '';
                    $dbHp = $data['no_hp'] ?? '';

                    // Match email case-insensitively and phone number
                    if (strcasecmp($dbEmail, $email) == 0 && $dbHp == $no_hp) {
                        $userFound = true;
                        $targetUid = $uid;
                        $oldHash = $data['password'] ?? ''; 
                        break;
                    }
                }
            }

            if ($userFound) {
                // CEK PASSWORD LAMA
                if (password_verify($password_baru, $oldHash)) {
                    // --- PESAN YANG SUDAH DIGANTI ---
                    $message = "❌ Demi keamanan, silakan gunakan kata sandi yang berbeda dari sebelumnya.";
                    $msg_type = "red";
                } else {
                    $newHash = password_hash($password_baru, PASSWORD_DEFAULT);
                    
                    $database->getReference('users/' . $targetUid)->update([
                        'password' => $newHash
                    ]);
                    $message = "✅ Password berhasil diubah! Silakan login.";
                    $msg_type = "green";
                }
                
            } else {
                $message = "❌ Email atau Nomor HP tidak ditemukan/tidak cocok.";
                $msg_type = "red";
            }
        } catch (Exception $e) {
            $message = "Terjadi kesalahan sistem. Coba lagi nanti.";
            $msg_type = "red";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Sandi - Carikost.id</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- THEME CONFIGURATION (TEAL MODERN) --- */
        :root {
            --color-primary: #00796B; /* Teal Dark */
            --color-primary-light: #e0f2f1;
            --color-danger: #d32f2f; /* Red for errors */
            --color-success: #2e7d32; /* Green for success */
            --bg-color-main: #f4f6f8; 
            --bg-color-card: white;
            --box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* BASE STYLE */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-color-main); 
            color: #333; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
        }

        /* CARD CONTAINER */
        .auth-container { 
            background: var(--bg-color-card); 
            padding: 30px; 
            border-radius: 12px; 
            width: 100%;
            max-width: 380px; /* Lebar lebih nyaman */
            box-shadow: var(--box-shadow);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        /* HEADER */
        .auth-container h2 { 
            text-align: center; 
            color: var(--color-primary);
            margin-bottom: 5px;
            font-weight: 700;
        }
        .auth-container p { 
            text-align: center; 
            color: #666; 
            font-size: 0.9em; 
            margin-bottom: 25px;
        }

        /* FORM ELEMENTS */
        label {
            display: block;
            font-weight: 600;
            font-size: 0.9em;
            color: #444;
            margin-bottom: 5px;
            margin-top: 15px;
        }
        
        input[type="email"], input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s;
        }
        input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.2);
            outline: none;
        }

        /* ALERT MESSAGE */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
            font-weight: 600;
            line-height: 1.4;
        }
        .alert.green { 
            background: #e6ffed; 
            color: var(--color-success); 
            border: 1px solid #c3e6cb;
        }
        .alert.red { 
            background: #fdf0f0; 
            color: var(--color-danger); 
            border: 1px solid #f5c6cb;
        }

        /* BUTTON */
        .btn-submit {
            width: 100%;
            padding: 12px;
            margin-top: 25px;
            border: none;
            border-radius: 8px;
            background: var(--color-primary);
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
            background: #00695c;
        }

        /* FOOTER LINK */
        .footer-link {
            text-align: center; 
            margin-top: 25px;
        }
        .footer-link a {
            color: var(--color-primary);
            font-size: 0.9em;
            font-weight: 600;
            text-decoration: none;
        }
        .footer-link a:hover {
            color: #00695c;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <h2>🔒 Atur Ulang Sandi</h2>
        <p>
            Verifikasi identitas Anda untuk mengatur ulang kata sandi.
        </p>

        <?php if($message): ?>
            <div class="alert <?= $msg_type ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <label for="email">Email Terdaftar</label>
            <input type="email" id="email" name="email" required placeholder="Contoh: user@gmail.com" value="<?= $_POST['email'] ?? '' ?>">

            <label for="no_hp">Nomor HP Terdaftar</label>
            <input type="text" id="no_hp" name="no_hp" required placeholder="Contoh: 08123456789" value="<?= $_POST['no_hp'] ?? '' ?>">

            <label for="password_baru">Password Baru</label>
            <input type="password" id="password_baru" name="password_baru" required placeholder="Minimal 8 karakter" minlength="8">

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-key"></i> Simpan Password Baru
            </button>
        </form>

        <div class="footer-link">
            <a href="login.php">← Kembali ke Halaman Login</a>
        </div>
    </div>

</body>
</html>