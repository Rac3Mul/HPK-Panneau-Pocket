=== HPK PanneauPocket Connect ===
Contributors: hpk
Tags: panneaupocket, widget, iframe, api
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.1
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

Si le dépôt GitHub est configuré, allez dans Extensions : une notification « mise à jour disponible » apparaît. Cliquez sur « Mettre à jour maintenant ».

= Dépôt GitHub public ? =

Rien à configurer : les mises à jour apparaissent dans Extensions automatiquement (via GitHub Releases).

= Dépôt GitHub privé ? =

Ajoutez dans `wp-config.php` :
`define( 'HPK_PP_GITHUB_TOKEN', 'votre-token-github' );`

== Changelog ==

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

= 1.2.0 =
Mises à jour en un clic depuis Extensions si le plugin est connecté au dépôt GitHub HPK.
