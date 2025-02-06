#!/bin/sh
VERSION=V5
################################## ################################   ####  # ##
# >> GLFTPD-WEBUI-AUTH
################################## ################################   ####  # ##
#
# Setup auth mode, changes nginx templates and webgui's config.php
# USAGE: auth.sh <basic|glftpd|both|none> [username] [password]
#
################################## ################################   ####  # ##

AUTH_MODES="basic|glftpd|both|none"
BIND_MNT=0
DOCKERENV=0

# shellcheck disable=SC2016
DEFAULT_HTPASSWD='shit:$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740'

LOG="$(date '+%F %T') GLFTPD-WEBUI-AUTH-${VERSION}"
echo "$LOG"

if [ -d ./etc ] && [ -d ./src ]; then
  echo "INFO: Detected hosts bind mount dirs.."
  BIND_MNT=1
fi

if [ -f "/.dockerenv" ]; then
  echo "INFO: Detected dockerenv.."
  DOCKERENV=1
fi

if [ "${DOCKERENV:-0}" -eq 1 ]; then
  logger "$LOG"
fi

if [ ${BIND_MNT:-0} -eq 0 ] && [ "$( id -u )" -ne 0 ]; then
    echo "ERROR: $0 needs root to run"
    exit 1
fi

if [ -z "$NGINX_ETC_DIR" ]; then
  for i in /etc/nginx ./etc/nginx ; do
    if [ -d "$i" ]; then
      NGINX_ETC_DIR="$i"
      break
    fi
  done
fi

if [ ! -d "$NGINX_ETC_DIR" ]; then
  echo "ERROR: nginx conf dir not found"
  exit 1
fi

if [ ! -s "${NGINX_ETC_DIR}/http.d/webui.conf" ]; then
  echo "ERROR: ${NGINX_ETC_DIR}/http.d/webui.conf not found"
  exit 1
fi

if [ -z "$APP_DIR" ]; then
  for i in /app /var/www/glftpd-webui ./src; do
    if [ -d "$i" ]; then
      APP_DIR="$i"
      break
    fi
  done
fi

if [ -z "$AUTH_DIR" ]; then
  for i in /auth /var/www/glftpd-webui-auth ./src/auth; do
    if [ -d "$i" ]; then
      AUTH_DIR="$i"
      break
    fi
  done
fi

if [ -n "$WEBUI_AUTH_USER" ]; then
  USERNAME="$WEBUI_AUTH_USER"
fi
if [ -n "$WEBUI_AUTH_PASS" ]; then
  PASSWORD="$WEBUI_AUTH_PASS"
fi
if [ -n "$WEBUI_AUTH_MODE" ]; then
  AUTH="$WEBUI_AUTH_MODE"
fi

if [ -z "$AUTH" ] && [ -n "$1" ]; then
  AUTH="$1"
fi
if [ -z "$USERNAME" ] && [ -n "$2" ]; then
  USERNAME="$2"
fi
if [ -z "$PASSWORD" ] && [ -n "$3" ]; then
  PASSWORD="$3"
fi

RESULT_ALLOW_TMPL=0
RESULT_HTPASSWD=0
RESULT_USER_PASS_CONFIG=0
RESULT_AUTH_CONFIG=0
RESULT_RELOAD_NGINX=0
RESULT_TMPL=0

cp -f "${NGINX_ETC_DIR}/auth.d/allow.conf.template" "${NGINX_ETC_DIR}/auth.d/allow.conf" || RESULT_ALLOW_TMPL=1

