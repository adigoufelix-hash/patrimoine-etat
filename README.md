# PatriGest

![Logo PatriGest](docs/logo_patrigest.svg)

**Gestion du patrimoine de l'État.** Plateforme web de recensement, d'affectation et de suivi des biens publics, construite en **architecture micro-services** (Laravel 12, PostgreSQL 16, Docker), aux couleurs du drapeau du Togo.

## Architecture

| Service | Rôle | Port | Base |
|---|---|---|---|
| gateway | interface web, droits, orchestration | 8000 | aucune |
| service-biens | biens et catégories | 8001 | biens |
| service-auth | comptes, rôles, jetons | 8002 | auth |
| service-affectations | services, affectations, incidents | 8003 | affectations |
| service-audit | journal d'audit en ajout seul | 8004 | audit |
| service-notifications | messages par rôle ou utilisateur | 8005 | notifications |
| service-maintenance | planification et alertes des maintenances | 8006 | maintenance |
| db | PostgreSQL 16 (six bases) | 5433 | |

- Les services échangent en HTTP/JSON dans le réseau Docker et s'appellent par leur nom.
- Chaque service possède sa **propre base** : les références entre services sont de simples identifiants (pas de clé étrangère entre bases).
- Chaque service exige l'en-tête `X-Service-Key` : seule la gateway (et les services qui interrogent les biens) connaît la clé.
- Le journal d'audit et les notifications sont envoyés **après la réponse**, avec un délai d'attente de 2 secondes : leur panne ne bloque jamais l'utilisateur.

## Installation en local

Prérequis : Docker Desktop (démarré) et Git.

```bash
git clone <adresse-du-depot> patrimoine
cd patrimoine

# 1. Fichiers d'environnement (la même clé de service dans tous les services)
for s in gateway service-biens service-auth service-affectations service-audit service-notifications service-maintenance; do
  cp $s/.env.example $s/.env
  sed -i 's/^SERVICE_KEY=.*/SERVICE_KEY=cle-locale-de-developpement/' $s/.env
done

# 2. Dépendances PHP et clé d'application
for s in gateway service-biens service-auth service-affectations service-audit service-notifications service-maintenance; do
  docker compose run --rm $s composer install
  docker compose run --rm $s php artisan key:generate
done

# 3. Démarrage
docker compose up -d

# 4. Bases de données (la base « biens » est créée par PostgreSQL)
for d in auth affectations audit notifications maintenance; do
  docker compose exec db psql -U patrimoine -d biens -c "CREATE DATABASE $d;"
done

# 5. Tables et données d'essai
docker compose exec service-biens php artisan migrate --seed
docker compose exec service-auth php artisan migrate --seed
docker compose exec service-affectations php artisan migrate --seed
docker compose exec service-audit php artisan migrate
docker compose exec service-notifications php artisan migrate
docker compose exec service-maintenance php artisan migrate
```

Ouvrir ensuite http://localhost:8000. La **première requête** après le démarrage peut être lente (plusieurs minutes sous Windows) : patienter.

Les valeurs de ce README (`cle-locale-de-developpement`, mot de passe `secret`) sont des valeurs d'essai pour le développement local. Elles ne doivent pas être utilisées en production.

## Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | admin@patrimoine.tg | Patrimoine2026! |
| Gestionnaire | gestionnaire@patrimoine.tg | Patrimoine2026! |
| Responsable de service | responsable@patrimoine.tg | Patrimoine2026! |

Tout visiteur peut créer un compte sur `/inscription` : il reçoit le rôle « responsable de service » (RG7). Seul un administrateur peut changer un rôle.

## Fonctionnalités

- Comptes, connexion, rôles et droits d'accès
- Biens : recherche, filtres, fiche détaillée, ajout, modification
- Catégories et services (administrateur)
- Affectation d'un bien et historique
- Incidents : signalement et suivi des statuts
- Maintenances : planification, clôture, alertes
- Notifications avec compteur de messages non lus
- Journal d'audit (administrateur)
- Tableau de bord, export Excel (CSV) et PDF (impression)
- Interface en français, adaptée au téléphone

