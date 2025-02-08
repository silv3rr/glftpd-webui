<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK show html
 *--------------------------------------------------------------------------*/

// show user select option values

function option_user() {
    if (!empty($_SESSION) && !empty($_SESSION['users'])) {
        if (!empty($_SESSION['postdata']['select_user'])) {
            foreach ($_SESSION['users'] as $user) {
                if (!empty($user) ) {
                    if (count($_SESSION['users']) > 1 && $user === $_SESSION['postdata']['select_user']) {
                        continue;
                    }
                    print "<option value=\"$user\">$user</option>" . PHP_EOL;
                }
            }
        } else {
            foreach ($_SESSION['users'] as $user) {
                $user = trim(sanitize_string($user));
                if (!empty($user) ) {
                    print "<option value=\"$user\">$user</option>" . PHP_EOL;
                }
            }
        }
    }
}

// display notifications on top

function show_notifications(...$args) {
    if (cfg::get('show_alerts')) {
        if (isset($args['mode_config_set']) && is_bool($args['mode_config_set']) && !$args['mode_config_set']) {
            print "  <div id='notification_mode_config_set' class='alert alert-danger' role='alert'>Mode not set in config.php</div>" . PHP_EOL;
            print "  <p></p>" . PHP_EOL;
        }
        if (cfg::get('mode') === "docker") {
            if (isset($args['docker_sock_exists']) && is_bool($args['docker_sock_exists']) && !$args['docker_sock_exists']) {
                print "  <div id='notification_dockersock' class='alert alert-danger' role='alert'>Cannot access '/run/docker.sock'</div>" . PHP_EOL;
                print "  <p></p>" . PHP_EOL;
            }
        }
        if (cfg::get('mode') === "local")  {
            if (isset($args['local_dockerenv_exists']) && is_bool($args['local_dockerenv_exists']) && $args['local_dockerenv_exists']) {
                print "  <div id='notification_local_dockerenv_exists' class='alert alert-warning' role='alert'>Webui runs in container but glftpd does not, limited options available</div>" . PHP_EOL;
                print "  <p></p>" . PHP_EOL;
            }
        }
        if (!empty($_SESSION['status']['gotty']) && $_SESSION['status']['gotty'] === "open") {
            print " <form action='/' method='POST' >" . PHP_EOL;
            print "     <div id='notification_status' class='alert alert-warning' role='alert'>" . PHP_EOL;
            print "         goTTY is still running, <button type='submit' name='termCmd' value='kill_gotty' class='btn btn-link color-custom pb-1'>click here</button> to close" . PHP_EOL;
            print "     </div>" . PHP_EOL;
            print " </form>" . PHP_EOL;
        } else {
            $_SESSION['update']['status'] = true;
        }
        // show cmd results from controller
        if (!empty(($_SESSION['results']))) {
            foreach(($_SESSION['results']) as $result) {
                print "  <div id='notification_results' class='alert alert-<?= cfg::get('theme')['btn-color-1'] ?>' role='alert'>{$result}</div>" . PHP_EOL;
                print '  <p></p>' . PHP_EOL;
            }
        } else {
            $_SESSION['update']['results'] = true;
        }
        // XXX: moved reload to controller
        //if (isset($args['local_dockerenv_exists']) && is_bool($args['local_dockerenv_exists']) && $args['local_dockerenv_exists']) {
        //    $args['form'] = "";
        //}
        if (!empty($args['form'])) {
            if ($args['form'] === "keep_user" && $_SESSION['postdata']['select_user'] !== "Select username...") {
                $user = $_SESSION["postdata"]["select_user"];
            }
            if ($args['form'] === "clear_user" || !isset($user)) {
                print "  <form id='form' action='/' method='POST'>" . PHP_EOL;
            } else {
                print "  <form id='form' action='?user={$user}' method='POST'>" . PHP_EOL;
            }
            if (isset($args['reload']) && $args['reload'] === "button") {
                print '    <button type="submit" class="btn btn-<?= cfg::get("theme")["btn-color-1"] ?>"><em class="fa-solid fa-retweet"></em>Reload</button>' . PHP_EOL;
            }
            print '    <p></p>' . PHP_EOL;
            print '  </form>' . PHP_EOL;
        }
        if (isset($args['reload']) && $args['reload'] === "auto") {
            print '<script type="text/javascript">setTimeout(function(){window.location.reload();},1);</script>';
        }
    }
}

function show_output() {
    define("JS_SCROLL",
        '<script type="text/javascript">' . PHP_EOL .
        '   setTimeout(function(){$("html, body").animate({scrollTop: $(".bottom").offset().top}, 0);},1000);' . PHP_EOL .
        '</script>'
    );
    print '<h6 class="out">Output:</h6>' . PHP_EOL;
    print '<pre class="out">' . PHP_EOL;
    print $_SESSION['cmd_output'];
    print "</pre>" . PHP_EOL;
    if (cfg::get('auto_scroll')) {
        print JS_SCROLL;
    }
    unset($_SESSION['cmd_output']);
}

function show_modal() {
    if (isset($_SESSION['modal']['func']) && $_SESSION['modal']['func'] === 'tty') {
        print '<script type="text/javascript">'. PHP_EOL;
        print '  ttyModal();'. PHP_EOL;
        print '</script>' . PHP_EOL;
        print '<pre class="out" id="wait" style="color:red;">';
        print '>> LOADING, PLEASE WAIT..' . PHP_EOL;
        print '</pre>' . PHP_EOL;
    } else {
        print "<script type='text/javascript'>";
        print "  showModal(\"{$_SESSION['modal']['title']}\", \"{$_SESSION['modal']['text']}\");";
        print "</script>" . PHP_EOL;
    }
    unset($_SESSION['modal']);
}
