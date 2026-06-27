# Documentation — Plugin Moreticket pour GLPI

**Licence :** GNU GPL v3+  
**Auteur :** Infotel (Xavier CAILLAUD)  
**Dépôt :** https://github.com/InfotelGLPI/moreticket

---

## Table des matières

1. [Présentation](#présentation)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Fonctionnalités](#fonctionnalités)
   - [Tickets en attente](#tickets-en-attente)
   - [Solution obligatoire](#solution-obligatoire)
   - [Durée sur la solution](#durée-sur-la-solution)
   - [Informations de clôture](#informations-de-clôture)
   - [Justification d'urgence](#justification-durgence)
   - [Réouverture automatique](#réouverture-automatique)
   - [Suivi des modifications](#suivi-des-modifications)
5. [Gestion des droits](#gestion-des-droits)
6. [Action automatique (cron)](#action-automatique-cron)
7. [Options de recherche](#options-de-recherche)
8. [Désinstallation](#désinstallation)

---

## Présentation

Le plugin **Moreticket** enrichit le cycle de vie des tickets GLPI avec des contrôles supplémentaires :

- Forcer la saisie d'une **raison d'attente** et d'une **date de report** lors du passage en statut « En attente »
- Imposer une **solution** lors de la résolution ou fermeture
- Exiger une **durée** sur le champ solution et créer automatiquement une tâche correspondante
- Ajouter un **onglet post-clôture** pour enrichir le ticket après fermeture
- Demander une **justification écrite** pour les urgences élevées
- Rouvrir **automatiquement** un ticket lorsqu'un document, une tâche ou un suivi est ajouté par un technicien

---

## Installation

1. Télécharger le plugin depuis [GitHub](https://github.com/InfotelGLPI/moreticket) ou la marketplace GLPI.
2. Décompresser l'archive dans le dossier `plugins/` (ou `marketplace/`) de votre installation GLPI.
3. Se connecter à GLPI en tant qu'administrateur.
4. Aller dans **Configuration › Plugins**, cliquer sur **Installer** puis **Activer** pour *Moreticket*.

---

## Configuration

Accès : **Configuration › Plugins › More ticket › Configurer**

| Option | Description |
|--------|-------------|
| **Utiliser les tickets en attente** | Active le formulaire de saisie de raison/type/date lors du passage en statut « En attente » |
| **Obliger la solution** | Impose la saisie d'une solution lors d'une résolution ou d'une fermeture |
| **Utiliser la durée sur la solution** | Ajoute un champ « Durée » au formulaire de solution ; crée automatiquement une tâche avec cette durée |
| **Statuts déclenchant la solution** | Sélection des statuts (Résolu, Clos…) qui imposent la solution |
| **Informations de clôture** | Active l'onglet post-clôture (commentaire, rédacteur, document) |
| **Justification d'urgence** | Active la demande de justification pour certains niveaux d'urgence |
| **Niveaux d'urgence concernés** | Sélection des niveaux déclenchant la justification |
| **Réouvrir après ajout de document** | Repasse automatiquement le ticket en « Assigné » lors de l'ajout d'un document |
| **Réouvrir après approbation** | Repasse le ticket en « Assigné » après validation/refus d'une approbation |
| **Réouvrir après tâche technicien** | Repasse le ticket en « Assigné » quand un technicien ajoute une tâche |
| **Réouvrir après suivi technicien** | Repasse le ticket en « Assigné » quand un technicien ajoute un suivi |

---

## Fonctionnalités

### Tickets en attente

Lorsque la fonctionnalité est activée et que l'utilisateur sélectionne le statut **« En attente »** dans le formulaire ticket, un panneau apparaît permettant de saisir :

- **Type d'attente** — dropdown hiérarchique configurable (ex. : *Attente fournisseur*, *Attente client*)
- **Raison** — champ texte libre décrivant la cause de la mise en attente
- **Date de report** — date à laquelle le ticket doit être relancé automatiquement

Un **onglet « Tickets en attente »** sur chaque ticket liste l'historique complet des cycles de suspension (date de début, date de fin, type, raison, statut précédent).

**Types d'attente** : accessibles via **Configuration › Dropdowns › Types d'attente**. Les types sont hiérarchiques (parent/enfant).

---

### Solution obligatoire

Lorsqu'un technicien tente de passer un ticket au statut configuré (par défaut : Résolu ou Clos) sans avoir renseigné de solution, le plugin bloque l'action et affiche un avertissement.

Une bannière est également affichée en haut du formulaire ticket pour rappeler l'obligation.

---

### Durée sur la solution

Lorsque cette option est activée, un champ **« Durée »** est ajouté au formulaire d'ajout de solution (`ITILSolution`).

- La durée est obligatoire si l'option est activée.
- À la validation, une **tâche est automatiquement créée** sur le ticket avec la durée saisie, permettant de la comptabiliser dans les indicateurs de temps.

---

### Informations de clôture

Un **onglet « Informations de clôture »** apparaît sur les tickets au statut Clos. Il permet d'enregistrer :

- Un **commentaire** post-clôture
- Le **rédacteur** (utilisateur GLPI)
- La **date** de l'information
- Un **document** attaché

Ces informations sont accessibles dans les résultats de recherche via les options dédiées.

---

### Justification d'urgence

Lorsqu'un ticket est créé ou modifié avec un niveau d'urgence figurant dans la liste configurée, un champ texte obligatoire s'affiche demandant une **justification écrite**.

La justification est enregistrée et consultable dans l'onglet correspondant du ticket.

---

### Réouverture automatique

Quatre déclencheurs peuvent rouvrir automatiquement un ticket au statut « Assigné » :

| Déclencheur | Description |
|-------------|-------------|
| Ajout de document | Un document est joint au ticket |
| Changement d'approbation | Une validation est accordée ou refusée |
| Ajout de tâche par technicien | Un technicien (non demandeur) ajoute une tâche |
| Ajout de suivi par technicien | Un technicien ajoute un suivi public |

Chaque déclencheur est activable indépendamment dans la configuration.

---

### Suivi des modifications

Le plugin enregistre le **dernier utilisateur ayant modifié un ticket** (suivi, tâche, validation). Cette information est disponible comme colonne dans les listes de tickets (icône cloche) via l'option de recherche **« Mis à jour par »**.

---

## Gestion des droits

Accès : **Administration › Profils › [profil] › onglet More ticket**

| Droit | Rôle |
|-------|------|
| `plugin_moreticket` | Accès complet au plugin (lecture, écriture, suppression, administration) |
| `plugin_moreticket_justification` | Autorisation d'ajouter une justification d'urgence |
| `plugin_moreticket_hide_task_duration` | Masquer le champ « Durée » dans les tâches (pour les profils concernés) |

À l'installation, le profil Super-Admin reçoit tous les droits.

---

## Action automatique (cron)

| Nom | Fréquence | Description |
|-----|-----------|-------------|
| `MoreticketWaitingTicket` | Quotidienne | Parcourt les tickets en attente dont la **date de report** est dépassée, restaure leur statut précédent et ajoute optionnellement un suivi de relance. |

La tâche est automatiquement enregistrée à l'installation. Elle est visible et configurable dans **Configuration › Actions automatiques**.

---

## Options de recherche

Les colonnes suivantes sont disponibles dans les listes de tickets :

| ID option | Colonne | Description |
|-----------|---------|-------------|
| 3452 | Type d'attente | Dernier type d'attente sélectionné |
| 3453 | Date de clôture | Date enregistrée dans les informations de clôture |
| 3454 | Commentaire de clôture | Commentaire post-clôture |
| 3455 | Rédacteur de clôture | Utilisateur ayant renseigné les informations de clôture |
| 3486 | Document de clôture | Document joint aux informations de clôture |
| 3487 | Mis à jour par | Dernier utilisateur ayant modifié le ticket |

---

## Désinstallation

1. Aller dans **Configuration › Plugins**.
2. Cliquer sur **Désactiver** puis **Désinstaller** pour *Moreticket*.

> **Attention :** La désinstallation supprime toutes les tables du plugin et les données associées (historique des attentes, informations de clôture, justifications d'urgence).
