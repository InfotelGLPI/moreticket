<?php

/*
 -------------------------------------------------------------------------
 moreticket plugin for GLPI
 Copyright (C) 2015-2026 by the moreticket Development Team.

 https://github.com/InfotelGLPI/moreticket
 -------------------------------------------------------------------------

 LICENSE

 This file is part of moreticket.

 moreticket is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 moreticket is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

header('Content-Type: text/javascript');

?>

var root_moreticket_doc = "<?php echo PLUGIN_MORETICKET_WEBDIR; ?>";
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
