<?php

//print "<pre>DEBUG: [auth] index.php \$_SERVER=" . print_r($_SERVER, true) . "</pre> <br>";
//print "<pre>DEBUG: [auth] index.php \$cfg['auth']={$cfg['auth']}</pre><br>";
//print "<pre>DEBUG: [auth] index.php \$cfg['auth_basic']=" . print_r($cfg['auth_basic'], true) . "</pre> <br>";

//if (!isset($_SESSION)) {
//    session_start();
//}

$debug = true;

if (!file_exists("/app/config.php")) {
    $cfg['auth'] = 'basic';
    $cfg['http_auth'] = array('username' => '', 'password' => '');
}

use shit\data;

require_once '/app/config.php';
require_once '/app/get_data.php';

$data = new data;

//var_dump($data);

// skip if auth not enabled in config

if (!isset($cfg['auth']) || ($cfg['auth'] === 'none')) {
    http_response_code(200);
    exit();
}

$_SESSION['auth_result'] = null;

// check basic http auth

if (isset($cfg['auth']) && ($cfg['auth'] === 'basic' || $cfg['auth'] === 'both')) {
    if (isset($cfg['auth_basic']['username']) && isset($cfg['auth_basic']['password'])) {
        // default shit/... : $_SERVER[HTTP_AUTHORIZATION] === 'Basic c2hpdDpFYXRTaDF0'))
        // print "<pre>DEBUG: [auth] index.php: got auth basic";
        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
            $http_auth = explode(" ", $_SERVER["HTTP_AUTHORIZATION"]);
            $auth_basic = explode(":", base64_decode($http_auth[1]));
            $auth_basic_username = $auth_basic[0];
            $auth_basic_password = $auth_basic[1];
            // print "<pre>DEBUG: [auth] index.php: HTTP_AUTHORIZATION auth_basic_username=${auth_basic_username} auth_basic_password=${auth_basic_password}</pre> <br>";
        } elseif ((isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])))  {
            $auth_basic_username = $_SERVER["PHP_AUTH_USER"];
            $auth_basic_password = $_SERVER["PHP_AUTH_PW"];
            // print "<pre>DEBUG: [auth] index.php: got PHP_AUTH_USER</pre> <br>";
        } else {
            header("WWW-Authenticate: Basic realm=Website");
            header("HTTP/1.0 401 Unauthorized");
            exit;
        }
        if ($auth_basic_username === $cfg['auth_basic']['username'] && $auth_basic_password === $cfg['auth_basic']['password']) {
            // print "<pre>DEBUG: [auth] index.php: basic MATCH (\$auth_basic_username={$auth_basic_username})</pre> <br>";
            $_SESSION['auth_username'] = '&lt;basic auth&gt;';
            unset($_SESSION['auth_mask']);
            unset($_SESSION['auth_flag']);
            //$_SESSION['auth_result'] = $auth_basic_username;
            $_SESSION['auth_result'] = 'MATCH';
            //http_response_code(200);
            //header("Location: /", true, 200);
        }
        //http_response_code(401);
        //exit();
    }
}

// check glftpd auth

