#!/bin/sh -x

DOCKER_GID="$(stat -c %g /var/run/docker.sock 2>/dev/null)"
grep -Eq "^docker:" /etc/group || echo "docker:x:${DOCKER_GID:-999}:nobody" >>/etc/group
echo "Looking up glftpd host... "
{ nslookup -type=a glftpd-web 127.0.0.11 | grep -A 99 answer:; } | grep -q Address || echo "127.0.0.1 glftpd-web" >>/etc/hosts
{ nslookup -type=a glftpd 127.0.0.11 | grep -A 99 answer:; } | grep -q Address || echo "127.0.0.1 glftpd" >>/etc/hosts
cp -rf /etc/nginx/http.d/webui.conf.template /etc/nginx/http.d/webui.conf
sed -i "s/\( *listen\) .* ssl;$/\1 ${WEBUI_PORT:-443} ssl;/" /etc/nginx/http.d/webui.conf
test -x /auth.sh && USERNAME="$WEBUI_USERNAME" PASSWORD="$WEBUI_PASSWORD" AUTH="$WEBUI_AUTHMODE" /auth.sh
chown 65534:root /app/config.php
$(echo /usr/sbin/php-fpm*) -F &
nginx -g "daemon off;"
