<?php
/**
 * @file http_helper.php
 * @brief Fonctions utilitaires cURL pour les appels HTTP.
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

/**
 * @brief Effectue une requête HTTP GET et retourne le contenu JSON.
 * @param string $url URL à interroger
 * @return string|null Contenu brut de la réponse ou null si erreur
 */

declare(strict_types=1);

function fetchJsonFromUrl(string $url): ?string
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Carbora/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($data === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    return ($data !== '') ? $data : null;
}