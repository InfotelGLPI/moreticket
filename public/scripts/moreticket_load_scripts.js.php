<?php

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