## Règles de gestion

| Code | Règle |
|---|---|
| RG1 | code d'inventaire unique |
| RG2 | une seule affectation active par bien |
| RG3 | l'historique est conservé, sans suppression |
| RG4 | un bien réformé ne peut plus être affecté |
| RG5 | seuls l'administrateur et le gestionnaire créent ou modifient un bien |
| RG6 | un responsable ne voit que les biens de son service |
| RG7 | un compte créé en libre-service est « responsable » |
| RG8 | un compte désactivé ne peut plus se connecter |
| RG9 | un incident passe de ouvert à en cours puis résolu (gestionnaire, administrateur) |
| RG10 | l'export est réservé à l'administrateur et au gestionnaire |
| RG11 | un administrateur ne peut pas retirer ses droits ni se désactiver |
| RG12 | noms de catégories et de services uniques, sans suppression |
| RG13 | le journal d'audit est en ajout seul |
| RG14 | une maintenance ne se planifie que sur un bien non réformé, sans date passée |
| RG15 | une notification n'est visible que par ses destinataires |
| RG16 | une alerte de maintenance n'est envoyée qu'une fois par jour |

## Normalisation de la base de données

Chaque service possède sa propre base (auth, biens, affectations, audit, notifications, maintenance).

- **1FN** : chaque colonne contient une valeur simple et chaque table a une clé primaire. Les affectations successives d'un bien sont des lignes de la table `affectations`, pas des colonnes `service1`, `service2`.
- **2FN** : toutes les clés primaires sont simples (`id`), donc aucune dépendance partielle. Les informations d'une catégorie ou d'un service sont stockées une seule fois dans `categories` et `services`. La table `lectures` porte un couple unique (`notification_id`, `utilisateur_id`).
- **3FN** : aucune colonne ne dépend d'une autre colonne non clé. Le nom du service n'est pas recopié dans `affectations`, le nom de la catégorie n'est pas recopié dans `biens`.
- **Exception justifiée** : dans le journal d'audit, `utilisateur_nom` et `role` sont recopiés volontairement, pour conserver ce qui était vrai au moment de l'action.
- **Micro-services** : `bien_id`, `signale_par`, `cree_par`, `utilisateur_id` et `service_id` (dans `users`) sont des références logiques sans clé étrangère, car elles désignent des données d'une autre base. L'existence est vérifiée par appel d'API.
- **Contraintes** : unicité du code d'inventaire, des noms de catégorie et de service, de l'adresse e-mail, du couple (notification, utilisateur).

## Sécurité et limites

- Mots de passe hachés, jetons stockés sous forme d'empreinte, protection CSRF, limitation des tentatives, droits contrôlés côté serveur.
- Clé de service entre les micro-services. En production : HTTPS, clé aléatoire longue dans les variables d'environnement de l'hébergeur, réseau privé entre services.
- Un événement d'audit ou une notification peut être perdu si le service est indisponible au moment de l'action (choix de disponibilité).
- Les alertes de maintenance partent à l'ouverture de la page (pas de tâche planifiée).
- Pas de vérification de l'adresse e-mail ni de réinitialisation du mot de passe.

## Documentation

| Document | Contenu |
|---|---|
| `docs/Analyse_du_besoin_Gestion_Patrimoine.docx` | besoin, acteurs, règles de gestion RG1 à RG16 |
| `docs/Fiche_technique_Gestion_Patrimoine.docx` | architecture, données, API, sécurité, identité visuelle |
| `docs/Guide_utilisation_Gestion_Patrimoine.docx` | utilisation par rôle |
| `docs/Proposition_financiere_Gestion_Patrimoine.docx` | estimation financière |
| `docs/UML.md` | cas d'utilisation, classes, séquences |
| `docs/MAQUETTES.md` | maquettes Figma et prototype |
