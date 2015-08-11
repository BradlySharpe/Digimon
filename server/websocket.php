<?php
  require ('vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
  require ('game.php');
  use Ratchet\Server\IoServer;
  use Ratchet\Http\HttpServer;
  use Ratchet\WebSocket\WsServer;
  use Ratchet\MessageComponentInterface;
  use Ratchet\ConnectionInterface;

  class WebSocket implements MessageComponentInterface{
    protected $clients = [];
    public static $port = 8080;

    /**
     * __construct constructor
     */
    public function __construct() {}

    /**
     * onOpen opens a new connection for a client and sets up the game
     * @param  ConnectionInterface $con connection object
     */
    public function onOpen(ConnectionInterface $con) {
      // Store the new connection to send messages to later
      //$this->clients->attach($con);
      //echo "New connection! ({$con->resourceId})\n";
      $this->clients[$con->resourceId] = array(
        'con' => $con,
        'game' => new Game($con)
      );
    }

    /**
     * onMessage handles messages from clients
     * @param  ConnectionInterface $from   client message was received from
     * @param  Mixed              $message message that was received
     */
    public function onMessage(ConnectionInterface $from, $message) {
      //echo sprintf('Received message from: %d - %s' . "\n", $from->resourceId, $message);
      $game = $this->_getGame($from);
      $game->handleMessage($message);
    }

    /**
     * onClose closes the connection to the client and ends the game
     * @param  ConnectionInterface $con connection to close
     */
    public function onClose(ConnectionInterface $con) {
      // The connection is closed, remove it, as we can no longer send it messages
      $this->closeGame($con);
      //echo "Connection {$con->resourceId} has disconnected\n";
    }

    /**
     * onError handles errors
     * @param  ConnectionInterface $con connection that caused the error
     * @param  Exception           $ex  exception thrown
     */
    public function onError(ConnectionInterface $con, \Exception $ex) {
      //echo "An error has occurred: {$ex->getMessage()}\n";
      $this->closeGame($con);
      $con->close();
    }

    /**
     * closeGame gracefully close the game
     * @param  [type] $con connection to close
     */
    private function closeGame($con) {
      $game = $this->_getGame($con);
      if ($game)
        $game->close();
      unset($this->clients[$con->resourceId]);
    }

    /**
     * _getGame returns the game object for the connection
     * @param  ConnectionInterface $con connection object
     * @return Game                     game object for connection
     */
    private function _getGame($con) {
      if (array_key_exists($con->resourceId, $this->clients))
        return $this->clients[$con->resourceId]['game'];
      return;
    }
  }

  /**
   * $webSocketServer WebSocket Server to handle connections for the game
   * @var WsServer
   */
  $webSocketServer = new WsServer(
    new WebSocket()
  );
  $webSocketServer->disableVersion(0);
  $webSocketServer->setEncodingChecks(!1);

  /**
   * $server Server to listen for connections
   * @var IoServer
   */
  $server = IoServer::factory(
    new HttpServer($webSocketServer),
    WebSocket::$port
  );

  /**
   * Start Server
   */
  echo "Server starting on port: " . WebSocket::$port . "\n";
  $server->run();
  ?>
