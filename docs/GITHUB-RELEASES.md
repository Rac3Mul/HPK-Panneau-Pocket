# Mises à jour GitHub — HPK PanneauPocket Connect

Dépôt : https://github.com/Rac3Mul/HPK-Panneau-Pocket

## Releases automatiques (GitHub Actions)

À chaque tag `v*`, GitHub Actions :
1. Construit `hpk-panneaupocket.zip`
2. Publie la Release avec le zip attaché
3. WordPress détecte la MAJ automatiquement

### Publier une nouvelle version

```bash
# 1. Incrémenter Version dans hpk-panneaupocket.php + readme.txt (Stable tag, changelog)
git add .
git commit -m "v1.2.4"
git push origin main

# 2. Créer et pousser le tag (déclenche la release auto)
git tag v1.2.4
git push origin v1.2.4
```

Attendre 1–2 minutes → vérifier **Releases** sur GitHub → le zip doit être attaché.

### Côté WordPress

- **Tableau de bord → Mises à jour** ou **Extensions**
- Cliquez **Mettre à jour maintenant**
- Réglages (token, City ID) conservés

## Repo public

Aucune config sur le site. URL par défaut dans le plugin :
`https://github.com/Rac3Mul/HPK-Panneau-Pocket/`

## Repo privé (optionnel)

Dans `wp-config.php` :
```php
define( 'HPK_PP_GITHUB_TOKEN', 'ghp_xxxxxxxx' );
```

## Compatibilité WordPress

Déclarée dans `readme.txt` et l'en-tête du plugin :
- **Requires at least:** 6.0
- **Tested up to:** 7.0 (couvre 6.9.x et 7.0)
