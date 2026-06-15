<?php
session_start();
require_once 'config.php';

// logout.php
if (basename($_SERVER['PHP_SELF']) == 'logout.php') {
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        // Clear the remember token from database
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
        
        // Delete the cookie by setting expiration to past
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}
?>