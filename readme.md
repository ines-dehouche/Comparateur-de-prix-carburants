# Carbora — Prix des carburants en France

> **Votre carburant en 3 clics !**  
> Site de consultation des prix des carburants en France métropolitaine.

---

## Auteurs

- BOUNAB Kahina
- DEHOUCHE Ines 

**Formation :** L2 Informatique
**Groupe :** A-09  
**Module :** Développement Web — CY Cergy Paris Université — 2025–2026

---

## URLs du site

Kahina : https://kahina.alwaysdata.net/ 
Ines : https://dehouche.alwaysdata.net/ 

---

## Description du projet

Carbora est un site web dynamique permettant à tout automobiliste de consulter et comparer les prix des carburants dans les stations service de France métropolitaine. L'utilisateur sélectionne sa région, son département et sa ville ; le serveur interroge en temps réel l'API officielle du gouvernement et affiche les résultats triés par prix.

Le projet met en œuvre l'architecture **3 tiers** exigée par le sujet :
navigateur (HTML5/CSS3/JS) - serveur PHP 8 - APIs externes.

---

## Pages du site

Fichiers et description 

- index.php: Accueil — formulaire de recherche région/département/ville, géolocalisation IP automatique, rappel dernière ville (cookie)
- carte.php: Carte interactive HTML `<map>` des 13 régions, navigation en cascade AJAX département → ville 
- resultats.php: Affichage des stations et prix via l'API carburants, filtre carburant, tri, prix minimum mis en évidence
- statistiques.php: Statistiques d'utilisation : top villes, carburants, jours/heures de pointe, régions, tableau CSV 
- tech.php: Page technique : film Ghibli aléatoire (JSON/XML), géolocalisation IP (XML + JSON), proxy PHP/cURL
- sitemap.php: Plan du site avec arborescence complète des pages et des fichiers
- communes.php: Point d'entrée AJAX — retourne la liste des communes d'un département (JSON)

---

## Architecture technique

```
ProjetWeb/
├── index.php
├── carte.php
├── resultats.php
├── statistiques.php
├── tech.php
├── sitemap.php
├── communes.php
├── readme.md
│
├── includes/
│   ├── config.inc.php        Constantes globales (chemins, noms, URLs)
│   ├── header.inc.php        En-tête commun + gestion thème
│   └── footer.inc.php        Pied de page commun
│
├── lib/
│   ├── api_carburants.php    Requêtes vers l'API data.economie.gouv.fr
│   ├── api_proxy.php         Proxy PHP/cURL — appels serveur↔API (pas navigateur <->API)
│   ├── api_geoloc.php        Géolocalisation par adresse IP (ipinfo.io)
│   ├── api_ghibli.php        Appel API Ghibli — film aléatoire (JSON)
│   ├── api_xml.php           Génération et parsing XML (format whatismyip)
│   ├── cookie_helper.php     Gestion cookies thème + dernière ville
│   ├── csv_handler.php       Lecture/écriture consultations.csv + statistiques
│   ├── csv_geo.php           Chargement régions/départements depuis CSV
│   └── http_helper.php       Fonctions cURL génériques
│
├── assets/
│   ├── css/
│   │   ├── style.css         Feuille de style principale
│   │   ├── theme_jour.css    Thème clair
│   │   └── theme_nuit.css    Thème sombre
│   ├── img/                  Images, logo, favicon, carte France
│   └── js/
│       ├── carte.js          Logique carte interactive + rescaling <area>
│       ├── filtres.js        Filtres et tri tableau résultats
│       └── stats.js          Graphiques page statistiques
│
└── data/
    ├── regions.csv           13 régions françaises (code INSEE + nom)
    ├── departements.csv      101 départements (code + nom + région)
    ├── communes.csv          Communes françaises (nom + code postal)
    ├── clean_postcodes.csv   Codes postaux nettoyés
    └── consultations.csv     Historique horodaté des recherches (généré automatiquement)
```

---

