<?php
$password_kamu = "admin123"; 

$hash_hasil = password_hash($password_kamu, PASSWORD_DEFAULT);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Password Generator</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .card { background: white; padding: 20px; border-radius: 10px; shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; word-break: break-all; }
        code { background: #eee; padding: 5px; display: block; margin-top: 10px; border: 1px solid #ccc; color: #d63384; }
    </style>
</head>
<body>
    <div class="card">
        <h3>Hasil Hash Password</h3>
        <p>Password Asli: <strong><?php echo $password_kamu; ?></strong></p>
        <p>Salin kode di bawah ini ke kolom <strong>passwordAdmin</strong> di database:</p>
        <code><?php echo $hash_hasil; ?></code>
        <hr>
        <small style="color: red;">*Hapus file ini setelah kamu berhasil mengupdate database demi keamanan!</small>
    </div>
</body>
</html>