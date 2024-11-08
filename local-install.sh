#!/bin/bash
VERSION=V4
################################## ################################   ####  # ##
# >> GLFTPD-WEBUI-LOCAL-INSTALLER
################################## ################################   ####  # ##
#
# VARIABLES:
#
# APPDIR="<path>"                  app dir (default is 'glftpd-webui')
# WWW_ROOT="<path>"                web server root (default is '/var/www'
# BIN_DIR="<path>                  binaries (default is /usr/local/bin)
# GL_DIR="<path>"                   glftpd install dir (default is '/glftpd')
# YES=0|1                          auto answer yes to prompts
# DRYRUN=0|1                       test, dont change anything
#
# HELP: './local-install.sh -h'
#
################################## ################################   ####  # ##

echo
echo "GLFTPD-WEBUI-${VERSION} :: LOCAL INSTALLER"
echo "----------------------------------"
echo

APPDIR="glftpd-webui"
AUTHDIR="glftpd-webui-auth"
WWW_ROOT="${1:-/var/www}"
BIN_DIR="${2:-/usr/local/bin}"
GL_DIR="${3:-/glftpd}"
#YES=0
#DRYRUN=1

BINS="hashgen passchk pywho spy"
STATIC_BINS="auth.sh gltool.sh gotty"
#AUTH_LIBS="lib/apr1-md5 lib/ip-lib lib/PHP-Htpasswd"
CHECK_SRC_PATHS="
    assets/js/*.js
    assets/css/*.css
    bin/*.sh
    etc/*.conf
    src/auth/*.php
    src/ui/*.php
    templates/*.html
"

COPY="cp"
MKDIR="mkdir"
LINK="ln"
EXIT_ERR="exit 1"
HTPASSWD_OUT=/etc/nginx/.htpasswd 
SED="sed"
CHOWN="chown"


# help

echo "Installs webgui to host with existing glftpd setup (tested on debian)"
echo "Note that using *docker* is recommended instead of local install"
echo
if echo "$*" | grep -Eiq '\-h'; then
    echo "Usage: $0 [WWW_ROOT] [BIN_DIR] [GL_DIR] [YES|DRYRUN]"
    echo
    echo "Options:"
    echo
    echo "  WWW_ROOT    webservers document root  (default: /var/www)"
    echo "  BIN_DIR     dir for binaries and scripts like gltool"
    echo "  GL_DIR       glftpd dir  (default: /glftpd)"
    echo "  DRYRUN      shows commands only, do not actually run"
    echo "  YES         do not prompt"
    echo
    echo "This script checks requirements , tries to configure nginx,"
    echo "copy files to document root, install binares etc."
    echo "It will ask before making changes. See README.md for details."
    echo
    exit 0
fi


echo "To abort press 'CTRL-C', for help run '$0 -h'"
echo


# args

for i in $WWW_ROOT $BIN_DIR $GL_DIR; do
    if ! test -d "$i"; then
        echo "ERROR: specified dir '$i' not found"
        $EXIT_ERR
    fi
done

if echo "$*" | grep -Eq 'YES'; then
    YES=1
    echo "Got 'Yes' option, skipping prompts"
    echo
fi
if echo "$*" | grep -Eq 'DRYRUN'; then
    COPY="echo cp "
    MKDIR="echo mkdir "
    LINK="echo ln "
    EXIT_ERR="echo exit 1"
    HTPASSWD_OUT="/dev/stdout"
    SED="echo sed "
    CHOWN="echo chown "
    echo "Dry-run enabled"
    echo
fi

# pre checks

for i in $CHECK_SRC_PATHS; do
    if [ ! -e "$i" ]; then
        echo "ERROR: missing '$i'"
        $EXIT_ERR
    fi
done

if [ "${YES:-0}" -ne 1 ]; then
    if [ -d "${WWW_ROOT}/${APPDIR}" ]; then
        # shellcheck disable=SC2162
        read -N 1 -s -p "Directory '${WWW_ROOT}/${APPDIR}' already exists, continue? [yN] " answer_exists
        echo
        if ! echo "$answer_exists" | grep -Eiq "^y$"; then
            exit
        fi
        echo
    fi
fi

if [ "$( id -u )" -ne 0 ]; then
    echo "ERROR: $0 needs root to run"
    exit 1
fi


# shellcheck disable=SC1091
. /etc/os-release

if [ -z "$ID" ]; then
    ID="<none>"
fi

echo "Detected OS ID: '$ID'"

if [ "$ID" = "debian" ]; then
    for i in nginx php php-fpm php-curl; do
        if ! dpkg -s $i >/dev/null 2>&1; then
            echo "Package '$i' not installed"
        fi
    done
fi

for i in nginx php; do
    if ! command -v $i >/dev/null 2>&1; then
        echo "ERROR: '$i' not found"
        $EXIT_ERR
    fi
done

if [ ! -d /etc/nginx ]; then
    echo "ERROR: /etc/nginx not found"
    $EXIT_ERR
fi

if ! nginx -V 2>&1 | grep -Eq -- '--with-http_auth_request_module'; then
    echo "ERROR: nginx module http_auth_request not installed"
    $EXIT_ERR
fi

if ! find /etc/php/*/fpm -type f -print >/dev/null 2>&1; then
    echo "ERROR: /etc/php/*/fpm not found"
    $EXIT_ERR
