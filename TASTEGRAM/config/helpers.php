<?php
// ── Path immagini ──────────────────────────────────────────────────────────
function avatarSrc(string $avatarUrl, string $base = '../img/'): string {
    if (empty($avatarUrl) || $avatarUrl === 'default_avatar.png') {
        return $base . 'default_avatar.png';
    }
    return $base . 'uploads/avatars/' . $avatarUrl;
}

function postImageSrc(?string $imagePath, string $base = '../img/'): string {
    if (empty($imagePath)) {
        return '';
    }
    return $base . 'uploads/foto/' . $imagePath;
}

// ── Tempo ──────────────────────────────────────────────────────────────────

/**
 * Converte datetime MySQL in "2h fa", "3g fa", ecc.
 */
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'ora';
    if ($diff < 3600)   return floor($diff / 60) . 'm fa';
    if ($diff < 86400)  return floor($diff / 3600) . 'h fa';
    if ($diff < 604800) return floor($diff / 86400) . 'g fa';
    return date('d/m/Y', strtotime($datetime));
}

// ── Upload ─────────────────────────────────────────────────────────────────

/**
 * Carica un'immagine sul server.
 * Lancia RuntimeException in caso di errore.
 * Restituisce il nome del file salvato.
 */
function uploadImage(array $file, string $destDir, string $prefix = 'img_', int $maxBytes = 5242880): string {
    $phpErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File troppo grande (limite server php.ini).',
        UPLOAD_ERR_FORM_SIZE  => 'File troppo grande (limite form).',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto, riprova.',
        UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante.',
        UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere su disco.',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException($phpErrors[$file['error']] ?? 'Errore upload sconosciuto.');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Immagine troppo grande. Massimo ' . round($maxBytes / 1048576) . 'MB.');
    }

    $allowed  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mimeType])) {
        throw new RuntimeException('Formato non supportato. Usa JPG, PNG o WebP.');
    }

    $ext      = $allowed[$mimeType];
    $filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
        throw new RuntimeException('Errore salvataggio. Controlla i permessi della cartella.');
    }

    return $filename;
}

/**
 * Elimina un file immagine dal disco (non elimina default_avatar.png).
 */
function deleteImage(string $filename, string $dir): void {
    if (empty($filename) || $filename === 'default_avatar.png') return;
    $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($path)) unlink($path);
}

// ── Output ─────────────────────────────────────────────────────────────────

/**
 * Escape HTML sicuro — shortcut per htmlspecialchars.
 */
function e(?string $str): string {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

/**
 * Risponde in JSON e termina (per le API).
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ── Notifiche ──────────────────────────────────────────────────────────────

/**
 * Inserisce una notifica nel DB (non notifica se stesso).
 */
function createNotification(PDO $sql, int $userId, int $actorId, string $type, ?int $postId = null): void {
    if ($userId === $actorId) return;
    $sql->prepare("
        INSERT IGNORE INTO notifications (user_id, actor_id, post_id, type)
        VALUES (?, ?, ?, ?)
    ")->execute([$userId, $actorId, $postId, $type]);
}

/**
 * Conta notifiche non lette.
 */
function countUnreadNotifications(PDO $sql, int $userId): int {
    $stmt = $sql->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
