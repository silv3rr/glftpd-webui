<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK get data
 *--------------------------------------------------------------------------*/

namespace shit;

use shit\debug;
use shit\local;
use shit\docker;

require_once 'debug.php';
require_once 'local_exec.php';
require_once 'docker_api.php';

class data {
    private array $cfg;
    public function __construct() {
        $this->cfg = require 'config.php';
        $this->get_user();
        $this->debug = new debug;
    }

    // 'wrapper' function for running commands:
    //   first element in array decides which function to send command to
    //   callback function 'func' in 'local' or 'docker' class, using args array.
    //   args should contain replace_pairs array for 'strtr($command)'

    public function func($args): mixed {
        $result = "";
        $func_get_args = func_get_args();
        $argv = $func_get_args;    
   
        if ($this->cfg['debug'] > 9) {
            //$vars = [];
            //$this->cfg = $args;
            //$this->cfg = array_splice($func_get_args, 0, 1)[0];
            //$argv = array_splice($func_get_args, 0, 1)[0];
            $this->debug->print(pre: true, pos: 'get_data func', args: $args);
            $this->debug->print(pre: true, pos: 'get_data func', func_get_args: $func_get_args);
            $this->debug->print(pre: true, pos: 'get_data func', _this_cfg: var_dump($this->cfg));
            $this->debug->print(pre: true, pos: 'get_data func', _args: var_dump($args));
            $this->debug->print(pre: true, pos: 'get_data func', _argv: var_dump($argv));
            //exit;
        }
        
        // TODO: use reflection instead of call_user_func_array?
        //       https://www.php.net/manual/en/reflectionfunction.invokeargs.php

        if ($this->cfg['mode'] == "local") {
            $local = new local;
            switch ($argv[0]) {
                case "ftpd":
                    $result = $local->test_ftp(
                        $this->cfg['services']['ftpd']['host'],
                        $this->cfg['services']['ftpd']['port']
                    );
                    break;
                case "irc":
                    $result = $local->test_port(
                        $this->cfg['services']['irc']['host'],
                        $this->cfg['services']['irc']['port']
                    );
                    break;
                case "sitebot":
                    $result = $local->test_port(
                        $this->cfg['services']['sitebot']['host'],
                        $this->cfg['services']['sitebot']['port']
                    );
                    break;
                default:
                    // $_SESSION['DEBUG']['argv'] = $argv;
                    //$this->debug->print(pos: 'get_data func', _SESSION_DEBUG_argv: $_SESSION['DEBUG']['argv']);

                    $result = call_user_func_array([$local, 'func'], $argv);
            }
        } elseif ($this->cfg['mode'] == "docker") {
            $docker = new docker;
            switch ($argv[0]) {
                case "ftpd":
                    $result = $docker->test_port(
                        $this->cfg['docker']['glftpd_container'],$this->cfg['services']['ftpd']['host'], $this->cfg['services']['ftpd']['port']
                    );
                    break;
                case "irc":
                    $result = $docker->test_port(
                        $this->cfg['docker']['glftpd_container'],
                        $this->cfg['services']['irc']['host'], $this->cfg['services']['irc']['port']
                    );
                    break;
                case "sitebot":
                    $result = $docker->test_port(
                        $this->cfg['docker']['glftpd_container'],
                        $this->cfg['services']['sitebot']['host'], $this->cfg['services']['sitebot']['port']
                    );
                    break;
                default:
                    $result = call_user_func_array([$docker, 'func'], $argv);
            }
        }
        return $result;
    }

