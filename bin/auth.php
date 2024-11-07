<?php

// glftpd-webui auth setup (like auth.sh)

if (!file_exists("/app/config.php")) {
    http_response_code(404);
    exit;
}

use shit\docker;
use shit\local;

require_once '/app/docker_api.php';
require_once '/app/local_exec.php';

if ($cfg['mode'] === "docker") {
    $docker = new docker;
} else {
    $local = new local;
}

switch ($mode) {
    // templates (need root)
    case "basic":
        if (!file_exists('/etc/nginx.htpasswd')) {
            file_put_contents('/etc/nginx.htpasswd', 'shit:$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740');
        };
        unlink('/etc/nginx/http.d/auth-server.conf.template', '/etc/nginx/http.d/auth-server.conf');
        copy('/etc/nginx/auth.d/auth_off.conf.template', '/etc/nginx/auth.d/auth_off.conf');
        copy('/etc/nginx/auth.d/auth_basic.conf.template', '/etc/nginx/auth.d/auth_basic.conf');
        break;
    case "glftpd":
        copy('/etc/nginx/http.d/auth-server.conf.template', '/etc/nginx/http.d/auth-server.conf');
        copy('/etc/nginx/auth.d/auth_off.conf.template', '/etc/nginx/auth.d/auth_off.conf');
        copy('/etc/nginx/auth.d/auth_request.conf.template', '/etc/nginx/auth.d/auth_request.conf');
        file_put_contents('/etc/nginx/auth.d/auth_basic.conf', 'auth_basic off;');
        break;
    case "both":
        $contents = file_get_contents('/app/config.php');
        $search = "/('http_auth'.*=>.*)\['username'.*=>.*'password'.*=>.*\],/";
        $replace = "$1" . "['username' => '" . $change_http_user . "' , 'password' => '". $change_http_password . "'],";
        $result = preg_replace($search, $replace, $contents);
        file_put_contents('/app/config.php', $result);
        unlink('/etc/nginx/.htpasswd');
        unlink('/etc/nginx/auth.d/auth_basic.conf');
        copy('/etc/nginx/http.d/auth-server.conf.template', '/etc/nginx/http.d/auth-server.conf');
        copy('/etc/nginx/auth.d/auth_off.conf.template', '/etc/nginx/auth.d/auth_off.conf');
        copy('/etc/nginx/auth.d/auth_request.conf.template', '/etc/nginx/auth.d/auth_request.conf');
        break;
    case "none":
        unlink('/etc/nginx/.htpasswd');
        unlink('/etc/nginx/http.d/auth-server.conf');
        unlink('/etc/nginx/auth.d/auth_off.conf');
        unlink('/etc/nginx/auth.d/auth_basic.conf');
        unlink('/etc/nginx/auth.d/auth_request.conf');
        break;
}
$contents = file_get_contents('/app/config.php');
$search = "/('auth'.*=>.*)\".*\",/";
$replace = "$1" . '"' . $mode . '"' . ',';
$result = preg_replace($search, $replace, $contents);
print_r($result);
file_put_contents('/app/config.php', $result);
if (isset($docker)) {
    $result = call_user_func_array([$docker, 'func'], ['nginx_reload']);
} elseif (isset($local)) {
    $result = call_user_func_array([$local, 'func'], ['nginx_reload']);
}

?>