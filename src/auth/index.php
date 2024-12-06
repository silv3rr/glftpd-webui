<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK auth index
 *--------------------------------------------------------------------------*/

// TODO: change http user/passwd

// Needs nginx auth_request module:
//
//   From https://nginx.org/en/docs/http/ngx_http_auth_request_module.html
//
// " If the subrequest returns a 2xx response code, the access is allowed.
//   If it returns 401 or 403, the access is denied with the corresponding error code.
//   Any other response code returned by the subrequest is considered an error.
//   For the 401 error, the client also receives the "WWW-Authenticate" header from the subrequest response. "
//

// Auth flow:
//
//        .--------------------------------------.
//        |  (200)                               |
//       \/                            .---> already logged in
// /index.php ---> auth/index.php -> auth/login.php
//           (302)                     `---> auth/login.php(POST) ---> auth/index.php(200) ---> /index.php
//                                                         |                                     ^ 
//                                                         '---  (login: 'both' or 'glftpd')  ---'
//

if (!file_exists("/app/config.php")) {
    http_response_code(404);
    exit;
}

if (empty($_SESSION)) {
    session_start();
}

unset($_SESSION['DEBUG']);
$_SESSION['DEBUG'] = array();

// do not leave debug on, it breaks auth flow and could allow any user without logging in
//$auth_debug = 1;

require_once '/app/config.php';
require_once '/app/format.php';

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
    print "<pre>DEBUG: auth index.php \$cfg['auth']={$cfg['auth']}</pre>";
    if (!empty($cfg['http_auth'])) {
        print "<pre>DEBUG: auth index.php \$cfg['http_auth']=" . print_r($cfg['http_auth'], true) . "</pre>";
    }
    //print "<pre>DEBUG: auth index.php \$_SERVER=" . print_r($_SERVER, true) . "</pre>";
    print "<pre>DEBUG: auth index.php \$_SERVER['HTTP_COOKIE']={$_SERVER['HTTP_COOKIE']}</pre>";
    print "<pre>DEBUG: auth index.php \$_SERVER['HTTP_AUTHORIZATION']={$_SERVER['HTTP_AUTHORIZATION']}</pre>";
    print "<pre>DEBUG: auth index.php \$_SERVER['PHP_AUTH_USER']={$_SERVER['PHP_AUTH_USER']}</pre>";
    print "<pre>DEBUG: auth index.php \$_SERVER['PHP_AUTH_PW']={$_SERVER['PHP_AUTH_PW']}</pre>";
}

// no auth setting

if (!isset($cfg['auth']) || empty($cfg['auth'])) {
    exit;
}

if (!empty(($_SESSION['http_auth_result'])) && !is_string($_SESSION['http_auth_result'])) {
    unset($_SESSION['http_auth_result']);
}

if (!empty(($_SESSION['glftpd_auth_result'])) && !is_string($_SESSION['glftpd_auth_result'])) {
    unset($_SESSION['userfile']);
    unset($_SESSION['glftpd_auth_result']);
}

unset($_SESSION['change_auth_mode']);
unset($_SESSION['change_http_user_result']);
unset($_SESSION['change_http_password_result']);

// change settings auth mode or http user and password, if user is logged in

if (!empty($auth_debug) && $auth_debug === 1) {
    print("<pre>DEBUG: auth index.php \$_POST['change_auth_mode']=" . $_POST['change_auth_mode'] . " \$_POST['change_http_user']=" . $_POST['change_http_user'] . " \$_POST['change_http_password']=" . $_POST['change_http_password'] . "</pre>");
    print("<pre>DEBUG: auth index.php \$_SESSION['http_auth_result']=" . $_SESSION['http_auth_result'] . " \$_SESSION['glftpd_auth_result']=" . $_SESSION['glftpd_auth_result'] . "</pre>");
}