if echo "$AUTH" | grep -Eq "^($AUTH_MODES)$"; then
  LOG="NOTICE: Setting mode to '$AUTH'..."
  echo "$LOG"
  if [ "${DOCKERENV:-0}" -eq 1 ]; then
    logger "$LOG"
  fi
  case $AUTH in
    basic)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        if command -v htpasswd >/dev/null 2>&1; then
          echo "$PASSWORD" | htpasswd -n -i "$USERNAME" > "${NGINX_ETC_DIR}/.htpasswd" || RESULT_HTPASSWD=1
        elif command -v openssl >/dev/null 2>&1; then
          printf '%s:%s\n' "$USERNAME" "$(openssl passwd -5 "$PASSWORD")">> "${NGINX_ETC_DIR}/.htpasswd" || RESULT_HTPASSWD=1
        fi
      else
        echo "$DEFAULT_HTPASSWD" > "${NGINX_ETC_DIR}/.htpasswd" || RESULT_HTPASSWD=1
      fi
      rm -f "${NGINX_ETC_DIR}/auth.d/auth_request.conf" || RESULT_TMPL=1
      for i in http.d/auth-server.conf auth.d/auth_off.conf auth.d/auth_basic.conf; do
        cp -f "${NGINX_ETC_DIR}/$i.template" "${NGINX_ETC_DIR}/$i" || RESULT_TMPL=1
      done
    ;;
    glftpd)
      rm -f "${NGINX_ETC_DIR}/.htpasswd" || RESULT_TMPL=1
      rm -f "${NGINX_ETC_DIR}/auth.d/auth_basic.conf" || RESULT_TMPL=1
      for i in http.d/auth-server.conf auth.d/auth_off.conf auth.d/auth_request.conf; do
        cp -f "${NGINX_ETC_DIR}/$i.template" "${NGINX_ETC_DIR}/$i" || RESULT_TMPL=1
      done
      #echo "auth_basic off;" > "${NGINX_ETC_DIR}/auth.d/auth_basic.conf"
    ;;
    both)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        test -n "$PASSWORD" && PW_MSG=" and password to '***'"
        LOG="$(date '+%F %T') GLFTPD-WEBUI-AUTH-${VERSION} Setting username to '$USERNAME'${PW_MSG}..."
        echo "$LOG"
        if [ "${DOCKERENV:-0}" -eq 1 ]; then
          logger "$LOG"
        fi
        OUT="$(sed "s|^\(.*'http_auth'\s*=>\).*$|\1 \['username\' => '$USERNAME', 'password' => '$PASSWORD'\],|" "${APP_DIR:-/app}/config.php";)"
        if [ -n "$OUT" ]; then
          echo "$OUT" > "${APP_DIR:-/app}/config.php" || RESULT_USER_PASS_CONFIG=1
        fi
      fi
      rm -f "${NGINX_ETC_DIR}/.htpasswd" || RESULT_HTPASSWD=1
      rm -f "${NGINX_ETC_DIR}/auth.d/auth_basic.conf" || RESULT_TMPL=1
      for i in http.d/auth-server.conf auth.d/auth_off.conf auth.d/auth_request.conf; do
        cp -f "${NGINX_ETC_DIR}/$i.template" "${NGINX_ETC_DIR}/$i" || RESULT_TMPL=1
      done
    ;;
    none)
      rm -f "${NGINX_ETC_DIR}/.htpasswd" || RESULT_TMPL=1
      for i in http.d/auth-server.conf auth.d/auth_off.conf auth.d/auth_basic.conf auth.d/auth_request.conf sites-enabled/auth-server; do
        rm -f "${NGINX_ETC_DIR}/$i" || RESULT_TMPL=1
      done
    ;;
  esac
  #  local install: restore dir paths
  if [ "${DOCKERENV:-0}" -eq 0 ] && [ -n "$APP_DIR" ] && [ -n "$AUTH_DIR" ]; then
    if echo "$APP_DIR" | grep -Eq '^/' && echo "$AUTH_DIR" | grep -Eq '^/' && echo "$NGINX_ETC_DIR" | grep -Eq '^/'; then
      sed -i 's|/app/config.php|'"${APP_DIR}/config.php"'|g' "${APP_DIR}/index.php" "${AUTH_DIR}/login.php"
      sed -i "s|'/app/|'${APP_DIR}/|g" "${APP_DIR}/index.php"
      sed -i 's|root /app;|root '"${APP_DIR}"';|g' "${NGINX_ETC_DIR}/http.d/webui.conf"
      sed -i 's|root /auth;|root '"${AUTH_DIR}"';|g' "${NGINX_ETC_DIR}/http.d/auth-server.conf"
    fi
  fi
  OUT=$(sed -r "s/^(.*'auth'\s*=>\s*\")($AUTH_MODES|)(\",.*)$/\1$AUTH\3/" "${APP_DIR:-/app}/config.php")
  if [ -n "$OUT" ]; then
    echo "$OUT" > "${APP_DIR:-/app}/config.php" || RESULT_AUTH_CONFIG=1
  fi
  if command -v /usr/sbin/nginx >/dev/null 2>&1; then
    pgrep nginx >/dev/null 2>&1 && /usr/sbin/nginx -s reload || RESULT_RELOAD_NGINX=1
  elif [ "${BIND_MNT:-0}" -eq 1 ]; then
    echo  "NOTICE: Reload nginx *manually*"
  fi

elif [ -n "$1" ]; then
  echo "USAGE: $0 <$AUTH_MODES> [username] [password]"
fi

if [ "$AUTH" = "basic" ]; then
  RESULT="$RESULT HTPASSWD=$RESULT_HTPASSWD "
  fi
if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
  RESULT="$RESULT CONFIG_USER_PASSWORD=$RESULT_USER_PASS_CONFIG "
fi
echo "RESULT: BIND_MNT=$BIND_MNT DOCKERENV=$DOCKERENV" | sed -e 's/=0/=false/g' -e 's/=1/=true/g'
echo "RESULT: NGINX_ETC_DIR='$NGINX_ETC_DIR' APP_DIR='$APP_DIR' AUTH_DIR='$AUTH_DIR'"
echo "RESULT: ALLOW=$RESULT_ALLOW_TMPL TEMPLATES=$RESULT_TMPL CONFIG_AUTH_MODE=$RESULT_AUTH_CONFIG RELOAD_NGINX=$RESULT_RELOAD_NGINX  (0=OK, 1=NOK)" #| sed -e 's/=0/=OK/g' -e 's/=1/=NOK/g'
