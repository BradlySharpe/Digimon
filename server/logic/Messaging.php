<?php

  class Messaging {

    public static function error($message) {
      return json_encode(
        array(
          'error' => true,
          'message' => $message
        )
      );
    }

    public static function pong() {
      return json_encode(
        array(
          'error' => false,
          'event' => 'pong'
        )
      );
    }

    public static function request($event, $action, $data) {
      return json_encode(
        array(
          'error' => false,
          'event' => $event,
          'action' => $action."Request",
          'data' => $data
        )
      );
    }

    public static function response($event, $action, $data) {
      return json_encode(
        array(
          'error' => false,
          'event' => $event,
          'action' => $action."Response",
          'data' => $data
        )
      );
    }

  }

 ?>
