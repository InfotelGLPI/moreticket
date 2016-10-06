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


class PluginMoreticketTicket extends CommonITILObject {
   
   static $rightname = "plugin_moreticket";
   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    * */
   public static function getTypeName($nb=0) {

      return _n('Ticket','Tickets',$nb);
   }
   
   static function emptyTicket(Ticket $ticket) {
      if (!empty($_POST)) {
         self::setSessions($_POST);
      }else if (!empty($_REQUEST)) {
         self::setSessions($_REQUEST);
      }
   }

   static function beforeAdd(Ticket $ticket) {

      if (!is_array($ticket->input) || !count($ticket->input)) {
         // Already cancel by another plugin
         return false;
      }
      PluginMoreticketWaitingTicket::preAddWaitingTicket($ticket);
      PluginMoreticketCloseTicket::preAddCloseTicket($ticket);
   }
   
   static function beforeAddUrgency(Ticket $ticket) {

      if (!is_array($ticket->input) || !count($ticket->input)) {
         // Already cancel by another plugin
         return false;
      }
      PluginMoreticketUrgencyTicket::preAddUrgencyTicket($ticket);
   }
   
   static function afterAdd(Ticket $ticket) {

      if (!is_array($ticket->input) || !count($ticket->input)) {
         // Already cancel by another plugin
         return false;
      }
      PluginMoreticketWaitingTicket::postAddWaitingTicket($ticket);
      PluginMoreticketUrgencyTicket::postAddUrgencyTicket($ticket);
      
            
      if (isset($_SESSION['glpi_plugin_moreticket_close'])){
         unset($_SESSION['glpi_plugin_moreticket_close']);
      }

      if (isset($_SESSION['glpi_plugin_moreticket_waiting'])){
         unset($_SESSION['glpi_plugin_moreticket_waiting']);
      }
      
      if (isset($_SESSION['glpi_plugin_moreticket_urgency'])){
         unset($_SESSION['glpi_plugin_moreticket_urgency']);
      }
   }
   
   static function beforeUpdate(Ticket $ticket) {
      
      if (!is_array($ticket->input) || !count($ticket->input)) {
         // Already cancel by another plugin
         return false;
      }

      PluginMoreticketWaitingTicket::preUpdateWaitingTicket($ticket);

   }
   
   static function beforeUpdateUrgency(Ticket $ticket) {
      
      if (!is_array($ticket->input) || !count($ticket->input)) {
         // Already cancel by another plugin
         return false;
      }

      PluginMoreticketUrgencyTicket::preUpdateUrgencyTicket($ticket);

   }
   
   static function afterUpdate(Ticket $ticket) {
      
      PluginMoreticketWaitingTicket::postUpdateWaitingTicket($ticket);
      PluginMoreticketUrgencyTicket::postUpdateUrgencyTicket($ticket);
      
            
      if (isset($_SESSION['glpi_plugin_moreticket_close'])){
         unset($_SESSION['glpi_plugin_moreticket_close']);
      }

      if (isset($_SESSION['glpi_plugin_moreticket_waiting'])){
         unset($_SESSION['glpi_plugin_moreticket_waiting']);
      }
   }
   
   static function setSessions($input){
      
      foreach($input as $key => $values){
         switch($key){
            case 'reason':
               $_SESSION['glpi_plugin_moreticket_waiting'][$key]   = $values;
               break;
            case 'plugin_moreticket_waitingtypes_id':
               $_SESSION['glpi_plugin_moreticket_waiting'][$key]  = $values;
               break;
            case 'date_report':
               $_SESSION['glpi_plugin_moreticket_waiting'][$key] = $values;
               break;
            case 'solutiontemplates_id':
               $_SESSION['glpi_plugin_moreticket_close'][$key] = $values;
               break;
            case 'solutiontypes_id':
               $_SESSION['glpi_plugin_moreticket_close'][$key] = $values;
               break;
            case 'solution':
               $_SESSION['glpi_plugin_moreticket_close'][$key] = $values;
               break;
            case 'justification':
               $_SESSION['glpi_plugin_moreticket_urgency'][$key] = $values;
               break;
         }
      }
   }
}

?>