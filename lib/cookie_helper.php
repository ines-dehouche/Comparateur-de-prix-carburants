<?php
/**
 * @file cookie_helper.php
 * @brief Gestion du cookie de thème graphique (jour / nuit / défaut).
 *
 * Trois situations possibles :
 *  - Cookie "jour"   : theme=jour  → charte graphique claire
 *  - Cookie "nuit"   : theme=nuit  → charte graphique sombre
 *  - Cookie "défaut" : aucun cookie → on applique le thème jour par défaut
 */

declare(strict_types=1);

/** Valeurs autorisées pour le cookie de thème */
const THEMES_VALIDES = ['jour', 'nuit'];

/** Nom du cookie */
const COOKIE_THEME = 'theme';

/** Durée de vie du cookie : 30 jours */
const COOKIE_DUREE = 30 * 24 * 3600;

/**
 * Lit et valide le cookie de thème.
 * Si le cookie existe mais contient une valeur invalide, on le supprime.
 *
 * @return string Le thème actif : 'jour' ou 'nuit'
 */
function get_theme(): string
{
    // Aucun cookie → situation "défaut" → thème jour
    if (!isset($_COOKIE[COOKIE_THEME])) {
        return 'jour'; // cookie défaut
    }

    $valeur = $_COOKIE[COOKIE_THEME];

    // Valeur invalide → on supprime le cookie et on retourne le défaut
    if (!in_array($valeur, THEMES_VALIDES, true)) {
        supprimer_cookie_theme();
        return 'jour'; // cookie défaut
    }

    // Cookie valide : 'jour' ou 'nuit'
    return $valeur;
}

/**
 * Pose ou met à jour le cookie de thème.
 * Doit être appelé AVANT tout affichage HTML (avant echo, require header...).
 *
 * @param string $theme 'jour' ou 'nuit'
 * @return bool true si le cookie a été posé, false sinon
 */
function set_cookie_theme(string $theme): bool
{
    if (!in_array($theme, THEMES_VALIDES, true)) {
        return false;
    }

    // Remplacez '/' par '/~votre_login/' si vous êtes sur hébergement partagé
    $path = '/';

    return setcookie(
        COOKIE_THEME,
        $theme,
        time() + COOKIE_DUREE,
        $path
    );
}

/**
 * Supprime le cookie de thème (valeur erronée ou reset).
 *
 * @return void
 */
function supprimer_cookie_theme(): void
{
    setcookie(COOKIE_THEME, '', time() - 3600, '/');
    unset($_COOKIE[COOKIE_THEME]);
}

/**
 * Retourne le chemin vers la feuille CSS du thème actif.
 *
 * @param string $theme 'jour' ou 'nuit'
 * @return string Chemin relatif vers le fichier CSS
 */
function get_css_theme(string $theme): string
{
    return 'assets/css/theme_' . $theme . '.css';
}

/**
 * Traite le paramètre GET ?theme=... s'il est présent dans l'URL.
 * Met à jour le cookie et redirige pour éviter de retraiter le paramètre.
 * À appeler EN TOUT PREMIER dans chaque page (avant require header).
 *
 * @return void
 */
function traiter_changement_theme(): void
{
    if (!isset($_GET[COOKIE_THEME])) {
        return;
    }

    $nouveau_theme = $_GET[COOKIE_THEME];

    if (in_array($nouveau_theme, THEMES_VALIDES, true)) {
        set_cookie_theme($nouveau_theme);
    } else {
        supprimer_cookie_theme();
    }

    // Redirection propre pour enlever le paramètre de l'URL
    $url_propre = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $url_propre);
    exit;
}