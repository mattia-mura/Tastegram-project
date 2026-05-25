<!-- ?php

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

echo json_encode(['success' => true]); -->

<?php
/**
 * API per l'eliminazione di un post
 * Percorso: /backend/api/delete_post.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

// 1. Controllo Autenticazione
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Devi effettuare il login per eliminare un post.']);
    exit;
}

try {
    // Caricamento connessione (Verifica che il percorso sia corretto per la tua struttura)
    require_once __DIR__ . '/../../config/Database.php';
    $sql = Database::getInstance()->getConnection();

    // 2. Ricezione dati JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $postId = isset($data['post_id']) ? (int)$data['post_id'] : 0;
    $userId = (int)$_SESSION['user_id'];

    if ($postId <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID post non valido.']);
        exit;
    }

    // 3. Recupero info post (per eliminare l'immagine e verificare la proprietà)
    $stmt = $sql->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post non trovato o non hai i permessi per eliminarlo.']);
        exit;
    }

    // 4. Eliminazione file immagine dal server
    if (!empty($post['image_path'])) {
        // basename garantisce di non uscire dalla cartella per errore
        $filename = basename($post['image_path']);
        $filePath = __DIR__ . '/../../img/uploads/foto/' . $filename;
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // 5. Eliminazione dal Database
    // Grazie alle chiavi esterne (ON DELETE CASCADE), commenti e like spariranno da soli
    $delete = $sql->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $delete->execute([$postId, $userId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Errore server: ' . $e->getMessage()]);
}