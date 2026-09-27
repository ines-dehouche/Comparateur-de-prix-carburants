<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.inc.php';
require_once __DIR__ . '/http_helper.php';

/**
 * @file api_ghibli.php
 * @brief Récupération de films Studio Ghibli via l'API ghibliapi.vercel.app.
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

/**
 * @brief Récupère la liste complète des films Ghibli depuis l'API.
 * @return array Tableau des films ou tableau vide si échec
 */

function getGhibliFilms(): array
{
    $json = fetchJsonFromUrl(API_GHIBLI_URL);

    if ($json === null) {
        return [];
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

/**
 * @brief Sélectionne aléatoirement un film parmi la liste Ghibli.
 * @return array|null Film aléatoire ou null si l'API est indisponible
 */

function getRandomGhibliFilm(): ?array
{
    $films = getGhibliFilms();

    if (empty($films)) {
        return null;
    }

    $index = array_rand($films);
    return $films[$index];
}
