# 🎨 Guide de personnalisation des couleurs — ArchiCompta Pro

## Les 2 fichiers clés à modifier

### 📁 Fichier 1 : `app/Providers/Filament/AdminPanelProvider.php`
**Rôle** : Définit les couleurs **principales** de Filament (boutons, badges, liens, focus)

Ligne ~31, modifiez le bloc `->colors([...])` :
```php
->colors([
    'primary' => Color::Indigo,    // Boutons principaux, liens, focus
    'success' => Color::Emerald,   // Messages succès, badges "actif"
    'danger'  => Color::Rose,      // Erreurs, suppressions, alertes
    'warning' => Color::Amber,     // Avertissements, badges "en attente"
    'info'    => Color::Sky,       // Infos, badges informatifs
    'gray'    => Color::Slate,     // Textes secondaires, bordures
])
```

**Couleurs Filament disponibles** (remplacez simplement le nom) :
| Nom | Aperçu | Usage recommandé |
|-----|--------|-----------------|
| `Color::Slate` | Gris bleuté | Textes, fond |
| `Color::Gray` | Gris neutre | Fond neutre |
| `Color::Zinc` | Gris froid | Fond moderne |
| `Color::Red` | Rouge vif | Erreurs, danger |
| `Color::Orange` | Orange | Avertissements chauds |
| `Color::Amber` | Ambre doré | Avertissements |
| `Color::Yellow` | Jaune | Mise en valeur |
| `Color::Lime` | Vert citron | Validation vive |
| `Color::Green` | Vert classique | Succès |
| `Color::Emerald` | Vert émeraude | Succès élégant |
| `Color::Teal` | Bleu-vert | Info alternative |
| `Color::Cyan` | Cyan | Info claire |
| `Color::Sky` | Bleu ciel | Info douce |
| `Color::Blue` | Bleu standard | Primary classique |
| `Color::Indigo` | Indigo profond | Primary pro |
| `Color::Violet` | Violet | Créatif |
| `Color::Purple` | Pourpre | Premium |
| `Color::Fuchsia` | Fuchsia | Moderne |
| `Color::Pink` | Rose | Doux |
| `Color::Rose` | Rose chaud | Danger doux |

**Couleur personnalisée HEX** :
```php
'primary' => Color::hex('#1B2A4A'),  // Votre couleur exacte
```

---

### 📁 Fichier 2 : `resources/views/filament/premium-styles.blade.php`
**Rôle** : Contrôle le CSS avancé (sidebar, tableaux, cartes, animations)

Voici chaque section et comment la modifier :

---

## 🔧 Modifications courantes

### Changer la couleur du SIDEBAR (barre latérale)

**Lignes 6-7** dans `premium-styles.blade.php` :
```css
:root {
    --ach-sidebar-from: #312e81;  /* Couleur HAUT du dégradé */
    --ach-sidebar-to: #1e1b4b;    /* Couleur BAS du dégradé */
}
```

**Exemples prêts à copier** :
```css
/* Bleu marine classique */
--ach-sidebar-from: #1B2A4A;
--ach-sidebar-to: #0f1a30;

/* Vert forêt */
--ach-sidebar-from: #065f46;
--ach-sidebar-to: #022c22;

/* Noir élégant */
--ach-sidebar-from: #1e1e2e;
--ach-sidebar-to: #11111b;

/* Bordeaux */
--ach-sidebar-from: #7f1d1d;
--ach-sidebar-to: #450a0a;

/* Bleu Cameroun */
--ach-sidebar-from: #009639;
--ach-sidebar-to: #006428;
```

### Changer la couleur de FOND de l'application

**Ligne 5** :
```css
--ach-bg: #f8fafc;  /* Gris très clair (défaut) */
```

Autres options :
```css
--ach-bg: #ffffff;   /* Blanc pur */
--ach-bg: #f1f5f9;   /* Gris plus marqué */
--ach-bg: #fafaf9;   /* Beige doux */
--ach-bg: #f0fdf4;   /* Vert très pâle */
```

### Changer les couleurs des CARTES KPI (tableau de bord)

