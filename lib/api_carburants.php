<?php
/**
 * @file api_carburants.php
 * @brief Interaction avec l'API officielle des prix des carburants.
 *
 * Source : https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/
 *          prix-des-carburants-en-france-flux-instantane-v2/records/
 *
 * L'API retourne des enregistrements JSON. Chaque enregistrement correspond
 * à une station service avec ses prix pour différents carburants.
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.inc.php';
require_once __DIR__ . '/http_helper.php';

/** URL de base de l'API carburants */
const API_CARBURANTS_URL = 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/prix-des-carburants-en-france-flux-instantane-v2/records/';

/** Nombre maximum de résultats par requête */
const API_LIMIT = 100;

/**
 * Récupère les stations service et leurs prix depuis l'API gouvernementale.
 *
 * Construit la requête selon les paramètres fournis (ville, département,
 * carburant), appelle l'API, puis normalise les résultats.
 *
 * @param string $ville     Nom de la ville (peut être vide)
 * @param string $dept      Code du département (peut être vide)
 * @param string $carburant Nom du carburant à filtrer (peut être vide)
 * @return array{succes: bool, stations: array, message: string}
 *         'succes' : true si la requête a réussi
 *         'stations' : tableau de stations normalisées
 *         'message' : message d'erreur éventuel
 */
function recuperer_stations(string $ville, string $dept, string $carburant = ''): array
{
    // Construction du filtre "where" pour l'API
    $filtres = [];

    if (!empty($ville)) {
        // Recherche insensible à la casse sur le nom de ville
        $ville_escaped = str_replace("'", "\\'", strtolower($ville));
        $filtres[] = 'lower(ville) like "%' . $ville_escaped . '%"';
    }

    if (!empty($dept)) {
        $dept_escaped = str_replace("'", "\\'", $dept);
        $filtres[]    = 'code_departement="' . $dept_escaped . '"';
    }

    // Paramètres de la requête API
    $params = [
        'limit'    => API_LIMIT,
        'order_by' => 'ville asc',
    ];

    if (!empty($filtres)) {
        $params['where'] = implode(' AND ', $filtres);
    }

    $url = API_CARBURANTS_URL . '?' . http_build_query($params);

    // Appel HTTP
    $json = fetchJsonFromUrl($url);

    if ($json === null) {
        return [
            'succes'   => false,
            'stations' => [],
            'message'  => 'Impossible de contacter l\'API des carburants. Vérifiez votre connexion.',
        ];
    }

    $data = json_decode($json, true);

    if (!is_array($data) || !isset($data['results'])) {
        return [
            'succes'   => false,
            'stations' => [],
            'message'  => 'Réponse de l\'API invalide ou inattendue.',
        ];
    }

    // Normalisation des données
    $stations = normaliser_stations($data['results'], $carburant);

    // Marquage des prix minimum par carburant
    $stations = marquer_prix_minimum($stations);

    return [
        'succes'   => true,
        'stations' => $stations,
        'message'  => '',
    ];
}

/**
 * Normalise les enregistrements bruts de l'API en un tableau uniforme.
 *
 * Extrait pour chaque station :
 *  - son nom, adresse, ville
 *  - ses prix par carburant (SP95, SP98, E10, E85, Gazole, GPLc)
 *  - ses services disponibles
 *
 * Si un filtre carburant est précisé, ne garde que les stations
 * proposant ce carburant.
 *
 * @param array  $records   Tableau brut des enregistrements API
 * @param string $carburant Filtre carburant (vide = tous)
 * @return array<int, array> Tableau de stations normalisées
 */
function normaliser_stations(array $records, string $carburant = ''): array
{
    $stations = [];

    // Correspondance entre les noms de carburant affichés et les champs API
    $champs_carburant = [
        'SP95'   => 'prix_sp95',
        'SP98'   => 'prix_sp98',
        'E10'    => 'prix_e10',
        'E85'    => 'prix_e85',
        'Gazole' => 'prix_gazole',
        'GPLc'   => 'prix_gplc',
    ];

    foreach ($records as $rec) {
        if (!is_array($rec)) {
            continue;
        }

        // Extraction des prix disponibles
        $prix = [];
        foreach ($champs_carburant as $nom_affiche => $champ_api) {
            $valeur = $rec[$champ_api] ?? null;
            if ($valeur !== null && $valeur !== '' && (float)$valeur > 0) {
                $prix[$nom_affiche] = (float)$valeur;
            }
        }

        // Filtrage par carburant si précisé
        if (!empty($carburant) && !isset($prix[$carburant])) {
            continue;
        }

        // Pas de prix du tout → on ignore la station
        if (empty($prix)) {
            continue;
        }

        // Extraction des services (champ "services_service" = chaîne séparée par "//")
        $services = [];
        if (!empty($rec['services_service'])) {
            $services = array_filter(
                array_map('trim', explode('//', (string)$rec['services_service']))
            );
            $services = array_values($services);
        }

        $stations[] = [
            'nom'          => $rec['nom'] ?? ($rec['enseignes'] ?? 'Station inconnue'),
            'marque'       => $rec['enseignes'] ?? '',
            'adresse'      => $rec['adresse'] ?? '',
            'ville'        => $rec['ville'] ?? '',
            'code_postal'  => $rec['cp'] ?? '',
            'latitude'     => $rec['geom']['lat'] ?? null,
            'longitude'    => $rec['geom']['lon'] ?? null,
            'prix'         => $prix,
            'services'     => $services,
            'est_minimum'  => [], // sera rempli par marquer_prix_minimum()
        ];
    }

    return $stations;
}

/**
 * Marque pour chaque station si son prix est le minimum de la colonne.
 *
 * Permet de mettre en évidence visuellement le prix le plus bas
 * par type de carburant dans le tableau des résultats.
 *
 * @param array $stations Tableau de stations normalisées
 * @return array Même tableau avec la clé 'est_minimum' renseignée
 */
function marquer_prix_minimum(array $stations): array
{
    // 1. Calculer les prix minimums par carburant
    $minimums = [];
    foreach ($stations as $station) {
        foreach ($station['prix'] as $carb => $prix) {
            if (!isset($minimums[$carb]) || $prix < $minimums[$carb]) {
                $minimums[$carb] = $prix;
            }
        }
    }

    // 2. Marquer chaque station
    foreach ($stations as &$station) {
        foreach ($station['prix'] as $carb => $prix) {
            $station['est_minimum'][$carb] = isset($minimums[$carb]) && $prix === $minimums[$carb];
        }
    }
    unset($station); // Bonne pratique : déréférencer la variable de boucle

    return $stations;
}
