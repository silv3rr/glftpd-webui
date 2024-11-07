<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK commands local mode
 *--------------------------------------------------------------------------*/

// params, replace pairs:
//   -u "{$username}" -p "{$password}" -g "{$group}" -i "{$mask}" -f "{$flags}" -a "{$gadmin}
//   -p "{$pgroup}" -t "{$tagline}" -k "{$credits}" -l "{$logins}" -r "{$ratio}

namespace shit;

return array(
    // action                  command
    'glftpd_status'         => '{$env_bus} /bin/systemctl status glftpd.socket || /sbin/service glftpd status',
    'glftpd_start'          => '{$runas} "{$env_bus}" /bin/systemctl start glftpd.socket || {$runas} /bin/service glftpd status',
    'glftpd_stop'           => '{$runas} "{$env_bus}" /bin/systemctl stop glftpd.socket || {$runas} /bin/service glftpd stop',
    'glftpd_restart'        => '{$runas} "{$env_bus}" /bin/systemctl restart glftpd.socket || {$runas} /bin/service glftpd restart',
    'glftpd_kill'           => '{$runas} "{$env_bus}" /usr/bin/pkill -9 -f glftpd',
    'ps_glftpd'             => '/usr/bin/pgrep -a -f glftpd',
    'ps_gotty'              => 'sh -c "ps aux|grep \"[gG]otty\" || busybox ps aux|grep \"[gG]otty\""',
    'tty_useredit'          => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$gl_dir}/bin/useredit -r{$gl_etc}/{$gl_conf} >/dev/null 2>&1 &"',
    'tty_eggdrop'           => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty /bin/busybox telnet 127.0.0.1 {$sitebot_port} >/dev/null 2>&1 &"',
    'tty_glspy'             => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$gl_dir}/bin/gl_spy -r{$gl_etc}/{$gl_conf} >dev/null 2>&1 &"',
    'tty_pywho'             => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$bin_dir}/pywho >/dev/null 2>&1 &"',
    'tty_pyspy'             => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$bin_dir}/spy >/dev/null 2>&1 &"',
    'kill_gotty'            => '/usr/bin/killall -9 gotty || /bin/busybox killall -9 gotty',
    'kill_glspy'            => '/usr/bin/killall -9 gl_spy || /bin/busybox killall -9 gl_spy',
    'kill_useredit'         => '/usr/bin/killall -9 useredit || /bin/busybox killall -9 useredit"',
    'pywho'                 => 'sh -c "{$bin_dir}/pywho &"',
    'passchk'               => '"{$bin_dir}/passchk" "{$username}" "{$password}" "{$gl_dir}/etc/passwd"',
    'gltool_log'            => '"{$bin_dir}/gltool.sh" -c LOGSHOW',
    'gltool_tail'           => '"{$bin_dir}/gltool.sh" -c LOGTAIL',
    'users_list'            => '"{$bin_dir}/gltool.sh" -c LISTUSERS',
    'users_raw'             => '"{$bin_dir}/gltool.sh" -c RAWUSERS',
    'groups_list'           => '"{$bin_dir}/gltool.sh" -c LISTGROUPS',
    'groups_raw'            => '"{$bin_dir}/gltool.sh" -c RAWGROUPS',
    'pgroups_raw'           => '"{$bin_dir}/gltool.sh" -c RAWPGROUPS',
    'ip_list'               => '"{$bin_dir}/gltool.sh" -c LISTIP -u "{$username}"',
    'ip_raw'                => '"{$bin_dir}/gltool.sh" -c RAWIP -u "{$username}"',
    'ip_add'                => '{$runas} "{$bin_dir}/gltool.sh" -c ADDIP -u "{$username}" -i "{$mask}"',
    'ip_del'                => '{$runas} "{$bin_dir}/gltool.sh" -c DELIP -u "{$username}" -i "{$mask}"',
    'user_add'              => '{$runas} "{$bin_dir}/gltool.sh" -c ADDUSER -u "{$username}" -p "{$password}" -g "{$group}" -i "{$mask}" -a "{$gadmin}"',
    'user_gadmin'           => '{$runas} "{$bin_dir}/gltool.sh" -c USERGADMIN -u "{$username}" -g "{$group}" -a "{$gadmin}"',
    'user_chgadmin'         => '{$runas} "{$bin_dir}/gltool.sh" -c CHGADMIN -u "{$username}" -g "{$group}"',
    'user_del'              => '{$runas} "{$bin_dir}/gltool.sh" -c DELUSER -u "{$username}"',
    'group_add'             => '{$runas} "{$bin_dir}/gltool.sh" -c ADDGROUP -g "{$group}"',
    'group_del'             => '{$runas} "{$bin_dir}/gltool.sh" -c DELGROUP -g "{$group}"',
    'group_change'          => '{$runas} "{$bin_dir}/gltool.sh" -c CHGRP -u "{$username}" -g "{$group}"',
    'add_user_group'        => '{$runas} "{$bin_dir}/gltool.sh" -c ADDUSERGROUP -u "{$username}" -g "{$group}"',
    'del_user_group'        => '{$runas} "{$bin_dir}/gltool.sh" -c DELUSERGROUP -u "{$username}" -g "{$group}"',
    'add_user_pgroup'       => '{$runas} "{$bin_dir}/gltool.sh" -c ADDUSERPROUP -u "{$username}" -s "{$pgroup}"',
    'del_user_pgroup'       => '{$runas} "{$bin_dir}/gltool.sh" -c DELUSERPGROUP -u "{$username}" -s "{$pgroup}"',
    'password_change'       => '{$runas} "{$bin_dir}/gltool.sh" -c CHPASS -u "{$username}" -p "{$password}"',
    'tagline_change'        => '{$runas} "{$bin_dir}/gltool.sh" -c CHTAG -u "{$username}" -t "{$tagline}"',
    'flag_add'              => '{$runas} "{$bin_dir}/gltool.sh" -c ADDFLAG -u "{$username}" -f "{$flags}"',
    'flag_del'              => '{$runas} "{$bin_dir}/gltool.sh" -c DELFLAG -u "{$username}" -f "{$flags}"',
    'flag_change'           => '{$runas} "{$bin_dir}/gltool.sh" -c CHFLAG -u "{$username}" -f "{$flags}"',
    'credits_change'        => '{$runas} "{$bin_dir}/gltool.sh" -c CHCREDITS -u "{$username}" -k "{$credits}"',
    'ratio_change'          => '{$runas} "{$bin_dir}/gltool.sh" -c CHRATIO -u "{$username}" -r "{$ratio}"',
    'logins_change'         => '{$runas} "{$bin_dir}/gltool.sh" -c CHLOGINS -u "{$username}" -l "{$logins}"',
    'userfile_raw'          => '"{$bin_dir}/gltool.sh" -c RAWUSERFILE -u "{$username}"',
    'usersgroups_raw'       => '"{$bin_dir}/gltool.sh" -c RAWUSERSGROUPS',
    'userspgroups_raw'      => '"{$bin_dir}/gltool.sh" -c RAWUSERSPGROUPS',
    'usergroup_raw'         => '"{$bin_dir}/gltool.sh" -c RAWUSERGROUP -u "{$username}"',
    'tag_raw'               => '"{$bin_dir}/gltool.sh" -c RAWTAG -u "{$username}"',
    'flag_raw'              => '"{$bin_dir}/gltool.sh" -c RAWFLAG -u "{$username}"',
    'creds_raw'             => '"{$bin_dir}/gltool.sh" -c RAWCREDS -u "{$username}"',
    'reset_user_stats'      => '"{$bin_dir}/gltool.sh" -c RESETUSERSTATS -u "{$username}"',
    'change_auth'           => '{$runas} "{$bin_dir}/auth.sh {$mode} {$username} {$password}',
    'nginx_reload'          => '{$runas} nginx -s reload',
    'glftpd_conn'           => '{$runas} netstat -nap|grep {$this->cfg["glftpd"]["port"} || {$runas} ss -nap|grep {$this->cfg["glftpd"]["port"}; }'
);
