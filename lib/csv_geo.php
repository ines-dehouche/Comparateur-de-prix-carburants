<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.inc.php';

/**
 * @file csv_geo.php
 * @brief Chargement des données géographiques depuis les fichiers CSV.
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

/**
 * @brief Charge la liste des régions françaises depuis regions.csv.
 * @return array Tableau associatif [code => nom]
 */

function charger_regions(): array
{
    $fichier = DATA_PATH . '/regions.csv';
    $regions = [];

    if (!file_exists($fichier)) {
        return $regions;
    }

    if (($fp = fopen($fichier, 'r')) === false) {
        return $regions;
    }

    // Ignorer l'en-tête
    fgetcsv($fp, 0, ',', '"', '\\');

    while (($ligne = fgetcsv($fp, 0, ',', '"', '\\')) !== false) {
        if (count($ligne) >= 2) {
            $regions[] = [
                'code' => trim($ligne[0]),
                'nom'  => trim($ligne[1]),
            ];
        }
    }

    fclose($fp);

    usort($regions, fn($a, $b) => strcmp($a['nom'], $b['nom']));

    return $regions;
}

/**
 * @brief Charge la liste des départements depuis departements.csv.
 * @return array Tableau des départements avec code, nom et région
 */

function charger_departements(): array
{
    $fichier = DATA_PATH . '/departements.csv';
    $depts   = [];

    if (!file_exists($fichier)) {
        return $depts;
    }

    if (($fp = fopen($fichier, 'r')) === false) {
        return $depts;
    }

    fgetcsv($fp, 0, ',', '"', '\\');

    while (($ligne = fgetcsv($fp, 0, ',', '"', '\\')) !== false) {
        if (count($ligne) >= 3) {
            $depts[] = [
                'code'        => trim($ligne[0]),
                'nom'         => trim($ligne[1]),
                'region_code' => trim($ligne[2]),
            ];
        }
    }

    fclose($fp);

    usort($depts, fn($a, $b) => strcmp($a['nom'], $b['nom']));

    return $depts;
}

/**
 * @brief Regroupe les départements par code région.
 * @param array $departements Tableau des départements
 * @return array Tableau [code_region => [departements]]
 */

function departements_par_region(array $departements): array
{
    $par_region = [];

    foreach ($departements as $d) {
        $rc = $d['region_code'];
        if (!isset($par_region[$rc])) {
            $par_region[$rc] = [];
        }
        $par_region[$rc][] = [
            'code' => $d['code'],
            'nom'  => $d['nom'],
        ];
    }

    return $par_region;
}

/**
 * @brief Charge les communes d'un département depuis communes.csv.
 * @param string $code_dept Code du département
 * @return array Liste des communes avec nom et code postal
 */

function charger_communes_par_dept(string $code_dept): array
{
    $fichier  = DATA_PATH . '/communes.csv';
    $communes = [];

    if (!file_exists($fichier)) {
        return $communes;
    }

    if (($fp = fopen($fichier, 'r')) === false) {
        return $communes;
    }

    fgetcsv($fp, 0, ',', '"', '\\');

    while (($ligne = fgetcsv($fp, 0, ',', '"', '\\')) !== false) {
        if (count($ligne) >= 3 && trim($ligne[2]) === $code_dept) {
            $communes[] = [
                'code' => trim($ligne[0]),
                'nom'  => trim($ligne[1]),
            ];
        }
    }

    fclose($fp);

    usort($communes, fn($a, $b) => strcmp($a['nom'], $b['nom']));

    return $communes;
}