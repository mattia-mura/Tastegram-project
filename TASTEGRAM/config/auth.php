<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
if (empty($_SESSION['user_id'])) {
    header('Location: /tastegram/backend/login/login.php');
    exit;
}
 
$currentUserId   = (int)    $_SESSION['user_id'];
$currentUsername =          $_SESSION['username']   ?? 'utente';
$currentAvatar   =          $_SESSION['avatar_url'] ?? 'default_avatar.png';
$isGuest         = ($currentUsername === 'ospite');
 
// Include helpers — disponibili in tutte le pagine protette
require_once __DIR__ . '/helpers.php';