<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK controller
 *--------------------------------------------------------------------------*/

// remove session and cookies

function reset_session() {
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time()-1000);
            setcookie($name, '', time()-1000, '/');
        }
    }
    unset($_GET);
    unset($_POST);
    unset($_SESSION);
    session_destroy();
}

function set_cmd_result($cmd_out) {
    $re = '/.*((?:DONE|INFO|WARN|ERROR): .*)/';
    $result = NULL;
    if (is_string($cmd_out)) {
        $result .= preg_replace($re, '\1<br>', $cmd_out);
    } elseif (is_array($cmd_out)) {
        $result = "";
        foreach ($cmd_out as $line) {
            $result .= preg_replace($re, '\1<br>', $line);
        }
    }
    if (!isset($_SESSION['results'])) {
        $_SESSION['results'] = array();
    }
    if (!empty($result)) {
        array_push($_SESSION['results'], $result);
    }
}

// debug: reset session
if (isset($_GET['reset']) && $_GET['reset']) {
    reset_session();
    print "<div style='color:red;border:2px solid'><h2>DEBUG: Session reset</h2><br>Removed query param and reloaded, exiting...</h2></div>" . PHP_EOL;
    exit;
}

// form submits, input controls and routing. mark changed values

if (isset($_SESSION['postdata'])) {
    if (cfg::get('debug') > 10) {
        $debug->print(pos: 'controller-1 set_cmd_result', _SESSION_postdata: $_SESSION['postdata']);
    }
    // 'xxCmd' buttons for logs etc
    if (!empty($_SESSION['postdata']['dockerCmd'])) {
        if ($_SESSION['postdata']['dockerCmd'] === 'docker_logs_glftpd') {
            include_once 'templates/logs.html';
            print(PHP_EOL . 'Showing <strong>glftpd docker logs</strong>...' . PHP_EOL . PHP_EOL);
            foreach(explode(PHP_EOL, $data->func($_SESSION['postdata']['dockerCmd'])) as $line) {
                print(trim(substr($line, 8)) . PHP_EOL);
            }
            unset($_SESSION['postdata']['dockerCmd']);
            print '</pre>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL;
            exit;
        }
        if ($_SESSION['postdata']['dockerCmd'] === 'docker_inspect_glftpd') {
            include_once 'templates/logs.html';
            print(PHP_EOL . 'Output from <strong>docker inpect glftpd</strong>...' . PHP_EOL . PHP_EOL);
            print format_cmd_out($data->func($_SESSION['postdata']['dockerCmd']));
            unset($_SESSION['postdata']['dockerCmd']);
            print '</pre>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL;
            exit;
        }
    }
    if (!empty($_SESSION['postdata']['gltoolCmd'])) {
        if ($_SESSION['postdata']['gltoolCmd'] === 'gltool_log') {
            include_once 'templates/logs.html';
            print format_cmd_out($data->func($_SESSION['postdata']['gltoolCmd']));
            unset($_SESSION['postdata']['gltoolCmd']);
            print '</pre>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL;
            exit;
        }
        if ($_SESSION['postdata']['gltoolCmd'] === "show_userstats") {
            if ($data->check_user() && isset($_SESSION['userfile'])) {
                $text = format_userstats();
                $html = htmlspecialchars(addslashes($text));
            } else {
                $text = "<user:none>";
                $html = "&lt;user:none&gt;";
            }
            if (cfg::get('modal')['userstats']) {
                $_SESSION['modal'] = array('func' => 'show', 'title' => "User Stats", 'text' => $html);
            } else {
                $_SESSION['cmd_output'] = $text;
            }
            unset($_SESSION['postdata']['gltoolCmd']);
        }
    }
    if (isset($_SESSION['postdata']['user_group'])) {
        if ($_SESSION['postdata']['user_group'] === "Select group...") {
            unset($_SESSION['postdata']['user_group']);
        }
    }
    if (isset($_SESSION['postdata']['help'])) {
        include_once 'templates/help.html';
        print(parse_markdown("templates/README.md"));
        unset($_SESSION['postdata']['help']);
        exit;
    }

    if (isset($_SESSION['postdata']['show_all_stats'])) {
        if (cfg::get('modal')['all_stats'] && !isset($_SESSION['postdata']['stats_page'])) {
            $out = '<p><a href="' . $_SERVER["PHP_SELF"] . '?stats=1"><button class="fixed-top">View full screen</a></button><p>';
            foreach (cfg::get('stats')['commands'] as $key => $item) {
                if ($item['show'] >= 1) {
                    $result = $data->get_chart_stats($item);
                    $color = cfg::get('stats')['options']['color'];
                    $svg = create_svg("pie", $result['chart_data'], $result['chart_labels'], cfg::get('palette')[$color]);
                    $out .= "<h6>{$item['stat']} " . ((substr($key, 0, 1) === 'G' ? "GROUP" : "USER") . "</h6>");
                    $out .= "<div style='float:right;'>";
                    $out .= str_replace(["\r\n", "\r", "\n", "\t"], ' ', $svg);
                    $out .= "</div>";
                    $out .= "<div>";
                    $out .= format_stats($result);
                    $out .= "</div>";
                }
            }
            $_SESSION['modal'] = array('func' => 'show', 'title' => "All Stats", 'text' => htmlspecialchars(addslashes("<div>{$out}</div>")));
            unset($_SESSION['postdata']['show_all_stats']);
        } else {
            include_once 'templates/stats.html.php';
            if ($_SESSION['postdata']['stats_page']) {
                unset($_SESSION['postdata']['stats_page']);
            }
            unset($_SESSION['postdata']['show_all_stats']);
            exit;
        };
    }

    // loop over postdata to get remaining inputs

    foreach ($_SESSION['postdata'] as $name => $value) {
        if (cfg::get('debug') > 9) {
            if (is_scalar($name)) {
                $debug->print(pos: 'controller scalar', name: $name);
            }
            if (is_array($name)) {
                $debug->print(pos: 'controller array', name: $name);
            }
            if (is_scalar($value) && !empty($value)) {
               $debug->print(pos: 'controller scalar', value: $name);
            }
            if (is_array($value)) {
                $debug->print(pos: 'controller array', value: $name);
            }
        }
        // sitewho, tty, other cmds
        if (is_scalar($value)) {
            // sitewho
            if ($name === "termCmd" && !empty($value) && $value === "pywho") {
                $text = "";
                $result = $data->func($value);
                if (is_array($result)) {
                    if (cfg::get('mode') === "local") {
                        $text = str_replace('[', '[', implode(PHP_EOL, ($data->func($value))));
                    } else {
                        foreach ($data->func($value) as $i) {
                            $text .= preg_replace('/\n(\[[0-9;]+m)/', '$1', $i);
                            $text = str_replace('[', '[', $text) . PHP_EOL;
                        }
                    }
                    $highlighter = new \AnsiEscapesToHtml\Highlighter();
                    $html = $highlighter->toHtml($text);
                    if (cfg::get('modal')['sitewho']) {
                        $format_html = htmlspecialchars(addslashes('<pre>' . $html . '</pre>'));
                        $_SESSION['modal'] = array('func' => 'show', 'title' => "Glftpd Sitewho (by pywho)", 'text' => $format_html);
                    } else {
                        $_SESSION['cmd_output'] = $html;
                    }
                }
                unset($_SESSION['postdata'][$name]);
            } elseif ($name === "termCmd" && !empty($value)) {
                if (preg_match('/^kill_[a-z_]+$/', $value)) {
                    $_SESSION['cmd_output'] = format_cmd_out($data->func($value));
                } else {
                    set_cmd_result($data->func($value));
                    $_SESSION['modal'] = array('func' => 'tty');
                }
                if (preg_match('/^kill_gotty$/', $value)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    //header("refresh:1;url=" . $_SERVER['PHP_SELF']);
                    //exit;
                }
                unset($_SESSION['postdata']['termCmd']);
            } elseif (preg_match('/^(glCmd|dockerCmd|gltoolCmd)$/', $name) && !empty($value)) {
                if (cfg::get('modal')['commands']) {
                    $_SESSION['modal'] = array('func' => 'show', 'title' => "Output", 'text' => $cmd_out );
                } else {
                    if (preg_match('/^glftpd_(start|stop|restart)$/', $value)) {
                        $data->func($value);
                        $_SESSION['results'][$value] = "DONE: {$value}";
                    } else {   
                        $_SESSION['cmd_output'] = format_cmd_out($data->func($value));
                    }
                }
                unset($_SESSION['postdata'][$name]);
            }
        }

        // apply button

        if(isset($_SESSION['postdata']['applyBtn'])) {
            //$debug->print(pos: 'controller', msg: 'got applyBtn');
            //$debug->print(pre: true, pos: 'controller', _SESSION_postdata: $_SESSION['postdata'], ipCmd: $_SESSION['postdata']['ipCmd']);
            // ip masks: empty textarea, delip all
            if ($data->check_user() && isset($_SESSION['postdata']['ipCmd']) && empty($_SESSION['postdata']['ipCmd']) && (!empty($_SESSION['userfile']) && !empty($_SESSION['userfile']['IP']))) {
                //$userfile_masks = preg_split('/[ \n]/', $_SESSION['userfile']['IP'], -1, PREG_SPLIT_NO_EMPTY);
                $userfile_masks = (is_array($_SESSION['userfile']['IP'])) ? $_SESSION['userfile']['IP'] : array($_SESSION['userfile']['IP']);
                foreach ($userfile_masks as $mask) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$mask}' => trim($mask),
                    );
                    set_cmd_result($data->func(['ip_del', $replace_pairs]));
                }
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['ipCmd']);
            } elseif (!empty($_SESSION['postdata']['ipCmd'])) {
                //$debug->print(pos: 'controller', msg: 'got ipCmd');
                $userfile_masks = [];
                $ipcmd_masks = preg_split('/[ \n]/', $_SESSION['postdata']['ipCmd'], -1, PREG_SPLIT_NO_EMPTY);
                //$debug->print(pre: true, pos: 'controller', ipcmd_masks: $ipcmd_masks);
                if ($data->check_user() && !empty($_SESSION['userfile']) && !empty($_SESSION['userfile']['IP'])) {
                    $userfile_masks = [];
                    if (is_array($_SESSION['userfile']['IP'])) {
                        $userfile_masks = $_SESSION['userfile']['IP'];
                    } else  {
                        $userfile_masks = array($_SESSION['userfile']['IP']);
                    }
                }
                //$debug->print(pre: true, pos: 'controller', userfile_masks: $userfile_masks);
                foreach ($userfile_masks as $mask) {
                    if (!in_array($mask, $ipcmd_masks)) {
                        $replace_pairs = array(
                            '{$username}' => $_SESSION['postdata']['select_user'],
                            '{$mask}' => trim($mask),
                        );
                        set_cmd_result($data->func(['ip_del', $replace_pairs]));
                    }
                }
                foreach ($ipcmd_masks as $mask) {
                    if (!in_array($mask, $userfile_masks)) {
                        $replace_pairs = array(
                            '{$username}' => $_SESSION['postdata']['select_user'],
                            '{$mask}' => trim($mask),
                        );
                        set_cmd_result($data->func(['ip_add', $replace_pairs]));
                    }
                }
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['ipCmd']);
            } // end ipCmd
            if ($data->check_user() && !empty($_SESSION['postdata']['setPassCmd'])) {
                $debug->print(pos: 'controller', msg: 'controller got setPassCmd');
                $replace_pairs = array(
                    '{$username}' => $_SESSION['postdata']['select_user'],
                    '{$password}' => $_SESSION['postdata']['setPassCmd']
                );
                set_cmd_result($data->func(['password_change', $replace_pairs]));
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['setPassCmd']);
            }
            if ($data->check_user() && isset($_SESSION['postdata']['flagCmd'])) {
                $debug->print(pos: 'controller', msg: 'got flagCmd');
                // single flag_del cmd
                if (is_string($_SESSION['postdata']['flagCmd']) && preg_match('/^flag_del\|[0-9A-Z]+$/', $_SESSION['postdata']['flagCmd'])) {
                    $flags = preg_replace('/^flag_del\|/', '', $_SESSION['postdata']['flagCmd']);
                    if (!empty($flags)) {
                        $replace_pairs = array(
                            '{$username}' => $_SESSION['postdata']['select_user'],
                            '{$flags}' => $flags,
                        );
                        set_cmd_result($data->func(['flag_del', $replace_pairs]));
                    }
                }
                // multiple flags: compare current vs new
                //   - submitted = allflags - newflags
                //   - unsubmitted = rest
                $flags_add = [];
                $flags_del = flags_list();
                $flags_userfile = [];
                $flags_submitted = [];
                //unset($flags_del['3']);
                if (is_array($_SESSION['postdata']['flagCmd'])) {
                    $flags_userfile = !empty($_SESSION['userfile']['FLAGS']) ? str_split($_SESSION['userfile']['FLAGS']) : [];
                    foreach ($_SESSION['postdata']['flagCmd'] as $flagcmd) {
                        if (preg_grep('/^flag_add\|[0-9A-Z]+$/', $_SESSION['postdata']['flagCmd'])) {
                            preg_match('/flag_add\|(?<flag>[0-9A-Z]+)/', $flagcmd, $matches);
                            $debug->print(pos: 'controller', matches_flag: $matches['flag']);
                            if (!in_array($matches['flag'], $flags_userfile)) {
                                array_push($flags_add, $matches['flag']);
                            }
                            unset($flags_del[$matches['flag']]);
                            array_push($flags_submitted, $matches['flag']);
                        }
                    }
                }
                // del all flags not selected in form and/or unused
                if (!empty($flags_del)) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$flags}' => implode(array_keys($flags_del))
                    );
                    $result = $data->func(['flag_del', $replace_pairs]);
                    $diff_userfile = array_diff($flags_userfile, $flags_submitted);
                    $sum = count($flags_userfile) + count($flags_add) + count($flags_del);
                    $debug->print(pre: true, pos: 'controller', flags_userfile: $flags_userfile, flags_del: $flags_del, flags_add: $flags_add, array_diff: array_diff($flags_userfile, $flags_del));
                    $debug->print(pos: 'controller', diff_userfile: $diff_userfile, count_flags_userfile: count($flags_userfile), count_flags_add: count($flags_add), count_flags_del: count($flags_del), count_flags_list: count(flags_list()));
                    $debug->print(pos: 'controller', flags_submitted: $flags_submitted, cnt_flags_submitted: count($flags_submitted), sum: $sum);
                    if (!empty($result) && !empty($flags_submitted) && empty($flags_add) && count(array_diff($flags_userfile, $flags_submitted))) {
                        set_cmd_result("DONE: deleted flags \"" . implode(array_values($diff_userfile)) . "\" from \"{$_SESSION['postdata']['select_user']}\"");
                    }
                }
                if (!empty($flags_add)) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$flags}' => implode(array_values($flags_add))
                    );
                    set_cmd_result($data->func(['flag_add', $replace_pairs]));
                }
                if (is_array($_SESSION['postdata']['flagCmd']) && preg_grep('/^flag_change\|[0-9A-Z]+$/', $_SESSION['postdata']['flagCmd'])) {
                    foreach ($_SESSION['postdata']['flagCmd'] as $flagcmd) {
                        preg_match('/(?<action>flag_change)\|(?<flag>[0-9A-Z]+)/', $flagcmd, $matches);
                        if (!empty($matches['action']) && !empty($matches['flag'])) {
                            $cmd = $matches['action'];
                            $replace_pairs = array(
                                '{$username}' => $_SESSION['postdata']['select_user'],
                                '{$flags}' => $matches['flag']
                            );
                            set_cmd_result($data->func([$cmd, $replace_pairs]));
                        }
                    }
                }
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['flagCmd']);
            } //end apply flagCmd
            if ($data->check_user() && !empty($_SESSION['postdata']['loginsCmd'])) {
                if (isset($_SESSION['logins']) && ($_SESSION['logins'] !== $_SESSION['postdata']['loginsCmd'])) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$logins}' => $_SESSION['postdata']['loginsCmd']
                    );
                    set_cmd_result($data->func(['logins_change', $replace_pairs]));
                }
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['loginsCmd']);
            }
            if ($data->check_user() && !empty($_SESSION['postdata']['ratioCmd'])) {
                if (isset($_SESSION['ratio']) && ($_SESSION['ratio'] !== $_SESSION['postdata']['ratioCmd'])) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$ratio}' => $_SESSION['postdata']['ratioCmd']
                    );
                    set_cmd_result($data->func(['ratio_change', $replace_pairs]));
                }
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['ratioCmd']);
            }
            if ($data->check_user() && !empty($_SESSION['postdata']['credsCmd'])) {
                if (isset($_SESSION['credits']) && ($_SESSION['credits'] !== $_SESSION['postdata']['credsCmd'])) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$credits}' => $_SESSION['postdata']['credsCmd']
                    );
                    set_cmd_result($data->func(['credits_change', $replace_pairs]));

                }
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['credsCmd']);
            }
            if ($data->check_user() && !empty($_SESSION['postdata']['tagCmd'])) {
                if (isset($_SESSION['tagline']) && ($_SESSION['tagline'] !== $_SESSION['postdata']['tagCmd'])) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$tagline}' => $_SESSION['postdata']['tagCmd']
                    );
                    set_cmd_result($data->func(['tagline_change', $replace_pairs]));
                }
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['tagCmd']);
            }
            unset($_SESSION['postdata']['applyBtn']);
            if ($data->check_user() && !empty($_SESSION['postdata']['userCmd']) && $_SESSION['postdata']['userCmd'] === 'reset_userstats') {
                $replace_pairs = array(
                    '{$username}' => $_SESSION['postdata']['select_user'],
                );
                set_cmd_result($data->func(['reset_userstats', $replace_pairs]));
                unset($_SESSION['postdata']['userCmd']);
            }
            unset($_SESSION['postdata']['applyBtn']);
        } //end applyBtn
        //
        // submit cmds, without apply
        //
        //$debug->print(pre: true, pos: 'controller-3', _SESSION_postdata: $_SESSION['postdata']);
        if ($name === 'userCmd') {
            if ($data->check_user() && $value === 'user_del') {
                $replace_pairs = array('{$username}' => $_SESSION['postdata']['select_user']);
                set_cmd_result($data->func(['user_del', $replace_pairs]));
                unset($_SESSION['postdata']['select_user']);
            }
            if ($value === 'user_add') {
                //$debug->print(pos: 'controller', msg: 'got userCmd', value: $value);
                //$debug->print(pre: true, pos: 'controller', _SESSION_postdata: $_SESSION['postdata']);
                //$debug->print(pre: true, pos: 'controller', _POST: $_POST);
                if (!empty($_SESSION['postdata']['user_name']) && !empty($_SESSION['postdata']['user_password'])) {
                    //$debug->print(pos: 'controller', msg: 'got user_name & user_password');
                    $group = "";
                    $mask = "";
                    $gadmin = 0;
                    if (isset($_SESSION['postdata']['user_group'])) {
                        if ($_SESSION['postdata']['user_group'] !== "Select group...") {
                            $group = $_SESSION['postdata']['user_group'];
                        }
                    }
                    if (isset($_SESSION['postdata']['user_ip'])) {
                        $mask = $_SESSION['postdata']['user_ip'];
                    }
                    if (isset($_SESSION['postdata']['user_gadmin'])) {
                        $gadmin = 1;
                    }
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['user_name'],
                        '{$password}' => $_SESSION['postdata']['user_password'],
                        '{$group}' => (!empty($group)) ? $group : "",
                        '{$mask}' => (!empty($mask)) ? $mask : "",
                        '{$gadmin}' => $gadmin
                    );
                    set_cmd_result($data->func(['user_add', $replace_pairs]));

                }
            }
            $_SESSION['update']['userfile'] = true;
            $_SESSION['update']['users'] = true;
            $_SESSION['update']['user_group'] = true;
            unset($_SESSION['postdata']['userCmd']);
        } //end userCmd
        if ($name === 'userGrpCmd' && isset($value)) {
            $debug->print(pos: 'controller', msg: 'got userGrpCmd', value: $value);
            foreach ($value as $k => $v) {
                if (in_array($v, [ "Add user to group...",  "Group Admin...", "Add user to privgroup..." ])) {
                    unset($value[$k]);
                }
            }
            $debug->print(pos: 'controller', msg: 'after', value: $value);
            if ($data->check_user() && is_array($value) && (preg_grep('/^(add|del)_user_group(_all)?\|.+/', $value))) {
                $debug->print(pos: 'controller', msg: 'userGrpCmd got preg_grep');
                foreach ($value as $user_group) {
                    $debug->print(pos: 'controller', msg: 'userGrpCmd', user_group: $user_group);
                    if (preg_match('/^del_user_group_all\|.+/', $user_group)) {
                        foreach (explode(PHP_EOL, str_replace('del_user_group_all|', '', $user_group)) as $group) {
                            $replace_pairs = array(
                                '{$username}' => $_SESSION['postdata']['select_user'],
                                '{$group}' => trim($group)
                            );
                            set_cmd_result($data->func(['del_user_group', $replace_pairs]));
                        }
                        break;
                    } else {
                        preg_match('/(?<action>(?:add|del)_user_group)\|(?<group>.+)/', $user_group, $matches);
                        $debug->print(pos: 'controller', msg: 'userGrpCmd', _matches_group: $matches['group'], user_group: $user_group);
                        if (!empty($matches['action']) && !empty($matches['group'])) {
                            $debug->print(pos: 'controller', msg: 'got usergrp matches');
                            $cmd = $matches['action'];
                            $replace_pairs = array(
                                '{$username}' => $_SESSION['postdata']['select_user'],
                                '{$group}' => trim($matches['group']),
                            );
                            if (in_array('gadmin', $value)) {
                                $replace_pairs['{$gadmin}'] = "1";
                            }
                            set_cmd_result($data->func([$cmd, $replace_pairs]));
                        }
                    }
                }
                $_SESSION['update']['user_group'] = true;
                $_SESSION['update']['userfile'] = true;
            }
            if ($data->check_user() && is_array($value) && (preg_grep('/^(add|del)_user_gadmin\|.+$/', $value))) {
                foreach ($value as $user_gadmin) {
                    preg_match('/(?<action>(?:add|del)_user_gadmin)\|(?<group>.+)/', $user_gadmin, $matches);
                    if (!empty($matches['action']) && !empty($matches['group'])) {
                        $gadmin = ($matches['action'] === "add_user_gadmin") ? 1 : 0;
                        if (isset($gadmin)) {
                            $replace_pairs = array(
                                '{$username}' => $_SESSION['postdata']['select_user'],
                                '{$group}' => trim($matches['group']) ,
                                '{$gadmin}' => $gadmin
                            );
                            set_cmd_result($data->func(['user_gadmin', $replace_pairs]));
                        }
                    }
                }
                $_SESSION['update']['userfile'] = true;
            }
            if ($data->check_user() && is_array($value) && (preg_grep('/^user_toggle_gadmin\|.+$/', $value))) {
                foreach ($value as $toggle_gadmin) {
                    preg_match('/user_toggle_gadmin\|(?<group>.+)/', $toggle_gadmin, $matches);
                    if (!empty($matches['group'])) {
                        $replace_pairs = array(
                            '{$username}' => $_SESSION['postdata']['select_user'],
                            '{$group}' => trim($matches['group']) ,
                        );
                        set_cmd_result($data->func(['user_chgadmin', $replace_pairs]));
                    }
                }
                $_SESSION['update']['userfile'] = true;
                $_SESSION['update']['user_group'] = true;
                $_SESSION['update']['groups'] = true;
            }
            if ($data->check_user() && is_array($value) && (preg_grep('/^(add|del)_user_pgroup?\|.+/', $value))) {
                foreach ($value as $user_pgroup) {
                    preg_match('/(?<action>(?:add|del)_user_pgroup)\|(?<pgroup>.+)/', $user_pgroup, $matches);
                    if (!empty($matches['action']) && !empty($matches[''])) {
                        $replace_pairs = array(
                            '{$username}' => $_SESSION['postdata']['select_user'],
                            '{$pgroup}' => trim($matches['pgroup']),
                        );
                        set_cmd_result($data->func([$matches['action'], $replace_pairs]));
                    }
                }
                $_SESSION['update']['pgroup'] = true;
            }
            unset($_SESSION['postdata']['userGrpCmd']);
        } //end userGrpCmd
        if ($name === 'grpCmd') {
            $debug->print(pos: 'controller', msg: 'grpCmd', _SESSION_postdata: $_SESSION['postdata']);
            if ($value === 'group_add' && !empty($_SESSION['postdata']['group_add'])) {
                $debug->print(pos: 'controller', msg: 'got group_add');
                $replace_pairs = array('{$group}' => $_SESSION['postdata']['group_add']);
                set_cmd_result($data->func(['group_add', $replace_pairs]));
            }
            if (preg_match('/^group_del\|.+$/', $value)) {
                $group = preg_replace('/^group_del\|/', '', $value);
                if (!empty($group)) {
                    $replace_pairs = array('{$group}' => $group);
                    set_cmd_result($data->func(['group_del', $replace_pairs]));
                }
            }
            $_SESSION['update']['groups'] = true;
            unset($_SESSION['postdata']['grpCmd']);
        } //end grpCmd
        // sort users and groups arrays
        if ($name === 'sortList') {
            $debug->print(pos: 'controller', msg: 'got grpCmd');
            if (preg_match('/^sort_.+\|.+$/', $value)) {
                preg_match('/sort_(?<list>.+)\|(?<order>(?:a-z|z-a|group))/', $value, $sort_matches);
                $debug->print(pre: true, pos: 'controller', msg: 'grpCmd', sort_matches: $sort_matches);
                if (isset($sort_matches)) {
                    if (!empty($sort_matches['list']) && !empty($sort_matches['order'])) {
                        //unset($_SESSION['postdata']['applyBtn']);
                        unset($_SESSION['postdata']);
                        $_SESSION['postdata']['select_user'] = htmlspecialchars(trim($_GET['user']));
                    } else {
                        unset($_SESSION['display_sort']);
                    }
                }
            }
        } //end sortList
        /*
        if (!empty($name)) {
            unset($_SESSION['postdata'][$name]);
        }
        */
    } // end foreach postdata
    //print "</pre>" . PHP_EOL;
    if (isset($sort_matches)) {
        if (!empty($sort_matches['list']) && !empty($sort_matches['order'])) {
            sort_array($sort_matches);
        } else {
            unset($_SESSION['display_sort']);
        }
    }
}
