<?php
/**
 * @file resultats.php
 * @brief Page d'affichage des résultats de prix de carburants.
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.1
 */

declare(strict_types=1);

$titre = 'Résultats - Carbora';

require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/lib/cookie_helper.php';
require_once __DIR__ . '/lib/csv_handler.php';

// Paramètres GET
$ville_param     = htmlspecialchars(trim($_GET['ville']     ?? ''), ENT_QUOTES, 'UTF-8');
$dept_param      = htmlspecialchars(trim($_GET['dept']      ?? ''), ENT_QUOTES, 'UTF-8');
$region_param    = htmlspecialchars(trim($_GET['region']    ?? ''), ENT_QUOTES, 'UTF-8');
$carburant_param = htmlspecialchars(trim($_GET['carburant'] ?? ''), ENT_QUOTES, 'UTF-8');

$recherche_valide = (!empty($ville_param) || !empty($dept_param));

if ($recherche_valide && !empty($ville_param)) {
    sauvegarder_cookie_derniere_ville($ville_param, $dept_param, $region_param);
    enregistrer_consultation($ville_param, $dept_param, $region_param, $carburant_param);
}

$styles_extra = <<<CSS
/* resultats.php — Styles internes */

.tableau-resultats tbody tr:hover { background-color: transparent !important; }
body.theme-nuit .tableau-resultats tbody tr:hover { background-color: transparent !important; }
.source-donnees a { text-decoration: none; font-weight: 600; }
.source-donnees a:hover { text-decoration: underline; }
CSS;

require_once __DIR__ . '/includes/header.inc.php';
?>

<main>

    <!-- En-tête -->
    <section class="resultats-entete">
        <h1>Résultats de recherche</h1>
        <?php if ($recherche_valide): ?>
            <div class="recherche-resume">
                <?php if (!empty($ville_param)): ?>
                    <span class="resume-item">
                        <span class="resume-label">Ville</span>
                        <span class="resume-valeur"><?php echo $ville_param; ?></span>
                    </span>
                <?php endif; ?>
                <?php if (!empty($dept_param)): ?>
                    <span class="resume-item">
                        <span class="resume-label">Département</span>
                        <span class="resume-valeur"><?php echo $dept_param; ?></span>
                    </span>
                <?php endif; ?>
                <span class="resume-item">
                    <span class="resume-label">Carburant</span>
                    <span class="resume-valeur"><?php echo !empty($carburant_param) ? $carburant_param : 'Tous'; ?></span>
                </span>
            </div>
        <?php else: ?>
            <p>Utilisez le formulaire de l'<strong>accueil</strong> pour lancer une recherche.</p>
        <?php endif; ?>
        <a href="index.php" class="btn-retour">Nouvelle recherche</a>
    </section>

    <!-- Zone résultats JS ou contenu par défaut -->
    <section id="zone-resultats">
        <?php if ($recherche_valide): ?>
            <div id="chargement" style="text-align:center;padding:2rem;">
                <p>Chargement des prix en cours...</p>
            </div>
            <div id="contenu-resultats" style="display:none;"></div>

        <?php else: ?>

            <!-- CONTENU PAR DÉFAUT quand aucune recherche -->

            <div class="sans-recherche">
                <div class="hero-resultats">
                    <img src="assets/img/hero_resultats.jpg" alt="Station service" class="hero-resultats-img"/>
                </div>
                <h2 class="hero-resultats-titre">Comment effectuer une recherche ?</h2>
                <div class="etapes">
                    <div class="etape">
                        <span class="etape-num">1</span>
                        <h3>Choisissez votre région</h3>
                        <p>Allez sur la page d'accueil et sélectionnez votre région dans la liste déroulante.</p>
                    </div>
                    <div class="etape">
                        <span class="etape-num">2</span>
                        <h3>Sélectionnez votre ville</h3>
                        <p>Affinez en choisissant votre département puis votre ville.</p>
                    </div>
                    <div class="etape">
                        <span class="etape-num">3</span>
                        <h3>Comparez les prix</h3>
                        <p>Les stations de votre secteur s'affichent ici, triées du moins cher au plus cher.</p>
                    </div>
                </div>
            </div>

            <section class="actualites">
                <h2>Actualités carburants</h2>
                <div class="actu-grille">
                    <article class="actu-item actu-item-image">
                        <img src="assets/img/actu_gazole_baisse.jpg" alt="Baril de pétrole et prix du carburant" class="actu-img"/>
                        <span class="actu-date">Avril 2026</span>
                        <h3>Les prix du Gazole en baisse</h3>
                        <p>Le prix moyen du Gazole en France métropolitaine continue sa tendance à la baisse, portée par un recul du cours du baril de pétrole sur les marchés internationaux.</p>
                    </article>
                    <article class="actu-item actu-item-image">
                        <img src="assets/img/actu_e10.jpg" alt="Pompe à essence E10" class="actu-img"/>
                        <span class="actu-date">Avril 2026</span>
                        <h3>E10 : le carburant le plus utilisé</h3>
                        <p>L'E10 s'impose désormais comme le carburant essence de référence en France. Compatible avec 95% des véhicules essence, il est généralement moins cher que le SP95 et le SP98.</p>
                    </article>
                    <article class="actu-item actu-item-image">
                        <img src="assets/img/actu_gazole.jpg" alt="Pistolet diesel à la pompe" class="actu-img"/>
                        <span class="actu-date">Mars 2026</span>
                        <h3>E85 : une alternative économique</h3>
                        <p>Le Superéthanol-E85 reste l'un des carburants les moins chers à la pompe. Avec un boîtier flex-fuel, de nombreux véhicules essence peuvent désormais en bénéficier.</p>
                    </article>
                    <article class="actu-item actu-item-image">
                        <img src="assets/img/actu_e85.jpg" alt="Carburant éthanol vert" class="actu-img"/>
                        <span class="actu-date">Mars 2026</span>
                        <h3>Où trouver le carburant le moins cher ?</h3>
                        <p>Les grandes surfaces proposent en général des prix inférieurs de 3 à 8 centimes par rapport aux stations autoroutières. Utilisez Carbora pour identifier les meilleures offres de votre secteur.</p>
                    </article>
                </div>
            </section>

            <section>
                <h2>Conseils pour trouver le meilleur prix</h2>
                <div class="conseils-grille">
                    <div class="conseil-item">
                        <span class="conseil-icone"></span>
                        <div>
                            <h3>Comparez plusieurs stations</h3>
                            <p>Les prix peuvent varier de plusieurs centimes dans une même ville. Cherchez sans filtre carburant pour tout voir d'un coup.</p>
                        </div>
                    </div>
                    <div class="conseil-item conseil-item-image">
                        <img src="assets/img/conseil_autoroute.jpg" alt="Autoroute en France" class="conseil-img"/>
                        <div>
                            <h3><span class="conseil-icone"></span> Évitez les stations autoroutières</h3>
                            <p>Les stations sur autoroute sont en moyenne 15 à 20% plus chères. Privilégiez les stations en ville ou en périphérie.</p>
                        </div>
                    </div>
                    <div class="conseil-item">
                        <span class="conseil-icone"></span>
                        <div>
                            <h3>Données officielles en temps réel</h3>
                            <p>Les prix proviennent directement de la base officielle du gouvernement (data.economie.gouv.fr), mise à jour en continu.</p>
                        </div>
                    </div>
                </div>
            </section>

        <?php endif; ?>
    </section><!-- /#zone-resultats -->

