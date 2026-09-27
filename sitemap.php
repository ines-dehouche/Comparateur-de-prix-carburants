<?php
/**
 * @file sitemap.php
 * @brief Plan du site Carbora - toutes les pages accessibles.
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/lib/cookie_helper.php';

traiter_changement_theme();
$theme_actif = get_theme();

$titre = 'Plan du site - Carbora';

require_once __DIR__ . '/includes/header.inc.php';
?>

<main class="sitemap-main">

    <div class="sitemap-hero">
        <h1>Plan du site</h1>
        <p>Retrouvez ici l'ensemble des pages disponibles sur <strong>Carbora</strong></p>
    </div>

    <div class="sitemap-grid">

        <!-- ── Accueil ── -->
        <section class="sitemap-card" aria-labelledby="sm-accueil">
            <div class="sm-card-header">
                <span class="sm-icon" aria-hidden="true">🏠</span>
                <h2 id="sm-accueil">Accueil</h2>
            </div>
            <ul class="sm-links">
                <li>
                    <a href="index.php">
                        <span class="sm-link-name">Page d'accueil</span>
                        <span class="sm-link-desc">Présentation du site et recherche rapide</span>
                    </a>
                </li>
            </ul>
        </section>

        <!-- ── Recherche & Résultats ── -->
        <section class="sitemap-card" aria-labelledby="sm-recherche">
            <div class="sm-card-header">
                <span class="sm-icon" aria-hidden="true">⛽</span>
                <h2 id="sm-recherche">Prix des carburants</h2>
            </div>
            <ul class="sm-links">
                <li>
                    <a href="carte.php">
                        <span class="sm-link-name">Carte des régions</span>
                        <span class="sm-link-desc">Sélectionner une région sur la carte interactive</span>
                    </a>
                </li>
                <li>
                    <a href="resultats.php">
                        <span class="sm-link-name">Résultats de recherche</span>
                        <span class="sm-link-desc">Liste des stations et prix par ville / département</span>
                    </a>
                </li>
            </ul>
        </section>

        <!-- ── Données & Stats ── -->
        <section class="sitemap-card" aria-labelledby="sm-stats">
            <div class="sm-card-header">
                <span class="sm-icon" aria-hidden="true">📊</span>
                <h2 id="sm-stats">Statistiques</h2>
            </div>
            <ul class="sm-links">
                <li>
                    <a href="statistiques.php">
                        <span class="sm-link-name">Statistiques d'utilisation</span>
                        <span class="sm-link-desc">Villes les plus consultées, carburants recherchés, tendances</span>
                    </a>
                </li>
            </ul>
        </section>

        <!-- ── Technique ── -->
        <section class="sitemap-card" aria-labelledby="sm-tech">
            <div class="sm-card-header">
                <span class="sm-icon" aria-hidden="true">🔧</span>
                <h2 id="sm-tech">Technique</h2>
            </div>
            <ul class="sm-links">
                <li>
                    <a href="tech.php">
                        <span class="sm-link-name">Page Tech / Développeur</span>
                        <span class="sm-link-desc">API Ghibli, géolocalisation par IP, informations techniques</span>
                    </a>
                </li>
            </ul>
        </section>

        <!-- ── Navigation ── -->
        <section class="sitemap-card" aria-labelledby="sm-nav">
            <div class="sm-card-header">
                <span class="sm-icon" aria-hidden="true">🗺️</span>
                <h2 id="sm-nav">Navigation</h2>
            </div>
            <ul class="sm-links">
                <li>
                    <a href="sitemap.php" aria-current="page">
                        <span class="sm-link-name">Plan du site</span>
                        <span class="sm-link-desc">Cette page — vue d'ensemble de toutes les sections</span>
                    </a>
                </li>
            </ul>
        </section>

        <!-- ── APIs utilisées ── -->
        <section class="sitemap-card sitemap-card-wide" aria-labelledby="sm-api">
            <div class="sm-card-header">
                <span class="sm-icon" aria-hidden="true">🌐</span>
                <h2 id="sm-api">APIs &amp; ressources externes</h2>
            </div>
            <ul class="sm-links sm-links-inline">
                <li>
                    <a href="https://data.economie.gouv.fr/explore/dataset/prix-des-carburants-en-france-flux-instantane-v2/api/"
                       target="_blank" rel="noopener noreferrer">
                        <span class="sm-link-name">API Prix carburants <span class="sm-ext" aria-label="lien externe">↗</span></span>
                        <span class="sm-link-desc">data.economie.gouv.fr — flux instantané v2</span>
                    </a>
                </li>
                <li>
                    <a href="https://ghibliapi.vercel.app/" target="_blank" rel="noopener noreferrer">
                        <span class="sm-link-name">Ghibli API <span class="sm-ext" aria-label="lien externe">↗</span></span>
                        <span class="sm-link-desc">Films du Studio Ghibli au format JSON</span>
                    </a>
                </li>
                <li>
                    <a href="https://ipinfo.io/" target="_blank" rel="noopener noreferrer">
                        <span class="sm-link-name">IPinfo <span class="sm-ext" aria-label="lien externe">↗</span></span>
                        <span class="sm-link-desc">Géolocalisation approximative par adresse IP</span>
                    </a>
                </li>
            </ul>
        </section>

    </div><!-- /.sitemap-grid -->

    <!-- Arborescence visuelle -->
    <section class="sitemap-tree-section" aria-labelledby="sm-arbo">
        <h2 id="sm-arbo">Arborescence du site</h2>
        <div class="sm-tree" role="tree" aria-label="Arborescence des pages">
            <div class="sm-tree-root" role="treeitem">
                <span class="sm-tree-icon">🌐</span>
                <span class="sm-tree-label">carbora — <em>kahina.alwaysdata.net</em></span>
            </div>
            <ul class="sm-tree-children" role="group">
                <li role="treeitem">
                    <a href="index.php">
                        <span class="sm-tree-icon">🏠</span> index.php
                        <span class="sm-tree-desc">— Accueil &amp; recherche rapide</span>
                    </a>
                </li>
                <li role="treeitem">
                    <a href="carte.php">
                        <span class="sm-tree-icon">🗺️</span> carte.php
                        <span class="sm-tree-desc">— Sélection région par carte</span>
                    </a>
                </li>
                <li role="treeitem">
                    <a href="resultats.php">
                        <span class="sm-tree-icon">⛽</span> resultats.php
                        <span class="sm-tree-desc">— Prix des stations par ville</span>
                    </a>
                </li>
                <li role="treeitem">
                    <a href="statistiques.php">
                        <span class="sm-tree-icon">📊</span> statistiques.php
                        <span class="sm-tree-desc">— Statistiques d'utilisation</span>
                    </a>
                </li>
                <li role="treeitem">
                    <a href="tech.php">
                        <span class="sm-tree-icon">🔧</span> tech.php
                        <span class="sm-tree-desc">— Page technique / développeur</span>
                    </a>
                </li>
                <li role="treeitem" aria-current="page">
                    <a href="sitemap.php">
                        <span class="sm-tree-icon">📋</span> sitemap.php
                        <span class="sm-tree-desc">— Plan du site (cette page)</span>
                    </a>
                    <ul class="sm-tree-children sm-tree-children-sub" role="group">
                        <li role="treeitem">
                            <span class="sm-tree-icon">📁</span> includes/
                            <span class="sm-tree-desc">— header.inc.php, footer.inc.php, config.inc.php</span>
                        </li>
                        <li role="treeitem">
                            <span class="sm-tree-icon">📁</span> lib/
                            <span class="sm-tree-desc">— csv_handler.php, cookie_helper.php</span>
                        </li>
                        <li role="treeitem">
                            <span class="sm-tree-icon">📁</span> assets/
                            <span class="sm-tree-desc">— css/style.css, img/, js/</span>
                        </li>
                        <li role="treeitem">
                            <span class="sm-tree-icon">📁</span> data/
                            <span class="sm-tree-desc">— consultations.csv, compteur.txt</span>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </section>

</main>

<style>
/* ============================================================
   sitemap.php — Styles internes (complètent style.css)
   ============================================================ */

