<?php

/*---------------------------------------------------------------------------*
*   GLFTPD:WEBUI configuration
*----------------------------------------------------------------------------*
*   'mode'  docker: uses docker api, runs commands in 'glftpd' container
*           local: exec commands locally (set 'runas_user')
*   'auth'  to change, set WEBUI_AUTH_MODE or run auth.sh
*           - basic: basic http authentication using nginx
*           - glftpd: login form checks gl user pass, +1 flag and addip
*           - both: combines basic/glftpd (checks 'http_auth' username/password)
*           - none: disable auth
*---------------------------------------------------------------------------*/

return $cfg = array(
    'auth'                      => "basic",
    'mode'                      => "docker",
    'show_more_opts'            => false,
    'show_alerts'               => true,
    'max_items'                 => 10,
    'debug'                     => 0,
    'http_auth'                 => ['username' => 'shit', 'password' => 'EatSh1t'],
    'spy'                       => ['enabled' => true, 'refresh' => true],
    'modal'                     => ["pywho" => true, "commands" => false],
    'title'                     => '<em class="fa-solid fa-left-right"></em>
                                    <span style="font-weight:bold;color:#5456c5;">GLFTPD</span>:
                                    <span style="text-decoration:underline;text-decoration-color:lightblue;">
                                    COMMAND CENTER</span>',
    'docker' => array(
      'api'               => "http://localhost/v1.44",
      'glftpd_container'  => "glftpd-webui-dev-glftpd-1",
      'bin_dir'           => "/glftpd/bin",
    ),
    'local' => array(
      'runas_user'        => "root",
      'bin_dir'           => "/usr/local/bin",
    ),
    'services' => array(
      "ftpd" => ['host' => "localhost", 'port' => "1337"],
      "sitebot" => ['host' => "localhost", 'port' => "3333"],
    ),
);
