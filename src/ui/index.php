<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK index -- "Don't worry, be crappy"
 *--------------------------------------------------------------------------*/

// TODO:
// add self service for users (addip, invite), use gl auth
// switch to mvc framework (laravel|symfony)


/*--------------------------------------------------------------------------*/
/* DEBUG
/*--------------------------------------------------------------------------*/
// reset session https://localhost/index.php?reset=1
// var_dump(session_status());


/*--------------------------------------------------------------------------*/
/* GET CONFIG
/*--------------------------------------------------------------------------*/

if (!file_exists("config.php")) {
    header("Location: " . "error_4xx.html");
}

class cfg {
    public static function get($key) {
        $config = require 'config.php';
        return (isset($key) ? $config[$key] : false);
    }
}

if (cfg::get('debug') > 0) {
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(-1);
}


use shit\debug;
use shit\data;
use shit\docker;
use shit\local;
use shit\controller;

require_once 'debug.php';
require_once 'format.php';
require_once 'get_data.php';
require_once 'show.php';
require_once 'lib/neilime/ansi-escapes-to-html/src/AnsiEscapesToHtml/Highlighter.php';
require_once 'lib/PHP-Htpasswd/Htpasswd.php';

$debug = new debug;
$data = new data;
$htpasswd = new Htpasswd('/etc/nginx/.htpasswd');


// TODO: update http auth user/pass using php
/*
} if (cfg::get('http_auth')['username'] !== 'shit') {
    $htpasswd->addUser(cfg::get('http_auth')['username'], cfg::get('http_auth')['password'], Htpasswd::ENCTYPE_APR_MD5);   
} elseif (cfg::get('http_auth')['username'] == 'shit' && cfg::get('http_auth')['password'] !== 'EatSh1t') {
    $htpasswd->updateUser('shit', cfg::get('http_auth')['password'], Htpasswd::ENCTYPE_APR_MD5);
}
*/


// mode: docker(docker_commands.php) or local(local_commands.php)
// arrays with strtr {$vars}
// replace pairs: 
//    params -u {$username} -p {$password} -g {$group}   -i {$mask}   -f {$flags} -a {$gadmin}
//           -p {$pgroup}   -t {$tagline}  -k {$credits} -l {$logins} -r {$ratio}
//    global {$bin_dir} {$gl_ct} 

// docker: check for .dockerenv and disable service controls if webui is running in ct

$docker_sock_exists = false;
$local_dockerenv_exists = false;

if (cfg::get('mode') || cfg::get('mode') === "docker") {
    $docker = new docker;
    $docker_sock_exists = @fsockopen('unix:///run/docker.sock');
} else {
    $local = new local;
    $dockerenv = is_file("/.dockerenv");
    $local_dockerenv_exists = ($dockerenv ? $dockerenv : false);
}

if (cfg::get('debug')) {
    print "<span class='debug'><small>" . PHP_EOL;
    $debug->print(
        pos: 'index',
        debug_lvl: "<strong>" . cfg::get('debug') . "</strong>",
        debug_mode: "'<strong>" . cfg::get('mode') . "</strong>'",
        local_dockerenv_exists: "'<strong>" . $local_dockerenv_exists . "</strong>'",
        auth: "'<strong>" . cfg::get('auth') . "</strong>'",
        file: __FILE__
    );
    print "</span></small><br>" . PHP_EOL;
}

if (cfg::get('debug') > 9) {
    print "<span><small>" . PHP_EOL;
    $debug->print(
    pos: 'index',
        _SERVER_REMOTE_ADDR: $_SERVER['REMOTE_ADDR'],
        _SERVER_HTTP_CLIENT_IP: $_SERVER['HTTP_CLIENT_IP'],
        _SERVER_HTTP_X_FORWARDED_FOR: $_SERVER['HTTP_X_FORWARDED_FOR'],
        _SERVER_REQUEST_METHOD: $_SERVER['REQUEST_METHOD'],
    );
    $debug->print(pos: 'index', pre: true, _SERVER: $_SERVER);
    print "</span></small><br>" . PHP_EOL;
}

if (cfg::get('debug') > 1) {
    if (!empty($_POST)) {
        $debug->print(pre: true, pos: 'index', _POST: $_POST);
    }
}


/*--------------------------------------------------------------------------*/
/* SESSION / POSTDATA
/*--------------------------------------------------------------------------*/

// start session

if (!isset($_SESSION)) {
    session_start();
}

