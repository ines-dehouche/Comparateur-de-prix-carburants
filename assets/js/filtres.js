/**
 * @file filtres.js
 * @brief Gestion dynamique des listes déroulantes Région → Département → Ville.
 *
 * Ce script utilise la variable DEPARTEMENTS_PAR_REGION injectée par PHP
 * et charge les communes via une requête AJAX (fetch) vers communes.php.
 *
 * @author BOUNAB Kahina - DEHOUCHE Ines
 * @version 1.0
 */

'use strict';

// === Sélection des éléments du DOM ===
const selectRegion  = document.getElementById('region');
const selectDept    = document.getElementById('departement');
const selectCommune = document.getElementById('commune');

/**
 * Peuple un <select> avec un tableau d'options.
 *
 * @param {HTMLSelectElement} selectEl   L'élément select à remplir
 * @param {Array}             items      Tableau d'objets {code, nom}
 * @param {string}            placeholder Texte de l'option vide
 * @param {boolean}           useNomAsValue Si true, utilise nom comme value (pour communes)
 */
function peupler_select(selectEl, items, placeholder, useNomAsValue) {
    selectEl.innerHTML = '';

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = placeholder;
    selectEl.appendChild(opt0);

    items.forEach(function(item) {
        const opt = document.createElement('option');
        // Pour les communes : on envoie le NOM (pas le code INSEE)
        // car l'API carburants filtre par nom de ville
        opt.value       = useNomAsValue ? item.nom : item.code;
        opt.textContent = item.nom;
        selectEl.appendChild(opt);
    });

    selectEl.disabled = items.length === 0;
}

// === Changement de région → met à jour les départements ===
if (selectRegion) {
    selectRegion.addEventListener('change', function() {
        const codeRegion = this.value;

        peupler_select(selectDept,    [], '-- Sélectionner d\'abord une région --', false);
        peupler_select(selectCommune, [], '-- Sélectionner d\'abord un département --', true);
        selectDept.disabled    = true;
        selectCommune.disabled = true;

        if (!codeRegion) return;

        const depts = DEPARTEMENTS_PAR_REGION[codeRegion] || [];
        if (depts.length === 0) return;

        peupler_select(selectDept, depts, '-- Sélectionner un département --', false);
    });
}

// === Changement de département → charge les communes via AJAX ===
if (selectDept) {
    selectDept.addEventListener('change', function() {
        const codeDept = this.value;

        peupler_select(selectCommune, [], '-- Chargement en cours... --', true);
        selectCommune.disabled = true;

        if (!codeDept) {
            peupler_select(selectCommune, [], '-- Sélectionner d\'abord un département --', true);
            return;
        }

        fetch('communes.php?dept=' + encodeURIComponent(codeDept))
            .then(function(reponse) {
                if (!reponse.ok) throw new Error('Erreur HTTP ' + reponse.status);
                return reponse.json();
            })
            .then(function(communes) {
                if (!Array.isArray(communes) || communes.length === 0) {
                    peupler_select(selectCommune, [], '-- Aucune commune trouvée --', true);
                    return;
                }
                // useNomAsValue = true → la valeur envoyée sera le nom de la ville
                peupler_select(selectCommune, communes, '-- Sélectionner une commune --', true);
            })
            .catch(function(erreur) {
                console.error('Erreur chargement communes :', erreur);
                peupler_select(selectCommune, [], '-- Erreur de chargement --', true);
            });
    });
}