    public function get_user(): bool|string {
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['user']) && $_GET['user'] !== "Select username...") {
            return htmlspecialchars(trim($_GET['user']));
        }
        return false;
    }

    public function get_users() {
        $_SESSION['users'] = $this->func('users_raw');
    }

    public function get_groups() {
        $groups_all = [];
        $result = $this->func('groups_raw');
        //$this->debug->print(pos: 'get_data get_groups', result: $result);
        if (is_array($result)) {
            foreach ($result as $group) {
                //$get_group = trim(sanitize_string($group));
                if (!empty($group)) {
                    $fields = explode(' ', $group, 2);
                    $groups_all[$fields[0]] = (!empty($fields[1])) ? $fields[1] : "";
                }
            }
        }
        $_SESSION['groups'] = $groups_all;
    }

    public function get_pgroups() {
        $pgroups_all = [];
        $result = $this->func('pgroups_raw');
        //$this->debug->print(pos: 'get_data get_pgroups', result: $result);
        if (is_array($result)) {
            foreach ($result as $pgroup) {
                //$get_pgroup = trim(sanitize_string($pgroup));
                if (!empty($pgroup)) {
                    $fields = explode(' ', $pgroup, 2);
                    $pgroups_all[$fields[0]] = (!empty($fields[1])) ? $fields[1] : "";
                }
            }
        }
        $_SESSION['pgroups'] = $pgroups_all;
    }

    public function get_users_groups() {
        $users_groups_all = [];
        $result = $this->func('usersgroups_raw');
        //$this->debug->print(pos: 'get_data get_usersgroups', result: $result);
        if(is_array($result)) {
            foreach ($result as $get_user_group) {
                //$get_user_group = trim(sanitize_string($get_user_group));
                if (!empty($get_user_group)) {
                    $user_group = explode(' ', $get_user_group);
                    $users_groups_all[$user_group[0]] = !empty($user_group[1]) ? $user_group[1] : "";
                }
            }
        }
        $_SESSION['users_groups'] = $users_groups_all;
    }

    public function get_userfile(): array {
        $userfile = [];
        if ($this->check_user()) {
            $replace_pairs = array('{$username}' => $_SESSION['postdata']['select_user']);
            $result = $this->func(['userfile_raw', $replace_pairs]);
            //$this->debug->print(pre: true, pos: 'get_data get_userfile', replace_pairs: $replace_pairs, result: $result);
            if (!empty($result)) {
                foreach ($result as $line) {
                    $fields = explode(' ', $line, 2);
                    if(empty($userfile[$fields[0]])) {
                        $userfile[$fields[0]] = $fields[1];
                    } else {
                        if (!is_array($userfile[$fields[0]])) {
                            $var = $userfile[$fields[0]];
                            $userfile[$fields[0]] = [$var];
                        }
                        array_push($userfile[$fields[0]], $fields[1]);
                    }
                }
            }
        }
        //$this->debug->print(pre: true, pos: 'get_data get_userfile-2', result: $userfile);
        return $userfile;
    }

    public function get_user_group(): array {
        $user_groups = [];
        $available_groups = [];
        if ($this->check_user() && isset($_SESSION['userfile'])) {
            if (!empty($_SESSION['userfile'] && !empty($_SESSION['userfile']['GROUP']))) {
                if (is_array($_SESSION['userfile']['GROUP'])) {
                    foreach($_SESSION['userfile']['GROUP'] as $group_line) {
                        $group_field = explode(' ', $group_line, 2);
                        if ($group_field[0] !== "NoGroup") {
                            $user_groups[$group_field[0]] = (($group_field[1]) ? ($group_field[1]) : "0");
                        }
                    }
                } else {
                    $group_field = explode(' ', $_SESSION['userfile']['GROUP'], 2);
                    if ($group_field[0] !== "NoGroup") {
                        $user_groups[$group_field[0]] = (($group_field[1]) ? ($group_field[1]) : "0");
                    }
                }
            }
            foreach ($_SESSION['groups'] as $group => $desc) {
                if (!empty($group) && !in_array($group, array_keys($user_groups))) {
                    $available_groups[] .= $group;
                }
            }
            ksort($user_groups, SORT_STRING | SORT_FLAG_CASE);
            ksort($available_groups, SORT_STRING | SORT_FLAG_CASE);
        }
        return array(
            "current" => $user_groups,
            "available" => $available_groups
        );
    }

    public function get_user_pgroup(): string|array {
        $user_pgroups = [];
        if ($this->check_user() && isset($_SESSION['userfile'])) {
            if (isset($_SESSION['userfile']['PRIVATE'])) {
                if (is_array($_SESSION['userfile']['PRIVATE'])) {
                    foreach($_SESSION['userfile']['PRIVATE'] as $pgroup) {
                        array_push($user_pgroups, $pgroup);
                    }
                } else {
                    $user_pgroups = [ $_SESSION['userfile']['PRIVATE'] ];
                }
            }
        }
        return $user_pgroups;
    }

    public function get_mask(): string|array {
        $masks = "";
        if ($this->check_user() && isset($_SESSION['userfile'])) {
            if (!empty($_SESSION['userfile'] && !empty($_SESSION['userfile']['IP']))) {
                $masks = (is_array($_SESSION['userfile']['IP'])) ? $_SESSION['userfile']['IP'] : [ $_SESSION['userfile']['IP'] ];
            }
        }
        return $masks;
    }

    public function check_user(): bool {
        if (!empty($_SESSION['postdata']['select_user']) && $_SESSION['postdata']['select_user'] !== "Select username...") {
            return true;
        }
        return false;
    }

    public function get_status() {
        $_SESSION['status'] = null;
        foreach (array_keys($this->cfg['services']) as $service) {
            $_SESSION['status'][$service] = (($this->func($service)) ? "up" : "down") ;
        }
        $result = $this->func('ps_gotty');
        if ((is_array($result)) && (!empty($result)) && (!preg_grep('/is not running/i', $result))) {
            $_SESSION['status']['gotty'] = "open";
        }
        //$_SESSION['update']['status'] = true;
    }

}
