# Déploiement sur Render (offre gratuite)

Ce guide explique comment héberger PatriGest sur [Render](https://render.com) avec l'offre gratuite :
**1 base PostgreSQL** et **7 services web** (un par service de l'architecture micro-services).

> ⚠️ Limites de l'offre gratuite :
> - Les services web se mettent en veille après ~15 min sans trafic ; le premier appel peut prendre ~1 min (réveil à froid).
> - La base PostgreSQL gratuite **expire après 30 jours**. Penser à la recréer ou à passer à l'offre payante (~6 $/mois) avant expiration.

## Vue d'ensemble

| Service Render | Dossier du dépôt | Rôle | Variables d'URL nécessaires |
|---|---|---|---|
| `patrimoine-gateway` | `gateway/` | site web (seul service public) | les 6 `SERVICE_*_URL` |
| `patrimoine-biens` | `service-biens/` | biens et catégories | — |
| `patrimoine-auth` | `service-auth/` | comptes et rôles | — |
| `patrimoine-affectations` | `service-affectations/` | affectations, incidents | `SERVICE_BIENS_URL` |
| `patrimoine-audit` | `service-audit/` | journal d'audit | — |
| `patrimoine-notifications` | `service-notifications/` | notifications | — |
| `patrimoine-maintenance` | `service-maintenance/` | maintenances | `SERVICE_BIENS_URL` |
| `patrimoine-db` | — | PostgreSQL (6 bases) | — |

Chaque service a son propre `Dockerfile` et `docker-entrypoint.sh` à la racine de son dossier :
le démarrage applique automatiquement les migrations (`php artisan migrate --force`) et,
si `SEED_DEMO=true`, les données d'exemple.

## Étape 1 — Pousser le code sur GitHub

Render déploie depuis un dépôt Git. Le code doit être à jour sur `origin/main` :

```bash
git add -A && git commit -m "Préparation déploiement Render" && git push
```

## Étape 2 — Créer la base PostgreSQL

1. Dashboard Render → **New → PostgreSQL**.
2. Nom : `patrimoine-db`, plan **Free**, région au choix (idéalement la même que les services web).
3. Noter le **Internal Database URL** (format `postgresql://USER:PASSWORD@HOST:5432/DB`) — c'est ce qui sera utilisé par les services.

### Créer les 6 bases logiques

Render ne crée qu'une base. Ouvrir un terminal et se connecter avec la commande `psql`
fournie dans le dashboard, puis créer les bases manquantes :

```sql
CREATE DATABASE biens;
CREATE DATABASE auth;
CREATE DATABASE affectations;
CREATE DATABASE audit;
CREATE DATABASE notifications;
CREATE DATABASE maintenance;
```

(La base créée par Render peut servir de l'une des six, ou servir de base « principale » non utilisée.)

Relever dans le dashboard : **host interne**, **port**, **utilisateur**, **mot de passe**.

## Étape 3 — Variables communes à tous les services

Pour **chacun** des 7 services web, dans *Environment*, ajouter :

| Variable | Valeur |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base32:...` — générer une clé par service : `php artisan key:generate --show` (ou laisser vide : une clé est générée au premier démarrage, mais elle change à chaque redéploiement → sessions perdues) |
| `APP_LOCALE` | `fr` |
| `SERVICE_KEY` | **la même longue clé aléatoire partout** (ex. `openssl rand -hex 32`) — sans elle tous les appels sont refusés (403) |
| `SESSION_DRIVER` | `file` |
| `SESSION_LIFETIME` | `120` |
| `SESSION_ENCRYPT` | `false` |
| `SESSION_PATH` | `/` |
| `SESSION_DOMAIN` | laisser vide |
| `BROADCAST_CONNECTION` | `log` |
| `FILESYSTEM_DISK` | `local` |
| `QUEUE_CONNECTION` | `sync` |
| `CACHE_STORE` | `file` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `APP_URL` | `https://<nom-du-service>.onrender.com` |

Pour les **6 services avec base** (tous sauf `patrimoine-gateway`), ajouter :

| Variable | Valeur |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | host **interne** du Postgres Render |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `biens`, `auth`, `affectations`, `audit`, `notifications` ou `maintenance` selon le service |
| `DB_USERNAME` | utilisateur du Postgres Render |
| `DB_PASSWORD` | mot de passe du Postgres Render |

## Étape 4 — Créer les 7 services web

Pour chaque service : **New → Web Service** → connecter le dépôt GitHub →

- **Name** : `patrimoine-gateway`, `patrimoine-biens`, … (voir le tableau)
- **Runtime** : `Docker`
- **Root Directory** : le dossier du service (`gateway`, `service-biens`, …)
- **Dockerfile Path** : `Dockerfile` (détecté automatiquement)
- **Instance Type** : `Free`
- **Region** : la même que la base

Une fois l'URL `https://<nom>.onrender.com` connue, ajouter les variables d'URL :

**patrimoine-gateway** :

```
SERVICE_BIENS_URL=https://patrimoine-biens.onrender.com
SERVICE_AUTH_URL=https://patrimoine-auth.onrender.com
SERVICE_AFFECTATIONS_URL=https://patrimoine-affectations.onrender.com
SERVICE_AUDIT_URL=https://patrimoine-audit.onrender.com
SERVICE_NOTIFICATIONS_URL=https://patrimoine-notifications.onrender.com
SERVICE_MAINTENANCE_URL=https://patrimoine-maintenance.onrender.com
```

**patrimoine-affectations** et **patrimoine-maintenance** :

```
SERVICE_BIENS_URL=https://patrimoine-biens.onrender.com
```

## Étape 5 — Données d'exemple (optionnel)

Pour pré-remplir la base avec les catégories, services et **comptes de démonstration**
(admin@patrimoine.tg / Patrimoine2026!, etc.), ajouter `SEED_DEMO=true` sur
`patrimoine-biens`, `patrimoine-auth` et `patrimoine-affectations`, puis redéployer
(*Manual Deploy → Deploy latest commit*). Une fois les données en place, remettre `SEED_DEMO=false`.

## Étape 6 — Vérification

1. Ouvrir `https://patrimoine-gateway.onrender.com` — la page de connexion doit s'afficher.
2. Se connecter avec un compte de démonstration.
3. Vérifier les journaux de chaque service dans le dashboard Render (*Logs*) : chercher `ERROR` ou `Failed`.

## Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| Toutes les pages renvoient une erreur / 403 entre services | `SERVICE_KEY` absent ou différent | Vérifier que la même valeur est sur les 7 services, puis *Manual Deploy* |
| `SQLSTATE ... could not connect` / migrations en boucle | `DB_HOST`/`DB_PASSWORD` faux, ou mauvais réseau | Utiliser le **host interne** (pas l'externe), services et base dans la **même région** |
| `Too many connections` | base free petite | normale si trafic élevé ; réessayer plus tard ou passer au plan payant |
| Page blanche après ~1 min | service endormi (gratuit) | attendre le réveil ; c'est le fonctionnement normal du free tier |
| Sessions perdues à chaque redéploiement | `APP_KEY` vide | définir `APP_KEY` explicitement dans les variables d'environnement |

## Exploitation

- **Sauvegardes** : le plan gratuit n'inclut pas de sauvegardes automatiques ;
  exporter via `pg_dump` avec l'URL externe si besoin.
- **Mises à jour** : `git push` sur `main` ne redéploie **pas** automatiquement
  (pas d'auto-deploy configuré en manuel) : utiliser *Manual Deploy* sur chaque service,
  ou activer *Auto-Deploy* dans les settings du service.
- **Coût** : 0 $ tant que tout reste sur le free tier (sous réserve de l'expiration
  de la base PostgreSQL après 30 jours).
