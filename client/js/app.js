(function() {
  "use strict";
  var app = {
    socket: undefined,
    socketUrl: 'ws://192.168.1.11:8080',
    id: undefined,
    token: undefined,
    hash: undefined,
    authenticated: false,
    lastState: undefined,
    init: function() {
      this.setStatus("Loading");
      if (!('jQuery' in window))
        throw new Exception('jQuery isn\'t loaded');
      if (!('JSON' in window))
        throw new Exception('Your browser doesn\'t support JSON');
      if ("function" != typeof JSON.stringify)
        throw new Exception('Your browser doesn\'t support JSON.stringify');
      if ("function" != typeof sha1)
        throw new Exception('Your browser hasn\'t loaded sha1.js');
      if (!('WebSocket' in window))
        throw new Exception('Your browser doesn\'t support WebSockets');
      this.createSocket();  
    },
    setStatus: function(message, error) {
      if (error)
        $("#status").addClass("error");
      else
        $("#status").removeClass("error");
      $("#statusMessage").text(message || "");

    },
    createSocket: function() {
      $("#login").fadeOut();
      $("#userDetails").slideUp();
      this.setStatus("Creating WebSocket");
      this.socket = new WebSocket(this.socketUrl);
      this.socket.onopen = app.socketOpened;
      this.socket.onmessage = app.socketReceiveMessage;
      this.socket.onerror = app.socketError;
      this.socket.onclose = app.socketClose;
    },
    socketOpened: function(e) {
      app.setStatus("WebSocket opened");
      app.disableLoginButtons(false);
      $("#login input[type='submit']").addClass("button-primary");
    },
    socketError: function(e) {
      //console.error(e);
    },
    socketClose: function(e) {
      var reason;
      // See http://tools.ietf.org/html/rfc6455#section-7.4.1
      if (event.code == 1000)
          reason = "Normal closure, meaning that the purpose for which the connection was established has been fulfilled.";
      else if(event.code == 1001)
          reason = "An endpoint is \"going away\", such as a server going down or a browser having navigated away from a page.";
      else if(event.code == 1002)
          reason = "An endpoint is terminating the connection due to a protocol error";
      else if(event.code == 1003)
          reason = "An endpoint is terminating the connection because it has received a type of data it cannot accept (e.g., an endpoint that understands only text data MAY send this if it receives a binary message).";
      else if(event.code == 1004)
          reason = "Reserved. The specific meaning might be defined in the future.";
      else if(event.code == 1005)
          reason = "No status code was actually present.";
      else if(event.code == 1006)
         reason = "The connection was closed abnormally, e.g., without sending or receiving a Close control frame";
      else if(event.code == 1007)
          reason = "An endpoint is terminating the connection because it has received data within a message that was not consistent with the type of the message (e.g., non-UTF-8 [http://tools.ietf.org/html/rfc3629] data within a text message).";
      else if(event.code == 1008)
          reason = "An endpoint is terminating the connection because it has received a message that \"violates its policy\". This reason is given either if there is no other sutible reason, or if there is a need to hide specific details about the policy.";
      else if(event.code == 1009)
         reason = "An endpoint is terminating the connection because it has received a message that is too big for it to process.";
      else if(event.code == 1010) // Note that this status code is not used by the server, because it can fail the WebSocket handshake instead.
          reason = "An endpoint (client) is terminating the connection because it has expected the server to negotiate one or more extension, but the server didn't return them in the response message of the WebSocket handshake. <br /> Specifically, the extensions that are needed are: " + event.reason;
      else if(event.code == 1011)
          reason = "A server is terminating the connection because it encountered an unexpected condition that prevented it from fulfilling the request.";
      else if(event.code == 1015)
          reason = "The connection was closed due to a failure to perform a TLS handshake (e.g., the server certificate can't be verified).";
      else
          reason = "Unknown reason";
      //console.log("Socket Error", event.code, reason);
      switch (event.code) {
        case 1006:
          app.socketClosed.call(app);
          break;
        default:
          break;
      }
    },
    socketReceiveMessage: function(e) {
      if (!e.data)
        throw new Exception('Invalid response from server');
      try {
        var message = jQuery.parseJSON(e.data);
        app.handleMessage.call(app, message);
      } catch (ex) {
        console.error("Invalid message returned from server", e.data, ex);
      }
    },
    socketSendMessage: function(message) {
      console.log("Sending Message", message);
      this.socket.send(JSON.stringify(message));
    },
    handleMessage: function(msg) {
      console.log("Handle Message", msg);
      if (msg.error) {
        this.reset();
        this.setStatus("Error: " + msg.message, true);
        return;
      }
      if (!msg.event)
        throw new Exception('Invalid message returned from server');

      switch (msg.event) {
        case 'authentication':
          this.authentication(msg.action, msg.data);
          break;
        case 'user':
          this.user(msg.action, msg.data);
          break;
        case 'pong':
          break;
        default:
          console.error('Unknown message type - ' + msg.event);
      }
    },
    socketClosed: function() {
      var msg, timeout = 10000, clientId = app.id;
      this.reset();
      if (this.authenticated) {
        msg = "WebSocket closed, restarting game";
        this.setStatus(msg, true);
        timeout= 5000;
      } else if (!clientId) {
        msg = "Cannot open WebSocket, retrying";
        this.setStatus(msg, true);
      } else if (clientId) {
        msg = "Server closed the connection, please try again later";
        this.setStatus(msg, true);
        return;
      }
      setTimeout(function() { app.createSocket.call(app); }, timeout);
    },
    authentication: function(action, data) {
      if ("loginResponse" == action) {
        if (true === data.loggedIn)
          this.createGame();
        else
          this.setStatus(data.message, true);
      } else if ("loginRequest" == action) {
        this.id = data.id;
        this.token = data.token;
        this.setStatus("Authentication required");
        $("#createUser").on('click', function() { app.createUser.call(app); });
        $("#loginForm").submit(function() {
          app.loginSubmit.call(app);
          return false;
        });
        $("#login").fadeIn(400, function() {
          $("#username").focus();
        });
      } else
        console.error("Unknown athentication action: " + action, data);
    },
    user: function(action, data) {
      if ("usernameExistsResponse" == action) {
        if (false === data.exists) {
          console.log("usernameExistsResponse", data);
          var userDetails = $("#userDetails"),
            username = $("#username"),
            password = $("#password");
          username.attr("disabled", "disabled");
          password.attr("disabled", "disabled");
          $("#login input[type='submit']").parent().fadeOut(400, function() {
            $("#createUser")
              .addClass("button-primary")
              .parent()
              .animate(
                {
                  'margin-left': '0',
                  'width': '100%'
                },
                400,
                'swing',
                function() {
                  $("#login").addClass("full");
                  userDetails.slideDown(400, function() {
                    $("#fullName").focus();
                  });
                }
              );
          });
        } else {
          $("#username").focus();
          alert('Username is already in use');
        }
      } else if ("createResponse" == action) {
        if (true === data.created) {
          this.setStatus("Account created");
          this.login();
        } else {
          this.reset();
          this.setStatus("There was a problem creating your account", true);
        }
      } else
        console.error("Unknown user action: " + action, data);
    },
    disableLoginButtons: function(disable) {
      if (disable)
        $("#login .action").attr('disabled', 'disabled');
      else
        $("#login .action").removeAttr('disabled');
    },
    createUser: function() {
      var userDetails = $("#userDetails"),
        username = $("#username"),
        password = $("#password");

      if (userDetails.is(":visible")) {
        var fullName = $("#fullName"),
          email = $("#email");
        if (!fullName.val().trim().length)
          alert("Please enter your fullname");
        else if (!email.val().trim().length)
          alert("Please enter your email address");
        else {
          this.socketSendMessage({
            'event' : 'user',
            'action' : 'create',
            'data' : {
              'username' : username.val(),
              'password' : sha1(password.val()),
              'fullname' : fullName.val(),
              'email' : email.val()
            }
          });
        }
      } else {
        if (!username.val().trim().length)
          alert("Please enter a username");
        else if (!password.val().trim().length)
          alert("Please enter a password");
        else
          this.socketSendMessage({
            'event' : 'user',
            'action' : 'usernameExists',
            'data' : {
              'username' : $("#username").val()
            }
          });
      }
    },
    loginSubmit: function() {
      if ($("#userDetails").is(":visible"))
        app.createUser.call(app);
      else
        app.login.call(app);
    },
    login: function() {
      this.setStatus("Logging in");
      var username = $("#username"),
        password = $("#password");
      this.hash = sha1(password.val());
      this.socketSendMessage({
        'event' : 'authentication',
        'action' : 'login',
        'data' : {
          'username' : username.val(),
          'password' : sha1(this.token+this.hash)
        }
      });
    },
    reset: function() {
      app.setStatus("Resetting game", true);
      $("#stage").empty();
      $("#login").fadeOut();
      app.authenticated = false;
      app.id = app.token = undefined;
    },
    createGame: function() {
      this.authenticated = true;
      $("#username").val("");
      $("#password").val("");
      $("#stage").empty();
      $("#login").fadeOut();
      this.setStatus("Creating Stage");
      var fragment = document.createDocumentFragment();
      for (var i = 15; i >= 0; i--) {
        var row = document.createElement('div');
        row.className = 'pixelGroup';
        for (var j = 0; j < 32; j++) {
          var el = document.createElement('div');
          el.className = "pixel";
          el.id = "pixel-"+i+"-"+j;
          row.appendChild(el);
        }
        fragment.appendChild(row);
      }

      this.lastState = this.createEmptyArray(16, 32);
      console.log(this.lastState);

      var stage = document.getElementById("stage");
      if (stage)
        stage.appendChild(fragment);

      this.setStatus("Setting Pixel Size");
      $(window).on('resize', debounce(function() { app.resizePixels(); }, 250));
      this.resizePixels();

      //TODO: DRAW GAME STATE
      this.setStatus("Starting Random Draw");
      setInterval(function() {
        app.setStatus("Randomising Stage");
        var newState = app.createEmptyArray(16, 32);
        for (var i = 0; i < 5; i++) {
          newState[(Math.floor(Math.random() * 16))][(Math.floor(Math.random() * 32))] = 1;
        }
        app.draw(newState);
      }, 100);

      this.setStatus("Running");
      $("#stage").fadeIn();
    },
    resizePixels: function() {
      var height = Math.floor((window.innerHeight - ($("#status").outerHeight())) / 16),
        width = Math.floor(window.innerWidth / 32);
      console.log(height, width);
      var length = Math.min(width,height),
        pixels = document.getElementsByClassName("pixel");

      if (length < 10) length = 10;
      if (length > 30) length = 30;

      for (var i = 0; i < pixels.length; i++) {
        pixels[i].style.width = length+"px";
        pixels[i].style.height = length+"px";
      }
      var pixelGroups = document.getElementsByClassName("pixelGroup");
      for (var j = 0; j < pixelGroups.length; j++) {
        pixelGroups[j].style.width = (32*length)+"px";
        pixelGroups[j].style.height = length+"px";
      }

      var stage = document.getElementById("stage");
      if (stage) {
        stage.style.left = ((window.innerWidth / 2) - ($("#stage").outerWidth() /2)) +"px";
        stage.style.top = ((window.innerHeight - $("#stage").outerHeight() - $("#status").outerHeight())/2)+"px";
        stage.style.position = "absolute";
      }
    },
    ping: function() {
      if (this.token)
        this.socketSendMessage({ 'event' : 'ping' });
      else
        clearInterval(window.ping);
    },
    createEmptyArray: function(length) {
      var arr = new Array(length || 0),
          i = length;
      if (arguments.length > 1) {
          var args = Array.prototype.slice.call(arguments, 1);
          while(i--) arr[length-1 - i] = this.createEmptyArray.apply(app, args);
      }
      return arr;
    },
    draw: function(state) {
      app.setStatus("Drawing");
      if (Array.isArray(state)) {
        for (var i = 0; i < state.length; i++) {
          for (var j = 0; j < state[i].length; j++) {
            if (state[i][j] != app.lastState[i][j])
              $("#pixel-"+i+"-"+j).toggleClass('fill');
          }
        }
      }
    }
  };

  if (app) {
    app.init();
    window.ping = setInterval(function() { app.ping.call(app); }, 10e3);
  } else
    console.error('App not found');
})();

function debounce (func, wait, immediate) {
  var timeout;
  return function() {
    var context = this, args = arguments;
    var later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}
