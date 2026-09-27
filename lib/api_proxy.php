<?php
/**
 * @file api_proxy.php
 * @brief Proxy serveur pour l'API gouvernementale des prix de carburants.
 *
 * Ce fichier fait transiter les requêtes vers l'API data.economie.gouv.fr
 * CÔTÉ SERVEUR, conformément aux exigences du projet :
 * "Les échanges se feront impérativement entre votre serveur Web et l'API"
 *
 * Paramètres GET acceptés :
 *  - lat    : latitude (float) pour recherche géographique
 *  - lon    : longitude (float) pour recherche géographique
 *  - rayon  : rayon en km (int, défaut 10)
 *  - dept   : code département (string)
 *  - ville  : nom de ville (string)
 *  - carb   : type de carburant (string)
 *  - limit  : nombre max de résultats (int, défaut 50)
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/lib/http_helper.php';

// ---- Headers JSON + CORS same-origin ----
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/**
 * Renvoie une réponse JSON d'erreur et termine le script.
 *
 * @param string $message Message d'erreur
 * @param int    $code    Code HTTP
 */
function erreur_json(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Récupération et validation des paramètres ----
$lat   = isset($_GET['lat'])   ? (float) $_GET['lat']   : null;
$lon   = isset($_GET['lon'])   ? (float) $_GET['lon']   : null;
$rayon = isset($_GET['rayon']) ? (int)   $_GET['rayon'] : 10;
$dept  = isset($_GET['dept'])  ? trim($_GET['dept'])     : '';
$ville = isset($_GET['ville']) ? trim($_GET['ville'])    : '';
$carb  = isset($_GET['carb'])  ? trim($_GET['carb'])     : '';
$limit = isset($_GET['limit']) ? (int)   $_GET['limit']  : 50;

// Validation rayon
if ($rayon < 1 || $rayon > 100) {
    $rayon = 10;
}

// Validation limit
if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// ---- Construction de l'URL API ----
$api_base = 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/'
          . 'prix-des-carburants-en-france-flux-instantane-v2/records/';

$params = ['limit' => $limit];

// Recherche géographique par coordonnées GPS
if ($lat !== null && $lon !== null) {
    if ($lat < 41.0 || $lat > 51.5 || $lon < -5.5 || $lon > 10.0) {
        erreur_json('Coordonnées hors de France métropolitaine.');
    }
    $rayon_m = $rayon * 1000; // km → mètres
    $params['where'] = sprintf(
        "distance(geom, geom'POINT(%f %f)', %dm)",
        $lon, $lat, $rayon_m
    );
    $params['order_by'] = sprintf(
        "distance(geom, geom'POINT(%f %f)')",
        $lon, $lat
    );
}
// Recherche par département
elseif (!empty($dept)) {
    $dept_clean = preg_replace('/[^0-9A-Za-z]/', '', $dept);
    $params['refine'] = 'code_departement:' . $dept_clean;
    if (!empty($ville)) {
        $ville_clean = addslashes(strip_tags($ville));
        $params['where'] = 'search(ville, "' . $ville_clean . '")';
    }
    $params['order_by'] = 'ville asc';
}
// Recherche par ville seule
elseif (!empty($ville)) {
    $ville_clean = addslashes(strip_tags($ville));
    $params['where'] = 'search(ville, "' . $ville_clean . '")';
    $params['order_by'] = 'ville asc';
} else {
    erreur_json('Paramètres insuffisants : fournissez lat/lon, dept ou ville.');
}

$url = $api_base . '?' . http_build_query($params);

// ---- Appel API côté serveur ----
$json_brut = fetchJsonFromUrl($url);

if ($json_brut === null) {
    erreur_json('Impossible de contacter l\'API gouvernementale.', 503);
}

$data = json_decode($json_brut, true);

if (!is_array($data)) {
    erreur_json('Réponse API invalide.', 502);
}

// ---- Filtrage optionnel par carburant ----
$champs_carb = [
    'SP95'   => 'sp95_prix',
    'SP98'   => 'sp98_prix',
    'E10'    => 'e10_prix',
    'E85'    => 'e85_prix',
    'Gazole' => 'gazole_prix',
    'GPLc'   => 'gplc_prix',
];

$resultats = [];

foreach (($data['results'] ?? []) as $rec) {
    // Coordonnées GPS de la station
    $geom = $rec['geom'] ?? null;
    if (!is_array($geom) || !isset($geom['lon'], $geom['lat'])) {
        continue; // Station sans coordonnées → on skip
    }

    // Construction des prix
    $prix = [];
    foreach ($champs_carb as $nom => $champ) {
        if (!empty($carb) && $nom !== $carb) continue;
        $val = $rec[$champ] ?? null;
        if ($val !== null && (float) $val > 0) {
            $prix[$nom] = round((float) $val, 3);
        }
    }

    // Si filtre carburant actif et aucun prix → skip
    if (!empty($carb) && empty($prix)) continue;
    // Si aucun prix du tout → skip
    if (empty($prix)) continue;

    // Services
    $services = [];
    if (is_array($rec['services_service'] ?? null)) {
        $services = $rec['services_service'];
    } elseif (is_string($rec['services_service'] ?? null)) {
        $services = array_filter(
            array_map('trim', explode('//', $rec['services_service']))
        );
    }

    $resultats[] = [
        'id'       => $rec['id']      ?? uniqid('sta_'),
        'nom'      => $rec['nom']     ?? 'Station inconnue',
        'adresse'  => $rec['adresse'] ?? '',
        'ville'    => $rec['ville']   ?? '',
        'cp'       => $rec['cp']      ?? '',
        'lat'      => (float) $geom['lat'],
        'lon'      => (float) $geom['lon'],
        'prix'     => $prix,
        'services' => array_values($services),
        'maj'      => $rec['prix_maj'] ?? '',
    ];
}

// ---- Réponse finale ----
echo json_encode([
    'total'     => count($resultats),
    'rayon_km'  => $rayon,
    'stations'  => $resultats,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);