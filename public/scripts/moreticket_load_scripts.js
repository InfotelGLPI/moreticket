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

// Plugin web root, mirroring PLUGIN_MORETICKET_WEBDIR from setup.php. GLPI exposes
// both variables in the page <head> (config_js) before any plugin script is
// loaded, so no server-side interpolation is needed here.
var root_moreticket_doc = ((window.CFG_GLPI && CFG_GLPI.root_doc) || '')
   + ((window.GLPI_PLUGINS_PATH && GLPI_PLUGINS_PATH.moreticket) || '/plugins/moreticket');

(function ($) {
   $.fn.moreticket_load_scripts = function () {

      init();

      // Start the plugin
      function init() {

         // Send data
         $.ajax({
            url: root_moreticket_doc + '/ajax/loadscripts.php',
            type: "POST",
            dataType: "json",
            data: 'action=load',
            success: function (data) {
               var moreticket = $(document).moreticket(data.params);
               if (data.inject_waiting) { moreticket.moreticket_injectWaitingTicket(); }
               if (data.inject_urgency) { moreticket.moreticket_urgency(); }
            }
         });
      }

      return this;
   };
}(jQuery));

$(document).ready(function() {
    setTimeout(function() {
        $(document).moreticket_load_scripts();
    }, 1000);
});
