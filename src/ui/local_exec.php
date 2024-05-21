<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK local exec
 *--------------------------------------------------------------------------*/

namespace shit;

use shit\debug;

require_once 'local_exec.php';
require_once 'debug.php';

class local {
    private $cfg;
    private array $commands;

    function __construct() {
        $this->cfg = require 'config.php';
        $this->commands = require 'local_commands.php';
        $this->debug = new debug;
        $this->debug->count = 0;
         
    }

    public function test_ftp(string $host, string $port): bool {
        if (@ftp_connect($host, $port, 3)) {
            return true;
        }
        return false;
    }

    public function test_port(string $host, string $port): bool {
        if (@fsockopen($host, $port, $errno, $errstr, 3)) {
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
                '{$bindir}' => $this->cfg['local']['bin_dir'],
                '{$runas}' => $this->cfg['local']['runas_user'],
            );
            if (is_array($args)) {
                $args[1] = array_merge($args[1], $replace_pairs);
                $command = strtr($command, $args[1]);
            } else {
                $command = strtr($command, $replace_pairs);
            }
            $result = call_user_func_array(['self', 'exec'], [$command]);
            return $result;
        }
        return false;
    }

}
