<?php

  class Monster {
    protected $stages = [];
    private $refreshRate = 1; //seconds
    private $currentState;
    private $lastDraw;
    private $lastIndex = 0;

    public function __construct() {
    }

    public function getStages() {
      return $this->stages;
    }

    public function getNextState() {
      if (empty($this->lastDraw) || (($this->lastDraw + $this->refreshRate) <= time())) {
        $this->lastDraw = time();
        $this->lastIndex = ($this->lastIndex == count($this->stages)-1) ? 0 : $this->lastIndex+1;

        $this->currentState = array(
          'xoffset' => $this->stages[$this->lastIndex]['xoffset'],
          'yoffset' => $this->stages[$this->lastIndex]['yoffset'],
          'monster' => $this->stages[$this->lastIndex]['sprite']
        );
      }
      return $this->currentState;
    }
  }
?>
