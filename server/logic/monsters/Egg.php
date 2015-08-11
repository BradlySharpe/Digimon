<?php
  class Egg extends Monster {

    private $tall = ['width' => 11, 'height' => 12, 'sprite' => []];
    private $small = ['width' => 14, 'height' => 10, 'sprite' => []];

    public function __construct() {
      parent::__construct();
      $this->createStages();
    }

    private function createStages() {
      //$this->tall->sprite = $this->createEmptyArray($this->tall->height, $this->tall->width);
      $Helper = Helper::getInstance();
      $this->tall['sprite'] = $Helper::createEmptyArray($this->tall['height'], $this->tall['width']);
      $_tall = [
        [0,3],[0,4],[0,5],[0,6],[0,7],
        [1,2],[1,3],[1,6],[1,7],[1,8],
        [2,1],[2,2],[2,7],[2,8],[2,9],
        [3,0],[3,4],[3,5],[3,8],[3,10],
        [4,0],[4,3],[4,4],[4,5],[4,10],
        [5,0],[5,3],[5,4],[5,10],
        [6,0],[6,3],[6,8],[6,9],[6,10],
        [7,1],[7,7],[7,8],[7,9],
        [8,1],[8,2],[8,3],[8,6],[8,7],[8,8],[8,9],
        [9,2],[9,3],[9,4],[9,6],[9,7],[9,8],
        [10,3],[10,4],[10,7],
        [11,4],[11,5],[11,6]
      ];
      foreach ($_tall as $key => $val) {
        $this->tall['sprite'][$val[0]][$val[1]] = 1;
      }

      $this->small['sprite'] = $Helper::createEmptyArray($this->small['height'], $this->small['width']);
      $_small = [
        [0,2],[0,3],[0,4],[0,5],[0,6],[0,7],[0,8],[0,9],[0,10],[0,11],
        [1,1],[1,5],[1,6],[1,9],[1,10],[1,11],[1,12],
        [2,0],[2,4],[2,5],[2,6],[2,10],[2,11],[2,13],
        [3,0],[3,4],[3,5],[3,13],
        [4,0],[4,4],[4,10],[4,11],[4,12],[4,13],
        [5,0],[5,9],[5,10],[5,11],[5,12],[5,13],
        [6,1],[6,2],[6,3],[6,4],[6,9],[6,10],[6,12],
        [7,2],[7,3],[7,4],[7,11],
        [8,3],[8,4],[8,9],[8,10],
        [9,5],[9,6],[9,7],[9,8]
      ];
      foreach ($_small as $key => $val) {
        $this->small['sprite'][$val[0]][$val[1]] = 1;
      }

      $this->stages = [
        [
          'xoffset' => 10,
          'yoffset' => 0,
          'sprite' => $this->tall
        ],
        [
          'xoffset' => 8,
          'yoffset' => 0,
          'sprite' => $this->small
        ]
      ];
    }
  }

?>
