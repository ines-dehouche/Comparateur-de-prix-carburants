<?php
/**
 * @file statistiques.php
 * @brief Page de statistiques d'utilisation du site Carbora.
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.2 — Corrections accessibilité WAVE (contraste, liens redondants)
 */
declare(strict_types=1);
$titre = 'Statistiques - Carbora';
require_once __DIR__ . '/includes/config.inc.php';
require_once __DIR__ . '/lib/cookie_helper.php';
require_once __DIR__ . '/lib/csv_handler.php';
traiter_changement_theme();
$theme_actif = get_theme();
$css_theme   = get_css_theme($theme_actif);
$nombreVisites       = incrementer_hits();
$top_villes          = top_villes_consultees(10);
$total_consultations = array_sum($top_villes);
$max_villes          = !empty($top_villes) ? max($top_villes) : 1;
$stats_carb          = stats_carburants();
$total_carb          = array_sum($stats_carb);
$max_carb            = !empty($stats_carb) ? max($stats_carb) : 1;
$stats_jours         = stats_jours_semaine();
$max_jours           = !empty($stats_jours) ? max($stats_jours) : 1;
$stats_heures        = stats_heures();
$max_heures          = !empty($stats_heures) ? max($stats_heures) : 1;
$top_regions         = top_regions_consultees(13);
$max_regions         = !empty($top_regions) ? max($top_regions) : 1;
$dernieres           = array_slice(array_reverse(lire_consultations()), 0, 10);
$nb_villes_distinctes = count(top_villes_consultees(9999));
$couleurs_carb = [
    'SP95'   => ['bg'=>'#92400E','text'=>'#FFFBEB','bar'=>'#F59E0B'],
    'SP98'   => ['bg'=>'#831843','text'=>'#FDF2F8','bar'=>'#EC4899'],
    'E10'    => ['bg'=>'#064E3B','text'=>'#ECFDF5','bar'=>'#10B981'],
    'E85'    => ['bg'=>'#4C1D95','text'=>'#F5F3FF','bar'=>'#8B5CF6'],
    'Gazole' => ['bg'=>'#1E3A8A','text'=>'#EFF6FF','bar'=>'#3B82F6'],
    'GPLc'   => ['bg'=>'#881337','text'=>'#FFF1F2','bar'=>'#F43F5E'],
    'Tous'   => ['bg'=>'#334155','text'=>'#F8FAFC','bar'=>'#94A3B8'],
];

