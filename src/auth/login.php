<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK auth login
 *--------------------------------------------------------------------------*/

if (empty($_SESSION)) {
    session_start();
}
if (!file_exists("/app/config.php")) {
    header("HTTP/1.0 404 Not Found", true, 404);
    readfile('templates/error_404.html');
    exit;
}

require_once '/app/config.php';
$debug_vars = 1;
$debug_buttons = 0;

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

<?php if (!empty($debug_buttons) && $debug_buttons === 1): ?>
        <div>
            <form id="debug" method="POST">
                <button type="submit" formaction="/auth/index.php" class="btn btn-secondary">Test Login</button>
                <button type="submit" formaction="/auth/logout.php" class="btn btn-secondary">Test Logout</button>
                <button type="submit" formaction="/auth/logout.php" name="http_auth_logout" value="1" class="btn btn-outline-secondary">Test BasicAuth Reset</button>
            </form>
        </div>
    <?php endif ?>

    <?php if (!empty($_SESSION['auth_mode_result']) && $_SESSION['auth_mode_result'] === "1" ): ?>
        <div class="alert alert-info" role="alert">
           Auth mode changed
           <?php unset($_SESSION['auth_mode_result']); ?>
        </div>
    <?php endif ?>
    <?php if (!empty($_SESSION['http_passwd_result']) && $_SESSION['http_passwd_result'] === "1" ): ?>
        <div class="alert alert-info" role="alert">
           Http auth password changed
           <?php unset($_SESSION['http_passwd_result']); ?>
        </div>
    <?php endif ?>
    <?php if (!isset($cfg['auth']) || empty($cfg['auth'])): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Auth mode not set in config.php</strong>
        </div>
    <?php endif ?>
    <?php if (!empty($cfg['auth'])): ?>
        <?php if ($cfg['auth'] === "basic"): ?>
            <div class="alert alert-info" role="alert">
                Basic auth ok, goto <a href='/' class="alert-link">main page</a>
            </div>
        <?php elseif ($cfg['auth'] === "glftpd" || $cfg['auth'] === "both"): ?>
            <?php if (empty($_SESSION['glftpd_auth_result'])): ?>
                <div class="alert alert-secondary" role="alert">
                    You are <strong>not</strong> logged in
                </div>
            <?php endif ?>
            <?php if (empty($_SESSION['http_auth_result']) || (!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] !== "1") || empty($_SERVER['PHP_AUTH_USER'])): ?>
                <div class="alert alert-danger" role="alert">
                    No http authentication from browser, try
                    <form id="form" method="POST" class="d-inline">
                        <button type="submit" formaction="/auth/index.php" value="Login" class="btn btn-link pb-1"><strong>Login</strong></button>
                    </form>
                </div>
            <?php endif ?>
            <?php if (!empty($_SESSION['http_auth_result']) && !empty($_SESSION['glftpd_auth_result'])): ?>
                <?php if ($_SESSION['http_auth_result'] === "1" && $_SESSION['glftpd_auth_result'] === "1"): ?>
                    <div class="alert alert-success" role="alert">
                        You are logged in, <a href='/' class="alert-link">goto main page</a>
                    </div>
                <?php endif ?>
            <?php endif ?>
        <?php endif ?>
    <?php endif ?>

    <div class="mb-4">
        <h1 style="display:inline">COMMAND CENTER</h1> <h1 style="display:inline;text-decoration:none">&nbsp;| LOGIN</h1>
    </div>
    <p></p>
    <?php if (!empty($cfg['auth'])): ?>
        <div class="group" id="auth">
            <div class="form-group">
                <div id="help" class="form-text text-muted small">Configured auth method is: <strong><?= $cfg['auth'] ?></strong></div>
            </div>
            <div class="form-group mb-1">
                <div id="help" class="form-text text-muted small">Allowed ip address: <strong><?= $_SERVER['HTTP_X_FORWARDED_FOR'] ?></strong></div>
            </div>
        </div>
        <?php if ($cfg['auth'] === "both"): ?>
            <div class="group" id="auth">
            <h5>HTTP authentication: <?= ($_SESSION['http_auth_result'] === "1") ? '<span class="text-success">successful' : '<span class="text-danger">failed' ?></span></h5>
            <div id="help" class="ml-2">Client browser user: <strong><?= (!empty($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER'] : "&lt;none&gt;" ?></strong></div>
            <?php if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SESSION['http_auth_result']) || (!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] !== "1")): ?>
                <div id="help" class="ml-2">User is not verified</div>
                <div id="help" class="text-muted small ml-2">Try logging in (again), this should popup a browser window to input user and password (then go back to this page)</div>
            <?php endif ?>
            </div>
        <?php endif ?>
        <?php if ($cfg['auth'] === "glftpd" || $cfg['auth'] === "both"): ?>
            <form id="form" method="POST" class="d-inline">
                <div class="group" style="margin-bottom:20px;">
                    <?php if (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1"): ?>
                        <div class="form-group">
                            <h5>Currently logged in as glftpd user</h5>
                            <?php if (!empty($_SESSION['glftpd_auth_user'])): ?>
                                <div class="col-3">
                                    Username: <strong><?= $_SESSION['glftpd_auth_user'] ?></strong>
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
                            <h5>Login with glftpd account</h5>
                        </div>
                        <div class="form-row align-items-center">
                            <label for="glftpd_user" class="ml-2">Username:</label>
                            <div class="col-auto">
                                <input type="text" id="glftpd_user" name="glftpd_user" placeholder="my-glftpd-username" class="form-control">
                            </div>
                        </div>
                        <p></p>
                        <div class="form-row align-items-center">
                            <label for="glftpd_password" class="ml-2">Password:</label>
                            <div class="col-auto">
                                <input type="password" id="glftpd_password" name="glftpd_password" placeholder="super-secr3t" class="form-control">
                            </div>
                        </div>
                    <?php endif ?>
                </div>

                <div class="ml-2 mb-5">
                    <?php if (!empty($_SESSION['glftpd_auth_result']) || (empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] !== "1")): ?>
                        <input type="submit" formaction="/auth/index.php" value="Login" class="btn btn-primary"/>
                    <?php endif ?>
                    <input type="submit" formaction="/auth/logout.php" value="Logout" class="btn btn-outline-primary"/>
                </div>

                <?php if ((!empty($_SESSION['http_auth_result']) && ($_SESSION['http_auth_result'] === "1")) || (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")): ?>
                    <div class="group">
                    <h5 class="text-muted">Change settings</h5>
                        <div class="form-group">
                            <div class="form-row align-items-center mb-1">
                                <label for="auth_mode" class="form-text text-muted ml-2">Auth method:</label>
                                <div class="col-auto ml-5">
                                    <select class="form-control form-control" id="auth_mode" name="auth_mode">
                                        <?php foreach (['basic', 'glftpd', 'both', 'none'] as $mode ): ?>
                                            <option <?= ($cfg['auth'] == $mode ? 'selected class="selected"' : '') ?> value="<?= $mode ?>"><?= $mode ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row align-items-center mb-1">
                                <label for="http_user" class="form-text text-muted ml-2">HTTP auth user:</label>
                                <div class="col-auto ml-4">
                                    <input type="input" class="form-control" id="http_user" name="http_user" placeholder="my-user" value=<?= (!empty($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER'] : "" ?>>
                                </div>
                            </div>
                            <div class="form-row align-items-center mb-1">
                                <label for="http_passwd" class="form-text text-muted ml-2">HTTP auth password:</label>
                                <div class="col-auto">
                                    <input type="password" class="form-control" id="http_passwd" name="http_passwd" placeholder="my-http-p4sswd"/>
                                </div>
                            </div>
                            <div id="help" class="form-text text-muted small mt-1 ml-1">In case of issues run '<strong>auth.sh</strong>' (e.g. reset password or rollback auth method)</div>
                            <p></p>
                            <button type="submit" formaction="/auth/index.php" class="btn btn-outline-secondary">Apply</button>
                            <p></p>
                        </div>
                    </div>
                <?php endif ?>
            </form>
        <?php endif ?>
    <?php endif ?>

    <?php if (!empty($debug_vars) && $debug_vars === 1): ?>
        <div class="pt-3 pb-3"><hr></div>
        <pre><strong>DEBUG:</strong></pre>
        <?= (!empty($_SESSION['http_auth_result']) ? "<pre>\$_SESSION['http_auth_result']={$_SESSION['http_auth_result']}</pre>" : '' ) ?>
        <?= (!empty($_SESSION['glftpd_auth_result']) ? "<pre>\$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}</pre>" : '' ) ?>
        <?= (!empty($_SESSION['glftpd_auth_user']) ? "<pre>\$_SESSION['glftpd_auth_user']={$_SESSION['glftpd_auth_user']}</pre>" : '' ) ?>
        <?= (!empty($_SERVER['PHP_AUTH_USER']) ? "<pre>\$_SERVER['PHP_AUTH_USER']={$_SERVER['PHP_AUTH_USER']}</pre>" : '' ) ?>
        <?= (!empty($_SERVER['HTTP_CLIENT_IP']) ? "<pre>\$_SERVER['HTTP_CLIENT_IP']={$_SERVER['HTTP_CLIENT_IP']}</pre>" : '' ) ?>
        <pre>$_SERVER['REMOTE_ADDR']=<?= $_SERVER['REMOTE_ADDR'] ?></pre>
        <pre>$_SERVER['HTTP_X_FORWARDED_FOR']=<?= $_SERVER['HTTP_X_FORWARDED_FOR'] ?></pre>
        <pre>$_POST=<?= print_r($_POST, true)?></pre>
        <pre>$_SERVER=<?= print_r($_SERVER, true) ?></pre>
        <pre>$_SESSION=<?= print_r($_SESSION, true)?></pre>
    <?php endif ?>

</body>
</html>
