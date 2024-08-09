<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK debug
 *--------------------------------------------------------------------------*/

namespace shit;

class debug {
    private $cfg;
    public int $count;

    function __construct() {
        $this->cfg = require 'config.php';
    }

    private function dbg_func() {
        return array(
            __FUNCTION__,
            __METHOD__,
            debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function']
        );
    }

    // print(key: 'value')
    //  pos: position in code
    //  pre: add '<pre>' html tag
    public function print(...$a) {
        if ($this->cfg['debug'] > 0) {
            $pos = (isset($a['pos']) ? $a['pos'] : "<none>");
            $out = "";
            foreach($a as $k => $v) {
                if ($k === 'pos' || $k === 'pre') {
                    continue;
                }
                $out .= "{$k}: " . ((is_array($v)) ? print_r($v, true) : $v) . " ";
            }
            if (isset($a['pre'])) {
                print "<pre>DEBUG: {$a['pos']} {$out}</pre>" . PHP_EOL;
            } else {
                print "DEBUG: {$a['pos']} {$out}" . "<br>" . PHP_EOL;
            }
        }
    }
    
    // trace(key: 'value')
    public function trace(...$a) {
        $trace = "<u>{$a['trace']}</u>";
        if (!isset($_SESSION['DEBUG'][$trace]) ) {
            $_SESSION['DEBUG'][$trace] = array();
        }
        if (!isset($_SESSION['DEBUG'][$trace][$this->count])) {
            $_SESSION['DEBUG'][$trace][$this->count] = array();
        }
        if ($this->cfg['debug'] > 2) {
            $out = "";
            foreach($a as $k => $v) {
                if ($k === 'ch' && gettype($v) === 'object') {
                    $k = 'curl_http_code';
                    $v = curl_getinfo($v, CURLINFO_HTTP_CODE);
                }
                if ($k === 'trace') {
                    continue;
                }
                $out .= "{$k}: " . ((is_array($v)) ? print_r($v, true) : $v) . " ";
            }
            array_push($_SESSION['DEBUG'][$trace][$this->count], $out);
        }
        $this->count++;
    }
}
