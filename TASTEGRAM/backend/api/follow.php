<?php
// backend/api/follow.php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

// Trova Database.php in modo robusto su XAMPP
foreach ([
    __DIR__ . '/../../config/Database.php',
    __DIR__ . '/../config/Database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/tastegram/config/Database.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

if (!class_exists('Database')) {
    echo json_encode(['success' => false, 'error' => 'Database non disponibile']);
    exit;
}

$sql      = Database::getInstance()->getConnection();
$data     = json_decode(file_get_contents('php://input'), true);
$targetId = (int) ($data['target_id'] ?? 0);
$myId     = (int) $_SESSION['user_id'];

if ($targetId <= 0 || $targetId === $myId) {
    echo json_encode(['success' => false, 'error' => 'Target non valido']);
    exit;
}

$check = $sql->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
$check->execute([$myId, $targetId]);
$alreadyFollowing = (bool) $check->fetchColumn();

if ($alreadyFollowing) {
    $sql->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?")
        ->execute([$myId, $targetId]);
    $following = false;
} else {
    $sql->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)")
        ->execute([$myId, $targetId]);
    $sql->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'follow')")
        ->execute([$targetId, $myId]);
    $following = true;
}

$fc = $sql->prepare("SELECT followers_count FROM users WHERE id = ?");
$fc->execute([$targetId]);

echo json_encode([
    'success'         => true,
    'following'       => $following,
    'followers_count' => (int) $fc->fetchColumn(),
]);
