<?php

if (empty($_SESSION)) {
    session_start();
}

require_once '/app/config.php';

//$login_debug = 1;

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>GLFTPD:WEBUI login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-Equiv="Cache-Control" Content="no-cache" />
    <meta http-Equiv="Pragma" Content="no-cache" />
    <meta http-Equiv="Expires" Content="0" />
    <link rel="stylesheet" href="lib/bootstrap-4.6.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>


    <div>
        <form id="form"  method="POST">
            <button type="submit" formaction="/auth/index.php" class="btn btn btn-secondary">Test Login</button>
            <button type="submit" formaction="/auth/logout.php" class="btn btn btn-secondary">Test Logout</button>
            <button type="submit" formaction="/auth/logout.php" name="basic_auth" value="1" class="btn btn-outline-secondary">Test BasicAuth Reset</button>
        </form>
    </div>


    <?php if (!empty($_SESSION['basic_auth_result'])): ?>
        <?php if ($_SESSION['basic_auth_result'] === "0"): ?>
            <div class="alert alert-danger" role="alert">
                Basic authentication failed
            </div>
        <?php endif ?>
        <?php if ($_SESSION['basic_auth_result'] === "1"): ?>
            <div class="alert alert-secondary" role="alert">
                Basic authentication successful
            </div>
        <?php endif ?>
    <?php endif ?>

    <?php if (!empty($_SESSION['glftpd_auth_result'])): ?>
        <?php if ($_SESSION['glftpd_auth_result'] === "0"): ?>
            <div class="alert alert-danger" role="alert">
                Incorrect glftpd username or password
            </div>
        <?php endif ?>
    <?php endif ?>

    <?php if (!empty($_SESSION['basic_auth_result']) && !empty($_SESSION['glftpd_auth_result'])): ?>
        <?php if ($_SESSION['glftpd_auth_result'] !== "1" || $_SESSION['basic_auth_result'] !== "1"): ?>
            <div class="alert alert-secondary" role="alert">
                You are logged out
            </div>
        <?php endif ?>
        <?php if ($_SESSION['basic_auth_result'] !== "0" && $_SESSION['glftpd_auth_result'] === "1"): ?>
            <div class="alert alert-success" role="alert">
                You are logged in, goto <a href='/'>main page</a>
            </div>
        <?php endif ?>
    <?php endif ?>

    <h1 style="display:inline">COMMAND CENTER</h1> <h1 style="display:inline;text-decoration:none">&nbsp;| LOGIN</h1>
    <p></p>
    <?php if (!empty($cfg['auth'])): ?>
        <div class="form-group mb-1">
            <small id="help" class="form-text text-muted">Configured auth method is: <strong><?= $cfg['auth'] ?></strong></small>
        </div>
        <div class="mb-1">&nbsp;</div>
    <?php endif ?>

    <?php if (!empty($cfg['auth']) && ($cfg['auth'] === "basic" || $cfg['auth'] === "both")): ?>
    <div class="form-group">
        <h5>Basic authentication</h5>
        <?php if (!empty($_SERVER['PHP_AUTH_USER'])): ?>
            <div class="col-3">
                User: <strong><?= $_SERVER['PHP_AUTH_USER'] ?></strong>
            </div>
        <?php endif ?>
    </div>
    <hr>
    <?php endif ?>

    <?php if (!empty($cfg['auth']) && ($cfg['auth'] === "glftpd" || $cfg['auth'] === "both")): ?>
        <form id="form" action="/auth/index.php" method="POST" class="d-inline">
            <?php if (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1"): ?>
                <div class="form-group">
                   <h5>Glftpd user login</h5>
                    <?php if (!empty($_SESSION['glftpd_auth_username'])): ?>
                        <div class="col-3">
                            Username: <strong><?= $_SESSION['glftpd_auth_username'] ?></strong>
                        </div>
                    <?php endif ?>
                    <?php if (!empty($_SESSION['glftpd_auth_mask'])): ?>
                        <div class="col-3">
                            Mask: <strong><?= $_SESSION['glftpd_auth_mask'] ?></strong>
                        </div>
                    <?php endif ?>
                    <?php if (!empty($_SESSION['glftpd_auth_flag'])): ?>
                        <div class="col-3">
                            Flags: <strong><?= $_SESSION['glftpd_auth_flag'] ?></strong>
                        </div>
                    <?php endif ?>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <h5>Glftpd login</h5>
                </div>
                <div class="form-group">
                    <label for="username" class="ml-2">Username:</label>
                    <div class="col-3">
                        <input type="text" id="username" name="username" placeholder="my-glftpd-user" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password" class="ml-2">Password:</label>
                    <div class="col-3">
                        <input type="password" id="password" name="password" placeholder="secr3t" class="form-control">
                    </div>
                </div>
                <input type="submit" value="Login" class="btn btn btn-primary"/>
            <?php endif ?>
        </form>
        <a href="/auth/logout.php"><button type="button" class="btn btn-outline-primary">Logout</button></a>
    <?php endif ?>

    <br>

<?php if (!empty($login_debug)): ?>
    <br><hr><br>
    <pre><strong>DEBUG:</strong></pre>
    <?= (!empty($_SESSION['basic_auth_result']) ? "<pre>\$_SESSION['basic_auth_result']={$_SESSION['basic_auth_result']}</pre>" : '' ) ?>
    <?= (!empty($_SESSION['glftpd_auth_result']) ? "<pre>\$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}</pre>" : '' ) ?>
    <?= (!empty($_SESSION['glftpd_auth_username']) ? "<pre>\$_SESSION['glftpd_auth_username']={$_SESSION['glftpd_auth_username']}</pre>" : '' ) ?>
    <?= (!empty($_SERVER['PHP_AUTH_USER']) ? "<pre>\$_SERVER['PHP_AUTH_USER']={$_SERVER['PHP_AUTH_USER']}</pre>" : '' ) ?>
    <pre>$_POST=<?= print_r($_POST, true)?></pre>
    <pre>$_SERVER=<?= print_r($_SERVER, true) ?></pre>
    <?php
        /*
        <pre>$_SESSION=<?= print_r($_SESSION, true)?></pre>
        <pre>$_SERVER=<?= print_r($_SERVER, true) ?></pre>
        $_SERVER['REMOTE_ADDR']=<?= $_SERVER['REMOTE_ADDR'] ?>
        $_SERVER['HTTP_CLIENT_IP']=<?= $_SERVER['HTTP_CLIENT_IP'] ?>
        $_SERVER['HTTP_X_FORWARDED_FOR']=<?=    $_SERVER['HTTP_X_FORWARDED_FOR'] ?>

        </pre>
        */
    ?>
    <br>
<?php endif ?>

</body>
</html>
