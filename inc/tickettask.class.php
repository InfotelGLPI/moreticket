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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * Class PluginMoreticketTicketTask
 */
class PluginMoreticketTicketTask extends CommonITILTask {

   static $rightname = "plugin_moreticket";

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string|translated
    */
   public static function getTypeName($nb = 0) {

      return _n('Ticket', 'Tickets', $nb);
   }

   /**
    * @param TicketTask $tickettask
    *
    * @return bool
    */
   static function beforeAdd(TicketTask $tickettask) {

      if (!is_array($tickettask->input) || !count($tickettask->input)) {
         // Already cancel by another plugin
         return false;
      }

      $config = new PluginMoreticketConfig();

      if (isset($tickettask->input['pending'])
          && $config->useWaiting() == true) {

          $waiting_ticket = new PluginMoreticketWaitingTicket();
          if (PluginMoreticketWaitingTicket::checkMandatory($tickettask->input)) {
              if (isset($tickettask->input['date_report'])
                  && ($tickettask->input['date_report'] == "0000-00-00 00:00:00"
                      || empty($tickettask->input['date_report']))) {
                  $tickettask->input['date_report'] = 'NULL';
              }

              $status = (in_array($tickettask->input['_job']->fields['status'],
                                  [CommonITILObject::SOLVED, CommonITILObject::CLOSED]))
                  ? CommonITILObject::ASSIGNED : $tickettask->input['_job']->fields['status'];

              // Then we add tickets informations
              $input = ['reason'                            => (isset($tickettask->input['reason'])) ? $tickettask->input['reason'] : "",
                        'tickets_id'                        => $tickettask->input['tickets_id'],
                        'date_report'                       => (isset($tickettask->input['date_report'])) ? $tickettask->input['date_report'] : "NULL",
                        'date_suspension'                   => date("Y-m-d H:i:s"),
                        'date_end_suspension'               => 'NULL',
                        'status'                            => $status,
                        'plugin_moreticket_waitingtypes_id' => (isset($item->input['plugin_moreticket_waitingtypes_id'])) ?? 0];
              if ($waiting_ticket->add($input)) {
                  unset($_SESSION['glpi_plugin_moreticket_waiting']);
              }
          }
      }
   }
}
