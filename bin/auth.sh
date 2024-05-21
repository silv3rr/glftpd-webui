#!/bin/sh

echo "Setting up auth ..."

if [ "$AUTH" = "basic" ] || [ "$AUTH" = "both" ]; then
  if [ -n "$USERNAME" ] && [ -n "$PASSWORD" ]; then
    sed -i "s|^\(.*'http_auth'\s*=>\).*$|\1 \['username\' => '$USERNAME', 'password' => '$PASSWORD'\]|" /app/config.php;
    echo "$PASSWORD" | htpasswd -n -i "$USERNAME" > /etc/nginx/.htpasswd
  fi
  if [ "$AUTH" = "basic" ]; then
    sed -i "/auth_request/d" /etc/nginx/http.d/webui.conf
    sed -i "/error_page 401 = @auth_401/d" /etc/nginx/http.d/webui.conf
  fi
fi

if [ "$AUTH" = "glftpd" ] || [ "$AUTH" = "both" ]; then
  # ...
  if [ "$AUTH" = "glftpd" ]; then
    sed -i "/auth_basic/d" /etc/nginx/http.d/webui.conf
  fi
elif [ "$AUTH" = "none" ]; then
  rm -rf /etc/nginx/http.d/auth.conf
  sed -i -r "s/(auth_basic|auth_request)/d" /etc/nginx/http.d/webui.conf
fi

if echo "$AUTH" | grep -Eq "^(basic|glftpd|both|none)$"; then
  sed -i -r "s/^(.*'auth'\s*=>\s*\")basic|glftpd|both|none(\"'\s*,.*)$/\1$AUTH\2/" /app/config.php
fi
