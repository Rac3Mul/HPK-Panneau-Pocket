# Mises à jour GitHub — HPK PanneauPocket Connect

## Configuration du dépôt (une fois)

1. Créez un repo GitHub : `https://github.com/Rac3Mul/HPK-Panneau-Pocket`
2. Poussez le contenu du dossier `hpk-panneaupocket/`
3. Branche par défaut : `main`

### Repo public (recommandé)

Aucune configuration sur le site WordPress : le plugin interroge GitHub automatiquement.

URL du dépôt (déjà configurée dans le plugin) :
`https://github.com/Rac3Mul/HPK-Panneau-Pocket/`

### Repo privé (optionnel)

Dans `wp-config.php` sur chaque site client :

```php
define( 'HPK_PP_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxx' );
```

Créez un token GitHub (Settings → Developer settings → Personal access tokens) avec `repo`.

## Publier une nouvelle version

1. Incrémentez la version dans `hpk-panneaupocket.php` (en-tête `Version:` et `HPK_PP_VERSION`)
2. Mettez à jour `Stable tag:` et le changelog dans `readme.txt`
3. Générez le zip (structure : `hpk-panneaupocket/hpk-panneaupocket.php` à la racine du zip)
4. Commit + push sur `main`
5. GitHub → **Releases** → **Draft a new release**
   - Tag : `v1.2.0` (ou `1.2.0`)
   - Titre : `Version 1.2.0`
   - **Attachez** `hpk-panneaupocket.zip` en fichier binaire
   - Ne cochez **pas** « Pre-release »
6. Publiez la release

## Côté WordPress (client)

- **Extensions** → notification « Une nouvelle version est disponible »
- Cliquez **Mettre à jour maintenant**
- Les réglages (token, City ID) sont **conservés**

## Mise à jour manuelle (sans GitHub)

Remplacez les fichiers dans `wp-content/plugins/hpk-panneaupocket/` via cPanel sans désactiver le plugin.
