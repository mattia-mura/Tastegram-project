<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/Database.php';
$sql = Database::getInstance()->getConnection();

$query    = trim($_GET['q'] ?? '');
$tab      = $_GET['tab'] ?? 'posts'; // posts | users
$results  = [];
$hasQuery = $query !== '';

// if ($hasQuery) {
//     if ($tab === 'users') {
//         // Cerca utenti per username o bio
//         $stmt = $sql->prepare("
//             SELECT id, username, avatar_url, bio, followers_count
//             FROM users
//             WHERE (username LIKE :q OR bio LIKE :q)
//               AND username != 'ospite'
//             ORDER BY followers_count DESC
//             LIMIT 30
//         ");
//         $stmt->execute([':q' => '%' . $query . '%']);
//         $results = $stmt->fetchAll();

//         // Per ogni utente controlla se lo segui già
//         foreach ($results as &$u) {
//             $fs = $sql->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
//             $fs->execute([$currentUserId, $u['id']]);
//             $u['is_following'] = (bool) $fs->fetchColumn();
//             $u['is_self']      = ($u['id'] === $currentUserId);
//         }
//         unset($u);

//     } else {
//         // Cerca post per titolo o tipo cucina
//         $stmt = $sql->prepare("
//             SELECT p.id, p.title_work, p.image_path, p.likes_count,
//                    p.comments_count, p.rating, p.cuisine_type, p.created_at,
//                    u.username, u.avatar_url
//             FROM posts p
//             JOIN users u ON u.id = p.user_id
//             WHERE p.title_work LIKE :q OR p.cuisine_type LIKE :q OR p.content LIKE :q
//             ORDER BY p.likes_count DESC, p.created_at DESC
//             LIMIT 30
//         ");
//         $stmt->execute([':q' => '%' . $query . '%']);
//         $results = $stmt->fetchAll();
//     }
// } else {
//     // Senza query: mostra post trending (più liked) e utenti suggeriti
//     if ($tab === 'users') {
//         $stmt = $sql->prepare("
//             SELECT id, username, avatar_url, bio, followers_count
//             FROM users
//             WHERE username != 'ospite' AND id != :uid
//             ORDER BY followers_count DESC
//             LIMIT 20
//         ");
//         $stmt->execute([':uid' => $currentUserId]);
//         $results = $stmt->fetchAll();

//         foreach ($results as &$u) {
//             $fs = $sql->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
//             $fs->execute([$currentUserId, $u['id']]);
//             $u['is_following'] = (bool) $fs->fetchColumn();
//             $u['is_self']      = false;
//         }
//         unset($u);

//     } else {
//         $stmt = $sql->prepare("
//             SELECT p.id, p.title_work, p.image_path, p.likes_count,
//                    p.comments_count, p.rating, p.cuisine_type, p.created_at,
//                    u.username, u.avatar_url
//             FROM posts p
//             JOIN users u ON u.id = p.user_id
//             ORDER BY p.likes_count DESC, p.created_at DESC
//             LIMIT 30
//         ");
//         $stmt->execute();
//         $results = $stmt->fetchAll();
//     }
// }