if (isset($cfg['auth']) && ($cfg['auth'] === 'glftpd' || $cfg['auth'] === 'both')) {
    // from https://github.com/mlocati/ip-lib
    require_once 'lib/ip-lib/ip-lib.php';
    if(isset($debug)) {
        //print_r($_SERVER);
        print "<br><br>DEBUG: \$_POST= " . print_r($_POST, true). "<br>";
        //print "DEBUG: \$_GET= " . print_r($_GET, true). "<br>";
    }

    if(isset($debug) && $debug) {
        print "<pre>DEBUG: [auth] \$_SESSION=" . print_r($_SESSION, true) . "</pre> <br>";
        //print "<pre>DEBUG: [auth] index.php: \$_SERVER=" . print_r($_SERVER, true) . "</pre> <br>";
    }
    
    function validate_hostmask($host) {
        $pattern = "/(?:.*@)?(?!-)(?!.*--)[A-Za-z0-9-]{1,63}(?<!-)(?:\.[A-Za-z0-9]{2,63})?$/";
        if (preg_match($pattern, $host)) {
            return true;
        }
        return false;
    }

    if ( (empty($_SESSION['auth_result']) || (!empty($_SESSION['auth_result']) && $_SESSION['auth_result'] === "NOMATCH")) && 
            (!empty($_POST['username']) && (!empty($_POST['password']))) ) {

        $username = htmlspecialchars(trim($_POST['username']));
        $password = htmlspecialchars(trim($_POST['password']));

        if(isset($debug) && $debug) {
            print "DEBUG: [auth] \$_POST=" . print_r($_POST, true) . "<br>";
        }

        // check addip mask

        //if(isset($debug) && $debug) {
        //    print "<pre>DEBUG: [auth] index.php: get_user()=". $data->get_user(); "</pre><br>";
        // }

        $_SESSION['postdata']['select_user'] = $username;

        $_SESSION['userfile'] = $data->get_userfile();

        // debug
        var_dump($data);
        print "<br>";
  
        if(isset($debug) && $debug) {
            print "<pre>DEBUG: [auth] index.php: select_user=" . print_r($_SESSION['postdata']['select_user'], true) . "</pre><br>";
        }
        //exit;

        if (isset($debug) && $debug) {
            print "<pre>DEBUG: [auth] index.php: \$_SESSION['userfile']=" . print_r($_SESSION['userfile'], true) . "</pre><br>";
        }

        if (!isset($_SESSION['userfile'])) {
            //http_response_code(401);
            header("HTTP/1.1 401 Unauthorized: userfile not found");
        }

        $result_addip = $_SESSION['userfile']['IP'];

        $addip_match = false;

        //foreach(explode(PHP_EOL, $result_addip) as $addip) {
        foreach($result_addip as $addip) {
            $mask = explode ('@', $addip)[1];
            if (isset($debug))
                print "DEBUG: [auth] index.php: \$addip=$addip -> \$mask=$mask<br>";
            $address = \IPLib\Factory::parseAddressString($_SERVER['HTTP_X_FORWARDED_FOR']);
            $range = \IPLib\Factory::parseRangeString($mask);
            if ($range->contains($address)) {
                if (isset($debug)) {
                    print "DEBUG: [auth] index.php: ip MATCH ( {$_SERVER['HTTP_X_FORWARDED_FOR']} and $mask )<br>";
                }
                $addip_match = true;
                break;
                //echo "DEBUG: auth OK filter/validate domain: " . $domain . "<br>";            
            } elseif ((filter_var($mask, FILTER_VALIDATE_DOMAIN) && validate_hostmask($mask)) && (strpos($addip, $mask) !== false) ) {
                if (isset($debug) && $debug) {
                    print "DEBUG: [auth] addip host MATCH = ($addip and $mask)<br>";
                }
                $addip_match = true;
                break;
            }
        }

        // check for siteop flag

        $result_flag = $_SESSION['userfile']['FLAGS'];

        if (isset($debug)) {
            //print "DEBUG: [auth] cmd=$cmd -> result_flag=$result_flag<br>";
            print "DEBUG: [auth] index.php: \$_SESSION['userfile']['FLAGS']={$_SESSION['userfile']['FLAGS']}<br>";
        }

        if (preg_match('/^[0-9A-Z]+$/', $result_flag) && strpos($result_flag, '1') !== false) {
            $flag_match = true;
        }

        if ($addip_match && $flag_match) {
            //$cmd = "[\"/glftpd/bin/passchk\", \"$username\", \"$password\", \"/glftpd/etc/passwd\"]";
            //print "DEBUG: [auth] sindex.php: iteop flag cmd=$cmd<br>";
            $_SESSION['auth_result'] = null;
            //$result_passchk = trim(substr($docker->exec("glftpd", $cmd), 8));
            $replace_pairs = array(
                '{$username}' => $username,
                '{$password}' => $password
            );
            $result_passchk = $data->func(['passchck', $replace_pairs]);
            if (is_array($result_passchk)) {
                $result_passchk = $result_passchk[0];
            }

            print "<br>";
            print "<pre>DEBUG: [auth] \$result_passchk=" . print_r($result_passchk, true) . "</pre><br>";

            if (!empty($result_passchk) && $result_passchk === "MATCH") {
                $_SESSION['auth_result'] = $result_passchk;
                $_SESSION['auth_username'] = $username;
                $_SESSION['auth_mask'] = $addip;
                $_SESSION['auth_flag'] = $result_flag;
                if (!isset($debug)) {
                    header("Location: /", true, 200);
                }
            }
        }
    }

    if(isset($debug)) {
        if (!empty($_SESSION['auth_result'])) {
            print "DEBUG: [auth] OK \$_SESSION['auth_result']={$_SESSION['auth_result']}";
        } else {
            print "DEBUG: [auth] NOK \$_SESSION['auth_result']={$_SESSION['auth_result']}";
        }
    }

}

if (!empty($_SESSION['auth_result']) && $_SESSION['auth_result'] === "MATCH") {
    http_response_code(200);
} else {
    unset($_SESSION['auth_username']);
    unset($_SESSION['auth_mask']);
    unset($_SESSION['auth_flag']);
    http_response_code(401);
}

exit();
