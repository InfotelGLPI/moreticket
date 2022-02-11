<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyDashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyDashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

class PluginMoreticketCrontask extends CommonDBTM
{

//   public static function getTypeName($nb = 0)
//   {
//      return __('More ticket', 'moreticket');
//   }
//
//   public static function MoreticketFollowup()
//   {
//      $ticket = new Ticket();
//      $tickets = $ticket->find(['status' => Ticket::WAITING , 'is_deleted' => "0"]);
//      $conf = new PluginMoreticketConfig();
//      $day = $conf->getField("day_sending");
//      $count = 0;
//      if (intval($day) > 0) {
//         foreach ($tickets as $t) {
//            $calendar = new Calendar();
//            $calendars_id = Entity::getUsedConfig('calendars_strategy', $t['entities_id'], 'calendars_id', 0);
//            if ($calendars_id > 0 && $calendar->getFromDB($calendars_id)) {
//               $cache_duration = $calendar->getDurationsCache();
//               $day_time = $cache_duration[1] * intval($day);
//               $enddate = $calendar->computeEndDate($t["begin_waiting_date"], $day_time);
//               $enddate = strtotime($enddate);
//            } else {
//               // cas 24/24 - 7/7
//               $day_time = DAY_TIMESTAMP * intval($day);
//               $begin_time = strtotime($t["begin_waiting_date"]);
//               $enddate = $begin_time + $day_time;
//            }
//            $today = strtotime(date('Y-m-d H:i:s'));
//            if ($enddate < $today && strtotime($t["date_mod"]) <= strtotime($t["begin_waiting_date"])) {
//               $followup = new TicketFollowup();
//               $input = [];
//               $input['tickets_id'] = $t['id'];
//               $input['_status'] = Ticket::WAITING;
//               $input['content'] = $conf->getField("followup_text");
//
//               $followup->add($input);
//               $count++;
//            }
//         }
//      }
//      return $count;
//   }
//
//
//   public static function MoreticketClosing()
//   {
//      $ticket = new Ticket();
//      $tickets = $ticket->find(['status' => Ticket::WAITING , 'is_deleted' => "0"]);
//      $conf = new PluginMoreticketConfig();
//      $day = $conf->getField("day_sending");
//      $dayClose = $conf->getField("day_closing");
//      $count = 0;
//      if ($dayClose > 0) {
//         foreach ($tickets as $t) {
//            $calendar = new Calendar();
//            $calendars_id = Entity::getUsedConfig('calendars_strategy', $t['entities_id'], 'calendars_id', 0);
//
//            if ($calendars_id > 0 && $calendar->getFromDB($calendars_id)) {
//               $cache_duration = $calendar->getDurationsCache();
//               $day_time = $cache_duration[1] * intval($day) + $cache_duration[1] * $dayClose;
//               $enddate = $calendar->computeEndDate($t["begin_waiting_date"], $day_time);
//               $enddate = strtotime($enddate);
//            } else {
//               // cas 24/24 - 7/7
//               $enddate = strtotime($t["begin_waiting_date"]) + (DAY_TIMESTAMP * $day) + (DAY_TIMESTAMP * $dayClose);
//            }
//
//            $today = strtotime(date('Y-m-d H:i:s'));
//            $problemTicket = new Problem_Ticket();
//            if ($enddate < $today && strtotime($t["date_mod"]) > strtotime($t["begin_waiting_date"])
//               && (strtotime($t["date_mod"]) + (DAY_TIMESTAMP * $dayClose)) < $today
//               && ($conf->getField('closing_with_problem') == 1
//                  || ($conf->getField('closing_with_problem') == 0
//                     && count($problemTicket->find(['tickets_id' => $t['id']])) > 0))) {
//               $input = [];
//               $input['id'] = $t['id'];
//               $input['status'] = Ticket::CLOSED;
//               $input['notifSatisfaction'] = false;
//
//               $ticket->update($input);
//               $count++;
//            }
//         }
//      }
//      return $count;
//
//   }
//
//   /**
//    * @param $name
//    *
//    * @return array
//    */
//   static function cronInfo($name)
//   {
//
//      switch ($name) {
//         case 'MoreticketFollowup':
//            return [
//               'description' => __('Moreticket - Send a followup to waiting ticket', 'moreticket')];   // Optional
//            break;
//         case 'MoreticketClosing':
//            return [
//               'description' => __('Moreticket - Closed the tickets that did not respond to the follow-up', 'moreticket')];   // Optional
//            break;
//      }
//      return [];
//   }
//
//   /**
//    *
//    * @param $task for log, if NULL display
//    *
//    *
//    * @return int
//    */
//   static function cronMoreticketClosing($task = null)
//   {
//      global $DB, $CFG_GLPI;
//
//      $result = self::MoreticketClosing();
//      $task->addVolume($result);
//      return $result;
//   }
//
//   static function cronMoreticketFollowup($task = null)
//   {
//      global $DB, $CFG_GLPI;
//
//      $result = self::MoreticketFollowup();
//      $task->addVolume($result);
//      return $result;
//   }
}
