<?php

namespace tests\units;

include_once __DIR__ . "/../../inc/profile.class.php";

use DbTestCase;

class PluginMoreticketProfile extends DbTestCase
{
   private $class;

   public function test_getTabNameForItem(){

      // Test with instance of CommonGLPI with empty name
      $temp = new \CommonGLPI();

      $this->newTestedInstance();
      $this->string($this->testedInstance->getTabNameForItem($temp))
         ->isEqualTo("");

      // Test with instance of Profile with name equals to class name
      $temp2 = new \Profile();

      $this->newTestedInstance();
      $this->string($this->testedInstance->getTabNameForItem($temp2))
          // Don't test equality because the tab name can be translated
          ->isNotEmpty();
   }

   public function test_displayTabContentForItem(){

   }

   public function test_createFirstAccess(){

   }

   public function test_addDefaultProfileInfos(){

   }

   /**
    *
    * @dataProvider haveUserRightProvider
    */
   public function test_showForm(){

      global $DB;

      $this->login('glpi', 'glpi');

      $res = $DB->insert(
          "glpi_profilerights", [
              'profiles_id'  => 0,
              'name'         => "plugin_moreticket_justification"
          ]
      );

      $res = $DB->insert(
          "glpi_profilerights", [
              'profiles_id'  => 0,
              'name'         => "plugin_moreticket_hide_task_duration"
          ]
      );

      // intercept the text printed with echo
      ob_start();

      $this->newTestedInstance();
      $this->variable($this->testedInstance->showForm(0));

      // store the text in variable
      $temp = ob_get_contents();

      // Stop interception
      ob_end_clean();

      // Find moreticket justification div
      $this->string($temp)->contains("_plugin_moreticket_justification");

      // Find moreticket hide_task_duration div
      $this->string($temp)->contains("_plugin_moreticket_hide_task_duration");
   }

   public function test_getAllRights(){
      $this->newTestedInstance();
      $this->array($this->testedInstance->getAllRights(true))
          ->hasSize(2);
      $this->array($this->testedInstance->getAllRights(false))
          ->hasSize(1);
   }

   public function test_translateARight(){
      $this->newTestedInstance();
      $this->integer($this->testedInstance->translateARight(''))->isEqualTo(0);
      $this->integer($this->testedInstance->translateARight('r'))->isEqualTo(READ);
      $this->integer($this->testedInstance->translateARight('w'))->isEqualTo(ALLSTANDARDRIGHT + READNOTE + UPDATENOTE);
      $this->string($this->testedInstance->translateARight('0'))->isEqualTo('0');
      $this->string($this->testedInstance->translateARight('1'))->isEqualTo('1');
      $this->integer($this->testedInstance->translateARight('FAKE'))->isEqualTo(0);
   }

   /**
    * @see self::testHaveUserRight()
    *
    * @return array
    */
   protected function haveUserRightProvider() {

      return [
          [
              'user'      => [
                  'login'    => 'post-only',
                  'password' => 'postonly',
              ],
              'rights' => [
                  ['name' => \Computer::$rightname, 'value' => CREATE, 'expected' => false],
                  ['name' => \Computer::$rightname, 'value' => DELETE, 'expected' => false],
                  ['name' => \Ticket::$rightname, 'value' => CREATE, 'expected' => true],
                  ['name' => \Ticket::$rightname, 'value' => DELETE, 'expected' => false],
                  ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMYTICKET, 'expected' => true],
                  ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLTICKET, 'expected' => false],
              ],
          ]
      ];
   }

}