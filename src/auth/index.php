<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK auth index
 *--------------------------------------------------------------------------*/
// TODO: add nginx htp auth digest / PHP_AUTH_DIGEST

// XXX: index.php(302) -> /auth/login.php -> /auth/index.php(POST) -> /auth/login.php

if (empty($_SESSION)) {
    session_start();
}

unset($_SESSION['DEBUG']);
$_SESSION['DEBUG'] = array();

// do not leave debug on, it breaks auth flow and allows any user without logging in
//$auth_debug = 1;

if (!file_exists("/app/config.php")) {
    $cfg['auth'] = 'basic';
    $cfg['http_auth'] = array('username' => null, 'password' => null);
}

require_once '/app/config.php';
require_once '/app/format.php';

//use shit\data;
//require_once '/app/get_data.php';
//$data = new data;
//var_dump($data);

use shit\docker;
require_once '/app/docker_api.php';
$docker = new docker;

print "<pre>" . print_r($docker, true) . "</pre>";


if(!empty($auth_debug) && $auth_debug === 1) {
    print "<pre>DEBUG: auth/index.php \$docker=" . print_r($docker, true) . "</pre>";
    print "<pre>DEBUG: auth/index.php \$cfg['auth']={$cfg['auth']}</pre>";
    if (!empty($cfg['http_auth'])) {
        print "<pre>DEBUG: auth/index.php \$cfg['http_auth']=" . print_r($cfg['http_auth'], true) . "</pre>";
    }
    //print "<pre>DEBUG: auth/index.php \$_SERVER=" . print_r($_SERVER, true) . "</pre>";
    print "<pre>DEBUG: auth/index.php \$_SERVER['HTTP_COOKIE']={$_SERVER['HTTP_COOKIE']}</pre>";
    print "<pre>DEBUG: auth/index.php \$_SERVER['HTTP_AUTHORIZATION']={$_SERVER['HTTP_AUTHORIZATION']}</pre>";
    print "<pre>DEBUG: auth/index.php \$_SERVER['PHP_AUTH_USER']={$_SERVER['PHP_AUTH_USER']}</pre>";
    print "<pre>DEBUG: auth/index.php \$_SERVER['PHP_AUTH_PW']={$_SERVER['PHP_AUTH_PW']}</pre>";
}


// if auth not enabled in config: exit

if (empty($cfg['auth']) || ($cfg['auth'] === 'none')) {
    http_response_code(200);
    exit();
}


// some input validation

if (empty($cfg['auth']) || ($cfg['auth'] === 'glftpd')) {
    unset($_SESSION['basic_auth_result']);
}

if (!is_string($_SESSION['basic_auth_result'])) {
    unset($_SESSION['basic_auth_result']);
}

if (!is_string($_SESSION['glftpd_auth_result'])) {
    unset($_SESSION['glftpd_auth_result']);
}

// user is already authenticated

