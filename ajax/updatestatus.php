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

Html::header_nocache();
Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");

if (isset($_POST['question'])) {
    if ($_POST['question'] == 1) {
        echo Html::scriptBlock("
      var allInput = document.getElementsByName(\"_status\");

      var pending = " . CommonITILObject::WAITING . ";
      allInput.forEach(function(element){

      var event = new Event('change');
      if(element.value == pending){
         element.checked = true;
         element.dispatchEvent(event);

         var idd = element.id;
         var chosen_li = $('#'+idd).parent();

         var xBtnDrop = chosen_li.parent().siblings(\".x-button-drop\");
         //clean old status class
         xBtnDrop.attr('class','x-button x-button-drop');

         //find status
         var cstatus = chosen_li.data('status');

         //add status to dropdown button
         xBtnDrop.addClass(cstatus);
      }
      });

      ");
    } else {
        if ($_POST['status'] == "") {
            echo Html::scriptBlock("
            var allInput = document.getElementsByName(\"_status\");

            var newStatus = " . CommonITILObject::INCOMING . ";
            allInput.forEach(function(element){

            var event = new Event('change');
            if(element.value == newStatus){
               element.checked = true;
               element.dispatchEvent(event);
                var idd = element.id;
                  var chosen_li = $('#'+idd).parent();

                  var xBtnDrop = chosen_li.parent().siblings(\".x-button-drop\");
                  //clean old status class
                  xBtnDrop.attr('class','x-button x-button-drop');

                  //find status
                  var cstatus = chosen_li.data('status');

                  //add status to dropdown button
                  xBtnDrop.addClass(cstatus);
            }
            });
      ");
        } else {
            echo Html::scriptBlock("
            var allInput = document.getElementsByName(\"_status\");

            allInput.forEach(function(element){

            var event = new Event('change');
            if(element.value == " . (int) $_POST['status'] . "){
               element.checked = true;
               element.dispatchEvent(event);

                var idd = element.id;
               var chosen_li = $('#'+idd).parent();

               var xBtnDrop = chosen_li.parent().siblings(\".x-button-drop\");
               //clean old status class
               xBtnDrop.attr('class','x-button x-button-drop');

               //find status
               var cstatus = chosen_li.data('status');

               //add status to dropdown button
               xBtnDrop.addClass(cstatus);
            }
            });
      ");
        }
    }


}