if ($hasQuery) {
    $searchTerm = '%' . $query . '%';

    if ($tab === 'users') {
        // Cerca utenti per username o bio
        // Usiamo :q1 e :q2 perché PDO non permette lo stesso nome parametro più volte
        $stmt = $sql->prepare("
            SELECT id, username, avatar_url, bio, followers_count
            FROM users
            WHERE (username LIKE :q1 OR bio LIKE :q2)
              AND username != 'ospite'
            ORDER BY followers_count DESC
            LIMIT 30
        ");
        $stmt->execute([
            ':q1' => $searchTerm,
            ':q2' => $searchTerm
        ]);
        $results = $stmt->fetchAll();

        // Per ogni utente controlla se lo segui già
        foreach ($results as &$u) {
            $fs = $sql->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
            $fs->execute([$currentUserId, $u['id']]);
            $u['is_following'] = (bool) $fs->fetchColumn();
            $u['is_self']      = ($u['id'] === $currentUserId);
        }
        unset($u);

    } else {
        // Cerca post per titolo, tipo cucina o contenuto
        // Usiamo :q1, :q2 e :q3 per evitare l'errore Invalid parameter number
        $stmt = $sql->prepare("
            SELECT p.id, p.title_work, p.image_path, p.likes_count,
                   p.comments_count, p.rating, p.cuisine_type, p.created_at,
                   u.username, u.avatar_url
            FROM posts p
            JOIN users u ON u.id = p.user_id
            WHERE p.title_work LIKE :q1 OR p.cuisine_type LIKE :q2 OR p.content LIKE :q3
            ORDER BY p.likes_count DESC, p.created_at DESC
            LIMIT 30
        ");
        $stmt->execute([
            ':q1' => $searchTerm,
            ':q2' => $searchTerm,
            ':q3' => $searchTerm
        ]);
        $results = $stmt->fetchAll();
    }
} else {
    // Senza query: mostra post trending (più liked) e utenti suggeriti
    if ($tab === 'users') {
        $stmt = $sql->prepare("
            SELECT id, username, avatar_url, bio, followers_count
            FROM users
            WHERE username != 'ospite' AND id != :uid
            ORDER BY followers_count DESC
            LIMIT 20
        ");
        $stmt->execute([':uid' => $currentUserId]);
        $results = $stmt->fetchAll();

        foreach ($results as &$u) {
            $fs = $sql->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
            $fs->execute([$currentUserId, $u['id']]);
            $u['is_following'] = (bool) $fs->fetchColumn();
            $u['is_self']      = false;
        }
        unset($u);

    } else {
        // Mostra i post con più like (Trending)
        $stmt = $sql->prepare("
            SELECT p.id, p.title_work, p.image_path, p.likes_count,
                   p.comments_count, p.rating, p.cuisine_type, p.created_at,
                   u.username, u.avatar_url
            FROM posts p
            JOIN users u ON u.id = p.user_id
            ORDER BY p.likes_count DESC, p.created_at DESC
            LIMIT 30
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();
    }
}

function timeAgo(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'ora';
    if ($diff < 3600)   return floor($diff/60) . 'm fa';
    if ($diff < 86400)  return floor($diff/3600) . 'h fa';
    if ($diff < 604800) return floor($diff/86400) . 'g fa';
    return date('d/m/Y', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esplora — Tastegram</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --tc:#C1440E; --or:#E2621B; --am:#F0882A; --cr:#FDF6EE; --br:#3D1A06; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fafafa; font-family: 'DM Sans', sans-serif; padding-bottom: 70px; }

        /* ── NAVBAR ── */
        .navbar {
            position: sticky; top: 0; z-index: 100;
            background: #fff; border-bottom: 1px solid #eee;
            padding: 10px 16px; display: flex; flex-direction: column; gap: 10px;
        }
        .navbar-top { display: flex; align-items: center; gap: 10px; }
        .nav-logo { font-size: 20px; font-weight: 700; color: var(--tc); text-decoration: none; }

        /* Search bar */
        .search-form { display: flex; align-items: center; flex: 1; gap: 8px; }
        .search-input-wrap { flex: 1; position: relative; }
        .search-input {
            width: 100%; padding: 10px 16px 10px 38px;
            border: 2px solid #f0f0f0; border-radius: 22px;
            font-size: 14px; font-family: 'DM Sans', sans-serif;
            transition: border-color .2s; background: #f5f5f5;
        }
        .search-input:focus { outline: none; border-color: var(--or); background: var(--cr); }
        .search-icon {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%); font-size: 15px; pointer-events: none;
        }
        .search-clear {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%); font-size: 15px;
            background: none; border: none; cursor: pointer;
            color: #bbb; display: none;
        }

        /* Tab */
        .tabs { display: flex; gap: 0; border-bottom: 1px solid #f0f0f0; }
        .tab-btn {
            flex: 1; padding: 8px; text-align: center;
            font-size: 13px; font-weight: 600; color: #aaa;
            text-decoration: none; border-bottom: 2px solid transparent;
            transition: all .2s;
        }
        .tab-btn.active { color: var(--tc); border-bottom-color: var(--tc); }

        /* ── GRIGLIA POST ── */
        .content-wrap { max-width: 480px; margin: 0 auto; }

        .post-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 2px; padding: 2px;
        }
        .grid-item {
            aspect-ratio: 1/1; overflow: hidden; position: relative;
            cursor: pointer; background: var(--cr);
            display: flex; align-items: center; justify-content: center;
        }
        .grid-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .grid-placeholder { font-size: 34px; }
        .grid-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,0); color: #fff;
            display: flex; align-items: center; justify-content: center;
            gap: 12px; font-size: 12px; font-weight: 700;
            transition: background .2s;
        }
        .grid-item:hover .grid-overlay { background: rgba(0,0,0,0.40); }

        /* Etichetta cucina sulla miniatura */
        .grid-badge {
            position: absolute; bottom: 5px; left: 5px;
            background: rgba(0,0,0,0.55); color: #fff;
            font-size: 10px; font-weight: 600; padding: 2px 7px;
            border-radius: 10px; pointer-events: none;
        }

        /* ── LISTA UTENTI ── */
        .user-list { padding: 8px 0; }
        .user-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-bottom: 1px solid #f5f5f5;
            background: #fff;
        }
        .user-avatar {
            width: 50px; height: 50px; border-radius: 50%;
            overflow: hidden; border: 2px solid var(--or);
            flex-shrink: 0; cursor: pointer; text-decoration: none;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-info { flex: 1; min-width: 0; }
        .user-username {
            font-size: 14px; font-weight: 700; color: var(--br);
            text-decoration: none; display: block;
        }
        .user-username:hover { text-decoration: underline; }
        .user-bio {
            font-size: 12px; color: #888; margin-top: 2px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .user-followers { font-size: 11px; color: #bbb; margin-top: 2px; }

        .btn-follow-sm {
            padding: 6px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 600; cursor: pointer;
            border: 1.5px solid; font-family: 'DM Sans', sans-serif;
            transition: all .2s; flex-shrink: 0;
        }
        .btn-follow-sm.follow { background: var(--tc); border-color: var(--tc); color: #fff; }
        .btn-follow-sm.following { background: #f0f0f0; border-color: #ddd; color: #555; }
        .btn-follow-sm.self { display: none; }

        /* ── SEZIONE LABEL ── */
        .section-label {
            padding: 12px 16px 6px;
            font-size: 12px; font-weight: 700; color: #bbb;
            text-transform: uppercase; letter-spacing: .5px;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center; padding: 60px 24px; color: #bbb;
        }
        .empty-state .emoji { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; line-height: 1.6; }

        /* ── BOTTOM NAV ── */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0;
            height: 58px; background: #fff;
            border-top: 1px solid #eee; display: flex; z-index: 100;
        }
        .bn-item {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 2px;
            text-decoration: none; color: #aaa;
            border: none; background: none; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }
        .bn-item .bn-icon { font-size: 22px; }
        .bn-item .bn-label { font-size: 10px; }
        .bn-item.active { color: var(--tc); }
        .bn-item.active .bn-label { font-weight: 700; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-top">
        <a href="feed.php" class="nav-logo">tastegram</a>
        <form class="search-form" method="GET" action="explore.php" id="search-form">
            <div class="search-input-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" id="search-input" class="search-input"
                       placeholder="Cerca piatti, ricette, utenti..."
                       value="<?= htmlspecialchars($query) ?>"
                       autocomplete="off">
                <button type="button" class="search-clear" id="clear-btn" onclick="clearSearch()">✕</button>
            </div>
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        </form>
    </div>

    <!-- Tab Posts / Utenti -->
    <div class="tabs">
        <a href="explore.php?tab=posts<?= $hasQuery ? '&q='.urlencode($query) : '' ?>"
           class="tab-btn <?= $tab === 'posts' ? 'active' : '' ?>">🍽️ Piatti</a>
        <a href="explore.php?tab=users<?= $hasQuery ? '&q='.urlencode($query) : '' ?>"
           class="tab-btn <?= $tab === 'users' ? 'active' : '' ?>">👤 Utenti</a>
    </div>
</nav>

<div class="content-wrap">

    <?php if ($tab === 'posts'): ?>

        <!-- SEZIONE LABEL -->
        <div class="section-label">
            <?= $hasQuery
                ? '🔍 Risultati per "' . htmlspecialchars($query) . '"'
                : '🔥 Post in evidenza' ?>
        </div>

        <?php if (empty($results)): ?>
            <div class="empty-state">
                <div class="emoji">🍳</div>
                <p>Nessun piatto trovato per<br><strong>"<?= htmlspecialchars($query) ?>"</strong></p>
            </div>
        <?php else: ?>
            <div class="post-grid">
                <?php foreach ($results as $p): ?>
                    <div class="grid-item" onclick="window.location='post.php?id=<?= $p['id'] ?>'">
                        <?php if (!empty($p['image_path'])): ?>
                            <img src="../img/uploads/foto/<?= htmlspecialchars($p['image_path']) ?>"
                                 onerror="this.style.display='none'"
                                 alt="<?= htmlspecialchars($p['title_work']) ?>">
                        <?php else: ?>
                            <div class="grid-placeholder">🍽️</div>
                        <?php endif; ?>

                        <div class="grid-overlay">
                            <span>❤️ <?= $p['likes_count'] ?></span>
                            <span>💬 <?= $p['comments_count'] ?></span>
                        </div>

                        <?php if (!empty($p['cuisine_type'])): ?>
                            <div class="grid-badge"><?= htmlspecialchars($p['cuisine_type']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>

        <!-- LISTA UTENTI -->
        <div class="section-label">
            <?= $hasQuery
                ? '🔍 Utenti trovati per "' . htmlspecialchars($query) . '"'
                : '👥 Utenti da scoprire' ?>
        </div>

        <?php if (empty($results)): ?>
            <div class="empty-state">
                <div class="emoji">👤</div>
                <p>Nessun utente trovato per<br><strong>"<?= htmlspecialchars($query) ?>"</strong></p>
            </div>
        <?php else: ?>
            <div class="user-list">
                <?php foreach ($results as $u): ?>
                    <div class="user-item">
                        <a href="profile.php?user=<?= urlencode($u['username']) ?>" class="user-avatar">
                            <img src="../img/<?= htmlspecialchars($u['avatar_url'] ?: 'default_avatar.png') ?>"
                                 onerror="this.src='../img/default_avatar.png'"
                                 alt="@<?= htmlspecialchars($u['username']) ?>">
                        </a>
                        <div class="user-info">
                            <a href="profile.php?user=<?= urlencode($u['username']) ?>" class="user-username">
                                @<?= htmlspecialchars($u['username']) ?>
                            </a>
                            <?php if (!empty($u['bio'])): ?>
                                <div class="user-bio"><?= htmlspecialchars(mb_strimwidth($u['bio'], 0, 60, '…')) ?></div>
                            <?php endif; ?>
                            <div class="user-followers"><?= number_format($u['followers_count']) ?> follower</div>
                        </div>
                        <?php if (!$isGuest && !$u['is_self']): ?>
                            <button class="btn-follow-sm <?= $u['is_following'] ? 'following' : 'follow' ?>"
                                    id="follow-<?= $u['id'] ?>"
                                    onclick="toggleFollow(<?= $u['id'] ?>)">
                                <?= $u['is_following'] ? '✓ Seguito' : '+ Segui' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
    <a href="feed.php" class="bn-item">
        <span class="bn-icon">🏠</span><span class="bn-label">Home</span>
    </a>
    <a href="explore.php" class="bn-item active">
        <span class="bn-icon">🔍</span><span class="bn-label">Esplora</span>
    </a>
    <?php if (!$isGuest): ?>
    <a href="new_post.php" class="bn-item">
        <span class="bn-icon" style="font-size:30px;color:var(--tc)">＋</span>
        <span class="bn-label">Pubblica</span>
    </a>
    <?php else: ?>
    <a href="../backend/login/registration.php" class="bn-item">
        <span class="bn-icon" style="font-size:30px;color:#ccc">＋</span>
        <span class="bn-label">Pubblica</span>
    </a>
    <?php endif; ?>
    <a href="notifications.php" class="bn-item">
        <span class="bn-icon">🔔</span><span class="bn-label">Notifiche</span>
    </a>
    <a href="profile.php?user=<?= urlencode($currentUsername) ?>" class="bn-item">
        <span class="bn-icon">👤</span><span class="bn-label">Profilo</span>
    </a>
</nav>

<script>
// Mostra/nascondi pulsante clear
const searchInput = document.getElementById('search-input');
const clearBtn    = document.getElementById('clear-btn');

function updateClearBtn() {
    clearBtn.style.display = searchInput.value.length > 0 ? 'block' : 'none';
}
searchInput.addEventListener('input', updateClearBtn);
updateClearBtn();

// Submit automatico dopo 500ms di pausa
let searchTimer;
searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    if (this.value.length === 0) {
        document.getElementById('search-form').submit();
        return;
    }
    if (this.value.length >= 2) {
        searchTimer = setTimeout(() => {
            document.getElementById('search-form').submit();
        }, 500);
    }
});

function clearSearch() {
    searchInput.value = '';
    clearBtn.style.display = 'none';
    document.getElementById('search-form').submit();
}

// Follow/unfollow inline
function toggleFollow(userId) {
    fetch('../backend/api/follow.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ target_id: userId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('follow-' + userId);
            btn.textContent     = data.following ? '✓ Seguito' : '+ Segui';
            btn.className       = 'btn-follow-sm ' + (data.following ? 'following' : 'follow');
        }
    });
}
</script>

</body>
</html>
