<?php
// backend/api/follow.php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
$sql = Database::getInstance()->getConnection();

$data     = json_decode(file_get_contents('php://input'), true);
$targetId = (int) ($data['target_id'] ?? 0);
$myId     = (int) $_SESSION['user_id'];

if ($targetId <= 0 || $targetId === $myId) {
    echo json_encode(['success' => false, 'error' => 'Target non valido']);
    exit;
}

// Controlla se segue già
$check = $sql->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
$check->execute([$myId, $targetId]);
$alreadyFollowing = (bool) $check->fetchColumn();

if ($alreadyFollowing) {
    // Smetti di seguire
    $sql->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?")
        ->execute([$myId, $targetId]);
    $following = false;
} else {
    // Inizia a seguire
    $sql->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)")
        ->execute([$myId, $targetId]);

    // Notifica
    $sql->prepare("
        INSERT INTO notifications (user_id, actor_id, type)
        VALUES (?, ?, 'follow')
    ")->execute([$targetId, $myId]);

    $following = true;
}

// Contatore follower aggiornato (i trigger nel DB lo gestiscono, rileggiamo)
$fc = $sql->prepare("SELECT followers_count FROM users WHERE id = ?");
$fc->execute([$targetId]);
$followersCount = (int) $fc->fetchColumn();

echo json_encode([
    'success'         => true,
    'following'       => $following,
    'followers_count' => $followersCount
]);
