<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK commands local mode
 *--------------------------------------------------------------------------*/

// params, replace pairs:
//   -u {$username} -p {$password} -g {$group} -i {$mask} -f {$flags} -a {$gadmin}
//   -p {$pgroup} -t {$tagline} -k {$credits} -l {$logins} -r {$ratio}

namespace shit;

return array(
    // action                  command
    'glftpd_status'         => '/bin/systemctl status glftpd.socket || sudo /sbin/service glftpd status',
    'glftpd_start'          => 'sudo -n -u {$runas} /bin/systemctl start glftpd.socket || sudo -n -u {$runas} /bin/service glftpd status',
    'glftpd_stop'           => 'sudo -n -u {$runas} /bin/systemctl stop glftpd.socket || sudo -n -u {$runas} /bin/service glftpd stop',
    'glftpd_restart'        => 'sudo -n -u {$runas} /bin/systemctl restart glftpd.socket || sudo -n -u {$runas} /bin/service glftpd restart',
    'glftpd_kill'           => '/usr/bin/sudo -n -u {$runas} /usr/bin/pkill -9 -f glftpd',
    'ps_glftpd'             => '/usr/bin/pgrep -a -f glftpd',
    'ps_gotty'              => 'sh -c "ps aux|grep \"[gG]otty\" || busybox ps aux|grep \"[gG]otty\""',
    'tty_useredit'          => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || sudo -n -u {$runas} /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$bin_dir}/useredit -r/glftpd/glftpd.conf >/dev/null 2>&1 &"',
    'tty_eggdrop'           => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || sudo -n -u {$runas} /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty busybox telnet localhost 3333 >/dev/null 2>&1 &"',
    'tty_glspy'             => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || sudo -n -u {$runas} /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$bin_dir}/gl_spy"', # >/dev/null 2>&1 &"',
    'tty_pywho'             => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || sudo -n -u {$runas} /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$bin_dir}/pywho >/dev/null 2>&1 &"',
    'tty_pyspy'             => 'sh -c "/usr/bin/killall -9 gotty >/dev/null 2>&1 || sudo -n -u {$runas} /bin/busybox /usr/bin/killall -9 gotty >/dev/null 2>&1; {$bin_dir}/gotty {$bin_dir}/spy >/dev/null 2>&1 &"',
    'kill_gotty'            => 'sudo -n -u {$runas} /usr/bin/killall -9 gotty || sudo -n -u {$runas} /bin/busybox killall -9 gotty',
    'kill_glspy'            => 'sudo -n -u {$runas} /usr/bin/killall -9 gl_spy || sudo -n -u {$runas} /bin/busybox killall -9 gl_spy',
    'kill_useredit'         => 'sudo -n -u {$runas} /usr/bin/killall -9 useredit || sudo -n -u {$runas} /bin/busybox killall -9 useredit"',
    'pywho'                 => 'sh -c "{$bin_dir}/pywho &"',
    'passchk'               => '{$bin_dir}/passchk {$username} {$password} /glftpd/etc/passwd',
    'gltool_log'            => '{$bin_dir}/gltool.sh -c LOGSHOW',
    'gltool_tail'           => '{$bin_dir}/gltool.sh -c LOGTAIL',
    'users_list'            => '{$bin_dir}/gltool.sh -c LISTUSERS',
    'users_raw'             => '{$bin_dir}/gltool.sh -c RAWUSERS',
    'groups_list'           => '{$bin_dir}/gltool.sh -c LISTGROUPS',
    'groups_raw'            => '{$bin_dir}/gltool.sh -c RAWGROUPS',
    'pgroups_raw'           => '{$bin_dir}/gltool.sh -c RAWPGROUPS',
    'ip_list'               => '{$bin_dir}/gltool.sh -c LISTIP -u {$username}',
    'ip_raw'                => '{$bin_dir}/gltool.sh -c RAWIP -u {$username}',
    'ip_add'                => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c ADDIP -u {$username} -i {$mask}',
    'ip_del'                => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c DELIP -u {$username} -i {$mask}',
    'user_add'              => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c ADDUSER -u {$username} -p {$password} -g {$group} -i {$mask} -a {$gadmin}',
    'user_gadmin'           => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c USERGADMIN -u {$username} -g {$group} -a {$gadmin}',
    'user_chgadmin'         => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHGADMIN -u {$username} -g {$group}',
    'user_del'              => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c DELUSER -u {$username}',
    'group_add'             => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c ADDGROUP -g {$group}',
    'group_del'             => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c DELGROUP -g {$group}',
    'group_change'          => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHGRP -u {$username} -g {$group}',
    'add_user_group'        => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c ADDUSERGROUP -u {$username} -g {$group}',
    'del_user_group'        => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c DELUSERGROUP -u {$username} -g {$group}',
    'add_user_pgroup'       => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c ADDUSERPROUP -u {$username} -s {$pgroup}',
    'del_user_pgroup'       => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c DELUSERPGROUP -u {$username} -s {$pgroup}',
    'password_change'       => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHPASS -u {$username} -p {$password}',
    'tagline_change'        => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHTAG -u {$username} -t "{$tagline}"',
    'flag_add'              => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c ADDFLAG -u {$username} -f {$flags}',
    'flag_del'              => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c DELFLAG -u {$username} -f {$flags}',
    'flag_change'           => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHFLAG -u {$username} -f {$flags}',
    'credits_change'        => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHCREDITS -u {$username} -k {$credits}',
    'ratio_change'          => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHRATIO -u {$username} -r {$ratio}',
    'logins_change'         => 'sudo -n -u {$runas} {$bin_dir}/gltool.sh -c CHLOGINS -u {$username} -l {$logins}',
    'userfile_raw'          => '{$bin_dir}/gltool.sh -c RAWUSERFILE -u {$username}',
    'usersgroups_raw'       => '{$bin_dir}/gltool.sh -c RAWUSERSGROUPS',
    'userspgroups_raw'      => '{$bin_dir}/gltool.sh -c RAWUSERSPGROUPS',
    'usergroup_raw'         => '{$bin_dir}/gltool.sh -c RAWUSERGROUP -u {$username}',
    'tag_raw'               => '{$bin_dir}/gltool.sh -c RAWTAG -u {$username}',
    'flag_raw'              => '{$bin_dir}/gltool.sh -c RAWFLAG -u {$username}',
    'creds_raw'             => '{$bin_dir}/gltool.sh -c RAWCREDS -u {$username}',
    'reset_user_stats'      => '{$bin_dir}/gltool.sh -c RESETUSERSTATS -u {$username}',
    //glftpd_status        => 'sudo -n -u {$runas} netstat -nap|grep {$this->cfg["ftpd"]["port"} || sudo -n -u {$runas} ss -nap|grep {$this->cfg["ftpd"]["port"}; }'
);
