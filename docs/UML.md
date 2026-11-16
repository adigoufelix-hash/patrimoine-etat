# Conception UML

## Diagramme de cas d'utilisation

Quatre acteurs, treize cas d'utilisation, colorés selon le micro-service qui les réalise.

![Diagramme de cas d'utilisation](cas_utilisation.svg)

## Diagramme de classes

Les traits pleins relient des classes d'un même service. Les traits pointillés
sont des références par identifiant entre services (pas de clé étrangère
entre bases différentes).

```mermaid
classDiagram
direction LR
class Utilisateur {
  +int id
  +string nom
  +string email
  +string mot_de_passe
  +string role
  +int service_id
  +bool actif
}
class Categorie {
  +int id
  +string nom
  +string description
}
class Bien {
  +int id
  +string code_inventaire
  +string designation
  +date date_acquisition
  +decimal valeur
  +string etat
  +int categorie_id
}
class Service {
  +int id
  +string nom
  +string code
  +string localisation
}
class Affectation {
  +int id
  +int bien_id
  +int service_id
  +date date_debut
  +date date_fin
  +string motif
}
class Incident {
  +int id
  +int bien_id
  +int signale_par
  +string type
  +string description
  +date date_signalement
  +string statut
}
Categorie "1" --> "0..*" Bien : classe
Service "1" --> "0..*" Affectation : recoit
Bien "1" ..> "0..*" Affectation : est affecte
Bien "1" ..> "0..*" Incident : subit
Utilisateur "1" ..> "0..*" Incident : signale
Service "1" ..> "0..*" Utilisateur : emploie
```

## Diagramme de séquence : affecter un bien à un service

```mermaid
sequenceDiagram
    actor G as Gestionnaire
    participant GW as Gateway
    participant AF as Service Affectations
    participant B as Service Biens
    participant DB as Base affectations

    G->>GW: Choisit un bien et un service
    GW->>AF: POST /api/affectations
    AF->>B: GET /api/biens/id
    B-->>AF: Bien et son état
    alt Bien introuvable
        AF-->>GW: 422 Bien introuvable
        GW-->>G: Message d'erreur
    else Bien réformé (RG4)
        AF-->>GW: 422 Affectation impossible
        GW-->>G: Message d'erreur
    else Bien disponible
        AF->>DB: Clôture l'affectation active (RG2, RG3)
        AF->>DB: Enregistre la nouvelle affectation
        DB-->>AF: Enregistrement confirmé
        AF-->>GW: 201 Created
        GW-->>G: Confirmation
    end
```

## Diagramme de séquence : créer un compte

```mermaid
sequenceDiagram
    actor V as Visiteur
    participant GW as Gateway
    participant AU as Service Auth

    V->>GW: Remplit le formulaire d'inscription
    GW->>AU: POST /api/register
    Note over AU: Le rôle responsable est imposé par le serveur (RG7)
    alt E-mail déjà utilisé ou données invalides
        AU-->>GW: 422 Erreurs de validation
        GW-->>V: Messages d'erreur
    else Données valides
        AU-->>GW: 201 avec jeton et profil
        GW-->>V: Session ouverte, redirection vers les biens
    end
```
