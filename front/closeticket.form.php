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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Moreticket\CloseTicket;

$closeTicket = new CloseTicket();

if (isset($_POST["add"])) {
    $closeTicket->check(-1, CREATE, $_POST);

    // Entity/ticket access control: the closing record and its uploaded document
    // must only be attached to a ticket the caller is allowed to update.
    $ticket = new Ticket();
    if (!$ticket->can((int) ($_POST['tickets_id'] ?? 0), UPDATE)) {
        throw new AccessDeniedHttpException();
    }

    // Document::add() builds the Document_Item relation from items_id/itemtype, which are
    // independent, forgeable POST fields (hidden inputs). Pin them to the ticket we just
    // authorized so the upload can only attach to that ticket — otherwise a forged
    // items_id/itemtype would attach the document to an arbitrary (cross-entity) ticket.
    $_POST['items_id'] = (int) $_POST['tickets_id'];
    $_POST['itemtype'] = Ticket::class;

    $doc = new Document();
    $DocId = $doc->add($_POST);

    // requesters_id is not trusted from the POST: CloseTicket::prepareInputForAdd() forces it
    // to the current user, so it is intentionally not forwarded here.
    $closeTicket->add(['tickets_id'    => $_POST['tickets_id'],
        'date'          => $_POST['date'],
        'comment'       => $_POST['comment'],
        'documents_id'  => $DocId]);
    Html::back();
}
