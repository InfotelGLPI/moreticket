<?php
/*
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
 * Class PluginMoreticketSolution
 */
class PluginMoreticketSolution extends CommonITILObject {

   static $rightname = "plugin_moreticket";

   function showFormSolution($tickets_id) {

      // validation des droits
      if (!$this->canView()) {
         return false;
      }


      echo '<tr class="tab_bg_2">';
      echo '<td>';
      echo __('Duration');
      echo '</td>';
      echo "<td><div id='duration_solution_" . $tickets_id . "'>";

      $toadd = [];
      for ($i=9; $i<=100; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("duration_solution", ['min'             => 0,
                                                    'max'             => 8 * HOUR_TIMESTAMP,
                                                    'addfirstminutes' => true,
                                                    'inhours'         => true,
                                                    'toadd'           => $toadd]);
      echo '</div></td>';
      echo '<td colspan="2"></td>';
      echo '</tr>';

   }

   /**
    * @param \Ticket $item
    *
    * @return bool
    */
   static function preUpdateSolution(Ticket $ticket) {
      global $CFG_GLPI;

      if (!is_array($ticket->input) || !count($ticket->input)) {
         // Already cancel by another plugin
         return false;
      }
      $config = new PluginMoreticketConfig();
      if ($config->useDurationSolution()) {
         if (isset($ticket->input['solution'])
             && (isset($ticket->input['duration_solution']) && $ticket->input['duration_solution'] > 0)) {

            if(!$CFG_GLPI['use_rich_text']) {
               $ticket->input['solution'] = html_entity_decode($ticket->input['solution']);
            }

            $tickettask = new TicketTask();
            $tickettask->add(['tickets_id'    => $ticket->getID(),
                              'date'          => date('Y-m-d H:i:s'),
                              'date_creation' => date('Y-m-d H:i:s'),
                              'users_id'      => Session::getLoginUserID(),
                              'users_id_tech' => Session::getLoginUserID(),
                              'content'       => $ticket->input['solution'],
                              'actiontime'    => $ticket->input['duration_solution']]);
         }
      }
   }

}