<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 moreticket plugin for GLPI
 Copyright (C) 2013-2016 by the moreticket Development Team.

 https://github.com/InfotelGLPI/moreticket
 -------------------------------------------------------------------------

 LICENSE

 This file is part of moreticket.

 moreticket is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 moreticket is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Moreticket;

use CommonITILActor;
use CommonITILTask;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Class TicketTask
 */
class TicketTask extends CommonITILTask
{
    public static $rightname = "plugin_moreticket";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return _n('Ticket', 'Tickets', $nb);
    }

    /**
     * @param TicketTask $tickettask
     *
     * @return bool
     */
    public static function beforeAdd(\TicketTask $tickettask)
    {

        if (!is_array($tickettask->input) || !count($tickettask->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = new Config();

        if (isset($tickettask->input['pending'])
          && $tickettask->input['pending']
          && $config->useWaiting() == true) {
            WaitingTicket::addWaitingTicket($tickettask);
        }
    }

    public static function afterAddTask(\TicketTask $task)
    {
        global $DB;
        $config = new Config();
        if ($config->fields['update_after_tech_add_task']) {
            $ticket = new \Ticket();
            $user = new User();
            $user->getFromDB($task->fields['users_id']);
            $condition = [
                'tickets_id' => $task->fields['tickets_id'],
                'users_id' => $task->fields['users_id'],
                'type' => CommonITILActor::ASSIGN
            ];
            $ticket->getFromDB($task->fields['tickets_id']);
            if (countElementsInTable('glpi_tickets_users', $condition) > 0 &&
                in_array($ticket->fields['status'], \Ticket::getProcessStatusArray())) {
                $DB->update(
                    \Ticket::getTable(),
                    [
                        'status' => \Ticket::WAITING
                    ],
                    [
                        'id' => $ticket->getID()
                    ]
                );
            }
        }
    }
}
