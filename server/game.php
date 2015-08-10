<?php


  define("BASE", dirname(__FILE__) . DIRECTORY_SEPARATOR);
  define("LOGIC_BASE", BASE . 'logic' . DIRECTORY_SEPARATOR);
  define("MONSTER_BASE", LOGIC_BASE . 'monsters' . DIRECTORY_SEPARATOR);

  echo BASE."\n" . LOGIC_BASE."\n" . MONSTER_BASE."\n";

  require (LOGIC_BASE . 'Messaging.php');
  require (LOGIC_BASE . 'DBase.php');
  require (LOGIC_BASE . 'Helper.php');
  require (MONSTER_BASE . 'Monster.php');
  require (MONSTER_BASE . 'Egg.php');

  class Game {
    private $_client = null;
    private $_db = null;
    private $_userId = null;
    private $_monster;
    const QUIET = true;

    public function __construct($client) {
      $this->_monster = new Egg();
      $this->_client = $client;
      $this->_db = new DBase($this);

      echo "Game created - client id: " . $this->_client->resourceId . "\nSending request for authentication\n";

      $token = $this->generateToken();
      if (!empty($token)) {
        $this->sendMessage(Messaging::request('authentication', 'login',
          array(
            'id' => $this->_client->resourceId,
            'token' => $token
          )
        ));
      }
    }

    public function close() {
      $this->invalidateTokens();
      echo "Player closed game\n";
    }

    public function handleMessage($message) {
      if (!self::QUIET)
        echo "(" . $this->_client->resourceId . ") Message Received: \n\t$message \n";
      $message = json_decode($message);
      switch ($message->event) {
        case 'user':
          $this->userMessage($message);
          break;
        case 'authentication':
          $this->authenticationMessage($message);
          break;
        case 'ping':
          if ($this->checkValidToken())
            $this->sendMessage(Messaging::pong());
          break;
        case 'stage':
          if ($this->checkValidToken()) {
            $monsterFrame = $this->_monster->getNextState();
            $Helper = Helper::getInstance();
            $stage = $Helper::createEmptyArray();
            $_monFrame = $monsterFrame['monster']['sprite'];

            print_r($_monFrame);
            for ($i=0; $i < count($_monFrame); $i++) {
              for ($j=0; $j < count($_monFrame[$i]); $j++) {
                if (0 != $_monFrame[$i][$j]) {
                  $stage[$i+$monsterFrame['xoffset']][$j+$monsterFrame['yoffset']] = $_monFrame[$i][$j];
                }
              }
            }
            $this->sendMessage(Messaging::response('stage', 'frame', '', array('frame' => $stage)));
          }
          break;
        default:
          $this->sendError("Unknown Message: " . json_encode($message));
          break;
      }
    }

    private function invalidateTokens() {
      $sql = "UPDATE client SET active = 0 WHERE resourceId = " . $this->_db->escape($this->_client->resourceId);
      $this->_db->query($sql);
    }

    private function invalidateOtherSessions() {
      $sql = "UPDATE client SET active = 0 WHERE userId = " . $this->_userId . " AND resourceId != " . $this->_db->escape($this->_client->resourceId);
      $this->_db->query($sql);
    }

    private function generateToken() {
      $this->invalidateTokens();
      $token = time();

      $this->_db->prepareInsert(array(
        'resourceId' => $this->_client->resourceId,
        'token' => $token
      ));

      if ($this->_db->insert('client'))
        return $token;
    }

    private function userMessage($message) {
      switch ($message->action) {
        case 'usernameExists':
          if (!empty($message->data->username)) {
            $sql = "SELECT `id` FROM user WHERE username = '" . $this->_db->escape(strtolower($message->data->username)) . "'";
            $users = $this->_db->fetchAll($sql);
            $this->sendMessage(Messaging::response('user', $message->action,
              array(
                'exists' => (count($users) == 1)
              )
            ));
            return;
          }
          $this->sendMessage(Messaging::response('user', $message->action, array('exists' => false)));
          return;
        case 'create':
          $data = $message->data;
          if (!empty($data->username) &&
              !empty($data->password) &&
              !empty($data->fullname) &&
              !empty($data->email)) {
            $this->_db->prepareInsert(
              array(
                'username' => strtolower($data->username),
                'password' => $data->password,
                'fullname' => $data->fullname,
                'email' => $data->email
              )
            );
            $this->sendMessage(Messaging::response('user', $message->action,
              array(
                'created' => $this->_db->insert('user')
              )
            ));
            return;
          } else
            $this->sendError("Invalid request to create user");
          return;
        default:
          $this->sendError("Unknown Message: " . json_encode($message));
          break;
      }
    }

    private function authenticationMessage($message) {
      switch ($message->action) {
        case 'login':
          $data = $message->data;
          if (!empty($data->username) &&
              !empty($data->password)) {
            if ($this->checkValidToken()) {
              $sql = "SELECT `id`, `password` FROM user WHERE active = 1 AND username = '" . $this->_db->escape(strtolower($data->username)) . "'";
              $users = $this->_db->fetchAll($sql);
              $loggedIn = false;
              if (1 == count($users)) {
                $this->_userId = $this->_db->escape($users[0]['id']);
                $password = $users[0]['password'];
                $hash = sha1($this->getToken().$password);
                $loggedIn = ($hash === $data->password);

                $sql = "UPDATE client SET userId = " . $this->_userId . " WHERE active = 1 AND resourceId = " . $this->_db->escape($this->_client->resourceId);
                $this->_db->query($sql);

                $this->invalidateOtherSessions();
              } else if (1 < count($users))
                $this->sendError("Invalid request to login user - Unexpected user count");
              $this->sendMessage(Messaging::response('authentication', $message->action,
                array(
                  'loggedIn' => $loggedIn,
                  'message' => ($loggedIn) ? "" : "Username or Password is incorrect"
                )
              ));
            }
            return;
          } else
            $this->sendError("Invalid request to login user");
          return;
        default:
          $this->sendError("Unknown Message: " . json_encode($message));
          break;
      }
    }

    public function checkValidToken($sendMessage = true) {
      $activeToken = $this->getToken();
      if (!empty($activeToken))
        return !empty($this->_userId) || (time() < ($activeToken + (2*60)));
      $this->invalidateTokens();
      if ($sendMessage)
        $this->sendError((empty($this->_userId)) ? "Token has expired" : "You have logged in elsewhere");
      return false;
    }

    private function getToken() {
      $sql = "SELECT `token` FROM client WHERE active = 1 AND resourceId = " . $this->_db->escape($this->_client->resourceId);
      $tokens = $this->_db->fetchAll($sql);
      if (1 == count($tokens)) {
        return $tokens[0]['token'];
      }
      return;
    }

    public function sendMessage($message) {
      if (!self::QUIET)
        echo "(" . $this->_client->resourceId . ") Message Sent: \n\t$message\n";
      $this->_client->send($message);
    }

    public function sendError($message) {
      $this->sendMessage(Messaging::error($message));
      $this->_client->close();
    }
  }
 ?>
