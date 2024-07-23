<?php
    if ($_POST['basic_auth']) {
        unset($_SESSION['basic_auth_result']);
    } else {
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                setcookie($name, '', time()-1000);
                setcookie($name, '', time()-1000, '/');
            }
        }
        unset($_GET);
        unset($_POST);
        unset($_SESSION);
        session_destroy();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>GLFTPD:WEBUI logout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-Equiv="Cache-Control" Content="no-cache" />
    <meta http-Equiv="Pragma" Content="no-cache" />
    <meta http-Equiv="Expires" Content="0" />
</head>
<body>
    <script type="text/javascript">
        document.cookie = "PHPSESSID=;Path=/;expires=Thu, 01 Jan 1970 00:00:01 GMT;";
        window.location = "/";
    </script>
</body>
</html>
