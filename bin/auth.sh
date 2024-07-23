#!/bin/sh -x

echo "Setting up webgui auth..."

rm -rf /etc/nginx/http.d/auth-server.conf
rm -rf /etc/nginx/auth.d/*.conf
cp -rf /etc/nginx/auth.d/allow_conf.template /etc/nginx/auth.d/allow.conf

func_setuser() {
  if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
    sed -i "s|^\(.*'http_auth'\s*=>\).*$|\1 \['username\' => '$USERNAME', 'password' => '$PASSWORD'\]|" /app/config.php;
    echo "$PASSWORD" | htpasswd -n -i "$USERNAME" > /etc/nginx/.htpasswd
  fi
}

if [ -z "$AUTH" ] && [ -n "$1" ]; then
  AUTH="$1"
fi

if echo "$AUTH" | grep -Eq "^(basic|glftpd|both|none)$"; then
  case $AUTH in
    basic)
      func_setuser
      cp -rf /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -rf /etc/nginx/auth.d/auth_basic.conf.template /etc/nginx/auth.d/auth_basic.conf
      sed -i "/auth_request/d" /etc/nginx/http.d/webui.conf
      sed -i "/error_page 401 = @auth_401/d" /etc/nginx/http.d/webui.conf
    ;;
    glftpd)
      cp -rf /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -rf /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_request.conf
      sed -i "/auth_basic/d" /etc/nginx/http.d/webui.conf
      echo "auth_basic off;" > /etc/nginx/auth.d/auth_basic.conf
    ;;
    both)
      func_setuser
      rm -rf /etc/nginx/auth.d/auth_basic.conf
      cp -rf /etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
      cp -rf /etc/nginx/auth.d/auth_request.conf.template /etc/nginx/auth.d/auth_request.conf
    ;;
    none)
      rm -rf /etc/nginx/http.d/auth-server.conf
      rm -rf /etc/nginx/auth.d/auth_basic.conf
      rm -rf /etc/nginx/auth.d/auth_request.conf
      echo "auth_basic off;" > /etc/nginx/auth.d/auth_basic.conf
      sed -i -r "s/(auth_basic|auth_request)/d" /etc/nginx/http.d/webui.conf
    ;;
  esac
  sed -i -r "s/^(.*'auth'\s*=>\s*\")(basic|glftpd|both|none|)(\",.*)$/\1$AUTH\3/" /app/config.php
  /usr/sbin/nginx -s reload
fi
