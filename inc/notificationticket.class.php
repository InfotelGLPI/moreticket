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
 * Class PluginMoreticketNotificationTicket
 */
class PluginMoreticketNotificationTicket extends CommonDBTM {

   static $types     = ['Ticket'];
   var    $dohistory = true;
   static $rightname = "plugin_moreticket";

   /**
    * @param \Ticket $ticket
    */
   static function afterAddTicket(Ticket $ticket) {
      $notification = new PluginMoreticketNotificationTicket();
      if (!$notification->getFromDBByCrit(['tickets_id' => $ticket->getID()])) {
         $notification->add(
            [
               'tickets_id'           => $ticket->getID(),
               'users_id_lastupdater' => $ticket->getField('users_id_lastupdater')
            ]);
      } else {
         $notification->update(
            [
               'id'                   => $notification->getID(),
               'tickets_id'           => $ticket->getID(),
               'users_id_lastupdater' => $ticket->getField('users_id_lastupdater')
            ]);
      }
   }

   /**
    * @param \Ticket $ticket
    */
   static function afterUpdateTicket(Ticket $ticket) {
      $notification = new PluginMoreticketNotificationTicket();
      if (!$notification->getFromDBByCrit(['tickets_id' => $ticket->getID()])) {
         $notification->add(
            [
               'tickets_id'           => $ticket->getID(),
               'users_id_lastupdater' => $ticket->getField('users_id_lastupdater')
            ]);
      } else {
         $notification->update(
            [
               'id'                   => $notification->getID(),
               'tickets_id'           => $ticket->getID(),
               'users_id_lastupdater' => $ticket->getField('users_id_lastupdater')
            ]);
      }
   }

   /**
    * @param \ITILFollowup $followup
    *
    * @return bool
    */
   static function afterAddFollowup(ITILFollowup $followup) {
      if (!$followup->getField('itemtype') == 'Ticket') {
         return false;
      }

      $notification = new PluginMoreticketNotificationTicket();
      $ticket       = new Ticket();
      $ticket->getFromDB($followup->getField('items_id'));
      if (!$notification->getFromDBByCrit(['tickets_id' => $ticket->getID()])) {
         $notification->add(
            [
               'tickets_id'           => $ticket->getID(),
               'users_id_lastupdater' => $ticket->getField('users_id_lastupdater')
            ]);
      } else {
         $notification->update(
            [
               'id'                   => $notification->getID(),
               'tickets_id'           => $ticket->getID(),
               'users_id_lastupdater' => $ticket->getField('users_id_lastupdater')
            ]);
      }
   }


   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'users_id_lastupdater':
            $res         = " ";
            $ticketUsers = new Ticket_User();
//            if ($values['users_id_lastupdater'] != Session::getLoginUserID()) {
//               if ($ticketUsers->getFromDBByCrit(['tickets_id' => $values['tickets_id'],
//                                                  'users_id' => $values['users_id_lastupdater'],
//                                                  'type' => Ticket_User::REQUESTER])) {
//                  if (!$ticketUsers->getFromDBByCrit(['tickets_id' => $values['tickets_id'],
//                                                      'users_id' => $values['users_id_lastupdater'],
//                                                      'type' => Ticket_User::ASSIGN])) {
                     $res = "<i class='itilstatus fas fa-bell waiting'></i>";
//                  }
//               }
//            }

            return $res;
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }
}
