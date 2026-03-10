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

use GlpiPlugin\Moreticket\CloseTicket;
use GlpiPlugin\Moreticket\Config;
use GlpiPlugin\Moreticket\UrgencyTicket;
use GlpiPlugin\Moreticket\WaitingTicket;

Html::header_nocache();
Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");

if (!isset($_POST['tickets_id']) || empty($_POST['tickets_id'])) {
    $_POST['tickets_id'] = 0;
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'showForm':
            $config = new Config();

            // Ticket is waiting
            if ($config->useWaiting()) {
                $waiting_ticket = new WaitingTicket();
                $waiting_ticket->showForm($_POST['tickets_id']);
            }

            // Ticket is closed
            if ($config->useSolution()) {
                if (isset($_POST['type']) && $_POST['type'] == 'add') {
                    $close_ticket = new CloseTicket();
                    $close_ticket->showForm($_POST['tickets_id']);
                }
            }
            break;
        case 'showFormUrgency':
            $config = new Config();
            if ($config->useUrgency()) {
                $urgency_ticket = new UrgencyTicket();
                $urgency_ticket->showForm($_POST['tickets_id']);
            }

            break;

        //      case 'showFormSolution':
        //         $config = new Config();
        //
        //         if ($config->useDurationSolution()) {
        //            $solution = new Solution();
        //            $solution->showFormSolution($_POST['tickets_id']);
        //         }
        //         break;
    }
}
