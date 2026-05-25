<?php
// backend/api/delete_post.php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
$sql = Database::getInstance()->getConnection();

$data   = json_decode(file_get_contents('php://input'), true);
$postId = (int) ($data['post_id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($postId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Post non valido']);
    exit;
}

// Verifica che il post appartenga all'utente loggato
$stmt = $sql->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$postId, $userId]);
$post = $stmt->fetch();

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

// Elimina il file immagine dal disco se esiste
if (!empty($post['image_path'])) {
    $filePath = __DIR__ . '/../../img/uploads/foto/' . $post['image_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Elimina il post (cascade elimina like, commenti, notifiche)
$sql->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?")
    ->execute([$postId, $userId]);

echo json_encode(['success' => true]);