// recursively sanitize incoming data and store POST values as $_SESSION['postdata'][$x]

if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['user']) && $_GET['user'] !== "Select username...") {
    $_SESSION['postdata']['select_user'] = htmlspecialchars(trim($_GET['user']));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST)) {
        $_SESSION['postdata'] = array_map('htmlspecialchars_recursive', $_POST);
        $_SESSION['postdata'] = array_map('trim_recursive', $_POST);
        if (isset($_POST['user']) && empty($_SESSION['postdata']['select_user'])) {
            $_SESSION['postdata']['select_user'] = $_POST['user'];
        }
        if (isset($_POST['set_user'])) {
            $_SESSION['postdata']['select_user'] = $_POST['set_user'];
        }
    }
    $query_params = "";
    if (!empty($_SESSION['postdata']['select_user']) && $_SESSION['postdata']['select_user'] !== "Select username...") {
        $query_params = "?user={$_SESSION['postdata']['select_user']}";
    }
    if (cfg::get('debug') > 9) {
        $debug->print(pre: true, pos: 'index-1', var_dump__SESSION_postdata: var_dump($_SESSION['postdata']));
        $debug->print(pos: 'index-1', _array_sum__array_map__is_string__SESSION_postdata: array_sum(array_map('is_string', $_SESSION['postdata'])));
        $debug->print(pos: 'index-1', _array_sum__array_map__is_array__SESSION_postdata: array_sum(array_map('is_array', $_SESSION['postdata'])));
        $debug->print(pos: 'index-1', _array_sum__array_map__is_object__SESSION_postdata: array_sum(array_map('is_object', $_SESSION['postdata'])));
        $debug->print(pos: 'index-1', _count__SESSION_postdata: count($_SESSION['postdata']));
    }
    if (!empty($_SESSION) && !empty($_SESSION['postdata'])) {
        $sum_str = array_sum(array_map('is_string', $_SESSION['postdata']));
        $sum_arr = array_sum(array_map('is_array', $_SESSION['postdata']));
        if (($sum_str+$sum_arr) === count($_SESSION['postdata'])) {
            unset($_POST);
            header("Location: " . $_SERVER['PHP_SELF'] . $query_params);
            exit;
        } else {
            unset($_SESSION['postdata']);
        }
    }
}

if (cfg::get('debug') > 9 && !empty($_SESSION['postdata'])) {
    $debug->print(pre: true, pos: 'index-2', var_dump__SESSION_postdata: var_dump($_SESSION['postdata']));
    $debug->print(pos: 'index-2', array_sum__array_map__is_string__SESSION_postdata: array_sum(array_map('is_string', $_SESSION['postdata'])));
    $debug->print(pos: 'index-2', array_sum__array_map__is_array__SESSION_postdata: array_sum(array_map('is_array', $_SESSION['postdata'])));
    $debug->print(pos: 'index-2', array_sum__array_map__is_object__SESSION_postdata: array_sum(array_map('is_object', $_SESSION['postdata'])));
    $debug->print(pos: 'index-2', count__SESSION_postdata: count($_SESSION['postdata']));
}


/*--------------------------------------------------------------------------*/
/* TGEST
/*--------------------------------------------------------------------------*/

$__test = null;

// test: old = non-recursive postdata handling

if ($__test == 'old') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $_SESSION['postData'] = array_map('htmlspecialchars', $_POST);
        $_SESSION['postData'] = array_map('trim', $_POST);
        if (array_sum(array_map('is_string', $_SESSION['postData'])) == count($_SESSION['postData'])) {
            unset($_POST);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            unset($_SESSION['postData']);
        }
    }
}

// test: post = set postdata to POST

if ($__test == 'post') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $query_params = "";
        if (!empty($_SESSION['postdata']['select_user']) && $_SESSION['postdata']['select_user'] !== "Select username...") {
            $query_params = "?user={$_SESSION['postdata']['select_user']}";
        }
        $_SESSION['postdata'] = $_POST;
        unset($_POST);
        print $_SERVER['PHP_SELF'];
        header("Location: " . $_SERVER['PHP_SELF'] . $query_params);
        exit;
    }
}

// test: uncomment to force user 'glftpd'

//$_SESSION['postdata']['select_user'] = 'glftpd';
//$debug->print(ppos: 'index', get: $_GET['user'], select_user: $_SESSION['postdata']['select_user']);

if (cfg::get('debug') > 0) {
    unset($_SESSION['DEBUG']);
    $_SESSION['DEBUG'] = array();
}

