<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK docker api
 *--------------------------------------------------------------------------*/

// calls docker engine api endpoints with curl via unix socket

// function 'func' executes (docker) commands from array, e.g.
//   'foo' => ['exec', 'POST', '/bin/foo']
//   callback: function exec('glftpd', 'commands')

namespace shit;

use shit\debug;
require_once 'docker_api.php';
require_once 'debug.php';

class docker {
    private array $cfg;
    private array $commands;
    
    public function __construct() {
        $this->cfg = require 'config.php';
        $this->commands = require 'docker_commands.php';
        $this->debug = new debug;
        $this->debug->count = 0;
    }

    public function api(string $http_method, string $endpoint, $postfields=null): string|bool {
        $url = $this->cfg['docker']['api'] . $endpoint;
        $this->debug->trace(count: $this->debug->count++, trace: 'docker-api-1', url: $url, postfields: $postfields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, "/var/run/docker.sock");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        if ($this->cfg['debug'] > 2) {
            $fp = fopen('/tmp/curl_err.log', 'a+');
            curl_setopt($ch, CURLOPT_STDERR, $fp);
        }
        if ($http_method == "POST") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            if (is_null($postfields)) {
                curl_setopt($ch, CURLOPT_NOBODY, true);
                unset($postfields);
            } else {
                $this->debug->trace(trace: 'docker-api-2', http_method: $http_method, postfields: $postfields);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            }
        }
        $data = curl_exec($ch);
        if (!curl_errno($ch)) {
            $this->debug->trace(trace: 'docker-api-3',  ch: $ch, data: $data);
        } else {
            $this->debug->trace(trace: 'docker-api-3',  ch: 'curl_errno', data: $data);
        }
        /*
        if (empty($data)) {
            switch(curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200: $response = 'OK' ; break;
                case 204: $response = 'no error' ; break;
                case 304: $response = 'not modified'; break;
                case 404: $response = 'not found'; break;
            }
            if (!empty($response)) {
                $data = "message: {$response}" . (!empty($endpoint) ? " ('{$endpoint}')" : '');
            }
        }
        */
        curl_close($ch);
        return $data;
    }

    public function test_port(string $container, string $host, string $port): bool {
        $exec = json_decode(
            self::api(
                "POST",
                "/containers/$container/exec",
                '{
                    "AttachStdout": true, "Tty": false, "Cmd": [
                        "echo", "|", "/bin/busybox", "telnet", "'. $host . '", "' . $port . '"
                    ]
                }'
            )
        );
        if (isset($exec->Id)) {
            self::api("POST", "/exec/{$exec->Id}/start", '{ "Detach": false, "Tty": false }');
            $json = (self::api("GET", "/exec/{$exec->Id}/json", null));
            if (isset(json_decode($json)->ExitCode) && (json_decode($json)->ExitCode === 0)) {
                return true;
            }
        }
        return false;
    }

    public function exec(string $id, string $cmd): mixed {
        $this->debug->trace(trace: 'docker-exec-1', id: $id, cmd: $cmd);
        $exec = self::api(
            "POST",
            "/containers/$id/exec",
            '{
                "AttachStdin": false,
                "AttachStdout": true,
                "AttachStderr": true,
                "Tty": false,
                "Cmd": ' . $cmd . '
            }'
        );
        $this->debug->trace(trace: 'docker-exec-2', exec: $exec);
        if (!preg_match('/no such container/i', $exec)) {
            $json = json_decode($exec);
            if ((json_last_error() === JSON_ERROR_NONE) && (isset($json->Id))) {
                $result = self::api("POST", "/exec/{$json->Id}/start", '{ "Detach": false, "Tty": false }');
                if (!preg_match('/exec failed/i', $result)) {
                    $this->debug->trace(trace: 'docker-exec-3', result: $result);
                    $json_result = json_decode($result);
                    return (json_last_error() === JSON_ERROR_NONE) ? $json_result : $this->format($result);
                }
            }
        }
        return false;
    }

    public function format(string $result): array {
        $return = array();
        $lines = explode(PHP_EOL, trim(substr($result, 8)));
        foreach($lines as $line) {
            $line = trim(sanitize_string($line));
            if (!empty($line)) {
                array_push($return, $line);
            }
        }
        return $return;
    }

    public function create(array $hostconfig): object {
        return json_decode(
            self::api(
                strtr(
                    "POST",
                    "/containers/create?name={$hostconfig['name']}",
                    '{
                        "Image": "{$image",
                        "Hostname": "{$name}",
                        "Workdir": "{$workdir}",

                        "PortBindings": {
                            "{$port}": [
                                {
                                    "HostIp": "{$hostip}",
                                    "HostPort": "{$hostport}"
                                }
                            ]
                        },
                        "NetworkingConfig": {
                            "EndpointsConfig": {
                                "{$network}": { }
                            }
                        }
                    }', $hostconfig
                )
            )
        );
    }

    public function start(string $id) {
        return json_decode(self::api("POST", "/containers/{$id}/start", ""));
    }

    public function list(bool $all=false) {
        return json_decode(self::api("GET", "/containers/json?all=$all"));
    }

    public function func(array|string $args): mixed {
        $action = is_array($args) ? $args[0] : $args;
        $command = $this->commands[$action];
        if (isset($command)) {
            $replace_pairs = array(
                '{$bin_dir}' => $this->cfg['docker']['bin_dir'],
                '{$gl_ct}' => $this->cfg['docker']['glftpd_container'],
            );
            $command[1] = strtr($command[1], $replace_pairs);
            if (is_array($args)) {
                $args[1] = array_merge($args[1], $replace_pairs);
                $command[2] = strtr($command[2], $args[1]);
            } else {
                $command[2] = strtr($command[2], $replace_pairs);
            }
            //$this->debug->print(pre: true, pos: 'docker_api', action: $action, _command_2: $command[2], command: $command, args: $args );
            $this->debug->trace(trace: 'docker-func-1', action: $action, command: $command);
            $result = call_user_func_array(['self', array_shift($command)], $command);
            $this->debug->trace(trace: 'docker-func-2', result: $result);
            return $result;
        }
        return false;
    }
}
