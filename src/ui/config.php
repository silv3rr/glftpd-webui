<?php

/*---------------------------------------------------------------------------*
*   GLFTPD:WEBUI configuration
*---------------------------------------------------------------------------*/

// TODO: allow changing these with env vars

return $cfg = array(
    'auth'                      => "both",
    'mode'                      => "local",
    'show_more_opts'            => false,
    'show_alerts'               => true,
    'max_items'                 => 10,
    'debug'                     => 0,
    'http_auth'                 => ['username' => 'shit' , 'password' => 'EatSh1t'],
    'spy'                       => ['enabled' => true, 'refresh' => true],
    'modal'                     => ["pywho" => true, "commands" => false],
    'title'                     => '<em class="fa-solid fa-left-right"></em>
                                    <span style="font-weight:bold;color:#5456c5;">GLFTPD</span>:
                                    <span style="text-decoration:underline;text-decoration-color:lightblue;">
                                    COMMAND CENTER</span>',
                                    'services' => array(
                                      "glftpd" => ['host' => "localhost", 'port' => "1336"],
                                      "sitebot" => ['host' => "localhost", 'port' => "80"],
                                  ),
                              
    'docker' => array(
        'api'               => "http://localhost/v1.44",
        'glftpd_container'  => "glftpd-webui-dev-glftpd-1",
        'bin_dir'           => "/glftpd/bin",
        'filemanager'  => array(
            'dirs' => array (
                'Glftpd Site' => 'glftpd/site',
            ),
            'files' => array (
                'glftpd.conf'   => 'glftpd',
                'eggdrop.conf'  => 'glftpd/sitebot',
                'ngBot.conf'    => 'glftpd/sitebot/pzs-ng',
            )
        )
    ),

    'local' => array( 
        'runas'             => "/usr/bin/sudo -n -u root",
        'bin_dir'           => "/usr/local/bin",
        'glftpd_dir'        => "/glftpd",
        'glftpd_etc'        => "/etc",
        'glftpd_conf'       => "glftpd.conf",
        'env_bus'           => "/usr/bin/env SYSTEMCTL_FORCE_BUS=1",
        'filemanager' => array(
            'dirs' => array (
                'Glftpd Site'                 => '/glftpd/site',
            ),
            'files' => array (
              'glftpd.conf'                 => '/etc/',
              'eggdrop.conf'                => '/glftpd/sitebot',
              'ngBot.conf'                  => '/glftpd/sitebot/pzs-ng',
            ),
        )
    ),
    'ui_buttons' => array(
        'glftpd' => array(
            'Status'        => ['cmd' => 'glftpd_status'],
            'Start'         => ['cmd' => 'glftpd_start'],
            'Stop'          => ['cmd' => 'glftpd_stop'],
            'Restart'       => ['cmd' => 'glftpd_restart'],
        ),
        'docker_cmd' => array(
            'Create'        =>  ['cmd' => 'docker_create_glftpd', 'disabled' => true ],
            'Inspect'       =>  ['cmd' => 'docker_inspect_glftpd'],
            'Top'           =>  ['cmd' => 'docker_top_glftpd'],
            'Kill'          =>  ['cmd' => 'docker_kill_glftpd'],
            'Tail logs'     =>  ['cmd' => 'docker_tail_glftpd'],
            'View logs'     =>  ['cmd' => 'docker_logs_glftpd'],
        ),
        'term_cmd' => array(
            'sitewho'       => ['cmd' => 'pywho', 'sep' => true ],
            'telnet bot'    => ['cmd' => 'tty_eggdrop', 'sep' => true ],
            'useredit'      => ['cmd' => 'tty_useredit'],
            'gl_spy'        => ['cmd' => 'tty_glspy'],
            'py_spy'        => ['cmd' => 'tty_pyspy', 'sep' => true ],
            'close tty'     => ['cmd' => 'kill_gotty', 'sep' => true ],
        ),
    ),
);