## APIs utilisées

| API | Format | Usage | URL |
|-----|--------|-------|-----|
| Prix carburants | JSON | Prix en temps réel par ville/département | https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/prix-des-carburants-en-france-flux-instantane-v2/records/ |
| Ghibli API | JSON | Film aléatoire sur page Tech | https://ghibliapi.vercel.app/films |
| IPinfo | JSON | Géolocalisation par IP | https://ipinfo.io/{ip}/json |
| whatismyip | XML | Géolocalisation alternative | https://api.whatismyip.com/ip-address-lookup.php |

- Tous les appels API transitent par le serveur PHP via cURL (api_proxy.php)
- conformément aux exigences du sujet — aucun appel direct depuis le navigateur.

---

## Stockage des données

### Côté serveur — CSV horodaté

Chaque recherche effectuée est enregistrée dans `data/consultations.csv` :

```
horodatage;ville;departement;region;carburant
2026-04-29 14:32:05;Paris;75;11;SP95
2026-04-29 14:35:12;Lyon;69;84;Gazole
```

### Côté client — Cookies

| Cookie | Contenu | Durée |
|--------|---------|-------|
| `theme_carbora` | `"jour"` ou `"nuit"` | 30 jours |
| `derniere_ville` | JSON `{ville, dept, region, date}` | 30 jours |

---

## Fonctionnalités principales

- **Recherche géographique** en cascade : région → département → ville (données CSV officielles data.gouv.fr)
- **Carte interactive** HTML pur (`<map>`, `<usemap>`, `<area>`) avec 13 zones polygonales IGN, sans librairie externe
- **Rescaling automatique** des coordonnées `<area>` en JavaScript selon la taille d'affichage
- **Prix en temps réel** depuis l'API officielle — prix minimum mis en évidence, tri et filtre par carburant
- **Géolocalisation IP** automatique à l'accueil pour pré-remplir la région
- **Thème jour/nuit** persistant via cookie, 2 feuilles CSS distinctes
- **Statistiques** : histogramme des villes, répartition des carburants, jours et heures de pointe, carte des régions
- **Formats JSON et XML** maîtrisés côté serveur (API carburants + Ghibli en JSON, géoloc en XML)
- **Accessibilité** : navigation clavier sur la carte, contrastes conformes WCAG
- **Validation W3C** : 0 erreur HTML5 et CSS3

---

## Technologies

| Côté client | Côté serveur |
|-------------|-------------|
| HTML5 (XHTML5 well-formed) | PHP 8 (`declare(strict_types=1)`) |
| CSS3 (3 feuilles : style + 2 thèmes) | cURL pour tous les appels API |
| JavaScript vanilla (Fetch/AJAX) | Lecture/écriture CSV |
| Balises sémantiques HTML5 | Cookies PHP (setcookie) |
| `<map>` / `<usemap>` / `<area>` | PhpDoc (commentaires doxygen-compatibles) |

---

## Installation

1. Copier l'arborescence `ProjetWeb/` dans le répertoire racine de l'hébergement web
2. S'assurer que PHP 8+ est disponible sur le serveur
3. Vérifier que le répertoire `data/` est accessible en écriture (`chmod 755 data/`)
4. Accéder au site via `https://votre-domaine/`

> Aucune base de données requise — tout le stockage est fichier (CSV).  
> Aucune dépendance externe côté client — aucun framework JS ni CSS.

---

## Validation & Qualité

| Critère | Résultat |
|---------|---------|
| Validation W3C HTML5 | 0 erreur |
| Validation W3C CSS3 | 0 erreur |
| Structure XHTML5 (XML well-formed) | Conforme |
| Vérificateur de liens (checklink) | Aucun lien brisé |
| Thème jour | Fonctionnel |
| Thème nuit | Fonctionnel |
| Responsive mobile | Grilles CSS adaptatives |

---

*Projet UE Développement Web — L2 Informatique — CY Cergy Paris Université — 2025–2026*
