<?php
/**
 * @file carte.php
 * @brief Page de sélection géographique interactive (région → département → ville).
 *
 * Fonctionnalités :
 *  - Carte interactive de France via balises HTML <map>, <usemap>, <area>
 *  - Navigation en cascade : région → département → ville
 *  - Chargement AJAX des communes via communes.php
 *  - Filtrage optionnel par type de carburant
 *  - Redirection vers resultats.php après sélection
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.2 — Corrections accessibilité WAVE (contraste, texte petit, événements clavier)
 */

declare(strict_types=1);

$titre = 'Carte des régions - Carbora';

require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/lib/cookie_helper.php';
require_once __DIR__ . '/lib/csv_geo.php';

// Chargement des données géographiques
$regions          = charger_regions();
$departements     = charger_departements();
$depts_par_region = departements_par_region($departements);

// Pré-sélections éventuelles depuis l'URL
$region_selectionnee = htmlspecialchars(trim($_GET['region'] ?? ''), ENT_QUOTES, 'UTF-8');
$dept_selectionne    = htmlspecialchars(trim($_GET['dept']   ?? ''), ENT_QUOTES, 'UTF-8');

// Retrouver le nom de la région sélectionnée pour l'affichage
$nom_region_sel = '';
foreach ($regions as $r) {
    if ($r['code'] === $region_selectionnee) {
        $nom_region_sel = $r['nom'];
        break;
    }
}

$styles_extra = <<<CSS
/* CSS INTERNE — Styles specifiques a carte.php */

