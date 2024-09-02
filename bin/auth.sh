#!/bin/sh

# glftpd-webui auth setup

if [ ! -s /etc/nginx/http.d/webui.conf ]; then
  echo "ERROR: /etc/nginx/http.d/webui.conf not found"
  exit 1
fi

# shellcheck disable=SC2016
default_htpasswd='shit:$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740'
modes="basic|glftpd|both|none"

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

cp -f /etc/nginx/auth.d/allow_conf.template /etc/nginx/auth.d/allow.conf

if echo "$AUTH" | grep -Eq "^($modes)$"; then
  echo "Setting webgui auth mode to '$AUTH'..."
  case $AUTH in
    basic)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        echo "$PASSWORD" | htpasswd -n -i "$USERNAME" > /etc/nginx/.htpasswd
      else
        echo "$default_htpasswd" > /etc/nginx/.htpasswd
      fi
      cp -f /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -f /etc/nginx/auth.d/auth_off.conf.template /etc/nginx/auth.d/auth_off.conf
      cp -f /etc/nginx/auth.d/auth_basic.conf.template /etc/nginx/auth.d/auth_basic.conf
    ;;
    glftpd)
      cp -f /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -f /etc/nginx/auth.d/auth_off.conf.template /etc/nginx/auth.d/auth_off.conf
      cp -f /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_request.conf
      echo "auth_basic off;" > /etc/nginx/auth.d/auth_basic.conf
    ;;
    both)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        sed -i "s|^\(.*'http_auth'\s*=>\).*$|\1 \['username\' => '$USERNAME', 'password' => '$PASSWORD'\],|" /app/config.php;
      fi
      rm -f /etc/nginx/.htpasswd
      rm -f /etc/nginx/auth.d/auth_basic.conf
      cp -f /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -f /etc/nginx/auth.d/auth_off.conf.template /etc/nginx/auth.d/auth_off.conf
      cp -f /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_request.conf
    ;;
    none)
      rm -f /etc/nginx/.htpasswd
      rm -f /etc/nginx/http.d/auth-server.conf
      rm -f /etc/nginx/auth.d/auth_off.conf
      rm -f /etc/nginx/auth.d/auth_basic.conf
      rm -f /etc/nginx/auth.d/auth_request.conf
    ;;
  esac
  sed -i -r "s/^(.*'auth'\s*=>\s*\")($modes|)(\",.*)$/\1$AUTH\3/" /app/config.php
  pgrep nginx >/dev/null 2>&1 && /usr/sbin/nginx -s reload
elif [ -n "$1" ]; then
  echo "$0 <$modes> [username] [password]"
fi