if ( ($cfg['auth'] === 'both') &&
     ((!empty($_SESSION['basic_auth_result']) && $_SESSION['basic_auth_result'] === "1") &&
      (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) ) {
    http_response_code(200);
    exit();
}

// keep already selected user

if (!empty($_SESSION['postdata']['select_user']) && $_SESSION['postdata']['select_user'] !== "Select username...") {
    $query_params = "?user={$_SESSION['postdata']['select_user']}";
}

// check basic http auth

//   1) $_SERVER[HTTP_AUTHORIZATION] === 'Basic c2hpdDpFYXRTaDF0'  (default=shit:xxx)
//   2) $_SERVER["PHP_AUTH_USER"] / $_SERVER["PHP_AUTH_PW"]
//   3) try browser

if (!empty($cfg['auth']) && ($cfg['auth'] === 'basic' || $cfg['auth'] === 'both')) {
    if (!isset($_SERVER["HTTP_AUTHORIZATION"]) || !isset($_SERVER["PHP_AUTH_PW"])) {
        $_SESSION['basic_auth_result'] = "0";
    }
    if (empty($_SERVER["HTTP_AUTHORIZATION"]) || empty($_SERVER["PHP_AUTH_PW"]))  {
        $_SESSION['basic_auth_result'] = "0";
    }
    if ( (!empty($cfg['http_auth']['username']) && !empty($cfg['http_auth']['password'])) &&
         ( empty($_SESSION['basic_auth_result']) || (!empty($_SESSION['basic_auth_result'] && $_SESSION['basic_auth_result'] === "0")) ) ) {
        if (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
            $http_auth = explode(" ", $_SERVER["HTTP_AUTHORIZATION"]);
            $http_auth = explode(":", base64_decode($http_auth[1]));
            $http_auth_username = $http_auth[0];
            $http_auth_password = $http_auth[1];
            if(!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php basic http_auth_username=${http_auth_username} http_auth_password=${http_auth_password}</pre>";
            }
        } elseif ((!empty($_SERVER["PHP_AUTH_USER"]) && !empty($_SERVER["PHP_AUTH_PW"])))  {
            $http_auth_username = $_SERVER["PHP_AUTH_USER"];
            $http_auth_password = $_SERVER["PHP_AUTH_PW"];
            if(!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php basic PHP_AUTH_USER=${$_SERVER['PHP_AUTH_USER']} PHP_AUTH_PW=${$_SERVER['PHP_AUTH_PW']}</pre>";
            }
        } else {
            if (!empty($auth_debug) && $auth_debug === 1) {
                header('WWW-Authenticate: Basic realm="Authentication Required"');
                header("HTTP/1.0 401 Unauthorized");
            }
        }
        // verify user/pass
        if ( (!empty($http_auth_username) && $http_auth_username === $cfg['http_auth']['username']) &&
            (!empty($http_auth_password) && $http_auth_password === $cfg['http_auth']['password']) ) {
            $_SESSION['basic_auth_result'] = '1';
            $_SESSION['basic_auth_username'] = $http_auth_username;
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php basic auth MATCH (\$http_auth_username={$http_auth_username})</pre>";
            }
            if ($cfg['auth'] === 'basic') {
                if (!empty($auth_debug) && $auth_debug !== 1) {
                    header("Location: /index.php" . $query_params, true, 200);
                    exit();
                }
            }
        }
    }
}


// check glftpd auth
// ip-lib from https://github.com/mlocati/ip-lib

