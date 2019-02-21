<?php

namespace tests\units;

include_once __DIR__ . "/../../inc/profile.class.php";

use DbTestCase;

class PluginMoreticketProfile extends DbTestCase
{
   private $class;

   public function test_getAllRights(){
      $this->newTestedInstance();
      $this->array($this->testedInstance->getAllRights(true))
         ->hasSize(2);
      $this->array($this->testedInstance->getAllRights(false))
          ->hasSize(1);
   }
}