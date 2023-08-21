var display = document.getElementById("display");

let WIDTH = Math.round($(display).width());
let HEIGHT = Math.round($(display).height());

const Tunnel = new Guacamole.WebSocketTunnel(
  "wss://<?php echo $_SERVER['URL_GUACD']; ?>"
);
const client = new Guacamole.Client(Tunnel);

var connected = false;
var dis = client.getDisplay();

document.getElementById("connect").addEventListener("click", function () {
  if (!connected) {
    client.connect(
      "token=<?php echo base64_encode($json); ?>&width=" +
        WIDTH +
        "&height=" +
        HEIGHT +
        "&dpi=96&GUAC_AUDIO=audio/L8&GUAC_AUDIO=audio/L16"
    );
  }
});

client.onstatechange = function clientStateChanged(state) {
  console.log("State change: " + state);
  if (state === 3) {
    connected = true;

    console.log("Client is connected !");
    document.getElementById("connect").style.visibility = "hidden";
  }
};

// Add client to display div
display.appendChild(dis.getElement());

// Error handler
client.onerror = function (error) {
  console.log(error);
};

// Connect
let clickHandler = function () {
  if (!connected)
    client.connect(
      "token=<?php echo base64_encode($json); ?>&width=" +
        WIDTH +
        "&height=" +
        HEIGHT +
        "&dpi=96&GUAC_AUDIO=audio/L8&GUAC_AUDIO=audio/L16"
    );
};

client.onaudio = function clientAudio(stream, mimetype) {
  console.log("AUDIO: " + mimetype);
  var context = Guacamole.AudioContextFactory.getAudioContext();
  context.resume().then(() => console.log("play audio"));
};

// Window resize...
window.onresize = function () {
  let display = document.getElementById("display");
  let W = Math.round($(display).width());
  let H = Math.round($(display).height());

  Tunnel.sendMessage("size", W, H);
};

// Disconnect on close
window.onunload = function () {
  if (connected) {
    client.disconnect();
  }

  document.getElementById("connect").style.visibility = "visible";
  connected = false;
};

function sendScaledMouseState(mouseState) {
  var scaledState = new Guacamole.Mouse.State(
    mouseState.x / dis.getScale(),
    mouseState.y / dis.getScale(),
    mouseState.left,
    mouseState.middle,
    mouseState.right,
    mouseState.up,
    mouseState.down
  );
  client.sendMouseState(scaledState);
}

// Mouse
var mouse = new Guacamole.Mouse(client.getDisplay().getElement());

mouse.onmousedown =
  mouse.onmouseup =
  mouse.onmousemove =
    function (mouseState) {
      sendScaledMouseState(mouseState);
      dis.showCursor(false);
    };

// Keyboard
var keyboard = new Guacamole.Keyboard(document);

keyboard.onkeydown = function (keysym) {
  client.sendKeyEvent(1, keysym);
};

keyboard.onkeyup = function (keysym) {
  client.sendKeyEvent(0, keysym);
};
