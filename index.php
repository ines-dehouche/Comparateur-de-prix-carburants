<?php
/**
 * @file index.php
 * @brief Page d'accueil du site Carbora.
 *
 * Fonctionnalités :
 *  - Sélection de la région, du département et de la ville via listes déroulantes
 *  - Géolocalisation approximative du visiteur (API ipinfo.io)
 *  - Mémorisation de la dernière ville consultée via cookie
 *  - Redirection vers resultats.php après soumission du formulaire
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

declare(strict_types=1);

$titre = 'Accueil - Carbora';

require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/lib/cookie_helper.php';
require_once __DIR__ . '/lib/api_geoloc.php';
require_once __DIR__ . '/lib/csv_geo.php';
require_once __DIR__ . '/lib/csv_handler.php';

// === Traitement thème (AVANT tout output) ===
traiter_changement_theme();

// === Chargement des données géographiques (CSV) ===
$regions      = charger_regions();
$departements = charger_departements();

// === Cookie dernière ville consultée ===
$derniere_ville = lire_cookie_derniere_ville();

// === Géolocalisation du visiteur ===
$ip_visiteur   = getVisitorIp();
$geo_visiteur  = getGeoInfoFromIpInfo($ip_visiteur);
$ville_detectee  = $geo_visiteur['city']   ?? null;
$region_detectee = $geo_visiteur['region'] ?? null;

// === Traitement formulaire POST ===
$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $region_code = trim($_POST['region']      ?? '');
    $dept_code   = trim($_POST['departement'] ?? '');
    $commune_nom = trim($_POST['commune']     ?? '');
    $carburant   = trim($_POST['carburant']   ?? '');

    if (empty($region_code)) { $erreurs[] = 'Veuillez sélectionner une région.'; }
    if (empty($dept_code))   { $erreurs[] = 'Veuillez sélectionner un département.'; }
    if (empty($commune_nom)) { $erreurs[] = 'Veuillez sélectionner une ville.'; }

    if (empty($erreurs)) {
        sauvegarder_cookie_derniere_ville($commune_nom, $dept_code, $region_code);
        enregistrer_consultation($commune_nom, $dept_code, $region_code);

        $params = http_build_query([
            'ville'     => $commune_nom,
            'dept'      => $dept_code,
            'region'    => $region_code,
            'carburant' => $carburant,
        ]);
        header('Location: resultats.php?' . $params);
        exit;
    }
}

require_once __DIR__ . '/includes/header.inc.php';
?>

<main>

    <!-- ===== HERO : formulaire de recherche ===== -->
    <section class="hero">
        <h1>Trouvez le carburant le moins cher près de chez vous</h1>
        <p class="hero-subtitle">
            Sélectionnez votre région, votre département et votre ville
            pour consulter les prix en temps réel dans les stations service.
        </p>

        <?php if (!empty($erreurs)): ?>
            <div class="alerte alerte-erreur" role="alert">
                <ul>
                    <?php foreach ($erreurs as $e): ?>
                        <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php" class="formulaire-recherche" id="form-recherche">

            <div class="champ-form">
                <label for="carburant">Type de carburant :</label>
                <select name="carburant" id="carburant">
                    <option value="">-- Tous les carburants --</option>
                    <option value="SP95">SP95</option>
                    <option value="SP98">SP98</option>
                    <option value="E10">E10</option>
                    <option value="E85">E85</option>
                    <option value="Gazole">Gazole (Diesel)</option>
                    <option value="GPLc">GPLc</option>
                </select>
            </div>

            <div class="champ-form">
                <label for="region">Région :</label>
                <select name="region" id="region" required>
                    <option value="">-- Sélectionner une région --</option>
                    <?php foreach ($regions as $r): ?>
                        <option value="<?php echo htmlspecialchars((string)$r['code'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string)$r['nom'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="champ-form">
                <label for="departement">Département :</label>
                <select name="departement" id="departement" required disabled>
                    <option value="">-- Sélectionner d'abord une région --</option>
                </select>
            </div>

            <div class="champ-form">
                <label for="commune">Ville / Commune :</label>
                <select name="commune" id="commune" required disabled>
                    <option value="">-- Sélectionner d'abord un département --</option>
                </select>
            </div>

            <div class="champ-form champ-bouton">
                <button type="submit" class="btn-recherche">
                    Rechercher les stations
                </button>
            </div>

        </form>
    </section>


    <!-- ===== GÉOLOCALISATION IP ===== -->
    <?php if ($ville_detectee !== null): ?>
    <section class="geo-section">
        <h2>Votre position détectée</h2>
        <p>
            D'après votre adresse IP (<code><?php echo htmlspecialchars($ip_visiteur, ENT_QUOTES, 'UTF-8'); ?></code>),
            vous semblez vous trouver à
            <strong><?php echo htmlspecialchars($ville_detectee, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if ($region_detectee): ?>
                — <?php echo htmlspecialchars($region_detectee, ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>.
        </p>
        <p class="note-geo">
            <em>La géolocalisation par adresse IP est approximative.</em>
        </p>
        <?php
        $params_geo = http_build_query([
            'ville'  => $ville_detectee,
            'dept'   => '',
            'region' => '',
        ]);
        ?>
        <a href="resultats.php?<?php echo $params_geo; ?>" class="btn-geo">
            Voir les stations proches de <?php echo htmlspecialchars($ville_detectee, ENT_QUOTES, 'UTF-8'); ?> →
        </a>
    </section>
    <?php endif; ?>


    <!-- ===== DERNIÈRE VILLE (cookie) ===== -->
    <?php if ($derniere_ville !== null): ?>
    <section class="derniere-ville">
        <h2>Votre dernière recherche</h2>
        <p>
            <?php if (!empty($derniere_ville['date'])): ?>
                Le <?php echo htmlspecialchars($derniere_ville['date'], ENT_QUOTES, 'UTF-8'); ?>,
            <?php endif; ?>
            vous avez consulté les prix à
            <strong><?php echo htmlspecialchars($derniere_ville['ville'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if (!empty($derniere_ville['dept'])): ?>
                (dép.&nbsp;<?php echo htmlspecialchars($derniere_ville['dept'], ENT_QUOTES, 'UTF-8'); ?>)
            <?php endif; ?>.
        </p>
        <?php
        $params_retour = http_build_query([
            'ville'  => $derniere_ville['ville']  ?? '',
            'dept'   => $derniere_ville['dept']   ?? '',
            'region' => $derniere_ville['region'] ?? '',
        ]);
        ?>
        <a href="resultats.php?<?php echo $params_retour; ?>" class="btn-retour">
            Reprendre cette recherche →
        </a>
    </section>
    <?php endif; ?>


    <!-- ===== COMMENT ÇA MARCHE ===== -->
    <section class="presentation">
        <h2>Comment ça marche ?</h2>
        <div class="etapes">
            <div class="etape">
                <span class="etape-num">1</span>
                <h3>Choisissez votre localisation</h3>
                <p>Sélectionnez votre région, votre département puis votre ville.</p>
            </div>
            <div class="etape">
                <span class="etape-num">2</span>
                <h3>Consultez les prix</h3>
                <p>Le site interroge l'API officielle du gouvernement (data.economie.gouv.fr) pour vous afficher les prix en temps réel.</p>
            </div>
            <div class="etape">
                <span class="etape-num">3</span>
                <h3>Comparez et économisez</h3>
                <p>Identifiez rapidement la station la moins chère près de chez vous.</p>
            </div>
        </div>
    </section>


    <!-- ===== POURQUOI UTILISER CARBORA ===== -->
    <section class="pourquoi">
        <h2>Pourquoi utiliser Carbora ?</h2>
        <div class="pourquoi-grille">
            <div class="pourquoi-item">
                <h3>Économisez sur chaque plein</h3>
                <p>Comparez les prix en temps réel et trouvez la station la moins chère autour de vous.</p>
            </div>
            <div class="pourquoi-item">
                <h3>Trouvez les stations proches</h3>
                <p>Localisez rapidement les stations de votre ville ou département grâce à notre carte interactive.</p>
            </div>
            <div class="pourquoi-item">
                <h3>Données en temps réel</h3>
                <p>Les prix sont mis à jour en continu depuis la source officielle du gouvernement français.</p>
            </div>
            <div class="pourquoi-item">
                <h3>Statistiques de consultation</h3>
                <p>Découvrez les villes les plus recherchées et les tendances des prix sur notre site.</p>
            </div>
        </div>
    </section>


    <!-- ===== TYPES DE CARBURANTS ===== -->
    <section class="carburants-info">
        <h2>Les types de carburants</h2>
        <div class="carburants-grille">
            <div class="carburant-item">
                <span class="carburant-badge gazole">Gazole</span>
                <p>Carburant diesel, le plus répandu en France. Utilisé par la majorité des véhicules de tourisme et utilitaires.</p>
            </div>
            <div class="carburant-item">
                <span class="carburant-badge sp95">SP95</span>
                <p>Sans plomb 95 octanes. Essence classique pour véhicules essence. Progressivement remplacé par l'E10.</p>
            </div>
            <div class="carburant-item">
                <span class="carburant-badge sp98">SP98</span>
                <p>Sans plomb 98 octanes. Indice d'octane plus élevé, recommandé pour les moteurs à hautes performances.</p>
            </div>
            <div class="carburant-item">
                <span class="carburant-badge e10">E10</span>
                <p>Contient 10% d'éthanol. Carburant écologique compatible avec la majorité des véhicules essence récents.</p>
            </div>
            <div class="carburant-item">
                <span class="carburant-badge e85">E85</span>
                <p>Superéthanol à 85% d'éthanol. Très économique, compatible avec les véhicules flex-fuel ou boîtiers E85.</p>
            </div>
            <div class="carburant-item">
                <span class="carburant-badge gplc">GPLc</span>
                <p>Gaz de pétrole liquéfié. Carburant propre et économique, nécessite une installation spécifique sur le véhicule.</p>
            </div>
        </div>
    </section>

</main>

<!--
    Données des départements par région, injectées depuis PHP pour le JS.
    Permet de peupler dynamiquement la liste des départements sans requête AJAX.
    @see assets/js/filtres.js
-->
<script>
const DEPARTEMENTS_PAR_REGION = <?php echo json_encode(departements_par_region($departements), JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="assets/js/filtres.js"></script>

<?php require_once __DIR__ . '/includes/footer.inc.php'; ?>