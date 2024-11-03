<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK formatting
 *--------------------------------------------------------------------------*/

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

function format_bytes(int $size, int $precision = 2): string {
    if ($size > 0) {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');
        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)] . "B";
    }
    return "0KB";
}

function format_lastlogin(): string {
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

function format_user_stats(): string {
    $all_fields = array('DAYUP', 'WKUP ', 'MONTHUP', 'ALLUP', '', 'DAYDN', 'WKDN', 'MONTHDN', 'ALLDN', '', 'NUKE', '');
    $out = "<pre><div style='color:lightgreen'><br>";
    $out .= "Showing stats for <strong>{$_SESSION['postdata']['select_user']}</strong><br>";
    if (!empty($_SESSION['userfile']) && !empty($_SESSION['userfile']['TIME'])) {
        $out .= "LAST LOGIN: " . format_lastlogin();
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
                        $out .= "{$stats[$i][1]} " . format_bytes((int)$stats[$i][2]) . " ({$last})<br>" ;
                    } else {
                        $out .= "{$stats[$i][0]}f/" . format_bytes((int)$stats[$i][1]) . "<br>";
                    }
                }
            }
        }
    }
    $out .= "<br></div></pre>";
    return $out;
}
