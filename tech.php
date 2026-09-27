<?php
/**
 * @file tech.php
 * @brief Page technique du projet Carbora.
 */

declare(strict_types=1);

$titre = 'Page technique - Carbora';

require_once __DIR__ . '/includes/header.inc.php';
require_once __DIR__ . '/lib/api_ghibli.php';
require_once __DIR__ . '/lib/api_geoloc.php';
require_once __DIR__ . '/lib/api_xml.php';

$film = getRandomGhibliFilm();
$ipVisiteur = getVisitorIp();
$geoInfo = getGeoInfoFromIpInfo($ipVisiteur);

// Génération des données XML (parsées pour affichage)
$xmlGhibliString = getGhibliFilmsXml();
$xmlGeolocString = getGeolocXml();

// Parsing XML pour affichage propre
$filmsXml = @simplexml_load_string($xmlGhibliString);
$geoXml   = @simplexml_load_string($xmlGeolocString);
?>

<main>
    <section>
        <h1>Page technique</h1>
        <p>
            Cette page présente les premiers tests d'API du projet Carbora,
            conformément aux attentes du sujet.
        </p>
    </section>

    <section>
        <h2>Film Ghibli aléatoire</h2>

        <?php if ($film !== null): ?>
            <article>
                <h3><?php echo htmlspecialchars($film['title'] ?? 'Titre inconnu', ENT_QUOTES, 'UTF-8'); ?></h3>

                <p>
                    <strong>Titre japonais :</strong>
                    <span lang="ja">
                        <?php echo htmlspecialchars($film['original_title'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </p>

                <p>
                    <strong>Année de sortie :</strong>
                    <?php echo htmlspecialchars($film['release_date'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <p>
                    <strong>Description :</strong>
                    <?php echo htmlspecialchars($film['description'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <?php if (!empty($film['image']) || !empty($film['movie_banner'])): ?>
                <div style="display:flex; gap:1.5rem; flex-wrap:wrap; align-items:stretch; margin-top:1rem;">

                    <?php if (!empty($film['image'])): ?>
                    <figure style="margin:0; flex:0 0 auto;">
                        <img src="<?php echo htmlspecialchars($film['image'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="Affiche du film <?php echo htmlspecialchars($film['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                             style="height:380px; width:auto; border-radius:8px; display:block; object-fit:cover;"/>
                        <figcaption>Affiche du film.</figcaption>
                    </figure>
                    <?php endif; ?>

                    <?php if (!empty($film['movie_banner'])): ?>
                    <figure style="margin:0; flex:1; min-width:0;">
                        <img src="<?php echo htmlspecialchars($film['movie_banner'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="Bannière du film <?php echo htmlspecialchars($film['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                             style="height:380px; width:100%; border-radius:8px; display:block; object-fit:cover;"/>
                        <figcaption>Bannière du film.</figcaption>
                    </figure>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

            </article>
        <?php else: ?>
            <p>Impossible de récupérer les informations du film pour le moment.</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Géolocalisation approximative du visiteur</h2>

        <?php if ($geoInfo !== null): ?>
            <ul>
                <li><strong>Adresse IP :</strong> <?php echo htmlspecialchars($geoInfo['ip'] ?? $ipVisiteur, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Ville :</strong> <?php echo htmlspecialchars($geoInfo['city'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Région :</strong> <?php echo htmlspecialchars($geoInfo['region'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Pays :</strong> <?php echo htmlspecialchars($geoInfo['country'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Coordonnées :</strong> <?php echo htmlspecialchars($geoInfo['loc'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Fuseau horaire :</strong> <?php echo htmlspecialchars($geoInfo['timezone'] ?? 'Non disponible', ENT_QUOTES, 'UTF-8'); ?></li>
            </ul>
            <p>La position affichée est une estimation obtenue à partir de l'adresse IP du visiteur.</p>
        <?php else: ?>
            <p>Impossible de récupérer la géolocalisation pour le moment.</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Données XML</h2>
        <p>
            Les mêmes données sont ici récupérées et converties au format XML
            via la classe <code>DOMDocument</code> de PHP, puis parsées avec
            <code>SimpleXML</code> pour un affichage structuré.
        </p>

        <h3>Films Ghibli via XML</h3>
        <p>
            Source : <a href="<?php echo htmlspecialchars(API_GHIBLI_URL, ENT_QUOTES, 'UTF-8'); ?>"
                        target="_blank" rel="noopener noreferrer">
                <?php echo htmlspecialchars(API_GHIBLI_URL, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </p>

        <?php if ($filmsXml !== false && isset($filmsXml->film)): ?>
            <p><?php echo count($filmsXml->film); ?> films récupérés.</p>
            <div class="tableau-container"><table class="tableau-resultats">
                <caption>Liste des films Studio Ghibli</caption>
                <thead>
                    <tr>
                        <th scope="col">Titre</th>
                        <th scope="col">Titre original</th>
                        <th scope="col">Année</th>
                        <th scope="col">Réalisateur</th>
                        <th scope="col">Score RT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filmsXml->film as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$f->titre, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td lang="ja"><?php echo htmlspecialchars((string)$f->titre_original, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$f->annee, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$f->realisateur, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$f->score_rt, ENT_QUOTES, 'UTF-8'); ?> %</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p>Impossible de récupérer les films Ghibli via XML.</p>
        <?php endif; ?>

        <h3>Géolocalisation du visiteur via XML</h3>
        <p>
            Source : <a href="https://ipinfo.io" target="_blank" rel="noopener noreferrer">ipinfo.io</a>
        </p>

        <?php if ($geoXml !== false && !isset($geoXml->erreur)): ?>
            <ul>
                <li><strong>Adresse IP :</strong> <?php echo htmlspecialchars((string)$geoXml->ip, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Ville :</strong> <?php echo htmlspecialchars((string)$geoXml->ville, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Région :</strong> <?php echo htmlspecialchars((string)$geoXml->region, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Pays :</strong> <?php echo htmlspecialchars((string)$geoXml->pays, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Coordonnées :</strong> <?php echo htmlspecialchars((string)$geoXml->coordonnees, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Fuseau horaire :</strong> <?php echo htmlspecialchars((string)$geoXml->fuseau_horaire, ENT_QUOTES, 'UTF-8'); ?></li>
            </ul>
            <p>La position affichée est une estimation obtenue à partir de l'adresse IP du visiteur.</p>
        <?php else: ?>
            <p>Impossible de récupérer la géolocalisation via XML.</p>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.inc.php'; ?>