.sitemap-main {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
}

/* ── Hero ── */
.sitemap-hero {
    padding: 2.5rem 0 2rem;
    border-bottom: 1px solid var(--color-border, #E2E8F0);
    margin-bottom: 2rem;
}
.sitemap-hero h1 {
    font-size: 1.7rem;
    font-weight: 700;
    color: var(--color-heading, #1E293B);
    margin-bottom: .4rem;
}
.sitemap-hero p {
    font-size: .95rem;
    color: var(--color-text-light, #64748B);
}
.sitemap-hero strong {
    color: #D85A30;
}

/* ── Grille cards ── */
.sitemap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}

.sitemap-card {
    background: var(--color-card-bg, #fff);
    border: 1px solid var(--color-border, #E2E8F0);
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    transition: box-shadow .2s, transform .15s;
}
.sitemap-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    transform: translateY(-2px);
}
.sitemap-card-wide {
    grid-column: 1 / -1;
}

.sm-card-header {
    display: flex;
    align-items: center;
    gap: .7rem;
    margin-bottom: 1rem;
    padding-bottom: .75rem;
    border-bottom: 2px solid #D85A30;
}
.sm-icon { font-size: 1.4rem; }
.sm-card-header h2 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-heading, #1E293B);
    margin: 0;
}

/* ── Liens dans les cards ── */
.sm-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: .6rem;
}
.sm-links-inline {
    flex-direction: row;
    flex-wrap: wrap;
    gap: .75rem;
}
.sm-links li a,
.sm-links-inline li a {
    display: flex;
    flex-direction: column;
    gap: .2rem;
    padding: .6rem .75rem;
    border-radius: 8px;
    text-decoration: none;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    transition: background .15s, border-color .15s;
}
.sm-links-inline li a {
    min-width: 200px;
}
.sm-links li a:hover,
.sm-links-inline li a:hover {
    background: #DBEAFE;
    border-color: #93C5FD;
}
.sm-links li a[aria-current="page"] {
    background: #FEF3C7;
    border-color: #F59E0B;
}
.sm-link-name {
    font-size: .9rem;
    font-weight: 700;
    color: #1D4ED8;
}
.sm-link-desc {
    font-size: .8rem;
    color: #374151;
    line-height: 1.4;
    font-weight: 400;
}
.sm-ext {
    font-size: .75rem;
    opacity: .7;
    margin-left: .2rem;
}

/* ── Arborescence ── */
.sitemap-tree-section {
    background: var(--color-card-bg, #fff);
    border: 1px solid var(--color-border, #E2E8F0);
    border-radius: 12px;
    padding: 1.5rem;
}
.sitemap-tree-section h2 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-heading, #1E293B);
    margin-bottom: 1.25rem;
    padding-bottom: .6rem;
    border-bottom: 2px solid #D85A30;
}

.sm-tree { font-size: .88rem; }
.sm-tree-root {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-weight: 700;
    color: var(--color-heading, #1E293B);
    margin-bottom: .75rem;
    font-size: .95rem;
}
.sm-tree-root em { font-style: normal; color: #D85A30; font-weight: 400; }
.sm-tree-children {
    list-style: none;
    padding: 0;
    margin: 0 0 0 1.5rem;
    border-left: 2px solid var(--color-border, #E2E8F0);
}
.sm-tree-children li {
    position: relative;
    padding: .35rem 0 .35rem 1.2rem;
    color: var(--color-text, #334155);
}
.sm-tree-children li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 1rem;
    height: 1px;
    background: var(--color-border, #E2E8F0);
}
.sm-tree-children a {
    text-decoration: none;
    color: var(--color-primary, #1D4ED8);
    font-weight: 500;
    transition: color .15s;
}
.sm-tree-children a:hover { color: #D85A30; }
.sm-tree-children li[aria-current="page"] > a { color: #D85A30; font-weight: 700; }
.sm-tree-icon { font-size: .9rem; margin-right: .25rem; }
.sm-tree-desc { color: #374151; font-weight: 400; font-size: .82rem; }
.sm-tree-children-sub {
    margin-top: .5rem;
    margin-left: 1.2rem;
    border-left-style: dashed;
    border-left-color: #CBD5E1;
}
.sm-tree-children-sub li {
    color: #374151;
    font-size: .82rem;
}
.sm-tree-children-sub li::before { background: #CBD5E1; }

/* ── Thème nuit ── */
body.theme-nuit .sitemap-hero h1 { color: #F1F5F9; }
body.theme-nuit .sitemap-hero p  { color: #94A3B8; }
body.theme-nuit .sitemap-card,
body.theme-nuit .sitemap-tree-section { background: #1E293B; border-color: #334155; }
body.theme-nuit .sitemap-card:hover   { box-shadow: 0 4px 20px rgba(0,0,0,.3); }
body.theme-nuit .sm-card-header h2    { color: #F1F5F9; }
body.theme-nuit .sitemap-tree-section h2 { color: #F1F5F9; }
body.theme-nuit .sm-links li a,
body.theme-nuit .sm-links-inline li a { background: #1E3A5F; border-color: #2563EB; }
body.theme-nuit .sm-links li a:hover,
body.theme-nuit .sm-links-inline li a:hover { background: #1D4ED8; border-color: #60A5FA; }
body.theme-nuit .sm-links li a[aria-current="page"] { background: #78350F; border-color: #F59E0B; }
body.theme-nuit .sm-link-name  { color: #93C5FD; }
body.theme-nuit .sm-link-desc  { color: #CBD5E1; }
body.theme-nuit .sm-tree-root  { color: #F1F5F9; }
body.theme-nuit .sm-tree-children { border-left-color: #334155; }
body.theme-nuit .sm-tree-children li { color: #CBD5E1; }
body.theme-nuit .sm-tree-children li::before { background: #334155; }
body.theme-nuit .sm-tree-children a  { color: #93C5FD; }
body.theme-nuit .sm-tree-desc  { color: #94A3B8; }
body.theme-nuit .sm-tree-children-sub li { color: #94A3B8; }
body.theme-nuit .sm-tree-children-sub li::before { background: #334155; }

/* ── Responsive ── */
@media (max-width: 600px) {
    .sitemap-grid { grid-template-columns: 1fr; }
    .sm-links-inline { flex-direction: column; }
    .sm-tree-children { margin-left: .75rem; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.inc.php'; ?>