fi

if ! find /run/php -name \*fpm\* >/dev/null 2>&1; then
    echo "ERROR: php-fpm sock not found in /run"
    $EXIT_ERR
fi

for i in curl ctype ftp json openssl; do
    if ! php -m 2>/dev/null | grep -Eq "^${i}$"; then
        echo "ERROR: php module '$i' not found"
        $EXIT_ERR
    fi
done

for i in $WWW_ROOT $BIN_DIR; do
    if [ ! -d "$i" ]; then
        echo "ERROR: '$i' not found"
        $EXIT_ERR
    fi
done

echo

if [ -z "$GL_DIR" ]; then
    for i in /glftpd /jail/glftpd; do
        if [ -d $i ]; then
            GL_DIR="$i"
            break
        fi
    done
fi

if [ -d "$GL_DIR" ]; then
    echo "Detected glftpd dir: '$GL_DIR'"
    echo
    #if [ -z "$2" ]; then
    #    BIN_DIR="${GL_DIR}/bin"
    #    echo "Bin dir set to: '${GL_DIR}/bin'"
    #fi
else    
    echo "Could not locate glftpd dir"
    $EXIT_ERR
fi


# show open tcp ports with process

func_ports() {
    awk 'function hextodec(str,ret,n,i,k,c) {
            ret = 0
            n = length(str)
            for (i = 1; i <= n; i++) {
                c = tolower(substr(str, i, 1))
                k = index("123456789abcdef", c)
                ret = ret * 16 + k
            }
            return ret
        }
        {
            x=hextodec(substr($2,index($2,":")-2,2))
            for (i=5; i>0; i-=2) {
                x = x"."hextodec(substr($2,i,2))d
                c = "find /proc/*/fd/* -type l -printf '\''%p %l\n'\'' 2>/dev/null | grep socket:.*" $10 " || echo 0"
                c | getline g; close(cmd)
            }
        }
    {
            print g,x":"hextodec(substr($2,index($2,":")+1,4))                
    }' < /proc/net/tcp
}

#echo "Open tcp ports:"
#func_ports | while read -r i; do
#    pid="$( echo "$i" | cut -d/ -f3)"
#    if [ -e "/proc/${pid}/comm" ]; then 
#        comm="$( < "/proc/${pid}/comm" )"
#    fi
#    echo "${i}  \"${comm:-"<none>"}\" (pid=$pid)"
#done
 
GL_PORT="$(sed -n 's/^glftpd *\([0-9]\+\)\/.*/\1/p' /etc/services)"
if ! echo "$GL_PORT" | grep -Eq '^[0-9]+$'; then
    GL_PORT="$(systemctl cat glftpd.socket | grep ListenStream= | awk -F: '{ print $(NF) }')"
fi

if [ -s /glftpd/sitebot/eggdrop.conf ]; then
    BOT_PORT="$(sed -n 's|^listen \([0-9]\+\) .*$|\1|p' /glftpd/sitebot/eggdrop.conf)"  
fi

if [ "${YES:-0}" -ne 1 ]; then
    # shellcheck disable=SC2162
    read -N 1 -s -p "Installing to web root directory '${WWW_ROOT}' in subdirs '${APPDIR}' and '${AUTHDIR}', continue? [Yn] " answer_install
    echo
    if echo "$answer_install" | grep -Eiq "^n$"; then
        exit
    fi
    echo
fi


# copy to document root

