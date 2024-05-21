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
    if (is_string($cmd_out)) {
        $result .= preg_replace('/.*((?:DONE|INFO|WARN|ERROR): .*)/', '\1<br>', $cmd_out);
    } elseif (is_array($cmd_out)) {
        $result = "";
        foreach ($cmd_out as $line) {
            $result .= preg_replace('/.*((?:DONE|INFO|WARN|ERROR): .*)/', '\1<br>', $line);
            //print "DEBUG: controller set_cmd_result \$result={$result}<br>";
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
    print "<div style='color:red;border:2px solid'><h2>DEBUG: RESET</h2>" . PHP_EOL;
    print "<h2>Session was reset<br>" . PHP_EOL;
    print "Remove query param and reload, exiting...</h2><div>" . PHP_EOL;
    exit();
}

// form submits, input controls and routing. mark changed values

if (isset($_SESSION['postdata'])) {
    if (cfg::get('debug') > 0) {
        //print "DEBUG: controller-1 \$_SESSION['postdata']=" . print_r($_SESSION['postdata'], true) . "<br>" . PHP_EOL;
    }
    // 'xxCmd' buttons for logs etc
    if (!empty($_SESSION['postdata']['dockerCmd'])) {
        if ($_SESSION['postdata']['dockerCmd'] === 'glftpd_logs') {
            include_once 'templates/logs.html';
            print $data->func($_SESSION['postdata']['dockerCmd']);
            unset($_SESSION['postdata']['dockerCmd']);
            print '</pre>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL;
            exit();
        }
        if ($_SESSION['postdata']['dockerCmd'] === 'glftpd_inspect') {
            include_once 'templates/logs.html';
            print format_cmdout($data->func($_SESSION['postdata']['dockerCmd']));
            unset($_SESSION['postdata']['dockerCmd']);
            print '</pre>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL;
            exit();
        }
    }
    if (!empty($_SESSION['postdata']['gltoolCmd'])) {
        if ($_SESSION['postdata']['gltoolCmd'] === 'gltool_log') {
            include_once 'templates/logs.html';
            print format_cmdout($data->func($_SESSION['postdata']['gltoolCmd']));
            unset($_SESSION['postdata']['gltoolCmd']);
            print '</pre>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL;
            exit();
        }
        if ($_SESSION['postdata']['gltoolCmd'] === "show_user_stats") {
            if ($data->check_user() && isset($_SESSION['userfile'])) {
                $html = htmlspecialchars(addslashes(fmt_user_stats()));
            } else {
                $html = "&lt;user:none&gt;";
            }
            $_SESSION['modal'] = array('func' => 'show', 'title' => "User Stats", 'text' => $html);
            unset($_SESSION['postdata']['gltoolCmd']);
        }
    }
    if (isset($_SESSION['postdata']['user_group'])) {
        if ($_SESSION['postdata']['user_group'] === "Select group...") {
            unset($_SESSION['postdata']['user_group']);
        }
    }

    // loop over postdata to get remaining inputs

    foreach ($_SESSION['postdata'] as $name => $value) {
        if (cfg::get('debug') > 9) {
            if (is_scalar($name)) {
                print "DEBUG: controller scalar \$name={$name}" . "<br>" . PHP_EOL;
            }
            if (is_array($name)) {
                print "DEBUG: controller array \$name=$" . print_r($name, true) . "<br>" . PHP_EOL;
            }
            if (is_scalar($value) && !empty($value)) {
                print "DEBUG: controller scalar \$value={$value}<br>" . PHP_EOL;
            }
            if (is_array($value)) {
                print "DEBUG: controller array \$value=" . print_r($value, true) . "<br>" . PHP_EOL;
            }
        }
        // sitewho, tty, other cmds
        if (is_scalar($value)) {
            // sitewho
            if ($name === "glCmd" && !empty($value) && $value === "pywho") {
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
                    if (cfg::get('modal')['pywho']) {
                        $format_html = htmlspecialchars(addslashes('<pre>' . $html . '</pre>'));
                        $_SESSION['modal'] = array('func' => 'show', 'title' => "Glftpd Sitewho (by pywho)", 'text' => $format_html);
                    } else {
                        $_SESSION['cmd_output'] = $html;
                    }
                }
                unset($_SESSION['postdata'][$name]);
            } elseif ($name === "termCmd" && !empty($value)) {
                if (preg_match('/^kill_[a-z_]+$/', $value)) {
                    $_SESSION['cmd_output'] = format_cmdout($data->func($value));
                } else {
                    set_cmd_result($data->func($value));
                    $_SESSION['modal'] = array('func' => 'tty');
                }
                unset($_SESSION['postdata']['termCmd']);
            } elseif (preg_match('/^(glCmd|dockerCmd|gltoolCmd)$/', $name) && !empty($value)) {
                if (cfg::get('modal')['commands']) {
                    $_SESSION['modal'] = array('func' => 'show', 'title' => "Output", 'text' => $cmd_out );
                } else {
                    if (preg_match('/^glftpd_(start|stop|restart)$/', $value)) {
                        $_SESSION['results'][$value] = "DONE: {$value}";
                    } else {   
                        $_SESSION['cmd_output'] = format_cmdout($data->func($value));
                    }
                }
                unset($_SESSION['postdata'][$name]);
            }
        }

        // apply button

        if(isset($_SESSION['postdata']['applyBtn'])) {
            //print "DEBUG: controller got applyBtn<br>";
            //print "<pre>DEBUG: controller \$_SESSION['postdata']=" . print_r($_SESSION['postdata'], true) . " (ipCmd={$_SESSION['postdata']['ipCmd']})</pre><br>" . PHP_EOL;

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
                //print "DEBUG: controller got ipCmd<br>";
                $userfile_masks = [];
                $ipcmd_masks = preg_split('/[ \n]/', $_SESSION['postdata']['ipCmd'], -1, PREG_SPLIT_NO_EMPTY);
                //print "<pre>DEBUG: controller ipcmd_masks=" . print_r($ipcmd_masks,true) . "</pre><br>".PHP_EOL;
                if ($data->check_user() && !empty($_SESSION['userfile']) && !empty($_SESSION['userfile']['IP'])) {
                    $userfile_masks = [];
                    if (is_array($_SESSION['userfile']['IP'])) {
                        $userfile_masks = $_SESSION['userfile']['IP'];
                    } else  {
                        $userfile_masks = array($_SESSION['userfile']['IP']);
                    }
                }
                //print "<pre>DEBUG: controller userfile_masks=" . print_r($userfile_masks,true) . "</pre><br>".PHP_EOL;
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
                ///print "DEBUG: controller got setPassCmd<br>";
                $replace_pairs = array(
                    '{$username}' => $_SESSION['postdata']['select_user'],
                    '{$password}' => $_SESSION['postdata']['setPassCmd']
                );
                set_cmd_result($data->func(['password_change', $replace_pairs]));
                $_SESSION['update']['userfile'] = true;
                unset($_SESSION['postdata']['setPassCmd']);
            }
            if ($data->check_user() && isset($_SESSION['postdata']['flagCmd'])) {
                // flags: compare current vs new
                //   - submitted = allflags - newflags
                //   - unsubmitted = rest
                //print "DEBUG: controller got flagcmd<br>";
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
                $flags_add = [];
                $flags_del = flags_list();
                if (is_array($_SESSION['postdata']['flagCmd'])) {
                    $flags_userfile = !empty($_SESSION['userfile']['FLAGS']) ? str_split($_SESSION['userfile']['FLAGS']) : [];
                    //print "<pre>DEBUG: controller flags_userfile=" . print_r($flags_userfile, true) . "</pre>";
                    foreach ($_SESSION['postdata']['flagCmd'] as $flagcmd) {
                        if (preg_grep('/^flag_add\|[0-9A-Z]+$/', $_SESSION['postdata']['flagCmd'])) {
                            preg_match('/flag_add\|(?<flag>[0-9A-Z]+)/', $flagcmd, $matches);
                            if (!empty($matches['flag'])) {
                                if (!in_array($matches['flag'], $flags_userfile)) {
                                    array_push($flags_add, $matches['flag']);
                                }
                                unset($flags_del[$matches['flag']]);
                            }
                        }
                    }
                }
                //print "<pre>DEBUG: controller flags matches['flag']={$matches['flag']} \$flags_userfile=" . print_r($flags_userfile, true) . "</pre><br>" . PHP_EOL;
                //print "<pre>DEBUG: controller flag del $flag</pre>" . "</pre><br>" . PHP_EOL;
                //print "<pre>DEBUG: controller flag unset {$matches['flag']} . "</pre><br>" . PHP_EOL;
                //print "<pre>DEBUG: controller flags-all-2 unset=".print_r($flags_del, true) . "</pre><br>" . PHP_EOL;
                // del all flags not in textarea and/or unused
                if (!empty($flags_del)) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$flags}' => implode(array_keys($flags_del))
                    );
                    $data->func(['flag_del', $replace_pairs]);
                }
                if (!empty($flags_add)) {
                    $replace_pairs = array(
                        '{$username}' => $_SESSION['postdata']['select_user'],
                        '{$flags}' => implode(array_keys($flags_add))
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
            if ($data->check_user() && !empty($_SESSION['postdata']['userCmd']) && $_SESSION['postdata']['userCmd'] === 'reset_user_stats') {
                $replace_pairs = array(
                    '{$username}' => $_SESSION['postdata']['select_user'],
                );
                set_cmd_result($data->func(['reset_user_stats', $replace_pairs]));
                unset($_SESSION['postdata']['userCmd']);
            }
            unset($_SESSION['postdata']['applyBtn']);
        } //end applyBtn

        // submit cmds, without apply

        //print "<pre>DEBUG: controller-3 userCmd postdata = " . print_r($_SESSION['postdata'], true) . "</pre><br>" . PHP_EOL;
        if ($name === 'userCmd') {
            if ($data->check_user() && $value === 'user_del') {
                $replace_pairs = array('{$username}' => $_SESSION['postdata']['select_user']);
                set_cmd_result($data->func(['user_del', $replace_pairs]));
                unset($_SESSION['postdata']['select_user']);
            }
            if ($value === 'user_add') {
                //print "DEBUG: controller got userCmd value={$value}<br>";
                //print "<pre>DEBUG: controller " . print_r($_SESSION['postdata'], true) . "</pre><br>" . PHP_EOL;
                //print "<pre>DEBUG:  controller " . print_r($_POST, true) . "<pre><br>" . PHP_EOL;
                if (!empty($_SESSION['postdata']['user_name']) && !empty($_SESSION['postdata']['user_password'])) {
                    //print "DEBUG: controller got user_name user_password<br>";
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
            //print "DEBUG: controller got userGrpCmd value=" . print_r($value, true) . "<br>";
            foreach ($value as $k => $v) {
                if (in_array($v, [ "Add user to group...",  "Group Admin...", "Add user to privgroup..." ])) {
                    unset($value[$k]);
                }
            }
            //print "DEBUG: controller value array [AFTER]:" . print_r($value, true) . "<br>";
            if ($data->check_user() && is_array($value) && (preg_grep('/^(add|del)_user_group(_all)?\|.+/', $value))) {
                //print "DEBUG: controller userGrpCmd got preg_grep<br>";
                foreach ($value as $user_group) {
                    //print "DEBUG: controller userGrpCmd user_group={$user_group}<br>";
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
                        //print "DEBUG: controller userGrpCmd got \$matches['group']={$matches['group']} user_group={$user_group}<br>";
                        if (!empty($matches['action']) && !empty($matches['group'])) {
                            //print "DEBUG: controller got usergrp matches<br>";
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
            //print "DEBUG: controller got grpCmd<br>";
            // sort users and groups arrays
            if (preg_match('/^sort_.+\|.+$/', $value)) {
                preg_match('/sort_(?<list>.+)\|(?<order>(?:a-z|z-a|group))/', $value, $sort_matches);
                if (isset($sort_matches)) {
                    if (!empty($sort_matches['list']) && !empty($sort_matches['order'])) {
                        sort_array($sort_matches);
                    } else {
                        unset($_SESSION['display_sort']);
                    }
                }
            }
            //print "DEBUG: controller grpCmd \$_SESSION['postdata']=" . print_r($_SESSION['postdata'], true) . "<br>" . PHP_EOL;
            if ($value === 'group_add' && !empty($_SESSION['postdata']['group_add'])) {
                //print "DEBUG: controller got group_add<br>";
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
        /* TODO: test / remove, as it breaks postdata
        if (!empty($name)) {
            unset($_SESSION['postdata'][$name]);
        }
        */
    } // end foreach postData
    //print "</pre>" . PHP_EOL;
    if (isset($sort_matches)) {
        if (!empty($sort_matches['list']) && !empty($sort_matches['order'])) {
            sort_array($sort_matches);
        } else {
            unset($_SESSION['display_sort']);
        }
    }
}
