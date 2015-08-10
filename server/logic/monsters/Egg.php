<?php

  require ('Monster.php');

  class Egg extends Monster {

    public function __construct() {
      parent::__construct();
      $this->stages = [0,1,2,3,4,5];
    }



  }

?>
