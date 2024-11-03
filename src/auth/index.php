<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK auth index
 *--------------------------------------------------------------------------*/

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
    $cfg['mode'] = 'docker';
}

require_once '/app/config.php';
require_once '/app/format.php';

//use shit\data;
//require_once '/app/get_data.php';
//$data = new data;
//var_dump($data);

use shit\docker;
use shit\local;
require_once '/app/docker_api.php';
require_once '/app/local_exec.php';

if ($cfg['mode'] === "docker") {
    $docker = new docker;
} else {
    $local = new local;
}

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

if (!empty(($_SESSION['http_auth_result'])) && !is_string($_SESSION['http_auth_result'])) {
    unset($_SESSION['http_auth_result']);
}

if (!empty(($_SESSION['glftpd_auth_result'])) && !is_string($_SESSION['glftpd_auth_result'])) {
    unset($_SESSION['glftpd_auth_result']);
}

// auth disabled

unset($_SESSION['http_passwd_result']);

if (!empty($auth_debug) && $auth_debug === 1) {
    print("<pre>DEBUG: auth index.php \$_POST['auth_mode']=" . $_POST['auth_mode'] . " \$_POST['http_passwd']=" . $_POST['http_passwd'] . "</pre>");
    print("<pre>DEBUG: auth index.php \$_SESSION_['http_auth_result']=" . $_SESSION['http_auth_result'] . " \$_SESSION['glftpd_auth_result']=" . $_SESSION['glftpd_auth_result'] . "</pre>");
}

if ((!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] === "1") &&
    (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) {

    $auth_mode = (isset($_POST['auth_mode'])) ? htmlspecialchars(trim($_POST['auth_mode'])) : NULL;
    $http_user = (isset($_POST['http_user'])) ? htmlspecialchars(trim($_POST['http_user'])) : NULL;
    $http_passwd = (isset($_POST['http_passwd'])) ? htmlspecialchars(trim($_POST['http_passwd'])) : NULL;

    print("<pre>DEBUG: auth index.php \$auth_mode={$auth_mode}</pre>");
    if (!empty($auth_mode)) {
        $replace_pairs = array('{$mode}' => $auth_mode);
        if (isset($docker)) {
            $result = call_user_func_array([$docker, 'func'], array(['auth_mode', $replace_pairs]));
        } elseif (isset($local)) {
            $result = call_user_func_array([$local, 'func'], array(['auth_mode', $replace_pairs]));
        }
        //print("<pre>DEBUG: auth index.php auth_mode result=" . print_r($result, true) . "</pre>");
        unset($auth_mode);
        unset($_POST['auth_mode']);
        unset($_SESSION['http_auth_result']);
        unset($_SESSION['userfile']);
        unset($_SESSION['glftpd_auth_result']);
        unset($_SESSION['glftpd_auth_user']);

        //logout
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
        if (isset($_SESSION)) {
            unset($_SESSION);
            session_destroy();
        }

        $_SESSION['auth_mode_result'] = "1";
        exit;
        //if(!empty($auth_debug) && $auth_debug !== 1) {
        //    header("Location: /auth/login.php", 200);
        //    exit;
        //}
    }

    if (!empty($http_user) || !empty($http_passwd)) {
        $contents = file_get_contents('/app/config.php');
        $search = "/('http_auth'.*=>.*)\['username'.*=>.*'password'.*=>.*\],/";
        $replace = "$1" . "['username' => '" . $http_user . "' , 'password' => '". $http_passwd . "'],";
        $result = preg_replace($search, $replace, $contents);
        //print('<pre>DEBUG: auth index.php http_passwd \$result=' . print_r($result, true) . '</pre>');
        unset($http_user);
        unset($http_passwd);
        unset($_POST['http_passwd']);
        unset($_SESSION['http_auth_result']);
        $_SESSION['http_passwd_result'] = "1";
        file_put_contents('/app/config.php', $result);
        //header("Location: /auth/login.php", 200);
        http_response_code(200);
        exit;
    }
}

