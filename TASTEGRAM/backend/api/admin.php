<?php
// backend/api/admin.php

// ✅ session_start() PRIMA del require — evita che api_bootstrap risponda 401
// perché trova $_SESSION vuota prima ancora di leggere il cookie di sessione.
if (session_status() === PHP_SESSION_NONE) session_start();

// api_bootstrap.php fornisce: $sql, $isAdmin, $currentUserId, jsonResponse()
require_once __DIR__ . '/../../config/api_bootstrap.php';

// A questo punto api_bootstrap ha già verificato l'autenticazione.
// Se siamo qui, l'utente è loggato — verifichiamo che sia admin.
if (!$isAdmin) {
    jsonResponse(['success' => false, 'error' => 'Accesso negato'], 403);
}

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {

    // ── Elimina qualsiasi post (admin) ─────────────────────────────────────
    case 'delete_post':
        $postId = (int) ($data['post_id'] ?? 0);
        if ($postId <= 0) {
            jsonResponse(['success' => false, 'error' => 'ID non valido'], 400);
        }

        $stmt = $sql->prepare("SELECT image_path FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            jsonResponse(['success' => false, 'error' => 'Post non trovato'], 404);
        }

        // Elimina immagine dal disco con basename() per sicurezza
        if (!empty($post['image_path'])) {
            $filename = basename($post['image_path']);
            $filePath = __DIR__ . '/../../img/uploads/foto/' . $filename;
            if (file_exists($filePath)) unlink($filePath);
        }

        $sql->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);
        jsonResponse(['success' => true, 'action' => 'post_deleted', 'post_id' => $postId]);
        break;

    // ── Elimina qualsiasi commento (admin) ─────────────────────────────────
    case 'delete_comment':
        $commentId = (int) ($data['comment_id'] ?? 0);
        if ($commentId <= 0) {
            jsonResponse(['success' => false, 'error' => 'ID non valido'], 400);
        }

        $check = $sql->prepare("SELECT id FROM comments WHERE id = ?");
        $check->execute([$commentId]);
        if (!$check->fetch()) {
            jsonResponse(['success' => false, 'error' => 'Commento non trovato'], 404);
        }

        $sql->prepare("DELETE FROM comments WHERE id = ?")->execute([$commentId]);
        jsonResponse(['success' => true, 'action' => 'comment_deleted', 'comment_id' => $commentId]);
        break;

    // ── Rimuovi qualsiasi like (admin) ─────────────────────────────────────
    case 'delete_like':
        $postId = (int) ($data['post_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        if ($postId <= 0 || $userId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Parametri non validi'], 400);
        }

        $sql->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?")->execute([$postId, $userId]);

        $cnt = $sql->prepare("SELECT likes_count FROM posts WHERE id = ?");
        $cnt->execute([$postId]);
        jsonResponse(['success' => true, 'likes_count' => (int) $cnt->fetchColumn()]);
        break;

    // ── Banna utente ───────────────────────────────────────────────────────
    case 'ban_user':
        $targetId = (int) ($data['user_id'] ?? 0);
        if ($targetId <= 0 || $targetId === $currentUserId) {
            jsonResponse(['success' => false, 'error' => 'ID non valido'], 400);
        }

        $chk = $sql->prepare("SELECT is_admin FROM users WHERE id = ?");
        $chk->execute([$targetId]);
        $target = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            jsonResponse(['success' => false, 'error' => 'Utente non trovato'], 404);
        }
        if ($target['is_admin']) {
            jsonResponse(['success' => false, 'error' => 'Non puoi bannare un admin'], 403);
        }

        $sql->prepare("UPDATE users SET is_admin = -1 WHERE id = ? AND is_admin = 0")
            ->execute([$targetId]);
        jsonResponse(['success' => true, 'action' => 'user_banned']);
        break;

    // ── Promuovi utente ad admin ───────────────────────────────────────────
    case 'promote_user':
        $targetId = (int) ($data['user_id'] ?? 0);
        if ($targetId <= 0) {
            jsonResponse(['success' => false, 'error' => 'ID non valido'], 400);
        }

        $sql->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$targetId]);
        jsonResponse(['success' => true, 'action' => 'user_promoted']);
        break;

    // ── Lista tutti gli utenti ─────────────────────────────────────────────
    case 'list_users':
        $stmt = $sql->prepare("
            SELECT id, username, email, avatar_url, is_admin,
                   followers_count, following_count, created_at,
                   (SELECT COUNT(*) FROM posts WHERE user_id = users.id) AS post_count
            FROM users
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        jsonResponse(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Azione non valida'], 400);
}