$styles_extra = <<<CSS
:root {
    --radius: 12px;
    --shadow: 0 2px 12px rgba(0,0,0,.07);
    --color-white: #ffffff;
    --color-border: #E2E8F0;
    --color-text: #1F2937;
    --color-bg-dark: #1E293B;
    --color-primary: #1D4ED8;
    --color-accent: #D85A30;
}
main {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem 1.5rem 3rem;
}
.stats-header h1{margin-bottom:.5rem}
.stats-intro{color:#4B5563;margin-bottom:1.5rem;font-size:.95rem}
.stats-intro code{background:#E2E8F0;padding:1px 6px;border-radius:4px;font-size:.85rem;color:#1F2937}
.metriques-grille{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:2rem}
.metrique-card{background:#F8FAFC;border:1px solid var(--color-border);border-radius:var(--radius);padding:1.25rem 1rem;text-align:center;display:flex;flex-direction:column;gap:.35rem}
.metrique-accent{background:linear-gradient(135deg,#1D4ED8,#F97316);border-color:transparent;color:white}
.metrique-accent .metrique-label{color:rgba(255,255,255,.85)}
.metrique-valeur{font-size:2rem;font-weight:700;color:var(--color-accent);line-height:1}
.metrique-accent .metrique-valeur{color:white}
.metrique-label{font-size:.78rem;color:#4B5563;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.stats-grille-principale{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
.stats-card{background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow)}
.stats-card h2{font-size:1.05rem;margin-bottom:1rem}
.stats-card-large{grid-column:1/-1}
.stats-legende{font-size:.88rem;color:#4B5563;margin-bottom:1rem}
.histogramme{display:flex;flex-direction:column;gap:.65rem}
.barre-item{display:grid;grid-template-columns:32px 140px 1fr auto 28px;align-items:center;gap:.65rem}
.barre-rang{font-size:1rem;text-align:center}
.barre-label{font-size:.92rem;font-weight:600;color:var(--color-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.barre-track{background:#E2E8F0;border-radius:20px;height:20px;overflow:hidden}
.barre-fill{height:100%;border-radius:20px;transition:width .8s ease}
.barre-or{background:linear-gradient(90deg,#F59E0B,#F97316)}
.barre-argent{background:linear-gradient(90deg,#94A3B8,#CBD5E1)}
.barre-bronze{background:linear-gradient(90deg,#CD7F32,#B45309)}
.barre-normal{background:linear-gradient(90deg,#1D4ED8,#3B82F6)}
.barre-valeur{font-size:.82rem;font-weight:600;color:var(--color-text);white-space:nowrap;min-width:70px;text-align:right}
.barre-unite{font-weight:400;color:#4B5563;font-size:.78rem}
.barre-lien{color:var(--color-primary);font-weight:700;text-decoration:none;padding:2px 5px;border-radius:5px}
.barre-lien:hover{background:var(--color-accent);color:white}
.carb-liste{display:flex;flex-direction:column;gap:.75rem}
.carb-item{display:flex;flex-direction:column;gap:.3rem}
.carb-header{display:flex;align-items:center;gap:.5rem}
.carb-badge{display:inline-block;padding:.2rem .7rem;border-radius:12px;font-size:.85rem;font-weight:700;border:1px solid rgba(0,0,0,0.15);}
.carb-badge-sm{display:inline-block;padding:.15rem .6rem;border-radius:10px;font-size:.78rem;font-weight:700}
.carb-pct{font-size:.82rem;color:#4B5563;margin-left:auto}
.carb-nb{font-size:.78rem;color:#374151}
.carb-track{background:#E2E8F0;border-radius:10px;height:10px;overflow:hidden}
.carb-fill{height:100%;border-radius:10px;transition:width .8s ease}
.barres-verticales{display:flex;align-items:flex-end;gap:.5rem;height:120px;padding:0 .5rem}
.barre-v-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%}
.barre-v-nb{font-size:.72rem;color:#4B5563;min-height:16px}
.barre-v-track{flex:1;width:100%;background:#E2E8F0;border-radius:4px 4px 0 0;display:flex;align-items:flex-end;overflow:hidden}
.barre-v-fill{width:100%;background:linear-gradient(180deg,#3B82F6,#1D4ED8);border-radius:4px 4px 0 0;transition:height .8s ease;min-height:0}
.barre-v-max{background:linear-gradient(180deg,#F97316,#D85A30)}
.barre-v-label{font-size:.75rem;color:#4B5563;font-weight:500}
.region-nom{display:flex;align-items:center;gap:.5rem}
.region-nom-label{font-size:.88rem;font-weight:600;color:var(--color-text,#1F2937)}
.region-code{font-size:.80rem;color:#374151;background:#E2E8F0;padding:1px 6px;border-radius:10px;font-weight:600;flex-shrink:0}
.btn-voir-resultats{display:inline-block;padding:.28rem .75rem;background:#1D4ED8;color:#ffffff !important;border-radius:8px;font-size:.78rem;font-weight:700;text-decoration:none;transition:background .2s;border:none}
.btn-voir-resultats:hover{background:#D85A30;color:#ffffff !important}
.heures-grille{display:grid;grid-template-columns:repeat(6,1fr);gap:4px;margin-bottom:.75rem}
.heure-case{border-radius:6px;padding:6px 4px;text-align:center;cursor:default;transition:transform .15s}
.heure-case:hover{transform:scale(1.1)}
.heure-num{display:block;font-size:.8rem;font-weight:700}
.heure-nb{display:block;font-size:.75rem;min-height:12px}
.heure-nulle{background:#F1F5F9;color:#4B5563}
.heure-faible{background:#DBEAFE;color:#1D4ED8}
.heure-moyenne{background:#1D4ED8;color:#ffffff}
.heure-forte{background:#1E3A8A;color:#ffffff}
.heures-legende{font-size:.75rem;color:#4B5563;display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.heures-legende span{display:inline-block;width:12px;height:12px;border-radius:2px;vertical-align:middle}
.regions-grille{display:flex;flex-direction:column;gap:.55rem}
.region-item{display:grid;grid-template-columns:220px 1fr 100px;align-items:center;gap:.65rem}
body.theme-nuit .region-code{background:#334155;color:#CBD5E1}
body.theme-nuit .region-nom-label{color:#F1F5F9}
.region-track{background:#E2E8F0;border-radius:10px;height:12px;overflow:hidden}
.region-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,#1D4ED8,#3B82F6);transition:width .8s ease}
.region-stats{display:flex;justify-content:space-between;font-size:.78rem}
.region-pct{color:#374151}
.tableau-container{overflow-x:auto;margin:1rem 0}
.tableau-consultations{width:100%;border-collapse:collapse;font-size:.88rem}
.tableau-consultations caption{font-size:.82rem;color:#4B5563;margin-bottom:.5rem;text-align:left}
.tableau-consultations th{background:var(--color-bg-dark);color:white;padding:.65rem 1rem;text-align:left;font-weight:600;font-size:.82rem}
.tableau-consultations td{padding:.6rem 1rem;border-bottom:1px solid #E2E8F0;vertical-align:middle}
.td-horodatage code{font-size:.78rem;color:#1F2937;background:#E2E8F0;padding:2px 6px;border-radius:4px}
.td-ville strong{color:var(--color-bg-dark)}
.source-donnees{font-size:.82rem;color:#4B5563;margin-top:.5rem}
.aucune-donnee{padding:2rem;text-align:center;color:#4B5563;background:#F8FAFC;border-radius:var(--radius);border:2px dashed #CBD5E1;font-size:.9rem}
.csv-info-section{margin-top:0}
.csv-info-grille{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.25rem}
.csv-info-item{display:flex;align-items:flex-start;gap:1rem;padding:1.25rem;background:#F8FAFC;border-radius:var(--radius);border-left:4px solid var(--color-accent)}
.csv-info-item h3{margin-bottom:.4rem;font-size:1rem}
.csv-info-item p{font-size:.88rem;color:#4B5563;margin:0}
.csv-info-item code{background:#E2E8F0;padding:1px 5px;border-radius:4px;font-size:.82rem}
body.theme-nuit .metrique-card{background:#1E293B;border-color:#334155}
body.theme-nuit .metrique-valeur{color:#F97316}
body.theme-nuit .metrique-label{color:#94A3B8}
body.theme-nuit .stats-card{background:#1E293B;border-color:#334155}
body.theme-nuit .stats-card h2{color:#F1F5F9}
body.theme-nuit .barre-track,body.theme-nuit .barre-v-track,body.theme-nuit .carb-track,body.theme-nuit .region-track{background:#334155}
body.theme-nuit .barre-label,body.theme-nuit .barre-valeur,body.theme-nuit .region-nom{color:#F1F5F9}
body.theme-nuit .barre-unite,body.theme-nuit .carb-pct,body.theme-nuit .region-pct{color:#94A3B8}
body.theme-nuit .stats-intro,body.theme-nuit .stats-legende{color:#94A3B8}
body.theme-nuit .heure-nulle{background:#1E293B;color:#94A3B8}
body.theme-nuit .heure-faible{background:#1E3A5F;color:#93C5FD}
body.theme-nuit .heure-moyenne{background:#1D4ED8;color:#DBEAFE}
body.theme-nuit .heure-forte{background:#1E3A8A;color:#ffffff}
body.theme-nuit .tableau-consultations td{border-bottom-color:#334155;color:#F1F5F9}
body.theme-nuit .td-horodatage code{background:#334155;color:#94A3B8}
body.theme-nuit .td-ville strong{color:#F1F5F9}
body.theme-nuit .csv-info-item{background:#0F172A}
body.theme-nuit .csv-info-item p{color:#94A3B8}
body.theme-nuit .aucune-donnee{background:#1E293B;border-color:#334155;color:#94A3B8}
@media(max-width:768px){
.stats-grille-principale{grid-template-columns:1fr}
.stats-card-large{grid-column:1}
.barre-item{grid-template-columns:28px 1fr}
.barre-track,.barre-valeur,.barre-lien{grid-column:1/-1}
.region-item{grid-template-columns:1fr}
.metriques-grille{grid-template-columns:repeat(2,1fr)}
.heures-grille{grid-template-columns:repeat(4,1fr)}}
CSS;

require_once __DIR__ . '/includes/header.inc.php';
?>
<main>
<section class="stats-header">
<h1>Statistiques d'utilisation</h1>
<p class="stats-intro">Suivez l'activité du site : visites, recherches et villes les plus consultées.</p>
<div class="metriques-grille">
<div class="metrique-card"><span class="metrique-valeur"><?php echo number_format($nombreVisites,0,',',' '); ?></span><span class="metrique-label">Visites totales</span></div>
<div class="metrique-card"><span class="metrique-valeur"><?php echo number_format(count(lire_consultations()),0,',',' '); ?></span><span class="metrique-label">Recherches totales</span></div>
<div class="metrique-card"><span class="metrique-valeur"><?php echo $nb_villes_distinctes; ?></span><span class="metrique-label">Villes distinctes</span></div>
<div class="metrique-card metrique-accent"><span class="metrique-valeur"><?php echo count($stats_carb); ?></span><span class="metrique-label">Types carburants</span></div>
</div>
</section>
<div class="stats-grille-principale">

<!-- TOP VILLES -->
<section class="stats-card stats-card-large">
<h2>Top villes consultées</h2>
<?php if(empty($top_villes)): ?><p class="aucune-donnee">Aucune consultation enregistrée.</p>
<?php else: ?>
<p class="stats-legende">Top <?php echo count($top_villes); ?> des villes les plus recherchées.</p>
<div class="histogramme">
<?php $rang=1; foreach($top_villes as $ville=>$nb):
$pct=(int)round(($nb/$max_villes)*100);
$classe=$rang===1?'barre-or':($rang===2?'barre-argent':($rang===3?'barre-bronze':'barre-normal'));
$med=$rang===1?'1.':($rang===2?'2.':($rang===3?'3.':$rang.'.'));
?>
<div class="barre-item">
<div class="barre-rang"><?php echo $med; ?></div>
<div class="barre-label"><?php echo htmlspecialchars($ville,ENT_QUOTES,'UTF-8'); ?></div>
<div class="barre-track"><div class="barre-fill <?php echo $classe; ?>" style="width:<?php echo $pct; ?>%" role="progressbar" aria-valuenow="<?php echo $nb; ?>" aria-valuemin="0" aria-valuemax="<?php echo $max_villes; ?>"></div></div>
<div class="barre-valeur"><?php echo $nb; ?> <span class="barre-unite">cons.</span></div>
<a href="resultats.php?<?php echo http_build_query(['ville'=>$ville]); ?>"
   class="barre-lien"
   aria-label="Voir les résultats pour <?php echo htmlspecialchars($ville,ENT_QUOTES,'UTF-8'); ?>">&#8594;</a>
</div>
<?php $rang++; endforeach; ?>
</div>
<?php endif; ?>
</section>

<!-- CARBURANTS -->
<section class="stats-card">
<h2>Carburants recherchés</h2>
<?php if(empty($stats_carb)): ?><p class="aucune-donnee">Aucune donnée.</p>
<?php else: ?>
<div class="carb-liste">
<?php foreach($stats_carb as $carb=>$nb):
$pct=(int)round(($nb/$max_carb)*100);
$ptot=$total_carb>0?round(($nb/$total_carb)*100,1):0;
$col=$couleurs_carb[$carb]??['bg'=>'#1E3A8A','text'=>'#EFF6FF','bar'=>'#3B82F6'];
?>
<div class="carb-item">
<div class="carb-header">
<span class="carb-badge" style="background:<?php echo $col['bg']; ?>;color:<?php echo $col['text']; ?>;"><?php echo htmlspecialchars($carb,ENT_QUOTES,'UTF-8'); ?></span>
<span class="carb-pct"><?php echo $ptot; ?>%</span>
<span class="carb-nb"><?php echo $nb; ?> rech.</span>
</div>
<div class="carb-track"><div class="carb-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $col['bar']; ?>;"></div></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<!-- JOURS -->
<section class="stats-card">
<h2>Consultations par jour</h2>
<?php if(array_sum($stats_jours)===0): ?><p class="aucune-donnee">Aucune donnée.</p>
<?php else: ?>
<div class="barres-verticales">
<?php foreach($stats_jours as $jour=>$nb):
$pct=$max_jours>0?(int)round(($nb/$max_jours)*100):0;
$est=$nb===$max_jours&&$nb>0;
?>
<div class="barre-v-item">
<div class="barre-v-nb"><?php echo $nb>0?$nb:''; ?></div>
<div class="barre-v-track" style="<?php echo $nb===0?'background:transparent;':'' ?>"><div class="barre-v-fill <?php echo $est?'barre-v-max':''; ?>" style="height:<?php echo $pct; ?>%"></div></div>
<div class="barre-v-label"><?php echo mb_substr($jour,0,3,'UTF-8'); ?></div>
</div>
<?php endforeach; ?>
</div>
<p class="stats-legende" style="margin-top:0.5rem;">Jour le plus actif : <strong><?php $mn=array_search($max_jours,$stats_jours); echo htmlspecialchars((string)$mn,ENT_QUOTES,'UTF-8'); ?></strong> (<?php echo $max_jours; ?> cons.)</p>
<?php endif; ?>
</section>

<!-- HEURES -->
<section class="stats-card">
<h2>Heures de pointe</h2>
<?php if(array_sum($stats_heures)===0): ?><p class="aucune-donnee">Aucune donnée.</p>
<?php else: ?>
<div class="heures-grille">
<?php for($h=0;$h<24;$h++):
$nb=$stats_heures[$h];
$pct=$max_heures>0?(int)round(($nb/$max_heures)*100):0;
$int=$pct>75?'heure-forte':($pct>40?'heure-moyenne':($pct>10?'heure-faible':'heure-nulle'));
?>
<div class="heure-case <?php echo $int; ?>" title="<?php echo sprintf('%02d',$h); ?>h : <?php echo $nb; ?> cons.">
<span class="heure-num"><?php echo sprintf('%02d',$h); ?></span>
<span class="heure-nb"><?php echo $nb>0?$nb:''; ?></span>
</div>
<?php endfor; ?>
</div>
<div class="heures-legende">
<span class="heure-nulle"></span> Aucune
<span class="heure-faible" style="margin-left:8px;"></span> Faible
<span class="heure-moyenne" style="margin-left:8px;"></span> Moyenne
<span class="heure-forte" style="margin-left:8px;"></span> Forte
</div>
<?php $hm=array_search(max($stats_heures),$stats_heures); ?>
<p class="stats-legende" style="margin-top:0.5rem;">Heure de pointe : <strong><?php echo sprintf('%02d',$hm); ?>h</strong> (<?php echo $max_heures; ?> cons.)</p>
<?php endif; ?>
</section>

<!-- REGIONS -->
<section class="stats-card stats-card-large">
<h2>Régions consultées</h2>
<?php
$noms_regions = [
    '11'=>'Île-de-France','24'=>'Centre-Val de Loire','27'=>'Bourgogne-Franche-Comté',
    '28'=>'Normandie','32'=>'Hauts-de-France','44'=>'Grand Est',
    '52'=>'Pays de la Loire','53'=>'Bretagne','75'=>'Nouvelle-Aquitaine',
    '76'=>'Occitanie','84'=>'Auvergne-Rhône-Alpes','93'=>'Provence-Alpes-Côte d\'Azur',
    '94'=>'Corse',
];
if(empty($top_regions)): ?><p class="aucune-donnee">Aucune donnée.</p>
<?php else: ?>
<div class="regions-grille">
<?php $tot_reg=array_sum($top_regions); foreach($top_regions as $reg=>$nb):
$pct=(int)round(($nb/$max_regions)*100);
$ptot=$tot_reg>0?round(($nb/$tot_reg)*100,1):0;
$reg_str = (string)$reg;
$nom_reg = $noms_regions[$reg_str] ?? $reg_str;
?>
<div class="region-item">
<div class="region-nom">
    <span class="region-nom-label"><?php echo htmlspecialchars($nom_reg,ENT_QUOTES,'UTF-8'); ?></span>
    <span class="region-code"><?php echo htmlspecialchars($reg_str,ENT_QUOTES,'UTF-8'); ?></span>
</div>
<div class="region-track"><div class="region-fill" style="width:<?php echo $pct; ?>%"></div></div>
<div class="region-stats"><span><?php echo $nb; ?> cons.</span><span class="region-pct"><?php echo $ptot; ?>%</span></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
</div>

<!-- DERNIERES CONSULTATIONS -->
<section class="stats-card" style="margin-bottom:1.5rem;">
<h2>Dernières consultations</h2>
<?php if(empty($dernieres)): ?>
<p class="aucune-donnee">Aucune consultation. <a href="index.php">Lancez une recherche</a> !</p>
<?php else: ?>
<p class="stats-legende">Les <?php echo count($dernieres); ?> dernières — source : <code>data/consultations.csv</code></p>
<div class="tableau-container">
<table class="tableau-consultations">
<caption>Historique des dernières consultations (CSV horodaté)</caption>
<thead><tr>
<th scope="col">Horodatage</th>
<th scope="col">Ville</th>
<th scope="col">Département</th>
<th scope="col">Région</th>
<th scope="col">Carburant</th>
<th scope="col">Action</th>
</tr></thead>
<tbody>
<?php foreach($dernieres as $idx => $c):
$carb=!empty($c['carburant'])?$c['carburant']:'Tous';
$col=$couleurs_carb[$carb]??['bg'=>'#1E3A8A','text'=>'#EFF6FF','bar'=>'#3B82F6'];
$params=http_build_query(['ville'=>$c['ville'],'dept'=>$c['departement'],'region'=>$c['region']]);
$label_btn = 'Voir les prix à ' . htmlspecialchars($c['ville'], ENT_QUOTES, 'UTF-8')
           . ' (' . htmlspecialchars($carb, ENT_QUOTES, 'UTF-8') . ')'
           . ' — ' . htmlspecialchars($c['horodatage'], ENT_QUOTES, 'UTF-8')
           . ' n°' . ($idx + 1);
?>
<tr>
<td class="td-horodatage"><code><?php echo htmlspecialchars($c['horodatage'],ENT_QUOTES,'UTF-8'); ?></code></td>
<td class="td-ville"><strong><?php echo htmlspecialchars($c['ville'],ENT_QUOTES,'UTF-8'); ?></strong></td>
<td><?php echo htmlspecialchars($c['departement'],ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($c['region'],ENT_QUOTES,'UTF-8'); ?></td>
<td><span class="carb-badge-sm" style="background:<?php echo $col['bg']; ?>;color:<?php echo $col['text']; ?>;"><?php echo htmlspecialchars($carb,ENT_QUOTES,'UTF-8'); ?></span></td>
<td>
    <a href="resultats.php?<?php echo $params; ?>"
       class="btn-voir-resultats"
       aria-label="<?php echo $label_btn; ?>">
       Voir les prix
    </a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<p class="source-donnees">Format CSV : <em>horodatage ; ville ; département ; région ; carburant</em></p>
<?php endif; ?>
</section>

<!-- INFO STOCKAGE -->
<section class="csv-info-section">
<h2>À propos du stockage</h2>
<div class="csv-info-grille">
<div class="csv-info-item"><div><h3>Fichier CSV horodaté</h3><p>Chaque recherche est enregistrée dans <code>data/consultations.csv</code> avec la date, l'heure, la ville, le département, la région et le carburant.</p></div></div>
<div class="csv-info-item"><div><h3>Cookie navigateur</h3><p>La dernière ville consultée est mémorisée 30 jours dans votre navigateur.</p></div></div>
<div class="csv-info-item"><div><h3>Données anonymes</h3><p>Aucune donnée personnelle collectée. Seuls la ville, le département, la région et le carburant sont enregistrés.</p></div></div>
</div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.inc.php'; ?>