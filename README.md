# GED Pharma - Système de Gestion Électronique de Documents

## Description
GED Pharma est une application complète de **Gestion Électronique de Documents (GED/DMS)** spécialement conçue pour le secteur pharmaceutique. Elle vise à assurer la conformité avec les normes réglementaires strictes telles que la **21 CFR Part 11** de la FDA, en garantissant la traçabilité, la sécurité et l'intégrité des documents tout au long de leur cycle de vie.

Le système permet une gestion centralisée des documents, des flux de travail automatisés pour l'approbation, ainsi qu'un suivi rigoureux des audits et des formations.

## Fonctionnalités Principales

### 🔐 Authentification & Sécurité
- **Authentification Sécurisée** : Système de connexion robuste via Laravel Sanctum.
- **Conformité 21 CFR Part 11** : Vérification par mot de passe et code PIN pour les signatures électroniques.
- **Gestion des Sessions** : Contrôle des sessions actives, déconnexion à distance.
- **Permissions** : Gestion fine des rôles et permissions utilisateurs.

### 📂 Gestion Documentaire (GED)
- **Cycle de Vie Complet** : Création, modification, archivage et suppression de documents.
- **Versionnage** : Gestion avancée des versions de documents.
- **Catégorisation** : Organisation par types, catégories et statuts.
- **Revue & Approbation** : Liste des documents nécessitant une revue.
- **Visualisation & Impression** : Aperçu intégré et génération de documents pour impression.

### 🔄 Workflows d'Approbation
- **Automatisation** : Création de flux de travail personnalisables.
- **Étapes Configurables** : Ajout, modification et réorganisation des étapes de validation.
- **Actions Utilisateur** : Soumission, approbation, rejet, demande de révision ou annulation.
- **Suivi** : Historique complet des actions effectuées sur chaque workflow.

### 📊 Audit Trail (Piste d'Audit)
- **Traçabilité Totale** : Enregistrement de toutes les actions critiques (création, modification, signature).
- **Intégrité des Données** : Vérification de l'intégrité des logs d'audit.
- **Rapports & Statistiques** : Génération de rapports d'audit et visualisation de statistiques.
- **Export** : Exportation des données d'audit pour analyse externe.

### 🎓 Gestion de la Formation
- **Suivi des Formations** : Assignation et suivi des formations liées aux procédures.
- **Attestations** : Validation de la lecture et de la compréhension des documents.

### ⚙️ Administration
- **Gestion des Utilisateurs** : Création et gestion des comptes utilisateurs.
- **Structure Organisationnelle** : Configuration des entités, départements et fonctions.
- **Configuration Système** : Paramètres globaux de l'application.

## Stack Technique

Le projet repose sur une architecture moderne et performante :

- **Backend** : [Laravel 12.0](https://laravel.com)
- **Frontend** : [Vue.js 3](https://vuejs.org) (Composition API)
- **Style** : [TailwindCSS 4.0](https://tailwindcss.com)
- **Build Tool** : [Vite](https://vitejs.dev)
- **Authentification** : Laravel Sanctum
- **Base de Données** : MySQL / SQLite (compatible)

## Prérequis

- PHP >= 8.2
- Composer
- Node.js & NPM
- Base de données (MySQL ou autre compatible Laravel)

## Installation

1. **Cloner le dépôt**
   ```bash
   git clone <votre-repo-url>
   cd GED
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances JavaScript**
   ```bash
   npm install
   ```

4. **Configurer l'environnement**
   Dupliquez le fichier d'exemple et configurez vos accès base de données :
   ```bash
   cp .env.example .env
   ```
   Ouvrez le fichier `.env` et mettez à jour les informations de connexion à la base de données (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

5. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

6. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

7. **Compiler les assets**
   ```bash
   npm run build
   ```

## Utilisation

Pour lancer l'environnement de développement :

1. **Lancer le serveur Laravel**
   ```bash
   php artisan serve
   ```

2. **Lancer le serveur de développement Vite** (dans un autre terminal)
   ```bash
   npm run dev
   ```

Accédez ensuite à l'application via `http://localhost:8000` (ou l'URL indiquée par Artisan).

## License

Ce projet est sous licence [MIT](https://opensource.org/licenses/MIT).
