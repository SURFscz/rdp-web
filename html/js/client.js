const Tunnel = new Guacamole.WebSocketTunnel(URL);
const client = new Guacamole.Client(Tunnel);

var connected = false;

// Window resize...
window.onresize = function () {
    var display = document.getElementById("display");

    let W = Math.round($(display).width());
    let H = Math.round($(display).height());

    Tunnel.sendMessage("size", W, H);
};

// Disconnect on close
window.onunload = function () {
    if (connected) {
        client.disconnect();
    }

    connected = false;
};

function connect() {
    if (!connected) {
        let display = document.getElementById("display");
        let connect_element = document.getElementById("connect");

        document.getElementById("connect").removeEventListener("click", connect);

        connect_element.classList.remove("fa-play-circle");

        connect_element.classList.add("fa-refresh");
        connect_element.classList.add("fa-pulse");
        connect_element.classList.add("fa-3x");
        connect_element.classList.add("fa-fw");

        client.connect(
            "token=" + JWT +
            "&width=" + Math.round($(display).width()) +
            "&height=" + Math.round($(display).height()) +
            "&dpi=96&" +
            "GUAC_AUDIO=audio/L8&GUAC_AUDIO=audio/L16"
        );
    }
}

if (!connected) {
    let connect_element = document.getElementById("connect");

    connect_element.addEventListener("click", connect);
    connect_element.classList.add("fa-play-circle");
}

client.onstatechange = function clientStateChanged(state) {
    console.log("State change: " + state);
    if (state === 3) {
        connected = true;

        console.log("Client is connected !");

        document.getElementById("connect").style.visibility = "hidden";
        document.getElementById("display").appendChild(client.getDisplay().getElement());
    }
};

// Error handler
client.onerror = function (error) {
    console.log(error);
};

// Audio handler
client.onaudio = function clientAudio(stream, mimetype) {
    console.log("AUDIO: " + mimetype);
    var context = Guacamole.AudioContextFactory.getAudioContext();
    context.resume().then(() => console.log("play audio"));
};

// MouseState
function sendScaledMouseState(mouseState) {
    var d = client.getDisplay();

    var scaledState = new Guacamole.Mouse.State(
        mouseState.x / d.getScale(),
        mouseState.y / d.getScale(),
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
mouse.onmousemove = function (mouseState) {
    sendScaledMouseState(mouseState);
    client.getDisplay().showCursor(false);
};

// Keyboard
var keyboard = new Guacamole.Keyboard(document);

keyboard.onkeydown = function (keysym) {
    client.sendKeyEvent(1, keysym);
};

keyboard.onkeyup = function (keysym) {
    client.sendKeyEvent(0, keysym);
};