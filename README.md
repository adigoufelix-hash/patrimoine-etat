# PatriGest

![Logo PatriGest](docs/logo_patrigest.svg)

**Gestion du patrimoine de l'État**

Plateforme web de recensement, d'affectation et de suivi des biens publics,
construite en architecture micro-services (Laravel, PostgreSQL, Docker).

## Architecture
| Service | Rôle | Port |
|---|---|---|
| gateway | interface web | 8000 |
| service-biens | biens et catégories | 8001 |
| service-auth | comptes et rôles | 8002 |
| service-affectations | affectations, historique, incidents | 8003 |
| db | PostgreSQL, une base par service | 5433 |

Les services communiquent par API REST (JSON) dans le réseau Docker.
Chaque service possède sa propre base : les références entre services se font
par identifiant (pas de clé étrangère inter-bases).

## Installation
```bash
git clone <url-du-depot> && cd patrimoine-etat
for s in gateway service-biens service-auth service-affectations; do
  cp $s/.env.example $s/.env
  docker compose run --rm $s composer install
  docker compose run --rm $s php artisan key:generate
done
docker compose up -d
docker compose exec db psql -U patrimoine -d biens -c "CREATE DATABASE auth;"
docker compose exec db psql -U patrimoine -d biens -c "CREATE DATABASE affectations;"
docker compose exec service-biens php artisan migrate --seed
docker compose exec service-affectations php artisan migrate --seed
```
Puis ouvrir http://localhost:8000/biens

## API principale
- `GET /api/biens`, `POST /api/biens` (service-biens, port 8001)
- `POST /api/affectations`, `GET /api/biens/{id}/affectations` (service-affectations, port 8003)

## Règles de gestion
RG1 code d'inventaire unique · RG2 une seule affectation active par bien ·
RG3 historique conservé · RG4 un bien réformé n'est plus affectable.

## Normalisation de la base de données
(à rédiger : voir ci-dessous)
## Conception
- Analyse du besoin : `docs/Analyse_du_besoin_Gestion_Patrimoine.docx`
- Diagrammes UML (cas d'utilisation, classes, séquence) : [docs/UML.md](docs/UML.md)
- Maquettes Figma et prototype : [docs/MAQUETTES.md](docs/MAQUETTES.md)

## Comptes de démonstration
| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | admin@patrimoine.tg | Patrimoine2026! |
| Gestionnaire | gestionnaire@patrimoine.tg | Patrimoine2026! |
| Responsable de service | responsable@patrimoine.tg | Patrimoine2026! |

Tout visiteur peut créer un compte sur `/inscription` : il reçoit le rôle
« responsable de service » (RG7). Seul un administrateur peut changer un rôle.
## Normalisation de la base de données

Chaque service possède sa propre base (auth, biens, affectations).

- **1FN** : chaque colonne contient une valeur simple et chaque table a une clé primaire. Les affectations successives d'un bien sont des lignes de la table `affectations`, pas des colonnes `service1`, `service2`.
- **2FN** : toutes les clés primaires sont simples (`id`), donc aucune dépendance partielle. Les informations d'une catégorie ou d'un service sont stockées une seule fois dans `categories` et `services`.
- **3FN** : aucune colonne ne dépend d'une autre colonne non clé. Le nom du service n'est pas recopié dans `affectations` (seulement `service_id`), le nom de la catégorie n'est pas recopié dans `biens`.
- **Micro-services** : `bien_id`, `signale_par` et `service_id` (dans `users`) sont des références logiques sans clé étrangère, car elles désignent des données d'une autre base. L'existence est vérifiée par appel d'API.
- **Contraintes** : unicité du code d'inventaire, du nom de catégorie, du nom de service et de l'adresse e-mail.

## Documentation

| Document | Contenu |
|---|---|
| `docs/Analyse_du_besoin_Gestion_Patrimoine.docx` | Besoin, acteurs, règles de gestion RG1 à RG12 |
| `docs/Fiche_technique_Gestion_Patrimoine.docx` | Architecture, données, API, sécurité |
| `docs/Guide_utilisation_Gestion_Patrimoine.docx` | Utilisation par rôle |
| `docs/Proposition_financiere_Gestion_Patrimoine.docx` | Estimation financière |
| `docs/UML.md` | Diagrammes UML |
| `docs/MAQUETTES.md` | Maquettes Figma |
