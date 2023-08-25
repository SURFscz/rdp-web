<?php

$username = "";

foreach (getallheaders() as $name => $value) {
    if ($name == $_SERVER['REMOTE_USER_NAME']) {
        $username = $value;
    }
}

function customError($errno, $errstr) {
    echo "<script>alert(\"$errstr\");</script>";
    die();
}

set_error_handler("customError",E_USER_WARNING);

if ($username == "") {
    trigger_error("I do not know who to service !", E_USER_WARNING);
}

if (isset($_COOKIE['token'])) {
    $json = $_COOKIE['token'];
} else {
    if (isset($_GET['host'])) {
        $host = $_GET['host'];
    } else {
        trigger_error("Missing Host parameter !", E_USER_WARNING);
    }

    if (!isset($_POST["totp"])) { ?>
        <!DOCTYPE html>
        <html>
            <head>
                <title>RDP-WEP</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link rel="stylesheet" href="css/client.css">
            </head>
            <body>
                <div>
                    <form class="modal-content animate" method="post">
                        <div class="imgcontainer">
                            <img src="img/logo.jpeg" alt="logo" class="logo">
                        </div>

                        <div class="container">
                            <label for="totp"><b>Access Code</b></label>
                            <input type="text" placeholder="Enter your current TOTP value" name="totp" required maxlength="6" size="6">
                                
                            <button type="submit" id="button">Connect</button>
                        </div>
                    </form>
                </div>
            </body>
        </html>
    <?php
        exit();
    }

    $totp = $_POST["totp"];

    $curl = curl_init();

    curl_setopt_array($curl, 
        [
            CURLOPT_URL => "https://" . $host . "/api/v1/user/validate",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{ \n  \"username\": \"". $username . "\",\n\t\"code\": \"" . $totp . "\"\n}",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
        ]
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        trigger_error("Erorr connecting to ". $host, E_USER_WARNING);
    }

    if ($httpcode != 200) {
        trigger_error("Invalid code", E_USER_WARNING);
    }

    $data = json_decode($response);

    if (!isset($data->model)) {
        trigger_error("No valid response, no model received", E_USER_WARNING);
    }
    if (!isset($data->model->username)) {
        trigger_error("No valid response, no username received", E_USER_WARNING);
    }
    if ($username != $data->model->username) {
        trigger_error("No valid response, recieived details for different user", E_USER_WARNING);
    }
    if (!isset($data->model->password)) {
        trigger_error("No valid response, no password received", E_USER_WARNING);
    }

    $password = $data->model->password;

    $config = [
        "connection"=>[
            "type"=>"rdp", 
            "settings"=>[
                "hostname" => $host,
                "port" => 3389,
                "username" => $username . "@" . $data->model->domain,
                "password" => $password,
                "enable-audio" => true,
                "resize-method" => 'display-update',
                "security" => "any",
                "ignore-cert" => true,
                "disable-audio" => false,
                "enable-audio-input" => true
            ]
        ]
    ];

    $iv= substr(md5("cepo"),8,16);
    $value = \openssl_encrypt(
        json_encode($config),
        'AES-256-CBC',
        $_SERVER['SECRETKEY'],
        0,
        $iv
    );

    if ($value === false) {
        trigger_error('Could not encrypt the data.', E_USER_WARNING);
    }

    $data = [
        'iv' => base64_encode($iv),
        'value' => $value,
    ];

    $json = json_encode($data);
    if (!is_string($json)) {
        trigger_error('Could not encrypt the data.', E_USER_WARNING);
    }

    setcookie("token", $json);
}
?> 
<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
        <title>Desktop Server</title>
        <link rel="stylesheet" href="css/client.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>
    <body>
        <script type="text/javascript" src="https://cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
        <script type="text/javascript" src="js/guacamole-common-js/all.min.js"></script>
        <script>
            const Tunnel = new Guacamole.WebSocketTunnel(
                "wss://<?php echo $_SERVER['URL_GUACD']; ?>"
            );
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

            let connect = function() {
                if (!connected) {
                    var display = document.getElementById("display");

                    document.getElementById("connect").RemoveEventListener("click", connect);

                    client.connect(
                    "token=<?php echo base64_encode($json); ?>&width=" +
                        Math.round($(display).width()) +
                        "&height=" +
                        Math.round($(display).height()) +
                        "&dpi=96&GUAC_AUDIO=audio/L8&GUAC_AUDIO=audio/L16"
                    );
                }
            }

            if (!connected) {
                var connect_element = document.getElementById("connect");

                connect_element.addEventListener("click", connect);
                connect_element.style.visibility = "visible";
            }

            client.onstatechange = function clientStateChanged(state) {
                console.log("State change: " + state);
                if (state === 3) {
                    connected = true;

                    console.log("Client is connected !");
                    document.getElementById("connect").style.visibility = "hidden";
                }
            };

            // Add client to display display
            document.getElementById("display").appendChild(client.getDisplay().getElement());

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
        </script>
    </body>
</html>