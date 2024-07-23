<?php

/*-----------------------------------------------------------------------*
*   GLFTPD:WEBUI configuration
*------------------------------------------------------------------------*
*   'mode'  docker: uses docker api, runs commands in 'glftpd' container
*           local: exec commands locally (set 'runas_user')
*   'auth'  basic: use basic http authentication (optional: set user/pass)
*           glftpd: login form checks ftp username/password
*           (or 'both', or 'none' to disable)
*-----------------------------------------------------------------------*/

return $cfg = array(
    'auth'                      => "both",
    'mode'                      => "docker",
    'show_more_opts'            => false,
    'show_alerts'               => true,
    'max_items'                 => 10,
    'debug'                     => 9,
    'http_auth'                 => ['username' => "shit", 'password' => "EatSh1t"],
    'spy'                       => ['enabled' => false, 'refresh' => false],
    'modal'                     => ["pywho" => true, "commands" => false],
    'title'                     => '<em class="fa-solid fa-left-right"></em>
                                    <span style="font-weight:bold;color:#5456c5;">GLFTPD</span>:
                                    <span style="text-decoration:underline;text-decoration-color:lightblue;">
                                    COMMAND CENTER</span>',

    'docker' => array(
      'api'               => "http://localhost/v1.44",
      'glftpd_container'  => "glftpd",
      'bin_dir'           => "/glftpd/bin",
    ),

    'local' => array(
      'runas_user'        => "root",
      'bin_dir'           => "/usr/local/bin",
    ),
    
    'services' => array(
      "sitebot" => ['host' => "localhost", 'port' => "1337"],
      "ftpd" => ['host' => "localhost", 'port' => "3333"],
    ),

    // debug
    '__services' => array(
      "sitebot" => ["host" => "localhost", "port" => "80"],
      "ftpd" => ["host" => "localhost", "port" => "7331"]
    ),

);

