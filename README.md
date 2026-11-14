# Gestion du patrimoine de l'État

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
Analyse du besoin, diagrammes UML (cas d'utilisation, classes, séquence) et maquettes Figma : voir le dossier `docs/`.