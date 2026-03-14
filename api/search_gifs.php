<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

function search_commons_gifs(string $query, int $limit = 24): array
{
    $limit = max(1, min($limit, 40));
    $query = trim($query);
    if ($query === '') {
        $query = 'reaction';
    }
    if (stripos($query, 'gif') === false) {
        $query .= ' gif';
    }

    $search = 'filemime:gif ' . $query;
    $params = [
        'action' => 'query',
        'generator' => 'search',
        'gsrsearch' => $search,
        'gsrnamespace' => 6,
        'gsrlimit' => $limit,
        'prop' => 'imageinfo',
        'iiprop' => 'url',
        'iiurlwidth' => 360,
        'format' => 'json'
    ];

    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query($params);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", [
                'User-Agent: RJITAlumniPortal/1.0 (admin@rjit.ac.in)',
                'Accept: application/json'
            ]),
            'timeout' => 15
        ]
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        throw new RuntimeException('GIF provider request failed.');
    }

    $payload = json_decode($raw, true);
    $pages = $payload['query']['pages'] ?? [];
    if (!is_array($pages)) {
        return [];
    }

    $results = [];
    foreach ($pages as $page) {
        $title = (string)($page['title'] ?? '');
        $imageInfo = $page['imageinfo'][0] ?? null;
        $originalUrl = (string)($imageInfo['url'] ?? '');
        if ($originalUrl === '' || stripos($originalUrl, '.gif') === false) {
            continue;
        }
        $results[] = [
            'id' => (int)($page['pageid'] ?? 0),
            'title' => preg_replace('/^File:/i', '', $title),
            'preview_url' => (string)($imageInfo['thumburl'] ?? $originalUrl),
            'url' => $originalUrl,
            'source' => 'wikimedia-commons'
        ];
    }

    return array_values($results);
}

try {
    $query = trim((string)($_GET['q'] ?? ''));
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24;
    $results = search_commons_gifs($query, $limit);

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'query' => $query,
        'data' => $results
    ]);
} catch (Throwable $e) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Unable to load GIFs right now.'
    ]);
}
