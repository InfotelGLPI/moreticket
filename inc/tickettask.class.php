<?php
/*
  -------------------------------------------------------------------------
  Moreticket plugin for GLPI
  Copyright (C) 2013 by the Moreticket Development Team.
  -------------------------------------------------------------------------

  LICENSE

  This file is part of Moreticket.

  Moreticket is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  Moreticket is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Moreticket. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


class PluginMoreticketTicketTask extends CommonITILTask {
   
   static $rightname = "plugin_moreticket";
   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    * */
   public static function getTypeName($nb=0) {

      return _n('Ticket','Tickets',$nb);
   }
   
   static function emptyTicket(TicketTask $tickettask) {
      if (!empty($_POST)) {
         self::setSessions($_POST);
      }
   }

   static function beforeAdd(TicketTask $tickettask) {

      if (!is_array($tickettask->input) || !count($tickettask->input)) {
         // Already cancel by another plugin
         return false;
      }

      self::setSessions($tickettask->input);
      unset($tickettask->input['_status']);
   }

   static function afterAdd(TicketTask $tickettask) {

      if (!is_array($tickettask->input) || !count($tickettask->input)) {
         // Already cancel by another plugin
         return false;
      }
      
      $updates['id']                                = $tickettask->input['tickets_id'];
      $updates['reason']                            = $tickettask->input['reason'];
      $updates['date_report']                       = $tickettask->input['date_report'];
      $updates['plugin_moreticket_waitingtypes_id'] = $tickettask->input['plugin_moreticket_waitingtypes_id'];
      $updates['status']                            = $_SESSION['glpi_plugin_moreticket_waiting']['_status'];
      $ticket                 = new Ticket();
      $ticket->update($updates);
   }
   
   static function beforeUpdate(TicketTask $tickettask) {
      
      if (!is_array($tickettask->input) || !count($tickettask->input)) {
         // Already cancel by another plugin
         return false;
      }
      
      self::setSessions($tickettask->input);
      unset($tickettask->input['_status']);
   }
   
   static function afterUpdate(TicketTask $tickettask) {
            
      if (!is_array($tickettask->input) || !count($tickettask->input)) {
         // Already cancel by another plugin
         return false;
      }
      
      $updates['id']                                = $tickettask->input['tickets_id'];
      $updates['reason']                            = $tickettask->input['reason'];
      $updates['date_report']                       = $tickettask->input['date_report'];
      $updates['plugin_moreticket_waitingtypes_id'] = $tickettask->input['plugin_moreticket_waitingtypes_id'];
      $updates['status']                            = $_SESSION['glpi_plugin_moreticket_waiting']['_status'];
      $ticket                 = new Ticket();
      $ticket->update($updates);
   }
   
   static function setSessions($input){
      
      foreach($input as $key => $values){
         switch($key){
            case '_status':
               $_SESSION['glpi_plugin_moreticket_waiting'][$key]   = $values;
               break;
    
         }
      }
   }
}

?>