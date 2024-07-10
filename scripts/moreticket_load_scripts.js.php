<?php
use Glpi\Event;
include('../../../inc/includes.php');
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
            dataType: "html",
            data: 'action=load',
            success: function (response, opts) {
               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts = scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
      }

      return this;
   };
}(jQuery));

$(document).moreticket_load_scripts();
