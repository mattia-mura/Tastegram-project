<!-- ?php
// backend/api/gnews.php
require_once __DIR__ . '/../../config/api_bootstrap.php';

define('GNEWS_KEY',  'LA_TUA_API_KEY_QUI'); // <-- gnews.io chiave gratuita
define('GNEWS_BASE', 'https://gnews.io/api/v4');

$action = $_GET['action'] ?? 'food_news';

switch ($action) {

    case 'food_news':
        $lang = in_array($_GET['lang'] ?? 'it', ['it','en']) ? ($_GET['lang'] ?? 'it') : 'it';
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $url = GNEWS_BASE . '/search?' . http_build_query([
            'q'       => $lang === 'it' ? 'cucina OR ricette OR cibo OR gastronomia' : 'food OR recipes OR cooking OR cuisine',
            'lang'    => $lang,
            'country' => $lang === 'it' ? 'it' : 'us',
            'max'     => 10,
            'page'    => $page,
            'sortby'  => 'publishedAt',
            'apikey'  => GNEWS_KEY,
        ]);

        $data = gnewsFetch($url);

        if (!$data) {
            jsonResponse(['success' => false, 'error' => 'Impossibile contattare GNews. Controlla che allow_url_fopen sia attivo in php.ini o che cURL sia abilitato.'], 503);
        }

        // Errore API (es. chiave non valida)
        if (isset($data['errors'])) {
            jsonResponse(['success' => false, 'error' => implode(', ', $data['errors'])], 401);
        }

        $articles = array_map(function($a) {
            return [
                'title'       => $a['title']          ?? '',
                'description' => $a['description']    ?? '',
                'url'         => $a['url']             ?? '',
                'image'       => $a['image']           ?? '',
                'publishedAt' => $a['publishedAt']     ?? '',
                'source'      => $a['source']['name']  ?? '',
            ];
        }, $data['articles'] ?? []);

        jsonResponse([
            'success'       => true,
            'articles'      => $articles,
            'totalArticles' => $data['totalArticles'] ?? 0,
            'page'          => $page,
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Azione non valida'], 400);
}

// ── Helper: prova cURL poi file_get_contents ───────────────────────────────
function gnewsFetch(string $url): ?array {

    // Tentativo 1: cURL (funziona sempre su XAMPP)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false, // necessario su XAMPP locale
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw !== false && $code === 200) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        }
    }

    // Tentativo 2: file_get_contents (se allow_url_fopen = On)
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'header'  => "Accept: application/json\r\nUser-Agent: Tastegram/1.0",
            ]
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        }
    }

    error_log('[Tastegram] GNews: né cURL né allow_url_fopen disponibili. URL: ' . $url);
    return null;
} -->

<?php
// backend/api/gnews.php
// Proxy sicuro verso GNews API — la chiave non viene mai esposta al browser.
// Registrati su https://gnews.io per la chiave gratuita (100 req/giorno).
require_once __DIR__ . '/../../config/api_bootstrap.php';

define('GNEWS_KEY',  'LA_TUA_API_KEY_QUI'); // <-- sostituisci con la tua chiave
define('GNEWS_BASE', 'https://gnews.io/api/v4');

$action = $_GET['action'] ?? 'food_news';

switch ($action) {

    // ── Notizie food (default) ─────────────────────────────────────────────
    // GET /api/gnews.php?action=food_news&lang=it
    case 'food_news':
        $lang  = in_array($_GET['lang'] ?? 'it', ['it','en']) ? ($_GET['lang'] ?? 'it') : 'it';
        $page  = max(1, (int) ($_GET['page'] ?? 1));

        $url = GNEWS_BASE . '/search?' . http_build_query([
            'q'        => 'cucina OR ricette OR food OR gastronomia',
            'lang'     => $lang,
            'country'  => $lang === 'it' ? 'it' : 'us',
            'max'      => 10,
            'page'     => $page,
            'sortby'   => 'publishedAt',
            'apikey'   => GNEWS_KEY,
        ]);

        $data = gnewsGet($url);
        if (!$data) {
            jsonResponse(['success' => false, 'error' => 'Notizie non disponibili'], 503);
        }

        // Normalizza gli articoli
        $articles = array_map(function($a) {
            return [
                'id'          => $a['id']          ?? md5($a['url'] ?? ''),
                'title'       => $a['title']        ?? '',
                'description' => $a['description']  ?? '',
                'url'         => $a['url']           ?? '',
                'image'       => $a['image']         ?? '',
                'publishedAt' => $a['publishedAt']   ?? '',
                'source'      => $a['source']['name'] ?? '',
                'sourceUrl'   => $a['source']['url']  ?? '',
            ];
        }, $data['articles'] ?? []);

        jsonResponse([
            'success'       => true,
            'articles'      => $articles,
            'totalArticles' => $data['totalArticles'] ?? 0,
            'page'          => $page,
        ]);
        break;

    // ── Top headlines food ─────────────────────────────────────────────────
    // GET /api/gnews.php?action=headlines
    case 'headlines':
        $url = GNEWS_BASE . '/top-headlines?' . http_build_query([
            'topic'   => 'health',     // il più vicino al food disponibile gratis
            'lang'    => 'it',
            'country' => 'it',
            'max'     => 6,
            'apikey'  => GNEWS_KEY,
        ]);

        $data = gnewsGet($url);
        $articles = array_map(function($a) {
            return [
                'title'       => $a['title']         ?? '',
                'description' => $a['description']   ?? '',
                'url'         => $a['url']            ?? '',
                'image'       => $a['image']          ?? '',
                'publishedAt' => $a['publishedAt']    ?? '',
                'source'      => $a['source']['name'] ?? '',
            ];
        }, $data['articles'] ?? []);

        jsonResponse(['success' => true, 'articles' => $articles]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Azione non valida'], 400);
}

// ── Helper HTTP ────────────────────────────────────────────────────────────
function gnewsGet(string $url): ?array {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 6,
            'header'  => "Accept: application/json\r\nUser-Agent: Tastegram/1.0",
        ]
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        error_log('[Tastegram] GNews request failed: ' . $url);
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}