if ((!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] === "1" && !empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1") ||
    (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) {

    if (isset($_POST['change_auth_mode']) || (isset($_POST['change_http_user']) || isset($_POST['change_http_password']))) {

        if (isset($_POST['change_auth_mode'])) {
            $change_auth_mode = htmlspecialchars(trim($_POST['change_auth_mode']));
            unset($_POST['change_auth_mode']);
        } else {
            $change_auth_mode = $cfg['auth'];
        }
        if (!empty($_POST['change_http_user'])) {
            $change_http_user = htmlspecialchars(trim($_POST['change_http_user']));
            unset($_POST['change_http_user']);
        } else {
            $change_http_user = $cfg['http_auth']['username'];
        }
        if (!empty($_POST['change_http_password'])) {
            $change_http_password = htmlspecialchars(trim($_POST['change_http_password']));
            unset($_POST['change_http_password']);
        } else {
            $change_http_password = $cfg['http_auth']['password'];
        }
        unset($_POST['change_auth_mode']);
        unset($_POST['change_http_user']);
        unset($_POST['change_http_password']);

        if (!empty($auth_debug) && $auth_debug === 1) {
            print("<pre>DEBUG: auth index.php change settings (\$change_auth_mode={$change_auth_mode} change_http_user={$change_http_user} change_http_password={$change_http_password}</pre>");
        }

        $replace_pairs = array(
            '{$mode}' => $change_auth_mode,
            '{$username}' => $change_http_user,
            '{$password}' => $change_http_password,
        );

        if (isset($docker)) {
            $result = call_user_func_array([$docker, 'func'], array(['change_auth', $replace_pairs]));
        } elseif (isset($local)) {
            $result = call_user_func_array([$local, 'func'], array(['change_auth', $replace_pairs]));
        };

        if (!empty($result)) {
            if (is_array($result) && preg_grep('/RESULT:.*CONFIG_AUTH_MODE=0/', $result)) {
                $_SESSION['change_auth_mode_result'] = "1";
            }
            if (is_array($result) && preg_grep('/RESULT:.*CONFIG_USER_PASSWORD=0/', $result)) {
                if (!empty($change_http_user)) {
                    $_SESSION['change_http_user_result'] = "1";
                }
                if (!empty($change_http_pssword)) {
                    $_SESSION['change_http_password_result'] = "1";
                }
            };

            print("<pre>DEBUG: auth index.php change \$result=" . print_r($result, true) . "</pre>");
            print("<pre>DEBUG: auth index.php change \$_SESSION['DEBUG']=" . print_r($_SESSION['DEBUG'], true) . "</pre>");

            //clear previous auth state
            unset($change_auth_mode);
            unset($change_http_user);
            unset($change_http_password);
            unset($_SESSION['userfile']);
            //unset($_SESSION['http_auth_result']);
            //unset($_SESSION['glftpd_auth_result']);
            unset($_SESSION['glftpd_auth_user']);
            unset($_SERVER["HTTP_AUTHORIZATION"]);
            unset($_SERVER["PHP_AUTH_USER"]);
            unset($_SERVER["PHP_AUTH_PW"]);

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
            unset($_POST);
            if (isset($_SESSION)) {
                unset($_SESSION);
                session_destroy();
            }

            if (!empty($auth_debug) && $auth_debug !== 1) {
                header("Location: /auth/login.php", 200);
                exit;
            } else {
                http_response_code(200);
                exit;
            }
        } else {
            http_response_code(500);
            exit;
        }
    }
}

// check auth

if (!empty($cfg['auth'])) {

    // mode: 'none' (disabled)

    if ($cfg['auth'] === 'none') {
        http_response_code(200);
        exit;
    }

    // mode: 'basic' from web server (skip)

    if ($cfg['auth'] === 'basic') {
        return;
    }

    // mode: 'both' glftpd and php http auth
    //    1) $_SERVER[HTTP_AUTHORIZATION]
    //    2) $_SERVER["PHP_AUTH_USER"] and $_SERVER["PHP_AUTH_PW"]
    //    3) try prompting user with browser popup
    //    default=shit 'Basic c2hpdDpFYXRTaDF0'

    if ($cfg['auth'] === 'both') {
        $http_auth_username = NULL;
        $http_auth_password = NULL;
        // user is already authenticated
        if ((!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] === "1") &&
            (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) { 
            http_response_code(200);
            exit;
         }
         // no http auth received
         if (!isset($_SERVER["HTTP_AUTHORIZATION"]) || !isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER["PHP_AUTH_PW"]) ||
              empty($_SERVER["HTTP_AUTHORIZATION"]) ||  empty($_SERVER["PHP_AUTH_USER"]) ||   empty($_SERVER["PHP_AUTH_PW"])) {
            $_SESSION['http_auth_result'] = "0";
        }
        if (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
            $http_auth = explode(" ", $_SERVER["HTTP_AUTHORIZATION"]);
            $http_auth = explode(":", base64_decode($http_auth[1]));
            $http_auth_username = $http_auth[0];
            $http_auth_password = $http_auth[1];
        } elseif ((!empty($_SERVER["PHP_AUTH_USER"]) && !empty($_SERVER["PHP_AUTH_PW"])))  {
            $http_auth_username = $_SERVER["PHP_AUTH_USER"];
            $http_auth_password = $_SERVER["PHP_AUTH_PW"];
        }
        // get user/pass from client browser popup
        if (empty($http_auth_username) || empty($http_auth_password)) {
            header('WWW-Authenticate: Basic realm="Authentication Required"');
            header("HTTP/1.0 401 Unauthorized");
            exit;
        }
        if (!empty($auth_debug) && $auth_debug === 1) {
            print "<pre>DEBUG: auth index.php PHP_AUTH_USER={$_SERVER['PHP_AUTH_USER']} PHP_AUTH_PW={$_SERVER['PHP_AUTH_PW']}</pre>";
            print "<pre>DEBUG: auth index.php http_auth_username={$http_auth_username} http_auth_password={$http_auth_password}</pre>";
        }
        // verify user/pass
        if ( (!empty($http_auth_username) && $http_auth_username === $cfg['http_auth']['username']) &&
             (!empty($http_auth_password) && $http_auth_password === $cfg['http_auth']['password']) ) {
            $_SESSION['http_auth_result'] = '1';
            $_SESSION['http_auth_username'] = $http_auth_username;
            if (!empty($auth_debug) && $auth_debug === 1) {
                print " <pre>DEBUG: auth index.php http_auth MATCH (\$http_auth_username={$http_auth_username})</pre>";
            }
        }
    }

    // mode: 'glftpd' (and 'both')

    if ($cfg['auth'] === 'glftpd' || $cfg['auth'] === 'both') {
        if ( (!empty($_POST['glftpd_user']) && !empty($_POST['glftpd_user'])) &&
              (empty($_SESSION['glftpd_auth_result']) || (!empty($_SESSION['glftpd_auth_result'] && $_SESSION['glftpd_auth_result'] !== "1"))) ) {

            $glftpd_user = htmlspecialchars(trim($_POST['glftpd_user']));
            $glftpd_password = htmlspecialchars(trim($_POST['glftpd_password']));

            require_once 'ip-lib/ip-lib.php';

            function validate_hostmask($host) {
                $pattern = "/(?:.*@)?(?!-)(?!.*--)[A-Za-z0-9-]{1,63}(?<!-)(?:\.[A-Za-z0-9]{2,63})?$/";
                if (preg_match($pattern, $host)) {
                    return true;
                }
                return false;
            }

            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth index.php glftpd \$_POST=" . print_r($_POST, true). "</pre>";
                print "<pre>DEBUG: auth index.php glftpd \$_SESSION=" . print_r($_SESSION, true) . "</pre>";
            }

            // get flags and ip from user file
            $replace_pairs = array(
                '{$username}' => $glftpd_user,
            );
            if (isset($docker)) {
                $result = call_user_func_array([$docker, 'func'], array(['userfile_raw', $replace_pairs]));
            } elseif (isset($local)) {
                $result = call_user_func_array([$local, 'func'], array(['userfile_raw', $replace_pairs]));
            }
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth index.php userfile_raw result=" . print_r($result, true) . "</pre>";
            }
            $glftpd_flags = "";
            $glftpd_ip = [];
            if (!empty($result)) {
                foreach ($result as $line) {
                    $fields = explode(' ', $line, 2);
                    if ($fields[0] === 'FLAGS') {
                        $glftpd_flags =  $fields[1];
                    }
                    if ($fields[0] === 'IP') {
                        array_push($glftpd_ip, $fields[1]);
                    }
                }
            }
            $_SESSION['userfile'] = [];
            $_SESSION['userfile']['FLAGS'] = $glftpd_flags;
            $_SESSION['userfile']['IP'] = $glftpd_ip;
            // check ip mask
            $glftpd_ip_match = false;
            foreach($_SESSION['userfile']['IP'] as $glftpd_ip) {
                $glftpd_mask = explode ('@', $glftpd_ip)[1];
                if (!empty($auth_debug) && $auth_debug === 1) {
                    print "<pre>DEBUG: auth index.php glftpd \$glftpd_ip=$glftpd_ip -> \$glftpd_mask=$glftpd_mask</pre>";
                }
                $address = \IPLib\Factory::parseAddressString($_SERVER['HTTP_X_FORWARDED_FOR']);
                $range = \IPLib\Factory::parseRangeString($glftpd_mask);
                if ($range->contains($address)) {
                    if (!empty($auth_debug) && $auth_debug === 1) {
                        print "<pre>DEBUG: auth index.php glftpd ip MATCH ( {$_SERVER['HTTP_X_FORWARDED_FOR']} and $glftpd_mask )</pre>";
                    }
                    $glftpd_ip_match = true;
                    break;
                } elseif ((filter_var($glftpd_mask, FILTER_VALIDATE_DOMAIN) && validate_hostmask($glftpd_mask)) && (strpos($glftpd_ip, $glftpd_mask) !== false) ) {
                    if (!empty($auth_debug) && $auth_debug === 1) {
                        print "<pre>DEBUG: auth index.php glftpd ip MATCH = ($glftpd_ip and $glftpd_mask)</pre>";
                    }
                    $glftpd_ip_match = true;
                    break;
                }
            }
            if (!empty($cfg['glftpd_auth']['check_ip_mask']) && $cfg['glftpd_auth']['check_ip_mask'] === false) {
                $glftpd_ip_match = true;
            }
            // check for siteop flag
            $gl_flag_result = $_SESSION['userfile']['FLAGS'];
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth index.php glftpd \$_SESSION['userfile']['FLAGS']={$_SESSION['userfile']['FLAGS']}</pre>";
            }
            if (preg_match('/^[0-9A-Z]+$/', $gl_flag_result) && strpos($gl_flag_result, '1') !== false) {
                $gl_flag_match = true;
            }
            if (!empty($cfg['glftpd_auth']['check_siteop']) && $cfg['glftpd_auth']['check_siteop'] === false) {
                $gl_flag_match = true;
            }
            if (!empty($auth_debug) && $auth_debug === 1) {
                print "<pre>DEBUG: auth index.php glftpd \$glftpd_ip_match={$glftpd_ip_match} \$gl_flag_match={$gl_flag_match}</pre>";
            }
            // verify gl password
            if ($glftpd_ip_match && $gl_flag_match) {
                $replace_pairs = array(
                    '{$username}' => $glftpd_user,
                    '{$password}' => $glftpd_password
                );
                //$passchk = $data->func(['passchk', $replace_pairs]);
                if (isset($docker)) {
                    $passchk = call_user_func_array([$docker, 'func'], array(['passchk', $replace_pairs]));
                } elseif (isset($local)) {
                    $passchk = call_user_func_array([$local, 'func'], array(['passchk', $replace_pairs]));
                }
                if (is_array($passchk)) {
                    $result_passchk = $passchk[0];
                } else {
                    $result_passchk = $passchk; 
                }
                if (!empty($auth_debug) && $auth_debug === 1) {
                    print "<pre>DEBUG: auth index.php \$result_passchk=" . print_r($result_passchk, true) . "</pre>";
                }
                if (!empty($result_passchk) && ($result_passchk === "1" || $result_passchk === "MATCH")) {
                    $_SESSION['glftpd_auth_result'] = "1";
                    $_SESSION['glftpd_auth_user'] = $glftpd_user;
                    $_SESSION['glftpd_auth_mask'] = $glftpd_ip;
                    $_SESSION['glftpd_auth_flag'] = $gl_flag_result;
                    header("Location: /index.php", 200);
                    exit;
                }
            }
        }
        unset($glftpd_user);
        unset($glftpd_password);
    }

    // debug result
    if (!empty($auth_debug) && $auth_debug === 1) {
        if (($cfg['auth'] === 'glftpd') && ((!empty($_SESSION['glftpd_auth_result']) && ($_SESSION['glftpd_auth_result'] === "0")))) {
            print "<br>DEBUG: auth index.php NOK: \$_SESSION['glftpd_auth_result']={$_SESSION['glftpd_auth_result']}<br>" . PHP_EOL;
        }
        if (($cfg['auth'] === 'both') && ((!empty($_SESSION['http_auth_result']) && ($_SESSION['http_auth_result'] === "0")))) {
            print "<br>DEBUG: auth index.php NOK: \$_SESSION['http_auth_result']={$_SESSION['http_auth_result']}<br>" . PHP_EOL;
        }
    }

    // return response

    switch ($cfg['auth']) {
        case "basic":
            return;
        case "glftpd":
            if (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1") {
                http_response_code(200);
                exit;
            }
            break;
        case "both":
            if ((!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] === "1") &&
                (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) {
                http_response_code(200);
                exit;
            } elseif ((!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] === "1") ||
                      (!empty($_SESSION['glftpd_auth_result']) && $_SESSION['glftpd_auth_result'] === "1")) {
                header("HTTP/1.0 401 Unauthorized");
            }
            break;
    }
}

unset($_SESSION['glftpd_auth_user']);
unset($_SESSION['glftpd_auth_mask']);
unset($_SESSION['glftpd_auth_flag']);

// return 401

print('<!DOCTYPE html>');
print('<html lang="en"><head><title>401 Unauthorized</title></head><body>');
if(!empty($auth_debug) && $auth_debug === 1) {
    print("<pre>👉 Debug enabled...<br></pre>");
}
if (!empty($_SESSION['http_auth_result']) && $_SESSION['http_auth_result'] === "1") {
    print("<pre>✅ Http auth ok...<br></pre>");
}
print('<pre>⛔ Login failed, <a href="/auth/login.php"><strong>try again</strong></a></pre>');
print('</body></html>');
http_response_code(401);
exit;
