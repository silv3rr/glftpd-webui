<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK local exec
 *--------------------------------------------------------------------------*/

namespace shit;

use \cfg;
use shit\debug;

require_once 'local_exec.php';
require_once 'debug.php';

class local {
    private array $commands;
    private $debug;

    function __construct() {
        $this->commands = require 'local_commands.php';
        $this->debug = new debug;
        $this->debug->count = 0;
    }

    public function test_ftp(string $host, string $port): bool {
        if (@ftp_connect($host, (int)$port, 3)) {
            return true;
        }
        return false;
    }

    public function test_port(string $host, string $port): bool {
        if (@fsockopen($host, (int)$port, $errno, $errstr, 3)) {
            return true;
        }
        return false;
    }

    public function shell_exec(string $action): mixed {
        $command = "";
        if (str_word_count($action) == 1) {
            $command = $this->commands[$action];
        } else {
            $command = $action;
        }
        $return = exec($command, $output, $result_code);
        if (!empty($return)) {
            return array(
                'command' => $command,
                'return' => $return,
                'output' => $output,
                'result_code' => $result_code
            );
        }
        return false;
    }

    public function exec(string $command): mixed {
        exec($command, $output, $result_code);
        $this->debug->trace(trace: 'local-shell-exec', shell_exec: $this->shell_exec($command));
        $this->debug->trace(trace: 'local-exec', command: $command, output: $output, result_code: $result_code);
        if (!empty($output)) {
            return $output;
        }
        return false;
    }

    public function func(array|string $args): mixed {
        $action = is_array($args) ? $args[0] : $args;
        $command = $this->commands[$action];
        if (isset($command)) {
            $replace_pairs = array(
                '{$runas}' => cfg::get('local')['runas'],
                '{$bin_dir}' => cfg::get('local')['bin_dir'],
                '{$gl_dir}' => cfg::get('local')['glftpd_dir'],
                '{$gl_etc}' => cfg::get('local')['glftpd_etc'],
                '{$sitebot_port}' => cfg::get('services')['sitebot']['port'],
                '{$env_bus}' => (isset(cfg::get('local')['env_bus']) ? cfg::get('local')['env_bus'] : ""),
            );
            if (is_array($args)) {
                $args[1] = array_merge($args[1], $replace_pairs);
                $command = strtr($command, $args[1]);
            } else {
                $command = strtr($command, $replace_pairs);
            }
            $result = call_user_func_array(self::class . '::exec', [$command]);
            return $result;
        }
        return false;
    }
}
