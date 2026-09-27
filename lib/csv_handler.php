<?php
/**
 * @file csv_handler.php
 * @brief Gestion des consultations : lecture/écriture CSV et cookie.
 *
 * Ce fichier gère deux mécanismes de stockage :
 *  1. Côté serveur : fichier CSV data/consultations.csv (horodaté)
 *  2. Côté client  : cookie "derniere_ville" (ville, dept, région, date)
 *
 * Format CSV : horodatage;ville;departement;region;carburant
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.1
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.inc.php';

/** Nom du cookie de dernière ville */
const COOKIE_DERNIERE_VILLE = 'derniere_ville';

/** Durée de vie du cookie dernière ville : 30 jours */
const COOKIE_VILLE_DUREE = 30 * 24 * 3600;

/** Séparateur utilisé dans le CSV de consultations */
const CSV_SEPARATOR = ';';

/** Fichier de compteur de hits */
const HITS_FILE = 'hits.txt';

/**
 * Enregistre une consultation dans le fichier CSV côté serveur.
 *
 * Chaque ligne contient : horodatage;ville;département;région;carburant
 * Exemple : "2026-04-20 14:30:00;Paris;75;11;SP95"
 *
 * @param string $ville     Nom de la ville consultée
 * @param string $dept      Code du département
 * @param string $region    Code de la région
 * @param string $carburant Type de carburant (vide = tous)
 * @return bool true si l'écriture a réussi, false sinon
 */
