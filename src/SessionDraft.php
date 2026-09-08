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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Form values kept in session between a refused submit and the redisplay of the form.
 *
 * Three controls of the plugin -- the waiting reason, the urgency justification and the
 * closing information -- refuse an incomplete post and let GLPI redirect back to the ticket.
 * What the user had typed only survives that redirect through the session, and this class is
 * the whole of that mechanism.
 *
 * Two properties matter and neither was held before:
 *
 * - a draft belongs to the ticket it was typed for. The session key is global to the user
 *   session, so a draft left by ticket A used to pre-fill the form of ticket B -- possibly in
 *   another entity, in front of somebody who never saw ticket A. Every draft is therefore
 *   stamped with its ticket and only handed back for that ticket;
 * - a draft holds the fields of the form, not the post. The three controls used to copy the
 *   whole input array, which on a ticket means its title, its content and its actors.
 */
final class SessionDraft
{
    public const WAITING = 'glpi_plugin_moreticket_waiting';

    public const URGENCY = 'glpi_plugin_moreticket_urgency';

    public const CLOSING = 'glpi_plugin_moreticket_close';

    /**
     * Key under which the ticket the draft was typed for is stamped.
     */
    private const TICKET = '_tickets_id';

    /**
     * Keep the form fields of a refused submit for the ticket that produced it.
     *
     * @param string $draft      one of the class constants
     * @param array  $values     the posted input
     * @param array  $fields     names of the form fields to keep
     * @param int    $tickets_id ticket the form belongs to, 0 on a creation form
     *
     * @return void
     */
    public static function remember(string $draft, array $values, array $fields, int $tickets_id): void
    {
        $kept = array_intersect_key($values, array_flip($fields));

        if (count($kept) === 0) {
            // Nothing worth restoring: leaving the previous draft in place would hand it to
            // the next form, which is exactly what this class exists to prevent.
            self::forget($draft);

            return;
        }

        $kept[self::TICKET] = $tickets_id;

        $_SESSION[$draft] = $kept;
    }

    /**
     * Give back the draft of a ticket, once, and drop it either way.
     *
     * @param string $draft      one of the class constants
     * @param int    $tickets_id ticket the form being displayed belongs to
     *
     * @return array the stored fields, empty when the draft belongs to another ticket
     */
    public static function restore(string $draft, int $tickets_id): array
    {
        $stored = $_SESSION[$draft] ?? null;

        self::forget($draft);

        if (!is_array($stored) || (int) ($stored[self::TICKET] ?? -1) !== $tickets_id) {
            return [];
        }

        unset($stored[self::TICKET]);

        return $stored;
    }

    /**
     * Drop a draft without reading it.
     *
     * @param string $draft one of the class constants
     *
     * @return void
     */
    public static function forget(string $draft): void
    {
        unset($_SESSION[$draft]);
    }
}
