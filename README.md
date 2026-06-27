## Moreticket plugin for GLPI

[![License](https://img.shields.io/badge/License-GNU%20GPL%20v3%2B-blue.svg?style=flat-square)](https://github.com/InfotelGLPI/moreticket/blob/master/LICENSE)
[![Web](https://img.shields.io/badge/Web-Infotel-blue.svg?style=flat-square)](https://blogglpi.infotel.com)
[![Translate](https://img.shields.io/badge/Translate-Transifex-cyan)](https://explore.transifex.com/infotelGLPI/GLPI_moreticket/)

---

### English

This plugin extends the GLPI ticket lifecycle with additional enforcement controls:

* When resolving or closing a ticket, the plugin requires a **solution** to be entered before the status change is accepted.

* When setting a ticket to **"Pending"**, a panel appears allowing you to define a waiting type, a reason for the suspension, and a postponement date.

* A **"Duration"** field can be added to the solution form; upon submission, a task is automatically created with that duration.

* A **post-closure tab** is available to record additional information (comment, author, document) after a ticket is closed.

* Supports **urgency justification**, **automatic ticket reopening** on document/task/follow-up events, and **modification tracking**.

**[Full English documentation →](docs/en/index.md)**

---

### Français

Ce plugin permet d'ajouter des nouvelles options sur un ticket GLPI :

* À la création d'un ticket, lors de la sélection du statut « Résolu » ou « Clos », le ticket vous proposera de remplir la solution du ticket.

* À la création et à la modification d'un ticket, lors de la sélection du statut « En attente », vous pourrez définir des types d'attente, la raison de la mise en attente ainsi qu'une date de report.

* Utiliser le champ « Durée » dans l'interface d'ajout de solution.

* Enfin une nouvelle zone sera disponible pour ajouter des informations suite à la clôture du ticket.

**[Documentation complète en français →](docs/fr/index.md)**
