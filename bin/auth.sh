#!/bin/sh

# glftpd-webui auth setup

# shellcheck disable=SC2016
default_htpasswd='shit:$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740'
modes="basic|glftpd|both|none"

rm -rf /etc/nginx/http.d/auth-server.conf
rm -rf /etc/nginx/auth.d/*.conf
cp -rf /etc/nginx/auth.d/allow_conf.template /etc/nginx/auth.d/allow.conf

if [ -n "$WEBUI_AUTH_USER" ]; then
  USERNAME="$WEBUI_AUTH_USER"
fi
if [ -n "$WEBUI_AUTH_PASS" ]; then
  PASSWORD="$WEBUI_AUTH_PASS"
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

if echo "$AUTH" | grep -Eq "^($modes)$"; then
  echo "Setting up webgui auth..."
  case $AUTH in
    basic)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        echo "$PASSWORD" | htpasswd -n -i "$USERNAME" > /etc/nginx/.htpasswd
        sed -i "s|^\(.*'http_auth'\s*=>\).*$|\1 \['username\' => '$USERNAME', 'password' => '$PASSWORD'\],|" /app/config.php;
      else
        echo "$default_htpasswd" > /etc/nginx/.htpasswd
      fi
      cp -rf /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -rf /etc/nginx/auth.d/auth_pages.conf.template /etc/nginx/auth.d/auth_pages.conf
      cp -rf /etc/nginx/auth.d/auth_basic.conf.template /etc/nginx/auth.d/auth_basic.conf
    ;;
    glftpd)
      cp -rf /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -rf /etc/nginx/auth.d/auth_pages.conf.template /etc/nginx/auth.d/auth_pages.conf
      cp -rf /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_request.conf
      echo "auth_basic off;" > /etc/nginx/auth.d/auth_basic.conf
    ;;
    both)
      if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
        sed -i "s|^\(.*'http_auth'\s*=>\).*$|\1 \['username\' => '$USERNAME', 'password' => '$PASSWORD'\],|" /app/config.php;
      fi
      rm -rf /etc/nginx/.htpasswd
      rm -rf /etc/nginx/auth.d/auth_basic.conf
      cp -rf /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -rf /etc/nginx/auth.d/auth_pages.conf.template /etc/nginx/auth.d/auth_pages.conf
      cp -rf /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_request.conf
    ;;
    none)
      rm -rf /etc/nginx/http.d/auth-server.conf
      rm -rf /etc/nginx/auth.d/auth_pages.conf
      rm -rf /etc/nginx/auth.d/auth_basic.conf
      rm -rf /etc/nginx/auth.d/auth_request.conf
      echo "auth_basic off;" > /etc/nginx/auth.d/auth_basic.conf
    ;;
  esac
  sed -i -r "s/^(.*'auth'\s*=>\s*\")($modes|)(\",.*)$/\1$AUTH\3/" /app/config.php
  /usr/sbin/nginx -s reload
else
  echo "$0 <$modes> [username] [password]"
fi