if ((cfg::get('debug') > 1) && (isset($_SESSION['postdata']))) {
    $debug->print(pre: true, pos: 'index', _SESSION_postdata: $_SESSION['postdata']);
}

//$debug->print(pre: true, pos: 'index', _SESSION_results: $_SESSION['results'], , _SESSION_cmd_output: $_SESSION['cmd_output']);
//$debug->print(pre: true, pos: 'index', _SESSION: $_SESSION);

// get data from glftpd files

$_SESSION['update'] = array(
    'userfile' => 0, 'users' => 0, 'groups' => 0, 'pgroups' => 0, 'user_group' => 0, 'status' => 0
);

if ($data->check_user()) {
    $_SESSION['userfile'] = $data->get_userfile();
}

//$debug->print(pos: 'index', _check__SESSION_userfile: $_SESSION['userfile']);

foreach (['tagline', 'credits', 'logins', 'ratio'] as $field) {
    if (!empty($_SESSION['userfile'][strtoupper($field)])) {
        $_SESSION[$field] = $_SESSION['userfile'][strtoupper($field)];
    }
}
$data->get_users();
$data->get_groups();
$data->get_pgroups();   
$data->get_users_groups();
$data->get_status();


/*--------------------------------------------------------------------------*/
/* CONTROLLER
/*--------------------------------------------------------------------------*/

require_once 'controller.php';

// get any updated values, before loading template

if (cfg::get('debug') > 1) {
    $debug->print(pos: "index", _SESSION_update: $_SESSION['update']);
}

if (isset($_SESSION['update']['userfile']) && $_SESSION['update']['userfile']) {
    if ($data->check_user()) {
        $_SESSION['userfile'] = $data->get_userfile();
    }
    foreach (['tagline', 'credits', 'logins', 'ratio'] as $field) {
        if (!empty($_SESSION['userfile'][strtoupper($field)])) {
            $_SESSION[$field] = $_SESSION['userfile'][strtoupper($field)];
        }
    }
}
if (isset($_SESSION['update']['users']) && $_SESSION['update']['users']) {
    $data->get_users();
}
if (isset($_SESSION['update']['groups']) && $_SESSION['update']['groups']) {
    $data->get_groups();
}
if (isset($_SESSION['update']['pgroups']) && $_SESSION['update']['pgroups']) {
    $data->get_pgroups();
}
if (isset($_SESSION['update']['user_group']) && $_SESSION['update']['user_group']) {
    $data->get_users_groups();
}

show_notifications(
    docker_sock_exists: $docker_sock_exists,
    local_dockerenv_exists: $local_dockerenv_exists
);

unset($_SESSION['results']);

// $debug->print(pre: true, pos: 'index [2]', _SESSION_cmd_output: $_SESSION['cmd_output']);


/*--------------------------------------------------------------------------*/
/* TEMPLATE
/*--------------------------------------------------------------------------*/

require 'templates/main.html.php';

if (isset($_SESSION['modal'])) {
    show_modal();
    unset($_SESSION['modal']);
}

if (isset($_SESSION['cmd_output'])) {
    show_output();
    unset($_SESSION['cmd_output']);
}

unset($_SESSION['display_sort']);

unset($_SESSION['update']);

if (cfg::get('debug') > 0 && !empty($_SESSION['DEBUG'])) {
    print "<hr><pre>DEBUG: index <strong>\$_SESSION['DEBUG']</strong><br>" .
    print_r($_SESSION['DEBUG'], true) . "</pre>" . PHP_EOL;
}

if (cfg::get('spy')['enabled']) {
    print '<script type="text/javascript" src="spy.js"></script>' . PHP_EOL;
    if (!cfg::get('spy')['refresh']) {
        print '<script type="text/javascript">set_norefresh();</script>' . PHP_EOL;
    }
}

if (isset($_SESSION['update']['status']) && $_SESSION['update']['status']) {
    print '<script type="text/javascript">$("#notifications_status").remove();';
}
if (isset($_SESSION['update']['results']) && $_SESSION['update']['results']) {
    print '<script type="text/javascript">$("#notifications_results").remove();';
}

print <<<_EOF_
<script type="text/javascript" src="assets/js/btn_form.js"></script>
<script type="text/javascript" src="assets/js/btn_col.js"></script>
<script type="text/javascript" src="assets/js/modal_event.js"></script>
<script type="text/javascript" src="assets/js/theme.js"></script>
</body>
</html>
_EOF_;
