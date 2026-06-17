# Documentation — Moreticket Plugin for GLPI
 
**License:** GNU GPL v2+  
**Author:** Infotel (Xavier CAILLAUD)  
**Repository:** https://github.com/InfotelGLPI/moreticket

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Features](#features)
   - [Waiting Tickets](#waiting-tickets)
   - [Mandatory Solution](#mandatory-solution)
   - [Solution Duration](#solution-duration)
   - [Closing Information](#closing-information)
   - [Urgency Justification](#urgency-justification)
   - [Automatic Reopening](#automatic-reopening)
   - [Modification Tracking](#modification-tracking)
5. [Rights Management](#rights-management)
6. [Automatic Action (Cron)](#automatic-action-cron)
7. [Search Options](#search-options)
8. [Uninstallation](#uninstallation)

---

## Overview

The **Moreticket** plugin extends the GLPI ticket lifecycle with additional enforcement controls:

- Require a **waiting reason** and **postponement date** when setting a ticket to "Pending"
- Enforce a **solution entry** on ticket resolution or closure
- Require a **duration** on the solution form and automatically create a matching task
- Add a **post-closure tab** to enrich the ticket after it is closed
- Request a **written justification** for high-urgency tickets
- **Automatically reopen** a ticket when a document, task, or technician follow-up is added

---

## Installation

1. Download the plugin from [GitHub](https://github.com/InfotelGLPI/moreticket) or the GLPI marketplace.
2. Extract the archive into the `plugins/` (or `marketplace/`) directory of your GLPI installation.
3. Log in to GLPI as an administrator.
4. Go to **Setup › Plugins**, then click **Install** and **Enable** for *Moreticket*.

---

## Configuration

Access: **Setup › Plugins › More ticket › Configure**

| Option | Description |
|--------|-------------|
| **Use waiting tickets** | Enables the reason/type/date form when a ticket is set to "Pending" |
| **Enforce solution** | Requires a solution entry when resolving or closing a ticket |
| **Use solution duration** | Adds a "Duration" field to the solution form; automatically creates a task with that duration |
| **Statuses triggering solution** | Select which statuses (Resolved, Closed…) enforce the solution |
| **Closing information** | Enables the post-closure tab (comment, author, document) |
| **Urgency justification** | Enables justification prompts for selected urgency levels |
| **Urgency levels concerned** | Selects which urgency levels trigger the justification prompt |
| **Reopen after document added** | Automatically sets the ticket back to "Assigned" when a document is attached |
| **Reopen after approval** | Sets the ticket back to "Assigned" after a validation is granted or refused |
| **Reopen after technician task** | Sets the ticket back to "Assigned" when a technician adds a task |
| **Reopen after technician follow-up** | Sets the ticket back to "Assigned" when a technician adds a public follow-up |

---

## Features

### Waiting Tickets

When this feature is enabled and a user selects the **"Pending"** status in the ticket form, a panel appears allowing them to enter:

- **Waiting type** — hierarchical dropdown configurable by administrators (e.g. *Waiting for supplier*, *Waiting for customer*)
- **Reason** — free-text field describing the cause of the suspension
- **Postponement date** — date on which the ticket should be automatically reopened

A **"Waiting Tickets" tab** on each ticket lists the full history of all suspension cycles (start date, end date, type, reason, previous status).

**Waiting types** are managed under **Setup › Dropdowns › Waiting types**. Types support parent/child hierarchy.

---

### Mandatory Solution

When a technician attempts to set a ticket to a configured status (default: Resolved or Closed) without having entered a solution, the plugin blocks the action and displays a warning.

A banner is also shown at the top of the ticket form as a persistent reminder.

---

### Solution Duration

When this option is enabled, a **"Duration"** field is added to the solution entry form (`ITILSolution`).

- The duration is mandatory if the option is active.
- Upon submission, a **task is automatically created** on the ticket with the entered duration, so it is counted in time-tracking indicators.

---

### Closing Information

A **"Closing Information" tab** appears on tickets with the Closed status. It allows recording:

- A **post-closure comment**
- The **author** (GLPI user)
- The **date** of the information entry
- An **attached document**

This information is available as columns in ticket search results.

---

### Urgency Justification

When a ticket is created or updated with an urgency level included in the configured list, a mandatory text field appears requesting a **written justification**.

The justification is saved and viewable in the corresponding ticket tab.

---

### Automatic Reopening

Four triggers can automatically reopen a ticket to the "Assigned" status:

| Trigger | Description |
|---------|-------------|
| Document added | A document is attached to the ticket |
| Approval change | A validation is granted or refused |
| Technician task added | A technician (non-requester) adds a task |
| Technician follow-up added | A technician adds a public follow-up |

Each trigger can be enabled or disabled independently in the configuration.

---

### Modification Tracking

The plugin records the **last user to modify a ticket** (follow-up, task, validation). This information is available as a column in ticket lists (bell icon) via the **"Updated by"** search option.

---

## Rights Management

Access: **Administration › Profiles › [profile] › More ticket tab**

| Right | Role |
|-------|------|
| `plugin_moreticket` | Full plugin access (read, write, delete, admin) |
| `plugin_moreticket_justification` | Permission to add an urgency justification |
| `plugin_moreticket_hide_task_duration` | Hide the "Duration" field in tasks for the profile |

At installation, the Super-Admin profile receives all rights.

---

## Automatic Action (Cron)

| Name | Frequency | Description |
|------|-----------|-------------|
| `MoreticketWaitingTicket` | Daily | Scans pending tickets whose **postponement date** has passed, restores their previous status, and optionally adds a follow-up note. |

The task is registered automatically at installation. It can be viewed and configured under **Setup › Automatic actions**.

---

## Search Options

The following columns are available in ticket lists:

| Option ID | Column | Description |
|-----------|--------|-------------|
| 3452 | Waiting type | Last selected waiting type |
| 3453 | Closing date | Date recorded in closing information |
| 3454 | Closing comment | Post-closure comment |
| 3455 | Closing author | User who entered the closing information |
| 3486 | Closing document | Document attached to closing information |
| 3487 | Updated by | Last user to modify the ticket |

---

## Uninstallation

1. Go to **Setup › Plugins**.
2. Click **Disable** then **Uninstall** for *Moreticket*.

> **Warning:** Uninstalling removes all plugin tables and associated data (waiting history, closing information, urgency justifications).