$MKDIR -v -p "${WWW_ROOT}/${APPDIR}"
$COPY -u -r -v src/ui/*.php src/ui/favicon.ico "${WWW_ROOT}/${APPDIR}"
for i in assets lib templates; do
    $COPY -u -r "$i" "${WWW_ROOT}/${APPDIR}"
done
$MKDIR -v -p "${WWW_ROOT}/${APPDIR}/lib"
$COPY -u -r -v src/auth/* "${WWW_ROOT}/${AUTHDIR}"
#$COPY -u -r $AUTH_LIBS "${WWW_ROOT}/${AUTHDIR}/lib"
$COPY -u -r -v README.md docs "${WWW_ROOT}/${APPDIR}/templates"
if [ ! -s "${WWW_ROOT}${APPDIR}/config.php" ]; then
    $COPY -v src/config.php.dist "${WWW_ROOT}/${APPDIR}/config.php"
fi


# nginx config

for i in dhparam.pem webui.cnf webui.crt webui.key; do
    $COPY -u -v -p etc/nginx/$i /etc/nginx
done
$MKDIR -v -p /etc/nginx/http.d
$MKDIR -v -p /etc/nginx/auth.d
$COPY -v etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/glftpd-webui-auth-server.conf
$COPY -v etc/nginx/http.d/webui.conf.template /etc/nginx/http.d/glftpd-webui.conf
for i in allow.conf auth_basic.conf auth_off.conf auth_request.conf; do
    $COPY -v etc/nginx/auth.d/$i.template /etc/nginx/auth.d
done
$COPY -v etc/nginx/auth.d/allow.conf.template /etc/nginx/auth.d/allow.conf

if [ ! -s /etc/nginx/.htpasswd ]; then
    # shellcheck disable=SC2016
    echo 'shit:$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740' > $HTPASSWD_OUT
fi

$SED -i 's|server glftpd:|server localhost:|g' /etc/nginx/http.d/glftpd-webui.conf
$SED -i 's|root /app;|root '"${WWW_ROOT}/${APPDIR}"';|g' /etc/nginx/http.d/webui.conf.template /etc/nginx/http.d/glftpd-webui.conf
$SED -i 's|root /auth;|root '"${WWW_ROOT}/${AUTHDIR}"';|g' /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/glftpd-webui-auth-server.conf
echo

# chown config.php, copy .gotty, vhost config

case $ID in
    alpine)
        $CHOWN -v nginx:root "${WWW_ROOT}/${APPDIR}/config.php"
        $COPY -v -u etc/dot_gotty /var/lib/nginx/.gotty
    ;;   
    debian)
        $CHOWN -v www-data:root "${WWW_ROOT}/${APPDIR}/config.php"
        $COPY -v -u etc/dot_gotty /var/www/.gotty
        $LINK -sf /etc/nginx/http.d/glftpd-webui-auth-server.conf /etc/nginx/sites-enabled/glftpd-webui-auth-server
        $LINK -sf /etc/nginx/http.d/glftpd-webui.conf /etc/nginx/sites-enabled/glftpd-webui
    ;;
    centos|almalinux|rocky)
        $CHOWN -v nginx:root "${WWW_ROOT}/${APPDIR}/config.php"
        $COPY -v -u etc/dot_gotty /var/lib/nginx/.gotty
        $LINK -sf /etc/nginx/http.d/glftpd-webui-auth-server.conf /etc/nginx/conf.d/glftpd-webui-auth-server.conf
        $LINK -sf /etc/nginx/http.d/glftpd-webui.conf /etc/nginx/conf.d/glftpd-webui.conf
    ;;
    \<none\>|*)
        echo "You need to manually set owner of '${WWW_ROOT}/${APPDIR}/config.php' to webserver user."
        echo "You need to manually copy 'etc/dot_gotty' to webserver user homedir."
    ;;
esac

# set auth dir

$SED -i 's|/app/config.php|'"${WWW_ROOT}/${APPDIR}/config.php"'|g' "${WWW_ROOT}/${AUTHDIR}/index.php" "${WWW_ROOT}/${AUTHDIR}/login.php"
$SED -i "s|'/app/|'${WWW_ROOT}/${APPDIR}/|g" "${WWW_ROOT}/${AUTHDIR}/index.php"

echo

# reload nginx

if nginx -t; then
    if ! nginx -s reload; then
        echo "ERROR: could not reload nginx"
    fi
else
    echo "ERROR: nginx config test failed"
fi


# sudo

if [ -d /etc/sudoers.d ]; then
    $COPY -u -v etc/sudoers.d/glftpd-web /etc/sudoers.d
else
    echo "ERROR: /etc/sudoers.d not found"
fi


# copy binaries

echo

if [ "${YES:-0}" -ne 1 ]; then
    # shellcheck disable=SC2162
    read -N 1 -s -p "Copying binaries to '$BIN_DIR', continue? [Yn] " answer_bin
    if echo "$answer_bin" | grep -Eiq "^n$"; then
        exit
    fi
    echo
fi

$COPY -u -v etc/pywho.conf "$BIN_DIR"
$COPY -u -v etc/spy.conf "$BIN_DIR"

for i in $STATIC_BINS; do
    $COPY -u -v "bin/$i" /usr/local/bin
done

# ID: debian, alpine, centos, ...
for i in $BINS; do
    if [ -s "bin/${ID}/${i}" ]; then
        $COPY -u -v "bin/${ID}/${i}" "$BIN_DIR"
    fi
done

CHECK_BINS=""
for i in $BINS; do
    if [ ! -x "$BIN_DIR/$i" ]; then
        CHECK_BINS+="$i "
    fi
done

if [ "$CHECK_BINS" != "" ]; then
    echo
    echo "You need to manually install these missing binaries:"
    echo
    for i in $CHECK_BINS; do
        case $i in
            hashgen)  echo "Recompile '$i':  gcc -o $BIN_DIR/hashgen bin/hashgen.c -lcrypto -lcrypt" ;;
            passchk)  echo "Recompile '$i':  gcc -o $BIN_DIR/passchk bin/passchk.c -lssl -lcrypto -lcrypt" ;;
            spy)      echo "Download '$i': https://github.com/silv3rr/pyspy/releases ('$ID') and extract to '$BIN_DIR'" ;;
            pywho)    echo "Download '$i': https://github.com/silv3rr/pywho/releases ('$ID') and extract to '$BIN_DIR'" ;;
            *)        echo "'$i'"
        esac
    done
fi


# config.php

$SED -i -r "s|^(.*'bin_dir'\s*=>\s*\")(.*)(\",.*)$|\1${BIN_DIR}\3|" "${WWW_ROOT}/${APPDIR}/config.php"
$SED -i -r "s|^(.*'glftpd_dir'\s*=>\s*\")(.*)(\",.*)$|\1${GL_DIR}\3|" "${WWW_ROOT}/${APPDIR}/config.php"

echo

if [ "${YES:-0}" -ne 1 ]; then
    echo -n "Current 'glftpd' listen port is '${GL_PORT:-"<none>"}' (localhost). "
    read -r -p "Press ENTER or set a different port: " answer_gl_port
    echo
fi
if echo "$answer_gl_port" | grep -Eq '^[0-9]+$'; then
    GL_PORT="$answer_gl_port"
fi
if [ -z "$GL_PORT" ]; then
    echo "ERROR: invalid glftpd port, config.php unchanged"
    echo
fi
$SED -i "s|^\(.*\"ftpd\"\s*=>\).*$|\1 \['host\' => 'localhost', 'port' => \"$GL_PORT\"\],|" "${WWW_ROOT}/${APPDIR}/config.php"

if [ "${YES:-0}" -ne 1 ]; then
    echo -n "Current 'sitebot' listen port is '${BOT_PORT:-"<none>"}' (localhost). "
    read -r -p "Press ENTER or set a different port: " answer_bot_port
    echo
fi
if echo "$answer_bot_port" | grep -Eq '^[0-9]+$'; then
    BOT_PORT="$answer_bot_port"
fi
if [ -z "$BOT_PORT" ]; then
    echo "No sitebot configured or invalid port, config.php unchanged"
    echo
fi
$SED -i "s|^\(.*\"sitebot\"\s*=>\).*$|\1 \['host\' => 'localhost', 'port' => \"$BOT_PORT\"\],|" "${WWW_ROOT}/${APPDIR}/config.php"


# spy

if ! pgrep -f "spy --web" >/dev/null; then
    echo "Starting web spy in background on localhost port 5000:"
    echo
    echo 'nohup bash -c "'"${BIN_DIR}/spy --web"'" </dev/null &>/dev/null &'
    echo
    nohup bash -c "${BIN_DIR}/spy --web" </dev/null &>/dev/null &
    echo
    if [ "${YES:-0}" -ne 1 ]; then
        echo "To auto start you can use bin/spy.sh, which runs the same cmd."
        echo
    fi
fi


# ---

echo "Done. Verify install is working for your specific environment."
echo
