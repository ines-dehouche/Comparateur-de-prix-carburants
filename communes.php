<?php
/**
 * @file communes.php
 * @brief Point d'accès AJAX : retourne les communes d'un département en JSON.
 *
 * Appelé par filtres.js avec le paramètre GET ?dept=XX.
 * Retourne un tableau JSON de la forme : [{"code":"...", "nom":"..."}, ...]
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/lib/csv_geo.php';

// En-tête JSON obligatoire AVANT tout autre output
header('Content-Type: application/json; charset=UTF-8');

$dept = trim($_GET['dept'] ?? '');

// Validation : le code département ne doit contenir que des chiffres et lettres
if (!preg_match('/^[0-9A-Za-z]{1,3}$/', $dept)) {
    echo json_encode([]);
    exit;
}

$communes = charger_communes_par_dept($dept);
echo json_encode($communes, JSON_UNESCAPED_UNICODE);
