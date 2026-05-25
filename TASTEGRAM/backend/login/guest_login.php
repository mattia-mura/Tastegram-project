<!-- ?php
// Login diretto per l'account ospite — nessuna password da verificare ['ospite', 'ospite@tastegram.it', '$2y$10$xyz', 'Account per visitatori']
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';
$sql = Database::getInstance()->getConnection();

$stmt = $sql->prepare("SELECT id, username, avatar_url FROM users WHERE username = 'ospite' LIMIT 1");
$stmt->execute();
$guest = $stmt->fetch();

if ($guest) {
    $_SESSION['user_id']    = (int) $guest['id'];
    $_SESSION['username']   = $guest['username'];
    $_SESSION['avatar_url'] = $guest['avatar_url'] ?? 'default_avatar.png';
    header('Location: ../../frontend/feed.php');
} else {
    header('Location: login.php?error=guest');
}
exit; -->
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, username, avatar_url FROM users WHERE username = 'ospite'");
    $stmt->execute();
    $guest = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($guest) {
        echo "Utente trovato! ID: " . $guest['id'] . "<br>";
        $_SESSION['user_id']    = (int) $guest['id'];
        $_SESSION['username']   = $guest['username'];
        $_SESSION['avatar_url'] = $guest['avatar_url'];
        
        echo "Sessione impostata. Reindirizzamento in corso...";
        // Commenta la riga sotto per vedere i messaggi sopra
        header('Refresh: 2; URL=../../frontend/feed.php'); 
    } else {
        die("ERRORE: Utente 'ospite' non trovato nella tabella 'users'.");
    }
} catch (Exception $e) {
    die("ERRORE DATABASE: " . $e->getMessage());
}