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
