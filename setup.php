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

// Init the hooks of the plugins -Needed
function plugin_init_moreticket() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['add_css']['moreticket']        = 'moreticket.css';
   $PLUGIN_HOOKS['csrf_compliant']['moreticket'] = true;
   $PLUGIN_HOOKS['change_profile']['moreticket'] = ['PluginMoreticketProfile', 'initProfile'];

   if (Session::getLoginUserID()) {
      Plugin::registerClass('PluginMoreticketProfile', ['addtabon' => 'Profile']);

      if (class_exists('PluginMoreticketProfile')) { // only if plugin activated
         $config = new PluginMoreticketConfig();

         $PLUGIN_HOOKS['add_javascript']['moreticket'] = ["scripts/moreticket.js"];

         if (Session::haveRight("plugin_moreticket", UPDATE) || Session::haveRight("plugin_moreticket_justification", READ)) {
            if (strpos($_SERVER['REQUEST_URI'], "ticket.form.php") !== false
                || strpos($_SERVER['REQUEST_URI'], "helpdesk.public.php") !== false
                || strpos($_SERVER['REQUEST_URI'], "tracking.injector.php") !== false
                   && ($config->useWaiting() == true || $config->useSolution() == true
                       || $config->useUrgency() == true || $config->useDurationSolution() == true)) {
                  $PLUGIN_HOOKS['add_javascript']['moreticket'][] = 'scripts/moreticket_load_scripts.js';
            }

            $PLUGIN_HOOKS['config_page']['moreticket'] = 'front/config.form.php';

            $PLUGIN_HOOKS['pre_item_update']['moreticket'] = ['TicketTask'     => ['PluginMoreticketTicketTask', 'beforeUpdate'],
                                                                   'TicketFollowup' => ['PluginMoreticketTicketFollowup', 'beforeUpdate']];
            $PLUGIN_HOOKS['pre_item_add']['moreticket']    = ['TicketTask'     => ['PluginMoreticketTicketTask', 'beforeAdd'],
                                                                   'ITILSolution' => ['PluginMoreticketSolution', 'preUpdateSolution'],
                                                                   'TicketFollowup' => ['PluginMoreticketTicketFollowup', 'beforeAdd']];

            $PLUGIN_HOOKS['item_empty']['moreticket'] = ['Ticket' => ['PluginMoreticketTicket', 'emptyTicket']];
         }

         if (Session::haveRight("plugin_moreticket_justification", READ) || Session::haveRight("plugin_moreticket", UPDATE)) {
            //allows to avoid conflicts with behaviors for adding the solution with a duration
            // and the parameter "Mandatory duration to solve/close a ticket" of the behaviors plugin
            $PLUGIN_HOOKS['pre_item_update'] = ['moreticket' => []] + $PLUGIN_HOOKS['pre_item_update'];

            $PLUGIN_HOOKS['pre_item_update']['moreticket']['Ticket'] = ['PluginMoreticketTicket', 'beforeUpdate'];
            $PLUGIN_HOOKS['pre_item_add']['moreticket']['Ticket']    = ['PluginMoreticketTicket', 'beforeAdd'];
            $PLUGIN_HOOKS['item_add']['moreticket']['Ticket']        = ['PluginMoreticketTicket', 'afterAdd'];
            $PLUGIN_HOOKS['item_update']['moreticket']['Ticket']     = ['PluginMoreticketTicket', 'afterUpdate'];
         }

         if (Session::haveRight('plugin_moreticket', READ)) {
            Plugin::registerClass('PluginMoreticketWaitingTicket', ['addtabon' => 'Ticket']);
            Plugin::registerClass('PluginMoreticketCloseTicket', ['addtabon' => 'Ticket']);
         }
      }
   }
}

// Get the name and the version of the plugin - Needed
/**
 * @return array
 */
function plugin_version_moreticket() {

   return [
      'name'           => __('More ticket', 'moreticket'),
      'version'        => "1.4.1",
      'author'         => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>",
      'homepage'       => "https://github.com/InfotelGLPI/moreticket",
      'license'        => 'GPLv2+',
      'minGlpiVersion' => "9.3"
   ];
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
/**
 * @return bool
 */
function plugin_moreticket_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.3', 'lt') || version_compare(GLPI_VERSION, '9.4', 'ge')) {
      echo __('This plugin requires GLPI >= 9.3');
      return false;
   }
   return true;
}

// Check configuration process for plugin : need to return true if succeeded
// Can display a message only if failure and $verbose is true
/**
 * @return bool
 */
function plugin_moreticket_check_config() {
   return true;
}
