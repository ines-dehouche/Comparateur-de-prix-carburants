<?php
/**
 * @file config.inc.php
 * @brief Configuration générale du projet Carbora.
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Paris');

define('SITE_NAME', 'Carbora');
define('SITE_TAGLINE', 'Votre carburant en 3 clics !');
define('SITE_DESCRIPTION', 'Projet L2 Développement Web autour des prix des carburants.');

define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LIB_PATH', ROOT_PATH . '/lib');
define('DATA_PATH', ROOT_PATH . '/data');
define('ASSETS_PATH', ROOT_PATH . '/assets');

define('LOGO_PATH', 'assets/img/logo_carbora.png');
define('FAVICON_PATH', 'assets/img/favicon_64.png');
define('MAIN_CSS_PATH', 'assets/css/style.css');

define('API_GHIBLI_URL', 'https://ghibliapi.vercel.app/films');
