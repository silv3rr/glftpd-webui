#!/bin/sh

# glftpd-webui auth setup :: nginx templates and config.php

if [ "$( id -u )" -ne 0 ]; then
    echo "ERROR: $0 needs root to run"
    exit 1
fi

if [ ! -s /etc/nginx/http.d/webui.conf ]; then
  echo "ERROR: /etc/nginx/http.d/webui.conf not found"
  exit 1
fi

# shellcheck disable=SC2016
default_htpasswd='shit:$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740'
modes="basic|glftpd|both|none"

if [ -z "$APPDIR" ]; then
  for i in /app /var/www/glftpd-webui; do
    if [ -d "$i" ]; then
      APPDIR="$i"
      break
    fi
  done
fi

if [ -z "$AUTHDIR" ]; then
  for i in /auth /var/www/glftpd-webui-auth; do
    if [ -d "$i" ]; then
      AUTHDIR="$i"
      break
    fi
  done
fi

if [ -n "$WEBUI_AUTH_USER" ]; then
  USERNAME="$WEBUI_AUTH_USER"
fi
if [ -n "$WEBUI_AUTH_pairSS" ]; then
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

if cp -f /etc/nginx/auth.d/allow.conf.template /etc/nginx/auth.d/allow.conf; then
  RESULT_ALLOW_TMPL=1
fi

if echo "$AUTH" | grep -Eq "^($modes)$"; then
  echo "$(date '+%F %T') Setting webgui auth mode to '$AUTH'..." | logger -s
  case $AUTH in
    basic)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        if ! command -v htpasswd >/dev/null 2>&1; then
          echo "$PASSWORD" | htpasswd -n -i "$USERNAME" > /etc/nginx/.htpasswd && RESULT_HTPASSWD=1
        else
          printf '%s:%s\n' "$USERNAME" "$(openssl passwd -5 "$PASSWORD")">> /etc/nginx/.htpasswd && RESULT_HTPASSWD=1
        fi
      else
        echo "$default_htpasswd" > /etc/nginx/.htpasswd && RESULT_HTPASSWD=1
      fi
      rm -f /etc/nginx/auth.d/auth_request.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/auth.d/auth_off.conf.template /etc/nginx/auth.d/auth_off.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/auth.d/auth_basic.conf.template /etc/nginx/auth.d/auth_basic.conf && RESULT_TMPL=1 || RESULT_TMPL=0
    ;;
    glftpd)
      rm -f /etc/nginx/.htpasswd && RESULT_TMPL=1 || RESULT_TMPL=0
      rm -f /etc/nginx/auth.d/auth_basic.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/auth.d/auth_off.conf.template /etc/nginx/auth.d/auth_off.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_requ && RESULT_TMPL=1 || RESULT_TMPL=0est.conf
      #echo "auth_basic off;" > /etc/nginx/auth.d/auth_basic.conf
    ;;
    both)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        if [ -n "$PASSWORD" ]; then
          PMSG=" and password to '***'"
        fi
        echo "$(date '+%F %T') Setting webgui username to '$USERNAME'$PMSG..." | logger -s
        out="$(sed "s|^\(.*'http_auth'\s*=>\).*$|\1 \['username\' => '$USERNAME', 'password' => '$PASSWORD'\],|" "${APPDIR:-/app}/config.php";)"
        if [ -n "$out" ]; then
          echo "$out" > "${APPDIR:-/app}/config.php" && RESULT_USER_PASS_CONFIG=1
        fi
      fi
      rm -f /etc/nginx/.htpasswd && RESULT_TMPL=1 || RESULT_TMPL=0
      rm -f /etc/nginx/auth.d/auth_basic.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/auth.d/auth_off.conf.template /etc/nginx/auth.d/auth_off.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      cp -f /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_request.conf && RESULT_TMPL=1 || RESULT_TMPL=0
    ;;
    none)
      rm -f /etc/nginx/.htpasswd && RESULT_TMPL=1 || RESULT_TMPL=0
      rm -f /etc/nginx/http.d/auth-server.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      rm -f /etc/nginx/auth.d/auth_off.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      rm -f /etc/nginx/auth.d/auth_basic.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      rm -f /etc/nginx/auth.d/auth_request.conf && RESULT_TMPL=1 || RESULT_TMPL=0
      rm -f /etc/nginx/sites-enabled/auth-server && RESULT_TMPL=1 || RESULT_TMPL=0
    ;;
  esac

  if [ ! -f /.dockerenv ] && [ -n "$APPDIR" ] && [ -n "$AUTHDIR" ]; then
    sed -i 's|/app/config.php|'"${APPDIR}/config.php"'|g' "${AUTHDIR}/index.php" "${AUTHDIR}/login.php"
    sed -i "s|'/app/|'${APPDIR}/|g" "${AUTHDIR}/index.php"
    sed -i 's|root /app;|root '"${APPDIR}"';|g' /etc/nginx/http.d/webui.conf
    sed -i 's|root /auth;|root '"${AUTHDIR}"';|g' /etc/nginx/http.d/auth-server.conf
  fi
  out=$(sed -r "s/^(.*'auth'\s*=>\s*\")($modes|)(\",.*)$/\1$AUTH\3/" "${APPDIR:-/app}/config.php")
  if [ -n "$out" ]; then
    echo "$out" > "${APPDIR:-/app}/config.php" && RESULT_AUTH_CONFIG=1
  fi
  pgrep nginx >/dev/null 2>&1 && /usr/sbin/nginx -s reload && RESULT_RELOAD_NGINX=1
elif [ -n "$1" ]; then
  echo "$0 <$modes> [username] [password]"
fi

if [ "$AUTH" = "basic" ]; then
  RESULT="$RESULT HTPASSWD=$RESULT_HTPASSWD "
  fi
if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
  RESULT="$RESULT CONFIG_USER_PASSWORD=$RESULT_USER_PASS_CONFIG "
fi
echo "RESULT: $RESULT ALLOW=$RESULT_ALLOW_TMPL TEMPLATES=$RESULT_TMPL CONFIG_AUTH_MODE=$RESULT_AUTH_CONFIG RELOAD_NGINX=$RESULT_RELOAD_NGINX"

