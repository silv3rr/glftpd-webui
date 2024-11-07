<?php

// change htpasswd (basic auth)

if (!empty($argv[1])) {
    $htpasswd = $argv[1];
}

require_once '/app/config.php';
require_once '/app/lib/PHP-Htpasswd/Htpasswd.php';
//require_once '/app/lib/apr1-md5/src/APR1_MD5.php';

$htpasswd_obj = new Htpasswd('/etc/nginx/.htpasswd');
$htpasswd_users = $htpasswd_obj->getUsers();

/*
print('DEBUG: htpasswd \$htpasswd_users[0]=' . array_keys($htpasswd_users)[0] . '<br>' . PHP_EOL . " \$htpasswd_users['shit']=" . $htpasswd_users['shit'] . '<br>' . PHP_EOL);
print('DEBUG: htpasswd \$htpasswd_obj->userExists=' . $htpasswd_obj->userExists('shit') . '</br>' . PHP_EOL);
print('DEBUG: htpasswd.php check shit=' . APR1_MD5::check('EatSh1t', '$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740') . "<br>" . PHP_EOL);
print('DEBUG: htpasswd.php check config=' . APR1_MD5::check($cfg['http_auth']['password'], $htpasswd_users['shit']) . "<br>" . PHP_EOL);
*/

$htpasswd_obj->updateUser(array_keys($htpasswd_users)[0], $htpasswd, Htpasswd::ENCTYPE_APR_MD5);

?>