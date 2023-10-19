<?php

$username = str_replace('"', '', $_SERVER['REMOTE_USER'] ?? "");
$server = ($_SERVER['REQUEST_SCHEME'] ?? "http") . "://" . ($_SERVER['DOMAIN'] ?? "localhost");

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

    if (isset($_SERVER["API_KEY"])) {

        $message = "{\"username\": \"". $username . "\"}";
        $headers = [
          "Content-Type: application/json",
	  "Authorization: Bearer " . $_SERVER["API_KEY"]
	];
    } else {
        if (!isset($_POST["totp"])) { ?>
            <!DOCTYPE html>
            <html>
                <head>
                    <title>RDP-WEP</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <link rel="stylesheet" href="<?php echo $server ?>/css/client.css">
                </head>
                <body>
                    <div>
                        <form class="modal-content animate" method="post">
                            <div class="imgcontainer">
                                <img src="<?php echo $server ?>/img/logo.jpeg" alt="logo" class="logo">
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

        $message = "{\"username\": \"". $username . "\",\"code\": \"" . $_POST["totp"] . "\"\n}";
        $headers = [
          "Content-Type: application/json"
	];
    }

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
	        CURLOPT_POSTFIELDS => $message,
	        CURLOPT_HTTPHEADER => $headers
        ]
    );

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        trigger_error("Error connecting to ". $host, E_USER_WARNING);
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
                "enable-audio-input" => true,
                "enable-drive" => true,
                "drive-name" => $username,
                "drive-path" => '/drive/' . $username,
                "create-drive-path" => true
            ]
        ]
    ];

    $iv= substr(md5("cepo"),8,16);
    $value = \openssl_encrypt(
        json_encode($config),
        'AES-256-CBC',
        $_SERVER['SECRET_KEY'],
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

    setcookie("token", $json, [
        'expires' => time() + 86400,
        'path' => '/',
        'domain' =>  $_SERVER['SERVER_NAME'],
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
?> 
<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
        <title>Desktop Server</title>
        <link rel="stylesheet" href="<?php echo $server ?>/css/client.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>
    <body>
        <div id="display" style="height:100vh;">
        </div>
        <script type="text/javascript" src="https://cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $server ?>/js/guacamole-common-js/all.min.js"></script>
        <script>
            const URL = "<?php echo $_SERVER['URL_GUACD']; ?>";
            const JWT = "<?php echo base64_encode($json); ?>"
        </script>
        <script type="text/javascript" src="<?php echo $server ?>/js/client.js"></script>
    </body>
</html>
