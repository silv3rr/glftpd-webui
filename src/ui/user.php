<?php

// nginx redirects pyspy's /user/<username> here

session_start();

header('Content-Type: application/json');

if (!empty($_GET["user"])) {
    print json_encode($_SESSION['userfile']);
} else {
    print json_encode(['user' => 'not found']);
}
