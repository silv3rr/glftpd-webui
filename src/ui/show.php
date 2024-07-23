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

//function show_notifications(bool $local_dockerenv, string $form = null, string $reload = null) {

function show_notifications(...$args) {
    $user = null;
    if (isset($args['form'])) {
        $form = $args['form'];
    }
    if (isset($args['local_dockerenv']) && $args['local_dockerenv']) {
        $form = "clear_user";
    }
    if (!empty($form)) {
        if ($form === "keep_user") {
            if ($_SESSION['postdata']['select_user'] !== "Select username...") {
                $user = $_SESSION["postdata"]["select_user"];
            }
        }
        if ($form === "clear_user" || empty($user)) {
            print "  <form id='form' action='/' method='POST'>" . PHP_EOL;
        } else {
            print "  <form id='form' action='?user={$user}' method='POST'>" . PHP_EOL;
        }
    }
    if (cfg::get('show_alerts')) {
        if (isset($args['docker_sock']) && !$args['docker_sock']) {
            print "  <div id='notification_dockersock' class='alert alert-danger' role='alert'>Cannot access '/run/docker.sock'</div>" . PHP_EOL;
            print "  <p></p>" . PHP_EOL;
        }
        if (isset($args['dockerenv']) && $args['dockerenv']) {
            print "  <div id='notification_dockenv' class='alert alert-warning' role='alert'>Running ui from container, glftpd not installed (disabled spy and service mgmt)</div>" . PHP_EOL;
            print "  <p></p>" . PHP_EOL;
        }
        if (!empty($_SESSION['status']['gotty']) && $_SESSION['status']['gotty'] === "open") {
            print "  <div id='notification_status' class='alert alert-warning' role='alert'>goTTY is still running," . PHP_EOL;
            print "  <button type='submit' name='termCmd' value='kill_gotty' class='btn btn-link color-custom pb-1'>click here</button> to close</div>" . PHP_EOL;
            print "  <p></p>" . PHP_EOL;
        } else {
            $_SESSION['update']['status'] = true;
        }
        if (!empty(($_SESSION['results']))) {
            foreach(($_SESSION['results']) as $result) {
                print "  <div id='notification_results' class='alert alert-primary' role='alert'>{$result}</div>" . PHP_EOL;
                print '  <p></p>' . PHP_EOL;
            }
        } else {
            $_SESSION['update']['results'] = true;
        }
    }
    if (!empty($form)) {
        if (!empty($reload) && $reload === "button") {
            print '    <button type="submit" class="btn btn-primary"><em class="fa-solid fa-retweet"></em>Reload</button>' . PHP_EOL;
        }
        print '    <p></p>' . PHP_EOL;
        print '  </form>' . PHP_EOL;
    }
    if (!empty($reload) && $reload === "auto") {
        $_SESSION['reload'] = true;
        print '<script type="text/javascript">setTimeout(function(){window.location.reload();},1);</script>';
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
    print JS_SCROLL;
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
