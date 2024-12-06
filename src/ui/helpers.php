<?php 

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK helper functions
 *--------------------------------------------------------------------------*/

// TODO: sanitize_string
//
// htmlspecialchars(), addslashes(), recursive/array:
//    htmlspecialchars(trim(substr($str, 8)));
//    htmlspecialchars(trim(preg_replace("/[[:cntrl:]]+/", PHP_EOL, $str)));
//    trim(preg_replace("/[[:cntrl:]]+/", PHP_EOL, $str));
//    preg_replace("/[[:cntrl:]]/", PHP_EOL, $str);
// remove control chars from docker output
//   - U+0001 U+0000 U+0000 U+0000 U+0000 U+0000 U+0000 U+0006
//   - U+002B
//   - chars: + ! & (?)
// remove all non alpha chars:
//   preg_replace('/[^a-zA-Z0-9_@#&%:\/\+\-\[\]\.\(\)\*\s]/s', '', $str);
// trim the ASCII control characters at the beginning and end of $binary
//   (from 0 to 31 inclusive)
//   $clean = trim($binary, "\x00..\x1F");


// config.php

class cfg {
    public static function get($key) {
        $config = (is_array(self::load())) ? self::load() : array();
        if (is_array($config) && isset($key) && isset($config[$key])) {
            return $config[$key];
        } else {
            return false;
        }
    }
    public static function set($config, $key, $value) {
        if (is_array($config) && isset($config[$key])) {
            $config[$key] = $value;
        }
    }
    public static function load() {
        //$config = require __DIR__ . '/config.php';
        $config = require 'config.php';
        return (is_array($config)) ? $config : array();
    }
    public static function save($config=[]) {
        if (empty($config)) {
            $config = self::load();
        }
        $header = <<<_EOF_
        <?php

        /*---------------------------------------------------------------------------*
        *   GLFTPD:WEBUI configuration
        *---------------------------------------------------------------------------*/

        return \$cfg = 
        _EOF_;        
        file_put_contents('config.tmp', $header . var_export($config, true) . ';' . PHP_EOL . '?>');
    }
}

function sanitize_string(string $str): string {
    if (!empty($str) && is_string($str)) {
        // remove control chars
        return preg_replace("/([[:cntrl:]\+]{8,}|[[:cntrl:]]+)/", PHP_EOL, $str);
    }
    return false;
}

function htmlspecialchars_recursive ($input, int $flags = ENT_COMPAT | ENT_HTML401, string $encoding = 'UTF-8', bool $double_encode = false): mixed {
    if (is_array($input)) {
        return array_map('htmlspecialchars_recursive', $input);
    }
    elseif (is_scalar($input)) {
        return htmlspecialchars($input, $flags, $encoding, $double_encode);
    }
    else {
        return $input;
    }
}

function trim_recursive ($input): mixed {
    if (is_array($input)) {
        return array_map('trim', $input);
    }
    elseif (is_scalar($input)) {
        return trim($input);
    }
    else {
        return $input;
    }
}

function sort_array(array $matches) {
    $_SESSION['display_sort'][$matches['list']] = true;
    switch ($matches['order']) {
        case "a-z":
            ksort($_SESSION[$matches['list']], SORT_STRING | SORT_FLAG_CASE);
            break;
        case "z-a":
            krsort($_SESSION[$matches['list']], SORT_STRING | SORT_FLAG_CASE);
            break;
        case "group":
            asort($_SESSION[$matches['list']], SORT_STRING | SORT_FLAG_CASE);
            break;
        default:
            $_SESSION['display_sort'][$matches['list']] = false;
    }
}

function flags_list(): array {
    return array(
        "1" => "SITEOP",
        "2" => "GADMIN",
        "3" => "GLOCK",
        "4" => "EXEMPT",
        "5" => "COLOR",
        "6" => "DELETED",
        "7" => "USEREDIT",
        "8" => "ANONYMOUS",
        "A" => "NUKE",
        "B" => "UNNUKE",
        "C" => "UNDUPE",
        "D" => "KICK",
        "E" => "KILL",
        "F" => "TAKE",
        "G" => "GIVE",
        "H" => "USERS",
        "I" => "IDLER",
        "J" => "CUSTOM1",
        "K" => "CUSTOM2",
        "L" => "CUSTOM3",
        "M" => "CUSTOM4",
        "N" => "CUSTOM5"
    );
}

function parse_markdown($md_file) {
    if (file_exists($md_file)) {
        $md_contents = file_get_contents($md_file) or die;
        require_once 'lib/parsedown-1.7.4/Parsedown.php';
        $Parsedown = new Parsedown();
        return $Parsedown->text($md_contents);
    } else {
        return false;
    }
}