</main>

<?php require_once __DIR__ . '/includes/footer.inc.php'; ?>

<?php if ($recherche_valide): ?>
<script>
var VILLE     = <?php echo json_encode($ville_param); ?>;
var DEPT      = <?php echo json_encode($dept_param); ?>;
var CARBURANT = <?php echo json_encode($carburant_param); ?>;

var API_BASE = 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/prix-des-carburants-en-france-flux-instantane-v2/records/';

var CHAMPS = {
    'SP95':   'sp95_prix',
    'SP98':   'sp98_prix',
    'E10':    'e10_prix',
    'E85':    'e85_prix',
    'Gazole': 'gazole_prix',
    'GPLc':   'gplc_prix'
};

function construireUrl() {
    var params = 'limit=100&order_by=ville%20asc';
    if (DEPT)  { params += '&refine=code_departement%3A' + encodeURIComponent(DEPT); }
    if (VILLE) { params += '&where=' + encodeURIComponent('search(ville, "' + VILLE.replace(/"/g, '') + '")'); }
    return API_BASE + '?' + params;
}

function normaliserStation(rec) {
    var prix = {};
    for (var nom in CHAMPS) {
        var val = rec[CHAMPS[nom]];
        if (val !== null && val !== undefined && parseFloat(val) > 0) {
            if (!CARBURANT || nom === CARBURANT) { prix[nom] = parseFloat(val); }
        }
    }
    if (Object.keys(prix).length === 0) return null;
    var services = [];
    if (Array.isArray(rec.services_service)) { services = rec.services_service; }
    else if (typeof rec.services_service === 'string') {
        services = rec.services_service.split('//').map(function(s){ return s.trim(); }).filter(Boolean);
    }
    return {
        nom: rec.nom || 'Station inconnue',
        adresse: rec.adresse || '',
        ville: rec.ville || '',
        cp: rec.cp || '',
        prix: prix,
        services: services
    };
}

function marquerMinimums(stations) {
    var mins = {};
    stations.forEach(function(s) {
        for (var c in s.prix) {
            if (!(c in mins) || s.prix[c] < mins[c]) mins[c] = s.prix[c];
        }
    });
    return stations.map(function(s) {
        var estMin = {};
        for (var c in s.prix) { estMin[c] = (s.prix[c] === mins[c]); }
        s.estMin = estMin;
        return s;
    });
}

function genererTableau(stations) {
    var carburants = [];
    stations.forEach(function(s) {
        Object.keys(s.prix).forEach(function(c) {
            if (carburants.indexOf(c) === -1) carburants.push(c);
        });
    });
    carburants.sort();

    var nb   = stations.length;
    var html = '<h2>' + nb + ' station' + (nb > 1 ? 's' : '') + ' trouv' + (nb > 1 ? 'ées' : 'ée') + '</h2>';
    html += '<div class="options-affichage">';
    html += '<label for="tri">Trier par :</label>';
    html += '<select id="tri" onchange="trierStations(this.value)">';
    html += '<option value="prix-asc">Prix croissant</option>';
    html += '<option value="prix-desc">Prix décroissant</option>';
    html += '<option value="nom">Nom</option>';
    html += '</select>';
    html += '<label><input type="checkbox" onchange="toggleDetails(this.checked)"/> Afficher les services</label>';
    html += '</div>';
    html += '<div class="tableau-container"><table class="tableau-resultats" id="tableau-stations">';
    html += '<caption>Prix des carburants par station</caption>';
    html += '<thead><tr><th scope="col">Station</th><th scope="col">Adresse</th><th scope="col">Ville</th>';
    carburants.forEach(function(c) { html += '<th scope="col">' + c + '</th>'; });
    html += '<th scope="col" class="detail-col" style="display:none">Services</th></tr></thead><tbody>';

    stations.forEach(function(s) {
        html += '<tr class="ligne-station" data-nom="' + s.nom + '">';
        html += '<td class="nom-station">' + s.nom + '</td>';
        html += '<td>' + s.adresse + '</td>';
        html += '<td>' + s.ville + ' (' + s.cp + ')</td>';
        carburants.forEach(function(c) {
            if (c in s.prix) {
                var p = s.prix[c].toFixed(3).replace('.', ',') + ' &euro;/L';
                html += '<td><span class="prix' + (s.estMin[c] ? ' prix-min' : '') + '">' + p + '</span></td>';
            } else {
                html += '<td><span class="prix-absent">&mdash;</span></td>';
            }
        });
        var svcs = s.services.length > 0
            ? '<ul class="liste-services">' + s.services.map(function(sv){ return '<li>' + sv + '</li>'; }).join('') + '</ul>'
            : '<span class="prix-absent">Non renseigné</span>';
        html += '<td class="detail-col" style="display:none">' + svcs + '</td></tr>';
    });

    html += '</tbody></table></div>';
    html += '<p class="source-donnees">Données fournies par <a href="https://data.economie.gouv.fr" target="_blank" rel="noopener noreferrer">data.economie.gouv.fr</a> en temps réel.</p>';
    return html;
}

(function() {
    var divC = document.getElementById('chargement');
    var divR = document.getElementById('contenu-resultats');
    fetch(construireUrl())
        .then(function(r) {
            if (!r.ok) throw new Error('Erreur HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            var stations = marquerMinimums(
                (data.results || []).map(normaliserStation).filter(function(s){ return s !== null; })
            );
            divC.style.display = 'none';
            divR.innerHTML = stations.length === 0
                ? '<p>Aucune station trouvée. Essayez sans filtre carburant ou avec un autre département.</p>'
                : genererTableau(stations);
            divR.style.display = '';
        })
        .catch(function(err) {
            divC.innerHTML = '<div class="alerte alerte-erreur" role="alert"><p><strong>Erreur :</strong> ' + err.message + '</p></div>';
        });
})();

function toggleDetails(a) {
    document.querySelectorAll('.detail-col').forEach(function(el){ el.style.display = a ? '' : 'none'; });
}

function trierStations(c) {
    var tbody = document.querySelector('.tableau-resultats tbody');
    if (!tbody) return;
    var lignes = Array.from(tbody.querySelectorAll('tr.ligne-station'));
    lignes.sort(function(a, b) {
        if (c === 'nom') return (a.dataset.nom || '').localeCompare(b.dataset.nom || '', 'fr');
        function p(l) {
            var v = Array.from(l.querySelectorAll('.prix'))
                .map(function(x){ return parseFloat(x.textContent.replace(',', '.')); })
                .filter(function(v){ return !isNaN(v); });
            return v.length ? Math.min.apply(null, v) : 9999;
        }
        return c === 'prix-asc' ? p(a) - p(b) : p(b) - p(a);
    });
    lignes.forEach(function(l){ tbody.appendChild(l); });
}
</script>
<?php endif; ?>
