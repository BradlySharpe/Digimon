<?php


    class Singleton
    {
        private static $instances = array();
        protected function __construct() {}
        protected function __clone() {}
        public function __wakeup()
        {
            throw new Exception("Cannot unserialize singleton");
        }

        public static function getInstance()
        {
            $cls = get_called_class(); // late-static-bound class name
            if (!isset(self::$instances[$cls])) {
                self::$instances[$cls] = new static;
            }
            return self::$instances[$cls];
        }
    }

    class Helper extends Singleton
    {
        protected function __construct()
        {

        }

        public static function createEmptyArray($height = 16, $width = 32) {
          $ar = [];
          for ($i=0; $i < $height; $i++) {
            $ar[$i] = [];
            for ($j=0; $j < $width; $j++) {
              $ar[$i][$j] = 0;
            }
          }
          return $ar;
        }
    }
