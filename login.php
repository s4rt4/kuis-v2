<?php
// login.php
session_start();
require 'config.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'teacher') {
        header("Location: admin.php");
        exit;
    } else {
        header("Location: student.php");
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        // Admin default fallback (asumsi password plain di file aslinya, atau md5.
        // Kita gunakan password_verify jika hash modern, tapi jika 'admin' hardcoded kita sesuaikan:
        // Di aplikasi MVP biasanya pake password_verify, mari kita tes dengan MD5 atau plaintext dulu.
        // Kita cek hash atau plaintext (jika legacy).
        
        // PENTING: Sesuai struktur aplikasi `teachers` lama, cek kesesuaian sandi
        $valid = false;
        if ($user) {
            if ($user['password'] === $password || $user['password'] === md5($password) || password_verify($password, $user['password'])) {
                $valid = true;
            }
        }

        if ($valid) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['user_level']    = $user['level'];
            $_SESSION['user_name']     = $user['display_name'] ?? $user['username'];
            $_SESSION['user_avatar']   = $user['avatar'];

            // Update last_login jika ada kolomnya
            $db->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);

            if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
                header("Location: admin.php");
            } else {
                header("Location: student.php");
            }
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Silakan isi username dan password.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Panel</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: #0f1b2d;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-title {
            font-family: 'Fredoka One', cursive;
            color: #4f46e5;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .btn-custom {
            background: #4f46e5;
            color: white;
            font-weight: bold;
            border-radius: 10px;
        }
        .btn-custom:hover {
            background: #3730a3;
            color: white;
        }
        .form-control {
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h2 class="login-title">Pintar Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="font-size: 0.9rem; border-radius:10px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Masukkan Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Masukkan Password">
            </div>
            
            <button type="submit" class="btn btn-custom w-100 py-2">Masuk ke Sistem</button>
            <div class="text-center mt-3" style="font-size:0.85rem">
                <a href="index.php" class="text-muted text-decoration-none">← Kembali ke Halaman Utama</a>
            </div>
        </form>
    </div>

</body>
</html>
