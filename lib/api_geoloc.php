<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.inc.php';
require_once __DIR__ . '/http_helper.php';

/**
 * @file api_geoloc.php
 * @brief Géolocalisation du visiteur par adresse IP via ipinfo.io.
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

/**
 * @brief Récupère l'adresse IP réelle du visiteur.
 * @return string Adresse IP du visiteur
 */

function getVisitorIp(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return trim($_SERVER['HTTP_CLIENT_IP']);
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return trim($_SERVER['REMOTE_ADDR']);
    }

    return '8.8.8.8';
}

/**
 * @brief Interroge l'API ipinfo.io pour obtenir la localisation d'une IP.
 * @param string $ip Adresse IP à géolocaliser
 * @return array|null Tableau associatif avec ville, région, pays ou null si échec
 */

function getGeoInfoFromIpInfo(string $ip): ?array
{
    $url = 'https://ipinfo.io/' . urlencode($ip) . '/geo';
    $json = fetchJsonFromUrl($url);

    if ($json === null) {
        return null;
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : null;
}