/* Layout */
.carte-header h1 { margin-bottom: 0.5rem; }
.carte-intro { color: #1F2937; } /* CORRIGE : était #374151, ratio insuffisant */

.carte-layout {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 2rem;
    align-items: start;
    padding: 0;
    background: none;
    box-shadow: none;
}

/* Zone carte */
.carte-zone {
    background: var(--color-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 2rem;
}

.carte-zone-titre { margin-bottom: 1rem; }

/* Image carte cliquable */
.carte-img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    cursor: crosshair;
    display: block;
    margin: 0 auto;
    transition: opacity 0.2s;
}
.carte-img:hover { opacity: 0.92; }
body.theme-nuit .carte-img { border-color: #334155; filter: brightness(0.85); }

/* Focus visible sur les zones de carte (accessibilite) */
map area:focus {
    outline: 3px solid #1D4ED8;
    outline-offset: 2px;
}

/* Figcaption — CORRIGE : taille 0.9rem (était 0.85rem trop petit), couleur renforcée */
figure figcaption {
    font-size: 0.9rem;
    color: #1F2937;
    margin-top: 0.5rem;
    text-align: center;
}
body.theme-nuit figure figcaption { color: #94A3B8; }

/* Sidebar */
.carte-sidebar {
    background: var(--color-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    position: sticky;
    top: 1rem;
}

/* Fieldsets */
.etape-fieldset {
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 1rem 1.25rem;
    transition: border-color 0.25s, opacity 0.25s;
}

.etape-fieldset:not([disabled]) { border-color: var(--color-primary); }
.etape-fieldset[disabled] { opacity: 0.5; border-color: var(--color-border); }

.etape-fieldset legend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--color-bg-dark);
    padding: 0 0.4rem;
}

.etape-badge {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--color-primary);
    color: white;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.etape-badge-inactive  { background: #CBD5E1; color: #1F2937; } /* CORRIGE : couleur texte renforcée */
.etape-badge-optionnel { background: #C2500A; font-size: 0.6rem; } /* CORRIGE : #F97316 ratio 2,8:1 insuffisant → #C2500A ratio 4,7:1 */

/* Labels — CORRIGE : #1F2937 ratio ~9:1 sur blanc (était #374151 insuffisant) */
.etape-fieldset label {
    display: block;
    font-size: 0.9rem;
    color: #1F2937;
    margin-bottom: 0.4rem;
    font-weight: 500;
}

.etape-fieldset select {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    font-size: 0.9rem;
    color: var(--color-text);
    background: var(--color-white);
    cursor: pointer;
    transition: border-color 0.2s;
}
.etape-fieldset select:focus {
    outline: 2px solid var(--color-primary);
    border-color: var(--color-primary);
}
.etape-fieldset select:disabled { background: #F1F5F9; cursor: not-allowed; }

/* Region active */
.region-active-block {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-radius: 8px;
    padding: 0.5rem 0.85rem;
}
.region-active-nom {
    font-weight: 700;
    color: var(--color-primary);
    font-size: 0.95rem;
}
/* CORRIGE : couleur texte bouton #1F2937 au lieu de #374151 */
.btn-reinitialiser {
    background: none;
    border: 1px solid #BFDBFE;
    border-radius: 6px;
    color: #1F2937;
    font-size: 0.85rem;
    padding: 3px 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-reinitialiser:hover { background: #FEF2F2; border-color: #FCA5A5; color: #EF4444; }

/* CORRIGE : couleur texte #1F2937 au lieu de #374151 */
.chargement-communes {
    font-size: 0.9rem;
    color: #1F2937;
    margin-top: 0.4rem;
    font-style: italic;
}

/* Bouton recherche */
.carte-bouton-zone { display: flex; flex-direction: column; gap: 0.5rem; }

.btn-recherche-carte {
    width: 100%;
    padding: 0.85rem;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
}
.btn-recherche-carte:hover:not([disabled]) {
    background: #C2500A; /* CORRIGE : var(--color-accent)=#F97316 ratio 2,8:1 → #C2500A ratio 4,7:1 */
    transform: translateY(-1px);
}
.btn-recherche-carte[disabled] { background: #CBD5E1; cursor: not-allowed; color: #1F2937; } /* CORRIGE */

.carte-note {
    font-size: 0.9rem;
    color: #DC2626;
    text-align: center;
    min-height: 1.2em;
    font-weight: 500;
}

/* Aide */
.aide-grille {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
.aide-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: #F8FAFC;
    border-radius: var(--radius);
    border-left: 4px solid var(--color-accent);
}
.aide-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #C2500A; /* CORRIGE : var(--color-accent)=#F97316 ratio 2,8:1 → #C2500A ratio 4,7:1 */
    color: white;
    font-weight: 700;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
/* CORRIGE : #1F2937 au lieu de #374151 */
.aide-item p { margin: 0; font-size: 0.9rem; color: #1F2937; }
.aide-item strong { color: #111827; }

/* Theme nuit */
body.theme-nuit .carte-zone,
body.theme-nuit .carte-sidebar { background: #1E293B; border: 1px solid #334155; }
body.theme-nuit .etape-fieldset { border-color: #334155; }
body.theme-nuit .etape-fieldset:not([disabled]) { border-color: #3B82F6; }
body.theme-nuit .etape-fieldset legend { color: #F1F5F9; }
body.theme-nuit .etape-fieldset label { color: #CBD5E1; }
body.theme-nuit .etape-fieldset select { background: #0F172A; color: #F1F5F9; border-color: #334155; }
body.theme-nuit .etape-fieldset select:disabled { background: #1E293B; }
body.theme-nuit .region-active-block { background: #1E3A5F; border-color: #3B82F6; }
body.theme-nuit .region-active-nom { color: #93C5FD; }
body.theme-nuit .btn-reinitialiser { color: #CBD5E1; border-color: #334155; }
body.theme-nuit .chargement-communes { color: #94A3B8; }
body.theme-nuit .aide-item { background: #0F172A; }
body.theme-nuit .aide-item p { color: #94A3B8; }
body.theme-nuit .aide-item strong { color: #F1F5F9; }
body.theme-nuit .carte-intro { color: #CBD5E1; }
body.theme-nuit .etape-badge-inactive { background: #334155; color: #CBD5E1; }

/* Responsive */
@media (max-width: 900px) {
    .carte-layout { grid-template-columns: 1fr; }
    .carte-sidebar { position: static; }
}
CSS;

require_once __DIR__ . '/includes/header.inc.php';
?>

<main>

    <!-- EN-TETE -->
    <section class="carte-header">
        <h1>Sélectionnez votre région</h1>
        <p class="carte-intro">
            Cliquez sur une région de la carte ou utilisez les menus déroulants
            pour choisir votre département et votre ville.
        </p>
    </section>

    <!-- LAYOUT PRINCIPAL : carte + sidebar -->
    <section class="carte-layout" aria-label="Sélection géographique">

        <!-- CARTE INTERACTIVE -->
        <div class="carte-zone">
            <h2 class="carte-zone-titre">Carte de France</h2>
            <figure>
                <img
                    src="assets/img/carte-france.webp"
                    alt="Carte administrative des 13 régions de France métropolitaine avec départements"
                    usemap="#carte-france-map"
                    class="carte-img"
                    id="carte-img"
                    width="1200"
                    height="1151"
                />
                <map name="carte-france-map" id="carte-france-map">

                    <!-- Occitanie (76) -->
                    <!-- CORRIGE : tabindex="0", onkeydown remplace onkeypress (déprécié), ajout Espace + preventDefault -->
                    <area shape="poly"
                          coords="372,971,378,951,378,928,397,924,423,921,453,911,469,912,478,917,474,928,482,946,480,934,489,954,498,965,508,975,499,981,490,1002,469,997,456,1010,473,901,621,841,495,872,645,933,643,1013,682,1070,680,1120,697,1125,690,1110,684,1089,685,1080,704,1025,589,1136,646,1137,659,1129,669,1122,535,1103,560,1116,579,1126,591,1137,611,1118,458,1066,504,1087,528,1096,591,1110,420,1089,390,1089,363,1066,392,1007,393,986,385,972,391,1020,394,993,515,833,485,889,424,994,411,963,403,969,401,982,426,954,409,944,438,929,396,959,623,841,591,798,566,796,541,785,528,812,498,852,485,898,640,837,659,805,682,850,647,805,684,850,745,849,723,922,715,961,801,969,765,890,739,850,681,934,666,972,677,998,693,978,715,960,817,970,786,979,761,989,746,1000,728,1015,663,1086,685,1049,694,1048,681,1115,602,1080,680,1111,649,1124,619,1111,692,810,715,798,735,807,756,819,774,858,780,871,795,882,814,883,836,888,844,909,849,920,839,933,833,952,822,959,807,962,799,984,808,978,742,850,714,961,802,960,645,1010,527,875,521,881,745,849,726,916,681,1115,682,850,681,828,649,842,672,831,690,841,696,855,628,864,609,855,628,857,652,833,696,852"
                          href="carte.php?region=76"
                          alt="Occitanie" title="Occitanie"
                          tabindex="0"
                          onclick="return updateRegion('76', 'Occitanie')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('76','Occitanie');}"/>

                    <!-- Corse (94) -->
                    <area shape="poly"
                          coords="1103,1022,1105,1048,1121,1059,1110,1078,1115,1095,1133,1107,1122,1120,1152,1138,1163,1145,1173,1126,1179,1120,1185,1083,1198,1043,1196,1008,1188,978,1190,943,1176,931,1173,972,1157,967,1146,978,1125,984,1117,992"
                          href="carte.php?region=94"
                          alt="Corse" title="Corse"
                          tabindex="0"
                          onclick="return updateRegion('94', 'Corse')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('94','Corse');}"/>

                    <!-- Bretagne (53) -->
                    <area shape="poly"
                          coords="-1,328,7,303,20,300,41,288,75,287,83,298,101,293,109,282,118,275,142,271,162,288,175,309,185,321,216,302,185,323,230,309,254,299,280,311,294,330,320,330,325,355,320,366,324,394,304,418,271,423,230,436,192,459,164,450,133,475,103,412,134,451,126,429,129,439,129,432,66,395,48,400,30,401,3,371,216,310,15,341,36,351,112,347,149,320,110,328,94,327"
                          href="carte.php?region=53"
                          alt="Bretagne" title="Bretagne"
                          tabindex="0"
                          onclick="return updateRegion('53', 'Bretagne')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('53','Bretagne');}"/>

                    <!-- Hauts-de-France (32) -->
                    <area shape="poly"
                          coords="541,132,564,117,556,94,558,25,603,12,632,2,644,34,667,44,691,50,684,40,704,75,728,79,746,95,774,106,779,130,773,143,778,164,772,180,762,187,762,220,740,220,730,227,737,249,727,254,733,265,717,288,687,259,659,259,647,259,616,246,592,245,572,247,564,187,569,170,561,149,550,114,552,139"
                          href="carte.php?region=32"
                          alt="Hauts-de-France" title="Hauts-de-France"
                          tabindex="0"
                          onclick="return updateRegion('32', 'Hauts-de-France')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('32','Hauts-de-France');}"/>

                    <!-- Grand Est (44) -->
                    <area shape="poly"
                          coords="713,351,723,320,713,293,743,224,763,222,773,183,787,149,804,143,827,122,827,142,831,164,860,176,883,200,909,196,934,205,978,205,995,238,1019,240,1062,242,1087,250,1120,258,1111,282,1096,300,1088,325,1083,353,1075,371,1080,388,1073,423,1082,440,1066,458,1038,448,1048,459,1031,427,992,397,981,404,958,399,943,396,913,420,913,436,887,437,881,450,850,424,827,397,811,404,788,409,758,407,731,229,762,410,745,390"
                          href="carte.php?region=44"
                          alt="Grand Est" title="Grand Est"
                          tabindex="0"
                          onclick="return updateRegion('44', 'Grand Est')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('44','Grand Est');}"/>

                    <!-- Normandie (28) -->
                    <area shape="poly"
                          coords="255,171,283,183,298,180,264,184,280,294,372,225,410,231,446,217,431,191,541,137,570,178,567,221,566,244,543,267,530,305,486,314,495,356,482,382,450,361,435,341,412,349,385,338,363,340,344,334,322,333,295,328,281,297,433,192,280,294,324,220,266,219,279,244,280,255,287,291,356,224,266,218,354,236,339,242,258,200,313,210,264,220,431,191,375,229,414,231,339,233,314,185"
                          href="carte.php?region=28"
                          alt="Normandie" title="Normandie"
                          tabindex="0"
                          onclick="return updateRegion('28', 'Normandie')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('28','Normandie');}"/>

                    <!-- Ile-de-France (11) -->
                    <area shape="poly"
                          coords="542,266,570,250,609,249,639,263,686,258,701,277,713,290,718,315,712,333,712,353,679,353,679,372,673,377,650,382,630,383,628,366,614,360,589,365,570,339,555,311"
                          href="carte.php?region=11"
                          alt="Île-de-France" title="Île-de-France"
                          tabindex="0"
                          onclick="return updateRegion('11', 'Île-de-France')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('11','Île-de-France');}"/>

                    <!-- Pays de la Loire (52) -->
                    <area shape="poly"
                          coords="187,486,207,460,227,447,252,432,285,420,307,425,322,399,327,334,312,404,332,335,385,333,332,331,403,348,450,357,392,388,453,356,491,378,449,359,450,357,493,391,473,430,466,437,447,451,426,470,419,496,417,508,398,516,363,520,336,530,348,564,351,594,357,605,338,613,323,614,302,616,280,612,258,595,248,592,230,516,245,578,216,496,453,356,324,360,452,360,209,487,224,480,231,482,487,396,407,437,332,335,416,432"
                          href="carte.php?region=52"
                          alt="Pays de la Loire" title="Pays de la Loire"
                          tabindex="0"
                          onclick="return updateRegion('52', 'Pays de la Loire')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('52','Pays de la Loire');}"/>

                    <!-- Centre-Val de Loire (24) -->
                    <area shape="poly"
                          coords="495,355,498,330,530,302,555,308,571,342,590,364,631,382,670,383,683,406,676,429,662,433,673,459,664,460,665,488,679,524,685,552,675,558,659,566,640,576,643,589,624,593,608,608,568,605,545,607,524,612,510,596,493,581,473,541,465,532,439,538,435,525,415,506"
                          href="carte.php?region=24"
                          alt="Centre-Val de Loire" title="Centre-Val de Loire"
                          tabindex="0"
                          onclick="return updateRegion('24', 'Centre-Val de Loire')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('24','Centre-Val de Loire');}"/>

                    <!-- Bourgogne-Franche-Comte (27) -->
                    <area shape="poly"
                          coords="669,485,674,454,680,428,691,409,682,355,713,354,732,382,755,405,787,407,825,395,850,418,868,442,892,458,896,481,917,433,954,401,995,401,1030,426,1043,454,1031,470,1027,493,1002,518,984,549,962,582,938,621,909,618,851,590,838,635,808,624,789,637,764,637,767,599,745,593,735,565,722,575,701,571,680,564"
                          href="carte.php?region=27"
                          alt="Bourgogne-Franche-Comté" title="Bourgogne-Franche-Comté"
                          tabindex="0"
                          onclick="return updateRegion('27', 'Bourgogne-Franche-Comté')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('27','Bourgogne-Franche-Comté');}"/>

                    <!-- Nouvelle-Aquitaine (75) -->
                    <area shape="poly"
                          coords="271,622,305,619,337,613,356,613,340,523,403,516,436,537,468,532,484,563,490,586,509,601,534,613,608,608,638,642,638,670,631,697,631,741,606,768,595,797,557,789,541,788,523,827,500,845,493,876,472,908,456,908,412,918,386,924,379,956,374,979,391,998,385,1030,370,1044,342,1075,322,1059,294,1049,266,1042,261,1009,233,999,267,937,284,855,303,831,291,809,297,754,305,719,303,702"
                          href="carte.php?region=75"
                          alt="Nouvelle-Aquitaine" title="Nouvelle-Aquitaine"
                          tabindex="0"
                          onclick="return updateRegion('75', 'Nouvelle-Aquitaine')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('75','Nouvelle-Aquitaine');}"/>

                    <!-- Auvergne-Rhone-Alpes (84) -->
                    <area shape="poly"
                          coords="596,802,627,748,636,732,631,687,642,652,624,595,639,595,661,569,693,568,737,573,752,593,767,606,767,626,802,630,836,636,864,592,903,621,936,622,957,621,961,635,975,625,995,602,1019,603,1023,634,1042,652,1039,666,1022,681,1043,713,1060,726,1060,746,1037,767,1008,770,976,780,981,799,956,816,938,833,926,853,912,865,933,888,915,897,891,891,884,879,871,866,859,887,842,877,820,878,796,882,778,853,760,819,738,809,716,794,694,809,682,830,657,800,636,829,629,839,620,832"
                          href="carte.php?region=84"
                          alt="Auvergne-Rhône-Alpes" title="Auvergne-Rhône-Alpes"
                          tabindex="0"
                          onclick="return updateRegion('84', 'Auvergne-Rhône-Alpes')"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('84','Auvergne-Rhône-Alpes');}"/>

                    <!-- Provence-Alpes-Cote d'Azur (93) -->
                    <area shape="poly"
                          coords="833,935,851,922,839,878,867,885,885,892,915,900,929,892,926,841,948,816,986,805,1014,768,1031,790,1051,800,1057,821,1043,844,1048,856,1051,875,1096,894,1120,883,1109,920,1109,936,1093,951,1066,970,1052,980,1039,988,1027,1008,1020,1024,999,1031,975,1036,956,1042,938,1024,911,1024,901,1001,876,1001,861,999,837,1004,816,993,799,984"
                          href="carte.php?region=93"
                          alt="Provence-Alpes-Côte d'Azur" title="Provence-Alpes-Côte d'Azur"
                          tabindex="0"
                          onclick="return updateRegion('93', &quot;Provence-Alpes-Côte d'Azur&quot;)"
                          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();return updateRegion('93',&quot;Provence-Alpes-Côte d'Azur&quot;);}"/>

                </map>
                <figcaption>
                    Carte administrative des 13 régions de France métropolitaine avec départements.
                    Source : IGN — Cliquez sur une région pour la sélectionner.
                </figcaption>
            </figure>
        </div>

        <!-- SIDEBAR : sélection en cascade -->
        <aside class="carte-sidebar" aria-label="Formulaire de sélection géographique">

            <!-- Étape 1 : Région -->
            <fieldset class="etape-fieldset" id="fieldset-region">
                <legend>
                    <span class="etape-badge">1</span>
                    Région
                </legend>
                <div id="region-selectionnee-affichage" class="region-active-block"
                     <?php echo empty($region_selectionnee) ? 'style="display:none"' : ''; ?>>
                    <span class="region-active-nom" id="region-active-nom">
                        <?php echo htmlspecialchars($nom_region_sel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <button type="button" class="btn-reinitialiser" onclick="reinitialiser()"
                            title="Choisir une autre région">
                        &#10005; Changer
                    </button>
                </div>
                <label for="select-region"
                       <?php echo !empty($region_selectionnee) ? 'style="display:none"' : ''; ?>>
                    Choisir une région :
                </label>
                <select id="select-region" name="region"
                        <?php echo !empty($region_selectionnee) ? 'style="display:none"' : ''; ?>>
                    <option value="">-- Sélectionner une région --</option>
                    <?php foreach ($regions as $r): ?>
                    <option value="<?php echo htmlspecialchars((string)$r['code'], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $r['code'] === $region_selectionnee ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string)$r['nom'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </fieldset>

            <!-- Étape 2 : Département -->
            <fieldset class="etape-fieldset" id="fieldset-dept"
                      <?php echo empty($region_selectionnee) ? 'disabled' : ''; ?>>
                <legend>
                    <span class="etape-badge <?php echo empty($region_selectionnee) ? 'etape-badge-inactive' : ''; ?>">2</span>
                    Département
                </legend>
                <label for="select-dept">Choisir un département :</label>
                <select id="select-dept" name="dept"
                        <?php echo empty($region_selectionnee) ? 'disabled' : ''; ?>>
                    <option value="">-- Sélectionner d'abord une région --</option>
                    <?php
                    if (!empty($region_selectionnee) && isset($depts_par_region[$region_selectionnee])):
                        foreach ($depts_par_region[$region_selectionnee] as $d):
                    ?>
                    <option value="<?php echo htmlspecialchars((string)$d['code'], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $d['code'] === $dept_selectionne ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string)$d['nom'], ENT_QUOTES, 'UTF-8'); ?>
                        (<?php echo htmlspecialchars((string)$d['code'], ENT_QUOTES, 'UTF-8'); ?>)
                    </option>
                    <?php endforeach; endif; ?>
                </select>
            </fieldset>

            <!-- Étape 3 : Ville -->
            <fieldset class="etape-fieldset" id="fieldset-ville" disabled>
                <legend>
                    <span class="etape-badge etape-badge-inactive" id="badge-ville">3</span>
                    Ville / Commune
                </legend>
                <label for="select-ville">Choisir une ville :</label>
                <select id="select-ville" name="ville" disabled>
                    <option value="">-- Sélectionner d'abord un département --</option>
                </select>
                <p class="chargement-communes" id="msg-chargement" style="display:none;">
                    &#8987; Chargement des communes...
                </p>
            </fieldset>

            <!-- Étape 4 : Carburant (optionnel) -->
            <fieldset class="etape-fieldset" id="fieldset-carburant">
                <legend>
                    <span class="etape-badge etape-badge-optionnel">&#9654;</span>
                    Carburant (optionnel)
                </legend>
                <label for="select-carburant">Type de carburant :</label>
                <select id="select-carburant" name="carburant">
                    <option value="">-- Tous les carburants --</option>
                    <option value="SP95">SP95</option>
                    <option value="SP98">SP98</option>
                    <option value="E10">E10</option>
                    <option value="E85">E85</option>
                    <option value="Gazole">Gazole (Diesel)</option>
                    <option value="GPLc">GPLc</option>
                </select>
            </fieldset>

            <!-- Bouton de recherche -->
            <div class="carte-bouton-zone">
                <button type="button"
                        id="btn-rechercher"
                        class="btn-recherche-carte"
                        onclick="lancerRecherche()"
                        disabled>
                    &#128269; Voir les stations
                </button>
                <p class="carte-note" id="note-manquant" aria-live="polite"></p>
            </div>

        </aside>
    </section>

    <!-- AIDE -->
    <section class="aide-section" aria-labelledby="titre-aide">
        <h2 id="titre-aide">Comment utiliser la carte ?</h2>
        <div class="aide-grille">
            <div class="aide-item">
                <span class="aide-num" aria-hidden="true">1</span>
                <p><strong>Cliquez sur une région</strong> directement sur la carte
                   ou utilisez le menu déroulant &laquo;&nbsp;Région&nbsp;&raquo;.</p>
            </div>
            <div class="aide-item">
                <span class="aide-num" aria-hidden="true">2</span>
                <p><strong>Choisissez votre département</strong> dans la liste
                   qui se déroule automatiquement.</p>
            </div>
            <div class="aide-item">
                <span class="aide-num" aria-hidden="true">3</span>
                <p><strong>Sélectionnez votre ville</strong> parmi les communes
                   chargées depuis nos données CSV.</p>
            </div>
            <div class="aide-item">
                <span class="aide-num" aria-hidden="true">4</span>
                <p><strong>Cliquez sur &laquo;&nbsp;Voir les stations&nbsp;&raquo;</strong>
                   pour afficher les prix en temps réel depuis l'API gouvernementale.</p>
            </div>
        </div>
    </section>

</main>

<script>
(function() {
    var IMG_W_ORIG = 1200;
    var IMG_H_ORIG = 1151;
    function rescaleMap() {
        var img = document.getElementById('carte-img');
        if (!img) return;
        var ratioW = img.offsetWidth  / IMG_W_ORIG;
        var ratioH = img.offsetHeight / IMG_H_ORIG;
        var areas = document.querySelectorAll('#carte-france-map area');
        areas.forEach(function(area) {
            var orig = area.getAttribute('data-coords-orig');
            if (!orig) {
                orig = area.getAttribute('coords');
                area.setAttribute('data-coords-orig', orig);
            }
            var nums = orig.split(',').map(Number);
            var rescaled = [];
            for (var i = 0; i < nums.length; i++) {
                rescaled.push(i % 2 === 0
                    ? Math.round(nums[i] * ratioW)
                    : Math.round(nums[i] * ratioH));
            }
            area.setAttribute('coords', rescaled.join(','));
        });
    }
    window.addEventListener('load',   rescaleMap);
    window.addEventListener('resize', rescaleMap);
})();

var DEPTS_PAR_REGION = <?php echo json_encode($depts_par_region, JSON_UNESCAPED_UNICODE); ?>;

function updateRegion(codeRegion, nomRegion) {
    var bloc    = document.getElementById('region-selectionnee-affichage');
    var nomSpan = document.getElementById('region-active-nom');
    var selectR = document.getElementById('select-region');
    var labelR  = document.querySelector('label[for="select-region"]');

    nomSpan.textContent   = nomRegion;
    bloc.style.display    = 'flex';
    selectR.style.display = 'none';
    if (labelR) labelR.style.display = 'none';
    selectR.value = codeRegion;

    var selectDept   = document.getElementById('select-dept');
    var fieldsetDept = document.getElementById('fieldset-dept');
    var badgeDept    = fieldsetDept.querySelector('.etape-badge');

    selectDept.innerHTML = '<option value="">-- Sélectionner un département --</option>';
    var depts = DEPTS_PAR_REGION[codeRegion] || [];
    depts.forEach(function(d) {
        var opt = document.createElement('option');
        opt.value       = d.code;
        opt.textContent = d.nom + ' (' + d.code + ')';
        selectDept.appendChild(opt);
    });

    fieldsetDept.removeAttribute('disabled');
    selectDept.removeAttribute('disabled');
    if (badgeDept) badgeDept.classList.remove('etape-badge-inactive');

    reinitialiserVille();
    return false;
}

function reinitialiser() {
    var bloc    = document.getElementById('region-selectionnee-affichage');
    var selectR = document.getElementById('select-region');
    var labelR  = document.querySelector('label[for="select-region"]');

    bloc.style.display    = 'none';
    selectR.style.display = '';
    selectR.value         = '';
    if (labelR) labelR.style.display = '';

    var fieldsetDept = document.getElementById('fieldset-dept');
    var selectDept   = document.getElementById('select-dept');
    fieldsetDept.setAttribute('disabled', '');
    selectDept.setAttribute('disabled', '');
    selectDept.innerHTML = '<option value="">-- Sélectionner d\'abord une région --</option>';
    var badgeDept = fieldsetDept.querySelector('.etape-badge');
    if (badgeDept) badgeDept.classList.add('etape-badge-inactive');

    reinitialiserVille();
}

function reinitialiserVille() {
    var fieldsetVille = document.getElementById('fieldset-ville');
    var selectVille   = document.getElementById('select-ville');
    var badgeVille    = document.getElementById('badge-ville');
    var btnRechercher = document.getElementById('btn-rechercher');
    var noteManquant  = document.getElementById('note-manquant');

    fieldsetVille.setAttribute('disabled', '');
    selectVille.setAttribute('disabled', '');
    selectVille.innerHTML = '<option value="">-- Sélectionner d\'abord un département --</option>';
    if (badgeVille)    badgeVille.classList.add('etape-badge-inactive');
    if (btnRechercher) btnRechercher.setAttribute('disabled', '');
    if (noteManquant)  noteManquant.textContent = '';
}

document.addEventListener('DOMContentLoaded', function() {

    var selectR = document.getElementById('select-region');
    selectR.addEventListener('change', function() {
        var code = this.value;
        if (!code) { reinitialiser(); return; }
        updateRegion(code, this.options[this.selectedIndex].text);
    });

    var selectDept = document.getElementById('select-dept');
    selectDept.addEventListener('change', function() {
        var codeDept      = this.value;
        var fieldsetVille = document.getElementById('fieldset-ville');
        var selectVille   = document.getElementById('select-ville');
        var badgeVille    = document.getElementById('badge-ville');
        var msgCharg      = document.getElementById('msg-chargement');
        var btnRechercher = document.getElementById('btn-rechercher');

        if (!codeDept) { reinitialiserVille(); return; }

        selectVille.innerHTML = '<option value="">Chargement...</option>';
        selectVille.setAttribute('disabled', '');
        if (msgCharg)      msgCharg.style.display = 'block';
        if (btnRechercher) btnRechercher.setAttribute('disabled', '');

        fetch('communes.php?dept=' + encodeURIComponent(codeDept))
            .then(function(r) {
                if (!r.ok) throw new Error('Erreur HTTP ' + r.status);
                return r.json();
            })
            .then(function(communes) {
                selectVille.innerHTML = '<option value="">-- Sélectionner une ville --</option>';
                communes.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = opt.textContent = c.nom;
                    selectVille.appendChild(opt);
                });
                fieldsetVille.removeAttribute('disabled');
                selectVille.removeAttribute('disabled');
                if (badgeVille) badgeVille.classList.remove('etape-badge-inactive');
                if (msgCharg)   msgCharg.style.display = 'none';
            })
            .catch(function(err) {
                selectVille.innerHTML = '<option value="">Erreur de chargement</option>';
                if (msgCharg) msgCharg.style.display = 'none';
                console.error('Erreur chargement communes :', err);
            });
    });

    var selectVille   = document.getElementById('select-ville');
    var btnRechercher = document.getElementById('btn-rechercher');
    var noteManquant  = document.getElementById('note-manquant');

    selectVille.addEventListener('change', function() {
        if (this.value) {
            btnRechercher.removeAttribute('disabled');
            if (noteManquant) noteManquant.textContent = '';
        } else {
            btnRechercher.setAttribute('disabled', '');
        }
    });

    <?php if (!empty($region_selectionnee)): ?>
    updateRegion(
        <?php echo json_encode($region_selectionnee); ?>,
        <?php echo json_encode($nom_region_sel); ?>
    );
    <?php if (!empty($dept_selectionne)): ?>
    document.getElementById('select-dept').value = <?php echo json_encode($dept_selectionne); ?>;
    document.getElementById('select-dept').dispatchEvent(new Event('change'));
    <?php endif; ?>
    <?php endif; ?>
});

function lancerRecherche() {
    var ville     = document.getElementById('select-ville').value;
    var dept      = document.getElementById('select-dept').value;
    var region    = document.getElementById('select-region').value;
    var carburant = document.getElementById('select-carburant').value;
    var note      = document.getElementById('note-manquant');

    if (!ville) { if (note) note.textContent = 'Veuillez sélectionner une ville.'; return; }
    if (!dept)  { if (note) note.textContent = 'Veuillez sélectionner un département.'; return; }

    window.location.href = 'resultats.php?' + new URLSearchParams({
        ville: ville, dept: dept, region: region, carburant: carburant
    }).toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.inc.php'; ?>