<?php
/**
 * Alumni Atlas — where RJIT alumni live and work.
 *
 * GET api/alumni_atlas.php
 *   ?branch=CSE&year_from=2015&year_to=2024&company=google   (all optional)
 *   ?city=Bengaluru                                            (adds the people list for one city)
 *
 * Aggregates are computed from active alumni. Private profiles are counted in
 * totals but never listed by name (admins see everyone, like the directory).
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../config/DbCompat.php';
include_once '../middleware/Auth.php';
include_once '../helpers/Cache.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$viewerId = $auth->validateRequest();

function atlas_clean(string $s, int $max = 80): string
{
    return mb_substr(trim(preg_replace('/\s+/', ' ', $s)), 0, $max);
}

try {
    $roleStmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
    $roleStmt->execute(['uid' => $viewerId]);
    $isAdmin = ((string)$roleStmt->fetchColumn()) === 'admin';

    $branch = atlas_clean((string)($_GET['branch'] ?? ''));
    $company = atlas_clean((string)($_GET['company'] ?? ''));
    $yearFrom = (int)($_GET['year_from'] ?? 0);
    $yearTo = (int)($_GET['year_to'] ?? 0);
    $cityFilter = atlas_clean((string)($_GET['city'] ?? ''));

    $where = "u.role = 'alumni' AND u.status = 'active'";
    $params = [];
    if ($branch !== '') {
        $where .= " AND LOWER(COALESCE(p.branch, '')) LIKE LOWER(:branch)";
        $params['branch'] = '%' . $branch . '%';
    }
    if ($company !== '') {
        $where .= " AND LOWER(COALESCE(p.current_company, '')) LIKE LOWER(:company)";
        $params['company'] = '%' . $company . '%';
    }
    if ($yearFrom >= 1950 && $yearFrom <= 2100) {
        $where .= " AND p.graduation_year >= :yf";
        $params['yf'] = $yearFrom;
    }
    if ($yearTo >= 1950 && $yearTo <= 2100) {
        $where .= " AND p.graduation_year <= :yt";
        $params['yt'] = $yearTo;
    }

    $cacheKey = Cache::key('atlas', md5(json_encode([$where, $params, $cityFilter, $isAdmin])));
    $cached = Cache::get($cacheKey);
    if ($cached !== null) {
        header('X-Cache: HIT');
        echo $cached;
        exit;
    }
    ob_start();

    $stmt = $db->prepare("
        SELECT u.user_id, p.full_name, p.location_city, p.location_country, p.current_company,
               p.job_role, p.designation, p.graduation_year, p.branch,
               COALESCE(p.is_private, " . (db_is_pgsql($db) ? 'false' : '0') . ") AS is_private
        FROM users u
        JOIN profiles p ON p.user_id = u.user_id
        WHERE $where
        LIMIT 20000
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $geo = json_decode((string)@file_get_contents(dirname(__DIR__) . '/assets/data/cities.json'), true) ?: ['cities' => [], 'aliases' => []];
    $lookup = [];
    foreach ($geo['cities'] as $name => $ll) {
        $lookup[strtolower($name)] = $name;
    }
    foreach ($geo['aliases'] as $alias => $name) {
        $lookup[strtolower($alias)] = $name;
    }
    $resolveCity = function (?string $raw) use ($lookup): ?string {
        $raw = strtolower(trim((string)$raw));
        if ($raw === '') {
            return null;
        }
        $raw = preg_replace('/[^a-z\s]/', ' ', $raw);
        $raw = trim(preg_replace('/\s+/', ' ', $raw));
        if (isset($lookup[$raw])) {
            return $lookup[$raw];
        }
        // "Bengaluru, Karnataka" / "Pune India" → first matching word group
        foreach ([3, 2, 1] as $n) {
            $parts = explode(' ', $raw);
            for ($i = 0; $i + $n <= count($parts); $i++) {
                $cand = implode(' ', array_slice($parts, $i, $n));
                if (isset($lookup[$cand])) {
                    return $lookup[$cand];
                }
            }
        }
        return null;
    };

    $cities = [];
    $unmapped = [];
    $companies = [];
    $batches = [];
    $branches = [];
    $countries = [];
    $people = [];

    foreach ($rows as $r) {
        $city = $resolveCity($r['location_city'] ?? '');
        $comp = atlas_clean((string)($r['current_company'] ?? ''), 120);
        $year = (int)($r['graduation_year'] ?? 0);
        $br = atlas_clean((string)($r['branch'] ?? ''), 120);
        $country = atlas_clean((string)($r['location_country'] ?? ''), 80);

        if ($city !== null) {
            if (!isset($cities[$city])) {
                $cities[$city] = ['city' => $city, 'lat' => $geo['cities'][$city][0], 'lng' => $geo['cities'][$city][1], 'count' => 0, 'companies' => []];
            }
            $cities[$city]['count']++;
            if ($comp !== '') {
                $cities[$city]['companies'][$comp] = ($cities[$city]['companies'][$comp] ?? 0) + 1;
            }
        } elseif (trim((string)$r['location_city']) !== '') {
            $raw = ucwords(strtolower(atlas_clean((string)$r['location_city'], 60)));
            $unmapped[$raw] = ($unmapped[$raw] ?? 0) + 1;
        }
        if ($comp !== '') {
            $key = mb_strtolower($comp);
            $companies[$key] = $companies[$key] ?? ['company' => $comp, 'count' => 0];
            $companies[$key]['count']++;
        }
        if ($year > 0) {
            $batches[$year] = ($batches[$year] ?? 0) + 1;
        }
        if ($br !== '') {
            $branches[$br] = ($branches[$br] ?? 0) + 1;
        }
        if ($country !== '') {
            $countries[mb_strtolower($country)] = true;
        }

        $private = filter_var($r['is_private'], FILTER_VALIDATE_BOOLEAN);
        if ($cityFilter !== '' && $city !== null && strcasecmp($city, $cityFilter) === 0 && (!$private || $isAdmin)) {
            $people[] = [
                'user_id' => (int)$r['user_id'],
                'name' => (string)($r['full_name'] ?: 'RJIT Alumnus'),
                'company' => $comp ?: null,
                'role' => $r['job_role'] ?: ($r['designation'] ?: null),
                'year' => $year ?: null,
                'branch' => $br ?: null,
            ];
        }
    }

    foreach ($cities as &$c) {
        arsort($c['companies']);
        $c['top_companies'] = array_slice(array_keys($c['companies']), 0, 3);
        unset($c['companies']);
    }
    unset($c);
    usort($cities, fn ($a, $b) => $b['count'] <=> $a['count']);
    $companies = array_values($companies);
    usort($companies, fn ($a, $b) => $b['count'] <=> $a['count']);
    krsort($batches);
    arsort($branches);
    arsort($unmapped);
    usort($people, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

    echo json_encode([
        'success' => true,
        'data' => [
            'totals' => [
                'alumni' => count($rows),
                'mapped' => array_sum(array_column($cities, 'count')),
                'cities' => count($cities),
                'companies' => count($companies),
                'countries' => max(1, count($countries)),
                'batches' => count($batches),
            ],
            'cities' => array_values($cities),
            'unmapped' => array_map(fn ($k, $v) => ['city' => $k, 'count' => $v], array_keys(array_slice($unmapped, 0, 30, true)), array_slice($unmapped, 0, 30, true)),
            'companies' => array_slice($companies, 0, 40),
            'batches' => array_map(fn ($y, $n) => ['year' => $y, 'count' => $n], array_keys($batches), $batches),
            'branches' => array_map(fn ($b, $n) => ['branch' => $b, 'count' => $n], array_keys($branches), $branches),
            'people' => $cityFilter !== '' ? array_slice($people, 0, 200) : null,
        ],
    ]);
    $json = (string)ob_get_flush();
    Cache::set($cacheKey, $json, 120);
} catch (Throwable $e) {
    error_log('alumni_atlas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load the alumni atlas right now.']);
}