if (!empty($cfg['auth'])) {

    // user is already authenticated

    if ( ($cfg['auth'] === 'both') &&
        ((!empty($_SESSION['basic_auth_result']) && $_SESSION['basic_auth_result'] === "1") &&
        (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) )
    {
        http_response_code(200);
        exit;
    }

    // check basic http auth

    //   1) $_SERVER[HTTP_AUTHORIZATION]   (default=shit -> 'Basic c2hpdDpFYXRTaDF0')
    //   2) $_SERVER["PHP_AUTH_USER"] / $_SERVER["PHP_AUTH_PW"]
    //   3) try prompting user with browser pop up

    if ($cfg['auth'] === 'basic' || $cfg['auth'] === 'both') {
        if (!isset($_SERVER["HTTP_AUTHORIZATION"]) || !isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER["PHP_AUTH_PW"]) ||
             empty($_SERVER["HTTP_AUTHORIZATION"]) ||  empty($_SERVER["PHP_AUTH_USER"]) ||  empty($_SERVER["PHP_AUTH_PW"]))
        {
            $_SESSION['basic_auth_result'] = "0";
        }
        if (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
            $http_auth = explode(" ", $_SERVER["HTTP_AUTHORIZATION"]);
            $http_auth = explode(":", base64_decode($http_auth[1]));
            $http_auth_username = $http_auth[0];
            $http_auth_password = $http_auth[1];
            if (empty($http_auth_username) || empty($http_auth_password)) {
                header('WWW-Authenticate: Basic realm="Authentication Required"');
                header("HTTP/1.0 401 Unauthorized");
            }
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php basic http_auth_username=${http_auth_username} http_auth_password=${http_auth_password}</pre>";
            }
        } elseif ((!empty($_SERVER["PHP_AUTH_USER"]) && !empty($_SERVER["PHP_AUTH_PW"])))  {
            $http_auth_username = $_SERVER["PHP_AUTH_USER"];
            $http_auth_password = $_SERVER["PHP_AUTH_PW"];
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php basic PHP_AUTH_USER=${$_SERVER['PHP_AUTH_USER']} PHP_AUTH_PW=${$_SERVER['PHP_AUTH_PW']}</pre>";
            }
        } else {
            header('WWW-Authenticate: Basic realm="Authentication Required"');
            header("HTTP/1.0 401 Unauthorized");
        }

        // verify user/pass
        if ( (!empty($http_auth_username) && $http_auth_username === $cfg['http_auth']['username']) &&
             (!empty($http_auth_password) && $http_auth_password === $cfg['http_auth']['password']) )
        {
            $_SESSION['basic_auth_result'] = '1';
            $_SESSION['basic_auth_username'] = $http_auth_username;
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php basic auth MATCH (\$http_auth_username={$http_auth_username})</pre>";
            }
            if ($cfg['auth'] === 'basic') {
                if (!empty($auth_debug) && $auth_debug !== 1) {
                    header("Location: /index.php" . $query_params, true, 200);
                    exit;
                }
            }
        }
    }

    // check glftpd auth

    if ($cfg['auth'] === 'glftpd' || $cfg['auth'] === 'both') {
        if ( (!empty($_POST['glftpd_user']) && !empty($_POST['glftpd_password'])) &&
              (empty($_SESSION['glftpd_auth_result']) || (!empty($_SESSION['glftpd_auth_result'] && $_SESSION['glftpd_auth_result'] === "1"))) )
        {
            // from https://github.com/mlocati/ip-lib
            require_once 'lib/ip-lib/ip-lib.php';

            function validate_hostmask($host) {
                $pattern = "/(?:.*@)?(?!-)(?!.*--)[A-Za-z0-9-]{1,63}(?<!-)(?:\.[A-Za-z0-9]{2,63})?$/";
                if (preg_match($pattern, $host)) {
                    return true;
                }
                return false;
            }
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php glftpd \$_POST=" . print_r($_POST, true). "</pre>";
                //print "DEBUG: auth/index.php \$_GET= " . print_r($_GET, true). "<br>";
                print "<pre>DEBUG: auth/index.php glftpd \$_SESSION=" . print_r($_SESSION, true) . "</pre>";
            }

            $glftpd_user = htmlspecialchars(trim($_POST['glftpd_user']));
            $glftpd_password = htmlspecialchars(trim($_POST['glftpd_password']));

            // get flags and ip from user file

            //print "<pre>DEBUG: auth/index.php get_user()=". $data->get_user(); "</pre>";
            //$_SESSION['userfile'] = $data->get_userfile();
            $replace_pairs = array(
                '{$username}' => $glftpd_user,
            );
            if ($cfg['mode'] || $cfg['mode'] === "docker") {
                $result = call_user_func_array(
                    [$docker, 'func'], array(['userfile_raw', $replace_pairs])
                );
            } else {
                $result = call_user_func_array(
                    [$local, 'func'], array(['userfile_raw', $replace_pairs])
                );
            }

            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php userfile_raw result=" . print_r($result, true) . "</pre>";
            }

            $gl_flags = "";
            $gl_ip = [];

            if (!empty($result)) {
                foreach ($result as $line) {
                    $fields = explode(' ', $line, 2);
                    if ($fields[0] === 'FLAGS') {
                        $gl_flags =  $fields[1];
                    }
                    if ($fields[0] === 'IP') {
                        array_push($gl_ip, $fields[1]);
                    }
                }
            }

            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php \$gl_ip=" . print_r($gl_ip, true) . "DEBUG: auth/index.php \$gl_flags={$gl_flags}" . "</pre>";
            }

            $_SESSION['userfile'] = [];
            $_SESSION['userfile']['FLAGS'] = $gl_flags;
            $_SESSION['userfile']['IP'] = $gl_ip;

            if(!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php glftpd select_user=" . print_r($_SESSION['postdata']['select_user'], true) . "</pre>";
                print "<pre>DEBUG: auth/index.php glftpd \$_SESSION['userfile']=" . print_r($_SESSION['userfile'], true) . "</pre>";
            }

            if (empty($_SESSION['userfile'])) {
                // http_response_code(401);
                header("HTTP/1.1 401 Unauthorized: userfile not found");
            }

            // check ip mask

            $gl_ip_match = false;
            //foreach(explode(PHP_EOL, $result_ip) as $gl_ip) {
            foreach($_SESSION['userfile']['IP'] as $gl_ip) {
                $gl_mask = explode ('@', $gl_ip)[1];
                if (!empty($auth_debug) && $auth_debug === 1) {
                    print "<pre>DEBUG: auth/index.php glftpd \$gl_ip=$gl_ip -> \$gl_mask=$gl_mask</pre>";
                }
                $address = \IPLib\Factory::parseAddressString($_SERVER['HTTP_X_FORWARDED_FOR']);
                $range = \IPLib\Factory::parseRangeString($gl_mask);
                if ($range->contains($address)) {
                    if (!empty($auth_debug) && $auth_debug === 1) {
                        print "<pre>DEBUG: auth/index.php glftpd ip MATCH ( {$_SERVER['HTTP_X_FORWARDED_FOR']} and $gl_mask )</pre>";
                    }
                    $gl_ip_match = true;
                    break;
                } elseif ((filter_var($gl_mask, FILTER_VALIDATE_DOMAIN) && validate_hostmask($gl_mask)) && (strpos($gl_ip, $gl_mask) !== false) ) {
                    if (!empty($auth_debug) && $auth_debug === 1) {
                        print "<pre>DEBUG: auth/index.php glftpd ip MATCH = ($gl_ip and $gl_mask)</pre>";
                    }
                    $gl_ip_match = true;
                    break;
                }
            }

            // check for siteop flag

            $gl_flag_result = $_SESSION['userfile']['FLAGS'];
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php glftpd \$_SESSION['userfile']['FLAGS']={$_SESSION['userfile']['FLAGS']}</pre>";
            }
            if (preg_match('/^[0-9A-Z]+$/', $gl_flag_result) && strpos($gl_flag_result, '1') !== false) {
                $gl_flag_match = true;
            }
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth/index.php glftpd \$gl_ip_match={$gl_ip_match} \$gl_flag_match={$gl_flag_match}</pre>";
            }

            // verify gl password

            if ($gl_ip_match && $gl_flag_match) {
                $replace_pairs = array(
                    '{$username}' => $glftpd_user,
                    '{$password}' => $glftpd_password
                );
                //$passchk = $data->func(['passchk', $replace_pairs]);
                if ($cfg['mode'] || $cfg['mode'] === "docker") {
                    $passchk = call_user_func_array([$docker, 'func'], array(['passchk', $replace_pairs]));
                } else {
                    $passchk = call_user_func_array([$local, 'func'], array(['passchk', $replace_pairs]));
                }
                if (is_array($passchk)) {
                    $result_passchk = $passchk[0];
                } else {
                    $result_passchk = $passchk;
                }
                if (!empty($auth_debug) && $auth_debug === 1) {
                    print "<pre>DEBUG: auth/index.php \$result_passchk=" . print_r($result_passchk, true) . "</pre>";
                }
                if (!empty($result_passchk) && $result_passchk === "1") {
                    $_SESSION['glftpd_auth_result'] = $result_passchk;
                    $_SESSION['glftpd_auth_user'] = $glftpd_user;
                    $_SESSION['glftpd_auth_mask'] = $gl_ip;
                    $_SESSION['glftpd_auth_flag'] = $gl_flag_result;
                    header("Location: /index.php" . $query_params , true, 200);
                    exit;
                }
            }
        }
    }

    // debug result

    if (!empty($auth_debug) && $auth_debug === 1) {
        if (($cfg['auth'] === 'glftpd') && ((!empty($_SESSION['glftpd_auth_result']) && ($_SESSION['glftpd_auth_result'] === "0")))) {
            print "<br>DEBUG: auth/index.php NOK: \$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}<br>" . PHP_EOL;
        }
        if (($cfg['auth'] === 'basic') && ((!empty($_SESSION['basic_auth_result']) && ($_SESSION['basic_auth_result'] === "0")))) {
            print "<br>DEBUG: auth/index.php NOK: \$_SESSION['basic_auth_result']={$_SESSION['basic_auth_result']}<br>" . PHP_EOL;
        }
    }

    // return response

    if ($cfg['auth'] === 'basic') {
        if (!empty($_SESSION['basic_auth_result'] && $_SESSION['basic_auth_result'] === "1")) {
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<br>DEBUG: auth/index.php basic OK: \$_SESSION['basic_auth_result']={$_SESSION['basic_auth_result']}<br>" . PHP_EOL;
            }
            http_response_code(200);
            exit;
        }
    }
    if ($cfg['auth'] === 'glftpd') {
        if (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1") {
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<br>DEBUG: auth/index.php glftpd OK: \$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}<br>" . PHP_EOL;
            }
            http_response_code(200);
            exit;
        }
    }
    if ($cfg['auth'] === 'both') {
        if ((!empty($_SESSION['basic_auth_result']) &&  $_SESSION['basic_auth_result'] === "1") ||
            (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) {
            header("Location: /auth/login.php", true, 302);
        }
        if ((!empty($_SESSION['basic_auth_result']) &&  $_SESSION['basic_auth_result'] === "1") &&
            (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) {
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<br>DEBUG: auth/index.php both OK:<br>" . PHP_EOL;
                print " - \$_SESSION['basic_auth_result']={$_SESSION['basic_auth_result']}<br>" . PHP_EOL;
                print " - \$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}<br>" . PHP_EOL;
            }
            http_response_code(200);
            exit;
        }
    }
}

if (!empty($auth_debug) && $auth_debug !== 1) {
    unset($_SESSION['glftpd_auth_user']);
    unset($_SESSION['glftpd_auth_mask']);
    unset($_SESSION['glftpd_auth_flag']);
}

print('<!DOCTYPE html><html lang="en"><body>');
print('<pre>⛔ Login failed, <a href="/auth/login.php">try again</a></pre>');
print('</body></html>');
http_response_code(401);
exit;
