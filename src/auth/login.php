<?php

//print "<pre>DEBUG: [auth] login.php \$_SERVER=" . print_r($_SERVER, true) . "</pre> <br>";
//exit();

/*
if (!isset($_SERVER["HTTP_AUTHORIZATION"])) {
    header("WWW-Authenticate: Basic realm=Website");
    header("HTTP/1.0 401 Unauthorized");
    //exit;
*/

//$debug = true;
$match = null;
session_start();
if (isset($_SESSION['auth_result']) && $_SESSION['auth_result'] === "MATCH") {
    $match = true;
    if (isset($_SESSION['auth_username'])) {
        $username = $_SESSION['auth_username'];
    }
    if (isset($_SESSION['auth_mask'])) {
        $mask = $_SESSION['auth_mask'];
    }
    if (isset($_SESSION['auth_flag'])) {
        $flag = $_SESSION['auth_flag'];
    }
} else {
    $match = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>GLFTPD:WEBUI.LOGIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-Equiv="Cache-Control" Content="no-cache" />
    <meta http-Equiv="Pragma" Content="no-cache" />
    <meta http-Equiv="Expires" Content="0" />
    <link rel="stylesheet" href="../lib/bootstrap-4.6.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../static/style.css">
</head>
<body>
    <div class="alert <?= (($match) ? "alert-success" : "alert-secondary") ?>" role="alert">
        You are logged <?= (($match) ? "in, goto <a href='/'>main page</a>" : "out") ?>
    </div>
    <form id="form" action="/auth/index.php" method="POST">
        <h1>COMMAND CENTER</h1>
        <p></p>
        <?php if ($match): ?>
            <div class="form-group">
                <small id="help" class="form-text text-muted">Showing info of currenly logged in user</small>
            </div>
            <div class="form-group">
                Username: <div class="col-3"><strong><?= $username ?></strong></div>
            </div>
            <?php if (!empty($mask)): ?>
            <div class="form-group">
                Mask: <div class="col-3"><strong><?= $mask ?></strong></div>
            </div>
            <?php endif ?>
            <?php if (!empty($flag)): ?>
            <div class="form-group">
                Flag: <div class="col-3">'<strong><?= $flag ?></strong>'</div>
            </div>
            <a href="/auth/logout.php"><button type="button" class="btn btn-outline-primary">Logout</button></a>
            <?php endif ?>
        <?php else: ?>
        <div class="form-group">
            <small id="help" class="form-text text-muted">Login with your glftpd username and password</small>
        </div>
        <div class="form-group">
            <label for="username">Username:</label>
            <div class="col-3">
                <input type="text" id="username" name="username" placeholder="my-glftpd-user" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <div class="col-3">
                <input type="password" id="password" name="password" placeholder="secr3t" class="form-control">
            </div>
        </div>
        <input type="submit" value="Submit"  class="btn btn btn-primary" <?= (($match) ? "disabled" : "")?> />
    <?php endif ?>
    </form>

<?= (isset($debug) ?
    "<br><hr><br><pre><strong>DEBUG:</strong><br>" .
    "\$match=$match<br>" .
    "\$_SESSION['auth_result']={$_SESSION['auth_result']}<br>" .
    "\$_SESSION['auth_username']={$_SESSION['auth_username']}<br>" .
    // "\$_SERVER['REMOTE_ADDR']={$_SERVER['REMOTE_ADDR']}<br>"  .
    // "\$_SERVER['HTTP_CLIENT_IP']={$_SERVER['HTTP_CLIENT_IP']}<br>" .
    // "\$_SERVER['HTTP_X_FORWARDED_FOR']={$_SERVER['HTTP_X_FORWARDED_FOR']}<br>" .
    "\$_SERVER=" .  print_r($_SERVER, true)  . "<br>" .
    "\$_SESSION=" .  print_r($_SESSION, true)  . "<br></pre>"
: "") ?>

</body>
</html>
