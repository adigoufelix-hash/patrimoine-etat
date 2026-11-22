# Conception UML

## Diagramme de cas d'utilisation

Quatre acteurs, seize cas d'utilisation, colorés selon le micro-service qui les réalise.

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
class Maintenance {
  +int id
  +int bien_id
  +string type
  +date date_prevue
  +date date_realisee
  +string statut
  +date alerte_le
}
class Evenement {
  +int id
  +int utilisateur_id
  +string action
  +string objet
  +json detail
  +string adresse_ip
  +datetime created_at
}
class Notification {
  +int id
  +string titre
  +string message
  +string role_cible
  +int utilisateur_id
}
class Lecture {
  +int notification_id
  +int utilisateur_id
  +datetime lue_at
}
Categorie "1" --> "0..*" Bien : classe
Service "1" --> "0..*" Affectation : recoit
Notification "1" --> "0..*" Lecture : lue par
Bien "1" ..> "0..*" Affectation : est affecte
Bien "1" ..> "0..*" Incident : subit
Bien "1" ..> "0..*" Maintenance : fait objet
Utilisateur "1" ..> "0..*" Incident : signale
Utilisateur "1" ..> "0..*" Evenement : realise
Utilisateur "1" ..> "0..*" Notification : recoit
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

## Diagramme de séquence : signaler un incident (journal et notifications)

Le journal et les notifications sont envoyés après la réponse, avec un délai
d'attente de 2 secondes : leur panne ne bloque jamais l'utilisateur.

```mermaid
sequenceDiagram
    actor R as Responsable
    participant GW as Gateway
    participant AF as Service Affectations
    participant B as Service Biens
    participant AU as Service Audit
    participant NO as Service Notifications

    R->>GW: Envoie le signalement
    GW->>B: GET /api/biens (le bien est-il dans mon service ? RG6)
    B-->>GW: Biens
    GW->>AF: POST /api/incidents
    AF->>B: GET /api/biens/id
    B-->>AF: Bien existant
    AF-->>GW: 201 Incident créé
    GW-->>R: Confirmation
    Note over GW,NO: Après la réponse, sans attente
    GW->>AU: POST /api/evenements (RG13)
    GW->>NO: POST /api/notifications (gestionnaires et administrateurs)
```

## Diagramme de séquence : alertes de maintenance

```mermaid
sequenceDiagram
    actor G as Gestionnaire
    participant GW as Gateway
    participant MA as Service Maintenance
    participant NO as Service Notifications

    G->>GW: Ouvre la page Maintenances
    GW->>MA: POST /api/maintenances/alertes
    Note over MA: En retard ou prévues sous 3 jours, une seule fois par jour (RG16)
    MA-->>GW: Maintenances à signaler (liste vide si déjà signalées)
    GW->>NO: POST /api/notifications (gestionnaires et administrateurs)
    GW->>MA: GET /api/maintenances
    MA-->>GW: Liste des maintenances
    GW-->>G: Page Maintenances
```