if ( (!empty($cfg['auth']) && ($cfg['auth'] === 'glftpd' || $cfg['auth'] === 'both')) &&
     (!empty($_POST['username']) && !empty($_POST['password'])) &&
     (empty($_SESSION['glftpd_auth_result']) || (!empty($_SESSION['glftpd_auth_result'] && $_SESSION['glftpd_auth_result'] === "1"))) ) {

    require_once 'lib/ip-lib/ip-lib.php';
    function validate_hostmask($host) {
        $pattern = "/(?:.*@)?(?!-)(?!.*--)[A-Za-z0-9-]{1,63}(?<!-)(?:\.[A-Za-z0-9]{2,63})?$/";
        if (preg_match($pattern, $host)) {
            return true;
        }
        return false;
    }

    if (!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php glftpd [1] \$_POST=" . print_r($_POST, true). "</pre>";
        //print "DEBUG: auth/index.php \$_GET= " . print_r($_GET, true). "<br>";
    }

    if (!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php glftpd [1] \$_SESSION=" . print_r($_SESSION, true) . "</pre>";
    }

    $username = htmlspecialchars(trim($_POST['username']));
    $password = htmlspecialchars(trim($_POST['password']));

    if(!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php glftpd [2] \$_POST=" . print_r($_POST, true) . "</pre>";
        //print "<pre>DEBUG: auth/index.php \$_GET=" . print_r($_GET, true) . "</pre>";
        //print "<pre>DEBUG: auth/index.php get_user()=". $data->get_user(); "</pre>";
        //print "<pre>DEBUG: auth/index.php  \$_SESSION=" . print_r($_SESSION, true) . "</pre>";
        //print "<pre>DEBUG: auth/index.php \$_SESSION['postdata']['select_user']=" . $_SESSION['postdata']['select_user'] . "</pre>";
    }

    //$_SESSION['postdata']['select_user'] = $username;

    $replace_pairs = array(
        '{$username}' => $username,
    );


    // TODO: test with data.php
    $test_data = 0;
    if(!empty($auth_debug) && $auth_debug === 1 && $test_data === 1) {
        $_SESSION['userfile'] = $data->get_userfile();
        func(['userfile_raw', $replace_pairs]);
        print "<pre>DEBUG: auth/index.php docker=" . print_r($docker, true). "</pre>";
        var_dump($data);
        print "<br>";
        //exit();
    }


    // get flags and ip from user file
    $result = call_user_func_array([$docker, 'func'], array(['userfile_raw', $replace_pairs]));

    if (!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php result=" . print_r($result, true) . "</pre>";
    }

    $flags = "";
    $ip = [];

    if (!empty($result)) {
        foreach ($result as $line) {
            $fields = explode(' ', $line, 2);
            if ($fields[0] === 'FLAGS') {
                $flags =  $fields[1];
            }
            if ($fields[0] === 'IP') {
                array_push($ip, $fields[1]);
            }
        }
    }

    if (!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php \$ip=" . print_r($ip, true) . "DEBUG: \$flags={$flags}" . "</pre>";
    }

    $_SESSION['userfile'] = [];
    $_SESSION['userfile']['FLAGS'] = $flags;
    $_SESSION['userfile']['IP'] = $ip;

    if(!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php glftpd select_user=" . print_r($_SESSION['postdata']['select_user'], true) . "</pre>";
    }

    if (!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php glftpd \$_SESSION['userfile']=" . print_r($_SESSION['userfile'], true) . "</pre>";
    }

    if (empty($_SESSION['userfile'])) {
        //http_response_code(401);
        header("HTTP/1.1 401 Unauthorized: userfile not found");
    }

    $result_ip = $_SESSION['userfile']['IP'];

    $ip_match = false;

    // check ip mask

    //foreach(explode(PHP_EOL, $result_ip) as $ip) {
    foreach($result_ip as $ip) {
        $mask = explode ('@', $ip)[1];
        if (!empty($auth_debug) && $auth_debug === 1) {
            print "<pre>DEBUG: auth/index.php glftpd \$ip=$ip -> \$mask=$mask</pre>";
        }
        $address = \IPLib\Factory::parseAddressString($_SERVER['HTTP_X_FORWARDED_FOR']);
        $range = \IPLib\Factory::parseRangeString($mask);
        if ($range->contains($address)) {
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php glftpd ip MATCH ( {$_SERVER['HTTP_X_FORWARDED_FOR']} and $mask )</pre>";
            }
            $ip_match = true;
            break;
        } elseif ((filter_var($mask, FILTER_VALIDATE_DOMAIN) && validate_hostmask($mask)) && (strpos($ip, $mask) !== false) ) {
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php glftpd ip MATCH = ($ip and $mask)</pre>";
            }
            $ip_match = true;
            break;
        }
    }

    // check for siteop flag

    $result_flag = $_SESSION['userfile']['FLAGS'];
    if (!empty($auth_debug) && $auth_debug === 1) {
        //print "DEBUG: auth/index.php cmd=$cmd result_flag=$result_flag<br>";
        print "<pre>DEBUG: auth/index.php glftpd \$_SESSION['userfile']['FLAGS']={$_SESSION['userfile']['FLAGS']}</pre>";
    }
    if (preg_match('/^[0-9A-Z]+$/', $result_flag) && strpos($result_flag, '1') !== false) {
        $flag_match = true;
    }

    if (!empty($auth_debug) && $auth_debug === 1) {
        print "<pre>DEBUG: auth/index.php glftpd \$ip_match={$ip_match} \$flag_match={$flag_match}</pre>";
    }

    // verify gl password

    if ($ip_match && $flag_match) {
        //$cmd = "[\"/glftpd/bin/passchk\", \"$username\", \"$password\", \"/glftpd/etc/passwd\"]";
        //print "DEBUG: auth/index.php siteop flag cmd=$cmd<br>";
        //$result_passchk = trim(substr($docker->exec("glftpd", $cmd), 8));
        $replace_pairs = array(
            '{$username}' => $username,
            '{$password}' => $password
        );
        //$result_passchk = $data->func(['passchk', $replace_pairs]);
        $result_passchk = call_user_func_array([$docker, 'func'], array(['passchk', $replace_pairs]));

        if (is_array($result_passchk)) {
            $result_passchk = $result_passchk[0];
        }

        if (!empty($auth_debug) && $auth_debug === 1) {
            print "<pre>DEBUG: auth/index.php \$result_passchk=" . print_r($result_passchk, true) . "</pre>";
        }

        if (!empty($result_passchk) && $result_passchk === "1") {
            $_SESSION['glftpd_auth_result'] = $result_passchk;
            $_SESSION['glftpd_auth_username'] = $username;
            $_SESSION['glftpd_auth_mask'] = $ip;
            $_SESSION['glftpd_auth_flag'] = $result_flag;

            //$_SESSION['postdata']['glftpd_auth_result'] = $result_passchk;

            if(!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php glftpd match \$_SESSION['glftpd_auth_username']={$ $_SESSION['glftpd_auth_username']}</pre>" . PHP_EOL;
            } else {
                header("Location: /index.php" . $query_params , true, 200);
                exit();
            }

            //print "<pre>DEBUG: auth \$_SESSION['postdata']=" . print_r($_SESSION['postdata'], true) . "</pre>";
        }
    }
}

