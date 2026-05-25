<?php
// config/api_bootstrap.php
// Bootstrap per i file in backend/api/ — risponde JSON 401 se non autenticato.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Carica Database
foreach ([
    __DIR__ . '/Database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/tastegram/config/Database.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

if (!class_exists('Database')) {
    echo json_encode(['success' => false, 'error' => 'Database non disponibile']);
    exit;
}

$sql = Database::getInstance()->getConnection();

// Carica helpers
foreach ([
    __DIR__ . '/helpers.php',
    $_SERVER['DOCUMENT_ROOT'] . '/tastegram/config/helpers.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

// Guard autenticazione
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

$currentUserId = (int)    $_SESSION['user_id'];
$isGuest       = (($_SESSION['username'] ?? '') === 'ospite');
$isAdmin       = (bool)  ($_SESSION['is_admin'] ?? false);

// Verifica is_admin dal DB se non già in sessione
if (!$isAdmin) {
    $chk = $sql->prepare("SELECT is_admin FROM users WHERE id = ?");
    $chk->execute([$currentUserId]);
    $row = $chk->fetch();
    if (!empty($row['is_admin'])) {
        $isAdmin = true;
        $_SESSION['is_admin'] = true;
    }
}