function enregistrer_consultation(
    string $ville,
    string $dept,
    string $region,
    string $carburant = ''
): bool {
    if (empty(trim($ville))) {
        return false;
    }

    $fichier    = DATA_PATH . '/consultations.csv';
    $horodatage = date('Y-m-d H:i:s');

    // Nettoyer les valeurs pour éviter les injections CSV
    $nettoyer = function (string $v): string {
        return str_replace([CSV_SEPARATOR, "\n", "\r"], ' ', trim($v));
    };

    $ligne = implode(CSV_SEPARATOR, [
        $horodatage,
        $nettoyer($ville),
        $nettoyer($dept),
        $nettoyer($region),
        $nettoyer($carburant),
    ]) . "\n";

    // Créer l'en-tête si le fichier n'existe pas encore
    if (!file_exists($fichier)) {
        $entete = implode(CSV_SEPARATOR, [
            'horodatage', 'ville', 'departement', 'region', 'carburant'
        ]) . "\n";
        file_put_contents($fichier, $entete, LOCK_EX);
    }

    return file_put_contents($fichier, $ligne, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Lit toutes les consultations depuis le CSV serveur.
 *
 * @param int $limite Nombre maximum de lignes (0 = toutes)
 * @return array<int, array{horodatage:string, ville:string, departement:string, region:string, carburant:string}>
 */
function lire_consultations(int $limite = 0): array
{
    $fichier       = DATA_PATH . '/consultations.csv';
    $consultations = [];

    if (!file_exists($fichier)) {
        return $consultations;
    }

    if (($fp = fopen($fichier, 'r')) === false) {
        return $consultations;
    }

    // Ignorer l'en-tête
    fgetcsv($fp, 0, CSV_SEPARATOR, '"', '\\');

    while (($ligne = fgetcsv($fp, 0, CSV_SEPARATOR, '"', '\\')) !== false) {
        if (count($ligne) >= 4) {
            $consultations[] = [
                'horodatage'  => trim($ligne[0]),
                'ville'       => trim($ligne[1]),
                'departement' => trim($ligne[2]),
                'region'      => trim($ligne[3]),
                'carburant'   => isset($ligne[4]) ? trim($ligne[4]) : '',
            ];
        }
        if ($limite > 0 && count($consultations) >= $limite) {
            break;
        }
    }

    fclose($fp);
    return $consultations;
}

/**
 * Retourne les villes les plus consultées.
 *
 * @param int $top Nombre de villes à retourner
 * @return array<string, int> [ville => nb_consultations], trié par fréquence
 */
function top_villes_consultees(int $top = 10): array
{
    $consultations = lire_consultations();
    $comptage      = [];

    foreach ($consultations as $c) {
        $ville = $c['ville'];
        if (!empty($ville)) {
            $comptage[$ville] = ($comptage[$ville] ?? 0) + 1;
        }
    }

    arsort($comptage);
    return array_slice($comptage, 0, $top, true);
}

/**
 * Retourne les régions les plus consultées.
 *
 * @param int $top Nombre de régions à retourner
 * @return array<string, int> [region => nb_consultations], trié par fréquence
 */
function top_regions_consultees(int $top = 13): array
{
    $consultations = lire_consultations();
    $comptage      = [];

    foreach ($consultations as $c) {
        $region = $c['region'];
        if (!empty($region)) {
            $comptage[$region] = ($comptage[$region] ?? 0) + 1;
        }
    }

    arsort($comptage);
    return array_slice($comptage, 0, $top, true);
}

/**
 * Retourne la répartition des carburants recherchés.
 *
 * @return array<string, int> [carburant => nb_recherches], trié par fréquence
 */
function stats_carburants(): array
{
    $consultations = lire_consultations();
    $comptage      = [];

    foreach ($consultations as $c) {
        $carb = !empty($c['carburant']) ? $c['carburant'] : 'Tous';
        $comptage[$carb] = ($comptage[$carb] ?? 0) + 1;
    }

    arsort($comptage);
    return $comptage;
}

/**
 * Retourne les consultations par jour de la semaine.
 *
 * @return array<string, int> [jour => nb_consultations]
 */
function stats_jours_semaine(): array
{
    $jours = [
        'Lundi' => 0, 'Mardi' => 0, 'Mercredi' => 0,
        'Jeudi' => 0, 'Vendredi' => 0, 'Samedi' => 0, 'Dimanche' => 0
    ];
    $noms = ['Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi',
             'Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi',
             'Sunday'=>'Dimanche'];

    foreach (lire_consultations() as $c) {
        if (!empty($c['horodatage'])) {
            $ts  = strtotime($c['horodatage']);
            $nom = $noms[date('l', $ts)] ?? '';
            if (isset($jours[$nom])) {
                $jours[$nom]++;
            }
        }
    }

    return $jours;
}

/**
 * Retourne les consultations par tranche horaire (0-23h).
 *
 * @return array<int, int> [heure => nb_consultations]
 */
function stats_heures(): array
{
    $heures = array_fill(0, 24, 0);

    foreach (lire_consultations() as $c) {
        if (!empty($c['horodatage'])) {
            $h = (int) date('G', strtotime($c['horodatage']));
            $heures[$h]++;
        }
    }

    return $heures;
}

/**
 * Incrémente et retourne le compteur de hits (visites).
 *
 * @return int Nombre total de visites
 */
function incrementer_hits(): int
{
    $fichier = DATA_PATH . '/' . HITS_FILE;
    $nb      = 0;

    if (file_exists($fichier)) {
        $nb = (int) file_get_contents($fichier);
    }

    $nb++;
    file_put_contents($fichier, (string) $nb, LOCK_EX);
    return $nb;
}

/**
 * Lit le compteur de hits sans l'incrémenter.
 *
 * @return int Nombre total de visites
 */
function lire_hits(): int
{
    $fichier = DATA_PATH . '/' . HITS_FILE;
    if (!file_exists($fichier)) {
        return 0;
    }
    return (int) file_get_contents($fichier);
}

/**
 * Sauvegarde la dernière ville consultée dans un cookie navigateur.
 *
 * @param string $ville  Nom de la ville
 * @param string $dept   Code département
 * @param string $region Code région
 * @return bool true si le cookie a été posé
 */
function sauvegarder_cookie_derniere_ville(
    string $ville,
    string $dept,
    string $region
): bool {
    if (empty(trim($ville))) {
        return false;
    }

    $valeur = json_encode([
        'ville'  => $ville,
        'dept'   => $dept,
        'region' => $region,
        'date'   => date('d/m/Y à H\hi'),
    ], JSON_UNESCAPED_UNICODE);

    return setcookie(
        COOKIE_DERNIERE_VILLE,
        $valeur,
        time() + COOKIE_VILLE_DUREE,
        '/'
    );
}

/**
 * Lit le cookie de dernière ville consultée.
 *
 * @return array{ville:string,dept:string,region:string,date:string}|null
 */
function lire_cookie_derniere_ville(): ?array
{
    if (!isset($_COOKIE[COOKIE_DERNIERE_VILLE])) {
        return null;
    }

    $data = json_decode($_COOKIE[COOKIE_DERNIERE_VILLE], true);

    if (!is_array($data) || empty($data['ville'])) {
        setcookie(COOKIE_DERNIERE_VILLE, '', time() - 3600, '/');
        return null;
    }

    return $data;
}