// debug result

if (!empty($cfg['auth']) && (!empty($auth_debug) && $auth_debug === 1)) {
    if (($cfg['auth'] === 'glftpd') && ((!empty($_SESSION['glftpd_auth_result']) && ($_SESSION['glftpd_auth_result'] === "0")))) {
        print "<br>DEBUG: auth/index.php NOK: \$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}<br>" . PHP_EOL;
    }
    if (($cfg['auth'] === 'basic') && ((!empty($_SESSION['basic_auth_result']) && ($_SESSION['basic_auth_result'] === "0")))) {
        print "<br>DEBUG: auth/index.php NOK: \$_SESSION['basic_auth_result']={$_SESSION['basic_auth_result']}<br>" . PHP_EOL;
    }
}

// return response

if (!empty($cfg['auth'])) {
    if (($cfg['auth'] === 'basic') && ((!empty($_SESSION['basic_auth_result']) && ($_SESSION['basic_auth_result'] === "1")))) {
        if(!empty($auth_debug) && $auth_debug === 1) {
            print "<br>DEBUG: auth/index.php basic OK: \$_SESSION['basic_auth_result']={$_SESSION['basic_auth_result']}<br>" . PHP_EOL;
        }
        http_response_code(200);
        exit();
    }
    if (($cfg['auth'] === 'glftpd') && (!empty($_SESSION['glftpd_auth_result']) && ($_SESSION['glftpd_auth_result'] === "1"))) {
            if(!empty($auth_debug) && $auth_debug === 1) {
            print "<br>DEBUG: auth/index.php glftpd OK: \$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}<br>" . PHP_EOL;
        }
        http_response_code(200);
        exit();
    }

    if ( ($cfg['auth'] === 'both') &&
          ((!empty($_SESSION['basic_auth_result']) && $_SESSION['basic_auth_result'] === "1") &&
           (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) ) {

        if (!empty($auth_debug) && $auth_debug === 1) {
            print "<br>DEBUG: auth/index.php both OK:<br>" . PHP_EOL;
            print "&nbsp;&nbsp; \$_SESSION['basic_auth_result']={$_SESSION['basic_auth_result']}<br>" . PHP_EOL;
            print "&nbsp;&nbsp; \$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}<br>" . PHP_EOL;
        }
        http_response_code(200);
        exit();
    }
    if ( ($cfg['auth'] === 'both') && (empty($_SESSION['basic_auth_result']) || empty($_SESSION['glftpd_auth_result'])) ) {
        header("Location: /auth/login.php", true, 302);
    }
}

if (!empty($auth_debug) && $auth_debug !== 1) {
    unset($_SESSION['glftpd_auth_username']);
    unset($_SESSION['glftpd_auth_mask']);
    unset($_SESSION['glftpd_auth_flag']);
}

http_response_code(401);
exit();
