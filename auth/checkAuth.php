<?php
function checkAuth($requiredRole = null) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['login_error'] = "Silakan login terlebih dahulu";
        header("Location: login.php");
        exit;
    }
    
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        $_SESSION['login_error'] = "Anda tidak memiliki akses ke halaman ini";
        header("Location: login.php");
        exit;
    }
    
    return true;
}
?>