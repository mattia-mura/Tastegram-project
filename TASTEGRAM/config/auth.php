<?php
// config/auth.php
// Guard per tutte le pagine protette.
// Uso: require_once __DIR__ . '/../config/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carica Database
require_once __DIR__ . '/Database.php';
$sql = Database::getInstance()->getConnection();

// Carica helpers (avatarSrc, postImageSrc, timeAgo, ecc.)
require_once __DIR__ . '/helpers.php';

// Guard: se non loggato → redirect al login
if (empty($_SESSION['user_id'])) {
    header('Location: /tastegram/backend/login/login.php');
    exit;
}

// Variabili disponibili in tutte le pagine protette
$currentUserId   = (int)   $_SESSION['user_id'];
$currentUsername =         $_SESSION['username']   ?? 'utente';
$currentAvatar   =         $_SESSION['avatar_url'] ?? 'default_avatar.png';
$isGuest         = ($currentUsername === 'ospite');
$isAdmin         = (bool) ($_SESSION['is_admin']   ?? false);

// Rilegge is_admin dal DB per sicurezza (evita manipolazione sessione)
if (!$isAdmin) {
    $chk = $sql->prepare("SELECT is_admin FROM users WHERE id = ?");
    $chk->execute([$currentUserId]);
    $row = $chk->fetch();
    if (!empty($row['is_admin'])) {
        $isAdmin = true;
        $_SESSION['is_admin'] = true;
    }
}
