<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK formatting
 *--------------------------------------------------------------------------*/

// TODO:
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


function sanitize_string(string $str): string {
    if (!empty($str) && is_string($str)) {
        // remove control chars
        return preg_replace("/([[:cntrl:]\+]{8,}|[[:cntrl:]]+)/", PHP_EOL, $str);
    }
    return false;
}

function htmlspecialchars_recursive ($input, int $flags = ENT_COMPAT | ENT_HTML401, string $encoding = 'UTF-8', bool $double_encode = false): mixed {
//function htmlspecialchars_recursive ($input, int $flag, string $encoding, bool $double_encode): mixed {    
    //static $flags, $encoding, $double_encode;
    if (is_array($input)) {
        return array_map('htmlspecialchars_recursive', $input);
    }
    elseif (is_scalar($input)) {
        //$flags = ((!empty($flags) && is_int($flags)) ? $flags : 0);
        //$double_encode = ((!empty($double_encode) && is_bool($flags)) ? $double_encode : false);
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

// ghetto json parsing and fmt'ing :)

function format_json($json): string {
    try {
        $out = "";
        foreach ($json as $em) {
            if (is_array($em)) {
                foreach (array_keys($em) as $k) {
                    $out .= (string)$k . ": ";
                    if (is_array($em[$k])) {
                        if (isset($em[$k][1])) {
                            for ($i = 0; $i < count($em[$k]); $i++) {
                                $out .=  PHP_EOL . "  - " . json_encode($em[$k][$i], JSON_PRETTY_PRINT);
                            }
                        } else {
                            foreach ($em[$k] as $key => $value) {
                                $out .= PHP_EOL . "  - {$key}:" . ((is_array($value)) ? json_encode($value, JSON_PRETTY_PRINT) : $value);
                            }
                        }
                    } else {
                        $out .= $em[$k];
                    }
                    $out .= PHP_EOL;
                }
                //$out .= PHP_EOL;
            } else {
                $out .= "$em" . PHP_EOL;
            }
        }
        return stripslashes($out) . "<br>" . PHP_EOL;
    } catch (Exception $e) {
        json_encode($json, JSON_PRETTY_PRINT);
    }
}

function format_msg_logs($output): string {
    $result = "";
    foreach ($output as $line) {
        $result .= trim(substr("$line", 8)) . PHP_EOL;
    }
    return $result;
}

function format_procs($json): string {
    $result = "<br>Processes:<br>" . PHP_EOL;
    $result .= isset($json['Titles']) ? json_encode($json['Titles']) . PHP_EOL : "";
    foreach ($json['Processes'] as $p) {
        $result .= json_encode($p) . PHP_EOL;
    }
    return preg_replace('/[\]\["]/', '', str_replace(',', ' ', stripslashes($result))) . PHP_EOL;
}

// docker mode: format json result
// local mode:  format $output from exec($command, $output, $result_code);

function format_cmdout(mixed $result): mixed {
    $out = null;
    if (is_array($result)) {
        $out = implode(PHP_EOL, $result);
    }
    if (is_object($result)) {
        $out = print_r($result, true);
    }
    if (is_string($result)) {
        $json = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if ((isset($json[0]['State'])) && (isset($json[0]['Status']))) {
                $out = "<br>State: <strong>{$json[0]['State']}</strong>, ";
                $out .= "Status: {$json[0]['Status']}<br>" . PHP_EOL;
            } elseif (isset($json['Processes'])) {
                $out = format_procs($json);
            } else {
                $out = format_json($json);
            }
        } else {
            $out = htmlspecialchars(sanitize_string(trim((substr($result, 8)))));
        }
    }
    return $out;
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

function fmt_bytes(int $size, int $precision = 2): string {
    if ($size > 0) {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');
        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)] . "B";
    }
    return "0KB";
}

function fmt_lastlogin(): string {
    if (!empty($_SESSION['userfile']) && !empty($_SESSION['userfile']['TIME'])) {
        $epoch  = explode(' ', $_SESSION['userfile']['TIME'])[1];
        $dt = new DateTime("@$epoch");
        return $dt->format('Y-m-d H:i');
    }
}

function format_stats(string $field): array {
    $i=0;
    $s=0;
    $section = array();
    if (isset($_SESSION['userfile'][$field])) {
        foreach(explode(' ', $_SESSION['userfile'][$field]) as $v) {
            switch ($i) {
                case $i > 2:
                    $i=0;
                case 0:
                    $section[$s][0] = $v;
                case 1:
                    $section[$s][1] = $v;
                    break;
                case 2:
                    $section[$s][2] = $v;
                    $s++;
                    //break;
                default:
            }
            $i++;
        }
    }
    return $section;
}

function fmt_user_stats(): string {
    $all_fields = array('DAYUP', 'WKUP ', 'MONTHUP', 'ALLUP', '', 'DAYDN', 'WKDN', 'MONTHDN', 'ALLDN', '', 'NUKE', '');
    $out = "<pre><div style='color:lightgreen'><br>";
    $out .= "Showing stats for <strong>{$_SESSION['postdata']['select_user']}</strong><br>";
    if (!empty($_SESSION['userfile']) && !empty($_SESSION['userfile']['TIME'])) {
        $out .= "LAST LOGIN: " . fmt_lastlogin();
    } else {
        $out .= "&lt;none&gt;";
    }
    $out .= "<br><br>";
    $out .= sprintf("PERIOD UP/DN%-4s[STAT_SECTION]%-5sFiles / Bytes", "", "") . "<br>";
    $out .= sprintf("%'-*s", 80, "-") . "<br>";
    foreach($all_fields as $field) {
        $stats = format_stats($field);
        if (!empty($_SESSION['userfile'][$field])) {
            $out .= sprintf("<strong>%-11s</strong>", $field);
            for ($i = 0; $i < count($stats); $i++) {
                if ($i === 0 || ($stats[$i][0] > 0 && $stats[$i][1] > 0 && $stats[$i][2] > 0)) {
                    $out .= ($i === 0) ? sprintf("%17s%7s", "[{$i}](DEFAULT)", "") : sprintf("%19s%16s", "[{$i}]", "");
                    if ($field === "NUKE") {
                        $last = "";
                        $epoch = $stats[$i][0];
                        if (!empty($epoch) && $epoch > 0) {
                            $dt = new DateTime("@$epoch");
                            $last = $dt->format('y-m-d H:i');
                        }
                        $out .= "{$stats[$i][1]} " . fmt_bytes((int)$stats[$i][2]) . " ({$last})<br>" ;
                    } else {
                        $out .= "{$stats[$i][0]}f/" . fmt_bytes((int)$stats[$i][1]) . "<br>";
                    }
                }
            }
        }
    }
    $out .= "<br></div></pre>";
    return $out;
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