**Lignes 75-80** — chaque carte a sa bordure colorée :
```css
.fi-wi-stats-overview-stat:nth-child(1) { border-top: 3px solid #6366f1 !important; } /* Indigo */
.fi-wi-stats-overview-stat:nth-child(2) { border-top: 3px solid #10b981 !important; } /* Émeraude */
.fi-wi-stats-overview-stat:nth-child(3) { border-top: 3px solid #f43f5e !important; } /* Rose */
.fi-wi-stats-overview-stat:nth-child(4) { border-top: 3px solid #8b5cf6 !important; } /* Violet */
.fi-wi-stats-overview-stat:nth-child(5) { border-top: 3px solid #0ea5e9 !important; } /* Bleu ciel */
.fi-wi-stats-overview-stat:nth-child(6) { border-top: 3px solid #f59e0b !important; } /* Ambre */
```

### Changer la couleur du TEXTE des en-têtes de tableau

**Ligne 96** :
```css
.fi-ta-header-cell {
    color: #64748b !important;  /* Gris bleuté (défaut) */
}
```

Remplacez par :
```css
color: #1e293b !important;  /* Plus foncé, plus lisible */
color: #4f46e5 !important;  /* Indigo vif */
```

### Changer la couleur du SURVOL des lignes de tableau

**Ligne 91** :
```css
.fi-ta-row:hover { background: rgba(99,102,241,0.03) !important; }
```

Exemples :
```css
/* Survol vert */
.fi-ta-row:hover { background: rgba(16,185,129,0.05) !important; }
/* Survol bleu */
.fi-ta-row:hover { background: rgba(59,130,246,0.05) !important; }
```

### Changer les couleurs des BORDURES de dossiers (investissement/fonctionnement)

**Lignes 162-164** :
```css
.border-l-investissement { border-left: 4px solid #6366f1 !important; }  /* Indigo */
.border-l-fonctionnement { border-left: 4px solid #10b981 !important; }  /* Vert */
.border-l-nopdf { border-left: 4px solid #f43f5e !important; }           /* Rouge */
```

### Changer le menu actif dans le sidebar

**Lignes 31-34** :
```css
.fi-sidebar-nav .fi-sidebar-item.fi-active {
    background: rgba(99,102,241,0.25) !important;  /* Fond du menu actif */
    border-left: 3px solid #818cf8;                 /* Barre latérale */
}
```

---

## 🎯 Changer les couleurs des BADGES dans les tableaux

Les badges sont définis dans chaque **Resource.php**, pas dans le CSS.

Par exemple dans `DossierResource.php` :
```php
Tables\Columns\BadgeColumn::make('depense.type')
    ->colors([
        'primary' => 'INVESTISSEMENT',   // Utilise la couleur "primary" de Filament
        'success' => 'FONCTIONNEMENT',   // Utilise "success"
    ]),
```

Couleurs de badge disponibles : `primary`, `success`, `danger`, `warning`, `info`, `gray`

---

## 🛠️ Palette de couleurs HEX utiles

| Couleur | HEX | Usage |
|---------|-----|-------|
| Indigo 500 | `#6366f1` | Boutons, liens |
| Indigo 900 | `#312e81` | Sidebar sombre |
| Émeraude 500 | `#10b981` | Succès, validé |
| Rose 500 | `#f43f5e` | Erreurs, danger |
| Ambre 500 | `#f59e0b` | Avertissements |
| Sky 500 | `#0ea5e9` | Information |
| Violet 500 | `#8b5cf6` | Accent premium |
| Slate 500 | `#64748b` | Textes secondaires |
| Slate 900 | `#0f172a` | Textes principaux |

---

## ✅ Résumé rapide

| Ce que je veux changer | Fichier à modifier | Section |
|----------------------|-------------------|---------|
| Couleur boutons/liens | `AdminPanelProvider.php` | `->colors(['primary' => ...])` |
| Couleur sidebar | `premium-styles.blade.php` | `--ach-sidebar-from / to` |
| Couleur fond page | `premium-styles.blade.php` | `--ach-bg` |
| Couleur cartes KPI | `premium-styles.blade.php` | `nth-child(N) border-top` |
| Couleur en-têtes tableau | `premium-styles.blade.php` | `.fi-ta-header-cell color` |
| Couleur badges | Chaque `*Resource.php` | `->colors([...])` |
| Couleur bordures dossiers | `premium-styles.blade.php` | `.border-l-*` |
| Couleur survol tableau | `premium-styles.blade.php` | `.fi-ta-row:hover` |
| Couleur texte general | `AdminPanelProvider.php` | `'gray' => Color::...` |
| Nom de l'application | `AdminPanelProvider.php` | `->brandName('...')` |
| Police de caractères | `AdminPanelProvider.php` | `->font('Inter')` |

Après toute modification, exécutez :
```
php artisan view:clear
```
Puis rechargez la page (Ctrl+F5).
