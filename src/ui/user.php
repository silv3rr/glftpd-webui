<?php

// nginx redirects pyspy's /user/<username> here

session_start();

header('Content-Type: application/json');

if (!empty($_GET["user"])) {
    print json_encode(
        array(
            'LINK' => "<a href=/index.php?user={$_GET['user']}>CHANGE USER</a>",
            'LOGINS' => $_SESSION['userfile']['LOGINS'],
            'FLAGS' => $_SESSION['userfile']['FLAGS'],
            'IP' => $_SESSION['userfile']['IP'],
            'RATIO' => $_SESSION['userfile']['RATIO'],
            'CREDITS' => $_SESSION['userfile']['CREDITS'],
        )
    );
} else {
    print json_encode(['user' => 'not found']);
}
