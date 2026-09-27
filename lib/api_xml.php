<?php
/**
 * @file api_xml.php
 * @brief Génération de données XML à partir des APIs externes du projet.
 *
 * Ce fichier expose les données récupérées depuis les APIs JSON
 * (Ghibli, Carburants, Géolocalisation) au format XML via
 * la classe DOMDocument de PHP.
 *
 * Usage : les fonctions retournent une chaîne XML bien formée,
 * prête à être affichée ou transmise à un parser XML.
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.inc.php';
require_once __DIR__ . '/api_ghibli.php';
require_once __DIR__ . '/api_geoloc.php';
require_once __DIR__ . '/api_carburants.php';

/**
 * Génère un document XML listant tous les films Ghibli.
 *
 * Récupère les films depuis l'API Ghibli (JSON), puis les
 * convertit en XML structuré avec DOMDocument.
 *
 * Structure produite :
 * <films>
 *   <film id="...">
 *     <titre>...</titre>
 *     <titre_original>...</titre_original>
 *     <annee>...</annee>
 *     <realisateur>...</realisateur>
 *     <score_rt>...</score_rt>
 *     <description>...</description>
 *   </film>
 *   ...
 * </films>
 *
 * @return string XML bien formé, ou XML d'erreur si l'API est indisponible
 */
function getGhibliFilmsXml(): string
{
    $films = getGhibliFilms();

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    if (empty($films)) {
        $erreur = $doc->createElement('erreur', 'Impossible de récupérer les films Ghibli.');
        $doc->appendChild($erreur);
        return $doc->saveXML();
    }

    $racine = $doc->createElement('films');
    $racine->setAttribute('source', API_GHIBLI_URL);
    $racine->setAttribute('total', (string) count($films));
    $doc->appendChild($racine);

    foreach ($films as $film) {
        $noeudFilm = $doc->createElement('film');
        $noeudFilm->setAttribute('id', htmlspecialchars($film['id'] ?? '', ENT_XML1, 'UTF-8'));

        $champs = [
            'titre'          => $film['title']          ?? '',
            'titre_original' => $film['original_title'] ?? '',
            'annee'          => $film['release_date']   ?? '',
            'realisateur'    => $film['director']       ?? '',
            'score_rt'       => $film['rt_score']       ?? '',
            'description'    => $film['description']    ?? '',
        ];

        foreach ($champs as $balise => $valeur) {
            $element = $doc->createElement($balise);
            $element->appendChild($doc->createTextNode($valeur));
            $noeudFilm->appendChild($element);
        }

        $racine->appendChild($noeudFilm);
    }

    return $doc->saveXML();
}

/**
 * Génère un document XML avec les informations de géolocalisation du visiteur.
 *
 * Récupère l'IP du visiteur puis interroge l'API ipinfo.io (JSON),
 * et convertit la réponse en XML.
 *
 * Structure produite :
 * <geolocalisation>
 *   <ip>...</ip>
 *   <ville>...</ville>
 *   <region>...</region>
 *   <pays>...</pays>
 *   <coordonnees>...</coordonnees>
 *   <organisation>...</organisation>
 *   <fuseau_horaire>...</fuseau_horaire>
 * </geolocalisation>
 *
 * @return string XML bien formé, ou XML d'erreur si l'API est indisponible
 */
function getGeolocXml(): string
{
    $ip  = getVisitorIp();
    $geo = getGeoInfoFromIpInfo($ip);

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    if ($geo === null) {
        $erreur = $doc->createElement('erreur', 'Impossible de récupérer la géolocalisation.');
        $doc->appendChild($erreur);
        return $doc->saveXML();
    }

    $racine = $doc->createElement('geolocalisation');
    $racine->setAttribute('source', 'https://ipinfo.io');
    $doc->appendChild($racine);

    $champs = [
        'ip'            => $geo['ip']       ?? '',
        'ville'         => $geo['city']     ?? '',
        'region'        => $geo['region']   ?? '',
        'pays'          => $geo['country']  ?? '',
        'coordonnees'   => $geo['loc']      ?? '',
        'organisation'  => $geo['org']      ?? '',
        'fuseau_horaire'=> $geo['timezone'] ?? '',
    ];

    foreach ($champs as $balise => $valeur) {
        $element = $doc->createElement($balise);
        $element->appendChild($doc->createTextNode($valeur));
        $racine->appendChild($element);
    }

    return $doc->saveXML();
}

/**
 * Génère un document XML avec les stations carburant pour une ville/département.
 *
 * Récupère les stations depuis l'API gouvernementale (JSON),
 * puis les convertit en XML structuré.
 *
 * Structure produite :
 * <stations total="...">
 *   <station>
 *     <nom>...</nom>
 *     <adresse>...</adresse>
 *     <ville>...</ville>
 *     <code_postal>...</code_postal>
 *     <prix>
 *       <carburant nom="SP95">1.849</carburant>
 *       ...
 *     </prix>
 *   </station>
 *   ...
 * </stations>
 *
 * @param string $ville     Nom de la ville (peut être vide)
 * @param string $dept      Code département (peut être vide)
 * @param string $carburant Filtre carburant (peut être vide)
 * @return string XML bien formé, ou XML d'erreur si l'API est indisponible
 */
function getStationsXml(string $ville = '', string $dept = '', string $carburant = ''): string
{
    $resultat = recuperer_stations($ville, $dept, $carburant);

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    if (!$resultat['succes']) {
        $erreur = $doc->createElement('erreur');
        $erreur->appendChild($doc->createTextNode($resultat['message']));
        $doc->appendChild($erreur);
        return $doc->saveXML();
    }

    $racine = $doc->createElement('stations');
    $racine->setAttribute('source', API_CARBURANTS_URL);
    $racine->setAttribute('total', (string) count($resultat['stations']));
    if (!empty($ville)) {
        $racine->setAttribute('ville', $ville);
    }
    if (!empty($dept)) {
        $racine->setAttribute('departement', $dept);
    }
    $doc->appendChild($racine);

    foreach ($resultat['stations'] as $s) {
        $noeudStation = $doc->createElement('station');

        $champs = [
            'nom'         => $s['nom'],
            'adresse'     => $s['adresse'],
            'ville'       => $s['ville'],
            'code_postal' => $s['code_postal'],
        ];

        foreach ($champs as $balise => $valeur) {
            $el = $doc->createElement($balise);
            $el->appendChild($doc->createTextNode($valeur));
            $noeudStation->appendChild($el);
        }

        // Bloc prix
        $noeudPrix = $doc->createElement('prix');
        foreach ($s['prix'] as $nomCarb => $valeur) {
            $elCarb = $doc->createElement('carburant', number_format($valeur, 3, '.', ''));
            $elCarb->setAttribute('nom', $nomCarb);
            $noeudPrix->appendChild($elCarb);
        }
        $noeudStation->appendChild($noeudPrix);

        $racine->appendChild($noeudStation);
    }

    return $doc->saveXML();
}