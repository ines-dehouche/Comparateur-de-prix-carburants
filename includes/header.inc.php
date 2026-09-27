<?php
/**
 * @file header.inc.php
 * @brief En-tête commun du site avec gestion du cookie de thème.
 *
 * Variables optionnelles que les pages peuvent définir AVANT d'inclure ce fichier :
 *  - $titre        (string) : titre de la page (défaut : SITE_NAME)
 *  - $styles_extra (string) : CSS interne spécifique à la page, injecté dans <head>
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.1
 */

declare(strict_types=1);

require_once __DIR__ . '/config.inc.php';
require_once LIB_PATH . '/cookie_helper.php';

// 1. Traiter le changement de thème en PREMIER (avant tout output)
traiter_changement_theme();

// 2. Lire le thème actif (jour / nuit / défaut)
$theme_actif = get_theme();
$css_theme   = get_css_theme($theme_actif);

// 3. Titre de page (défaut : nom du site)
if (!isset($titre) || trim((string) $titre) === '') {
    $titre = SITE_NAME;
}
if (!isset($styles_extra)) {
    $styles_extra = "";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?php echo htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(SITE_DESCRIPTION, ENT_QUOTES, 'UTF-8'); ?>"/>
    <link rel="icon" href="<?php echo FAVICON_PATH; ?>"/>
    <!-- Feuille CSS de base -->
    <link rel="stylesheet" href="<?php echo MAIN_CSS_PATH; ?>"/>
    <!-- Feuille CSS du thème actif (jour ou nuit) -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css_theme, ENT_QUOTES, 'UTF-8'); ?>"/>
    <?php if ($styles_extra !== ''): ?>
    <!-- Styles CSS internes spécifiques à cette page -->
    <style>
    <?php echo $styles_extra; ?>
    </style>
    <?php endif; ?>
</head>
<body class="theme-<?php echo htmlspecialchars($theme_actif, ENT_QUOTES, 'UTF-8'); ?>">
<header class="site-header">
    <div class="wrapper header-container">
        <a href="index.php" class="brand" aria-label="Carbora - Accueil">
            <div class="brand-text">
                <img src="<?php echo LOGO_PATH; ?>" alt="Logo Carbora" class="brand-logo" width="170" height="113"/>
                <span class="brand-tagline"><?php echo SITE_TAGLINE; ?></span>
            </div>
        </a>

        <nav aria-label="Navigation principale" class="main-nav">
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="carte.php">Carte</a></li>
                <li><a href="resultats.php">Résultats</a></li>
                <li><a href="statistiques.php">Statistiques</a></li>
                <li><a href="tech.php">Tech</a></li>
            </ul>
        </nav>

        <!-- Boutons de changement de thème (images cliquables) -->
        <div class="theme-switcher">
            <?php if ($theme_actif === 'nuit'): ?>
                <a href="?theme=jour" title="Passer en mode jour">
                    <img src="assets/img/soleil.png" alt="Mode jour" width="32" height="32"/>
                </a>
            <?php else: ?>
                <a href="?theme=nuit" title="Passer en mode nuit">
                    <img src="assets/img/lune.png" alt="Mode nuit" width="32" height="32"/>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>