=== HPK PanneauPocket Connect ===
Contributors: hpk
Tags: panneaupocket, widget, iframe, api
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intégration PanneauPocket : widget flottant, shortcodes, publication WordPress vers l'API officielle.

== Description ==

* Widget flottant PanneauPocket (iframe app.panneaupocket.com)
* Shortcodes et blocs Gutenberg
* Widgets Elementor
* Publication d'actualités WordPress vers PanneauPocket
* Mises à jour automatiques via GitHub Releases

== Installation ==

1. Téléversez `hpk-panneaupocket.zip` via Extensions → Ajouter
2. Activez le plugin
3. PanneauPocket → Réglages API : token, City ID, URLs
4. PanneauPocket → Widget flottant : activer le widget

== Frequently Asked Questions ==

= Comment mettre à jour le plugin ? =

Les mises à jour apparaissent dans Extensions ou Tableau de bord → Mises à jour. Cliquez sur « Mettre à jour maintenant ».

= Dépôt GitHub public ? =

Rien à configurer : les mises à jour apparaissent dans Extensions automatiquement (via GitHub Releases).

= Dépôt GitHub privé ? =

Ajoutez dans `wp-config.php` :
`define( 'HPK_PP_GITHUB_TOKEN', 'votre-token-github' );`

== Changelog ==

= 1.2.6 =
* Retrait d'un document/image (bouton ×)
* Bibliothèque repliée par défaut (déplier / replier)
* Aperçu agrandi au survol des miniatures

= 1.2.5 =
* Logo PanneauPocket officiel (widget + aperçu admin)
* Bibliothèque d'images intégrée (assets/img/base, sous-dossiers)
* Avertissement droits d'auteur sur les images
* Emojis dans le titre et le contenu
* Images visibles dans l'aperçu mobile (contenu + documents)

= 1.2.4 =
* Page Publication : éditeur visuel (gras, italique, liens…)
* Upload images/PDF via médiathèque WordPress
* Aperçu mobile en direct avant envoi

= 1.2.3 =
* Compatibilité WordPress 6.9.x et 7.0 déclarée
* Releases GitHub automatiques (GitHub Actions)
* Suppression du bandeau diagnostic sur la page Extensions

= 1.2.2 =
* Test : mise à jour automatique via GitHub Releases (aucun changement fonctionnel)

= 1.2.1 =
* Dépôt GitHub par défaut : Rac3Mul/HPK-Panneau-Pocket

= 1.2.0 =
* Mises à jour automatiques via GitHub Releases (Plugin Update Checker)
* Correctif enregistrement City ID (groupes de réglages séparés)
* Diagnostic widget flottant

= 1.1.2 =
* Fix : City ID effacé lors de l'enregistrement d'autres pages de réglages

= 1.1.0 =
* Fix : menu admin bloqué par garde-fou doublons

== Upgrade Notice ==

= 1.2.3 =
Compatibilité WordPress 7.0 et releases automatiques.
