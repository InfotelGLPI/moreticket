<?php

/**
 * -------------------------------------------------------------------------
 * moreticket plugin for GLPI
 * Copyright (C) 2015-2026 by the moreticket Development Team.
 *
 * https://github.com/InfotelGLPI/moreticket
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of moreticket.
 *
 * moreticket is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * moreticket is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Moreticket;

use Ticket;

/**
 * Item-level rights for a record that only exists as a child of a ticket.
 *
 * None of the moreticket child tables carries an entities_id column, so
 * CommonDBTM::isEntityAssign() is false for all of them and checkEntity() is a
 * no-op. Without that, can()/check() collapses to the global profile right and
 * anyone holding it reaches a row attached to a ticket of another entity --
 * through the form, a tab, or the core massive action endpoint.
 *
 * Anchoring the item-level rights on the parent ticket restores the boundary
 * whatever the entry point: Ticket carries entities_id and its own visibility
 * rules (own / group / all), so delegating to it gives the child exactly the
 * reach the caller already has on the ticket itself.
 *
 * The parent:: calls are kept so the global right is still required: this trait
 * narrows access, it never widens it.
 *
 * No direct-access die() here, unlike the classes of src/: this file declares a
 * trait and nothing else, so being reached directly executes nothing.
 */
trait ParentTicketRights
{
    /**
     * Whether the caller holds $right on the ticket this record hangs from.
     *
     * A row with no parent is unreachable rather than free: it should not exist,
     * and there is no boundary left to check it against.
     *
     * @param int      $right      One of the READ / UPDATE constants
     * @param int|null $tickets_id Parent to check, read from the loaded row when
     *                             null -- creation has to name it from the input,
     *                             since nothing is loaded yet
     *
     * @return bool
     */
    private function canOnParentTicket(int $right, ?int $tickets_id = null): bool
    {
        $tickets_id ??= (int) ($this->fields['tickets_id'] ?? 0);

        if ($tickets_id <= 0) {
            return false;
        }

        $ticket = new Ticket();

        return (bool) $ticket->can($tickets_id, $right);
    }

    public function canCreateItem(): bool
    {
        return parent::canCreateItem()
            && $this->canOnParentTicket(UPDATE, (int) ($this->input['tickets_id'] ?? 0));
    }

    public function canViewItem(): bool
    {
        return parent::canViewItem() && $this->canOnParentTicket(READ);
    }

    public function canUpdateItem(): bool
    {
        return parent::canUpdateItem() && $this->canOnParentTicket(UPDATE);
    }

    public function canDeleteItem(): bool
    {
        return parent::canDeleteItem() && $this->canOnParentTicket(UPDATE);
    }

    public function canPurgeItem(): bool
    {
        return parent::canPurgeItem() && $this->canOnParentTicket(UPDATE);
    }
}
